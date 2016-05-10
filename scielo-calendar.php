<?php
/**
 * Plugin Name: SciELO Calendário
 * Plugin URI: http://scielo.org
 * Description: Plugin de importação dos eventos do calendário SciELO para WordPress
 * Version: 1.0
 */

 define('SCICAL_VERSION', '0.1' );

 define('SCICAL_PLUGIN_PATH',  plugin_dir_path(__FILE__) );

require_once(SCICAL_PLUGIN_PATH . 'lib/iCalcreator.class.php');
require_once(SCICAL_PLUGIN_PATH . 'lib/PHPMailerAutoload.php');
require_once(SCICAL_PLUGIN_PATH . 'functions.php');
require_once(SCICAL_PLUGIN_PATH . 'metabox.php');
require_once(SCICAL_PLUGIN_PATH . 'send-report.php');
require_once(SCICAL_PLUGIN_PATH . 'settings-page.php');

if(!class_exists('SCiELOCalendar_Plugin')) {
    class SCiELOCalendar_Plugin {

		private $config = Array();
        /**
         * Construct the plugin object
         */
        public function __construct() {
            // register actions
            add_action( 'init', array(&$this, 'plugin_init') );
            add_action( 'admin_menu', array(&$this, 'create_admin_menu') );
			add_action( 'admin_action_reset', array(&$this,'reset_admin_action') );
			add_action( 'admin_action_sync', array(&$this,'sync_admin_action') );
			add_action( 'admin_action_report', array(&$this,'report_admin_action') );

            // custom columns at list page
			add_action( 'manage_posts_custom_column' , array(&$this,'event_custom_columns'), 10, 2 );
			add_filter( 'manage_edit-event_columns' , array(&$this,'event_column_register') );
			add_filter( 'pre_get_posts', array(&$this,'set_events_order_in_admin') );
            // custom sort at list page
			add_filter( 'manage_edit-event_sortable_columns', array(&$this, 'event_column_register_sortable') );
			add_filter( 'request', array(&$this, 'event_column_orderby') );

            // schedule daily and weekly events
            if ( !wp_next_scheduled( 'scielo_sync_events' ) ) {
                wp_schedule_event( current_time( 'timestamp' ), 'daily', 'scielo_sync_events');
            }
            add_action( 'scielo_sync_events', array($this, 'sync_admin_action') );


            if ( !wp_next_scheduled( 'scielo_daily_report' ) ) {
                wp_schedule_event( current_time( 'timestamp' ), 'daily', 'scielo_daily_report');
            }
            add_action( 'scielo_daily_report', array($this, 'send_daily_report') );

            if ( !wp_next_scheduled( 'scielo_monday_report' ) ) {
                wp_schedule_event( current_time( 'timestamp' ), 'daily', 'scielo_monday_report');
            }
            add_action( 'scielo_monday_report', array($this, 'send_monday_report') );

        }

        // Plugin deactivation
        public static function deactivate(){
            wp_clear_scheduled_hook('scielo_daily_report');
            wp_clear_scheduled_hook('scielo_monday_report');
        }

		// Plugin initialization
		function plugin_init() {
			$this->create_custom_post_type();
			$this->config = get_option('scieloevent_config');

		}

		// Create event post type
		function create_custom_post_type(){
			register_post_type( 'event',
		      array(
		        'labels' => array(
		          'name' => 'SciELO Calendário',
		          'singular_name' => 'Evento',
		        ),
		        'public' => true,
		        'has_archive' => true,
				'menu_position' => 30,
				'capability_type' => 'post',
		      )
		    );
		}

		// Add plugin option menu item
		function create_admin_menu() {
		    add_submenu_page('edit.php?post_type=event', 'Custom Post Type Admin', 'Opções', 'edit_posts', basename(__FILE__), 'events_settings_page');

			add_action( 'admin_init', array(&$this, 'register_settings')  );
		}

		// Plugin config settings
		function register_settings() {
		    register_setting( 'event-settings-group', 'scieloevent_config' );
			register_setting( 'event-settings-group', 'scieloevent_last_sync' );
            register_setting( 'event-settings-group', 'scieloevent_last_send' );
		}

		// Action that perform read and parser of ICS file and load events in WordPress
		function sync_admin_action() {

			if($this->config['feed_url'] != "") {

                // delete previous imported events
                $events = get_posts("post_type=event&posts_per_page=-1&post_status=any");
    			foreach($events as $event) {
    				wp_delete_post($event->ID, true);
    			}
                // get previous saturdar as start date for import
                $week_start_date = strtotime("previous saturday", strtotime(date('Y-m-d')));

				$events = sync_events($this->config['feed_url']);
				foreach($events as $event) {
                    $event_start_date = ical2date($event['dtstart']);

					// only import events from current week forward
					if ($event_start_date >= $week_start_date){
						$created_year = $event['created']['year'];
						$created_month = sprintf("%02d", (int)$event['created']['month']);
						$created_day = sprintf("%02d", $event['created']['day']);
						$created_date = sprintf("%s-%s-%s", $created_year, $created_month, $created_day);
                        $description = str_replace(array("\r\n","\n\r","\\n","\r"), '<br/>', $event['description']);

                        // Create post object
						$event_post = array(
						  'post_title'		=> $event['summary'],
						  'post_content'	=> $description,
						  'post_author'		=> 1,
						  'post_type'   	=> 'event',
						  'post_status' 	=> 'publish',
						  'post_date'		=> $created_date,
						);

						// Insert the post into the database
						$post_id = wp_insert_post( $event_post );

						if((int)$post_id > 0) {
                            // check if is a multiple day event
                            if (intval($event['dtend']['day']) > intval($event['dtstart']['day'])){
                                // tweak for multiple day event:
                                // subtract one day from last day because ICAL register one day more when event ends at 00:00
                                $event['dtend']['day'] = strval(intval($event['dtend']['day'])-1);
                            }

							add_post_meta($post_id, 'start', $event['dtstart']);
							add_post_meta($post_id, 'end', $event['dtend']);
							add_post_meta($post_id, 'duration', $event['duration']);
							add_post_meta($post_id, 'location', $event['location']);
							add_post_meta($post_id, 'organizer', $event['organizer']);
							add_post_meta($post_id, 'start_timestamp', ical2datetime($event['dtstart']));
						}
					}
				}

				update_option('scieloevent_last_sync', time());
				header("Location: edit.php?post_type=event");
			}

		}

		// Action that delete all events
		function reset_admin_action() {
			// delete events
			$events = get_posts("post_type=event&posts_per_page=-1&post_status=any");
			foreach($events as $event) {
				wp_delete_post($event->ID, true);
			}
            header("Location: edit.php?post_type=event");

		}

		// Action that send report email's
		function report_admin_action() {
			send_report($_GET['period']);
		}


		// Format Start and End column to list page
		function event_custom_columns( $column, $post_id ) {
			if ($column == 'start' || $column == 'end'){
				$d = get_post_meta( $post_id, $column, true );

				echo '<em>' . format_ical_date($d) . '</em>';
			}
		}

		// Add Start and End column to list page
		function event_column_register( $columns ) {
			unset($columns['date']);
			$columns = array_merge( $columns, array('start' => 'Data inicial', 'end' => 'Data final'));

		    return $columns;
		}

		// Change default order of event list in admin
		function set_events_order_in_admin( $wp_query ) {
			global $pagenow;

			if ( is_admin() && 'edit.php' == $pagenow && $_GET['post_type'] == 'event' &&   !isset($_GET['orderby'])) {
		    	$wp_query->set( 'orderby', 'meta_value' );
		    	$wp_query->set( 'meta_key', 'start_timestamp' );
		    	$wp_query->set( 'order', 'ASC' );
			}
		}

		// Register the start date column as sortable
		function event_column_register_sortable( $columns ) {
			$columns[ 'start' ] = 'start';

			return $columns;
		}

		// Apply order by
		function event_column_orderby( $vars ) {
			if ( isset( $vars['orderby'] ) && 'start' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' => 'start_timestamp',
					'orderby' => 'meta_value'
				) );
			}

			return $vars;
		}

        // Weekly report
        function send_monday_report(){
            // Get the current date time
            $dateTime = new DateTime();

            // Check that the day is Monday
            if($dateTime->format('N') == 1){
                send_report('week');
            }
        }
        // Daily Report
        function send_daily_report(){
            send_report('day');
        }

	}
}

// Instantiate the plugin class
$scielo_calendar_plugin = new SCiELOCalendar_Plugin();
