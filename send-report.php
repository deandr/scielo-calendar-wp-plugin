<?php
require_once(SCICAL_PLUGIN_PATH . 'lib/PHPMailerAutoload.php');

function send_report($period = 'week'){

    $config = get_option('scieloevent_config');

    $args = array(
        'post_type' => 'event',
        'posts_per_page' => '-1',
        'order_by' => 'meta_value',
        'meta_key' => 'start_timestamp',
        'order' => 'ASC'
    );

    $events = get_posts($args);
    $range_start_date = strtotime(date('Y-m-d'));

    if ($period == 'day'){
        $range_end_date = strtotime(date('Y-m-d'));
    }else{
        $range_end_date = strtotime("next friday", strtotime(date('Y-m-d')));
    }


    echo "range_start_date: [" . $range_start_date ."]";
    echo "range_end_date: [" . $range_start_date ."]";

    foreach($events as $event) {

        $start = get_post_meta($event->ID, 'start', true);
    	$end = get_post_meta($event->ID, 'end', true);

        $start_time = ical2date($start);
        $end_time = ical2date($end);

        echo "start_time: [" . $start_time ."]";
        echo "end_time: [" . $end_time ."]";

        if ($start_time >= $range_start_date && $end_time <= $range_end_date){
            $duration = get_post_meta($event->ID, 'duration', true);
        	$location = get_post_meta($event->ID, 'location', true);
        	$organizer = get_post_meta($event->ID, 'organizer', true);

            echo '<h2>' . $event->post_title . '</h2>';
        }


    }


/*
    $phpmailer = new PHPMailer();
    $phpmailer->CharSet = 'UTF-8';
    $phpmailer->IsSMTP();
    $phpmailer->Host = $config['smtp_server'];
    $phpmailer->SMTPAuth = true;   // enable SMTP authentication
    $phpmailer->Port = $config['smtp_port'];
    $phpmailer->Username = $config['smtp_user'];
    $phpmailer->Password = $config['smtp_password'];

    $phpmailer->SetFrom($config['from_email'], $config['from_name']);

    $phpmailer->Subject = "Eventos do dia";

    $body = "Bom dia, <br/><br/>";
    $body .= "Os eventos do dia são: <br/><br/>";

    $body .= "<br/>Atenciosamente, <br/>";
    $body .= "Eventos<br/>";

    $phpmailer->AddAddress($config['to_email'], $config['to_name']);


    $phpmailer->MsgHTML($body);

    if(!$phpmailer->Send()) {
      echo "Mailer Error: " . $phpmailer->ErrorInfo;
    } else {
      echo "Mensagem enviada!";
    }
*/
    die();

}

?>
