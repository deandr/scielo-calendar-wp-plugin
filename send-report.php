<?php

function send_report($period = 'week'){
    $config = get_option('scieloevent_config');

    $args = array(
        'post_type' => 'event',
        'posts_per_page' => '-1',
        'orderby' => 'meta_value_num',
        'meta_key' => 'start_timestamp',
        'order' => 'ASC'
    );

    $events = get_posts($args);

    // always get events of current week
    $range_start_date = strtotime("previous saturday", strtotime(date('Y-m-d')));
    $range_end_date = strtotime("next saturday", strtotime(date('Y-m-d')));

    $current_date = strtotime(date('Y-m-d'));

    /* debug
    echo "range_start_date: [" . $range_start_date ."]";
    echo "range_end_date: [" . $range_end_date ."]";
    */

    $event_list = '';
    $total_events = 0;
    foreach($events as $event) {

        $start = get_post_meta($event->ID, 'start', true);
    	$end = get_post_meta($event->ID, 'end', true);
        $start_timestamp = get_post_meta($event->ID, 'start_timestamp', true);

        $start_time = ical2date($start);
        $end_time = ical2date($end);

        /* debug
        echo "start_time: [" . $start_time ."]\n";
        echo "end_time: [" . $end_time ."]\n";
        echo "start_timestamp: [" . $start_timestamp ."]\n";
        print_r($event);
        */

        // check if event is in the current week
        if ($start_time >= $range_start_date && $start_time <= $range_end_date){

            if ($period == 'day'){
                // check if is a current day event
                if ( ($start_time >= $current_date && $start_time <= $current_date) ||
                     ($start_time <= $current_date && $end_time >= $current_date) ){
                    // OR is event that starts before current date and continue
                }else{
                    continue;
                }
            }

            $duration = get_post_meta($event->ID, 'duration', true);
        	$location = get_post_meta($event->ID, 'location', true);
        	$organizer = get_post_meta($event->ID, 'organizer', true);

            $event_list .= '<div style="margin: 20px 0 20px 0; border-bottom: solid 1px #ccc">';
            $event_list .= '<strong>' . $event->post_title . '</strong><br/>';
            $event_list .= format_ical_date($start);
            $end_date = format_ical_date($end);

            if ($start['day'] == $end['day'] && $start['month'] == $end['month']){
                $event_list .= ' - ' . substr($end_date, strpos($end_date, ' ') + 1);
            }else{
                $event_list .= ' - ' . $end_date;
            }
            if ($location != ''){
                $event_list .= '<br/>Onde: ' . $location . '<br/>';
            }
            $event_list .= '<p>' . $event->post_content . '</p>';
            $event_list .= '</div>';
            $total_events++;

        }

    }
    // send email if have events
    if ($total_events > 0){
        send_email($event_list, $period, $total_events);
    }
}

function send_email($content, $period, $total_events){
    $config = get_option('scieloevent_config');

    $phpmailer = new PHPMailer;
    $phpmailer->CharSet = 'UTF-8';
    $phpmailer->IsSMTP();
    $phpmailer->Host = $config['smtp_server'];
    $phpmailer->SMTPAuth = true;   // enable SMTP authentication
    $phpmailer->Port = $config['smtp_port'];
    $phpmailer->Username = $config['smtp_user'];
    $phpmailer->Password = $config['smtp_password'];

    $phpmailer->SetFrom($config['from_email'], $config['from_name']);

    $body = "Bom dia, <br/>";
    if ($period == 'day'){
        $subject = "Eventos do dia";
        if ($total_events > 0){
            $body .= "<p>Segue lista de eventos do dia: </p>";
        }else{
            $body .= "<p>Não existem eventos programados até o momento para hoje.</p>";
        }
    }else{
        $subject = "Eventos do semana";
        if ($total_events > 0){
            $body .= "<p>Segue lista de eventos da semana: </p>";
        }else{
            $body .= "<p>Não existem eventos programados até o momento para a semana.</p>";
        }
    }
    $phpmailer->Subject = $subject;

    $body .= $content;

    $body .= "<br/>Atenciosamente, <br/>";
    $body .= "SciELO<br/>";

    $phpmailer->AddAddress($config['to_email'], $config['to_name']);
    if ($config['bcc_email'] != ''){
        $phpmailer->AddBCC($config['bcc_email']);
    }

    $phpmailer->MsgHTML($body);

    if ($phpmailer->Send()) {
        update_option('scieloevent_last_send', time() . '|' . $period . '|' . $total_events );
        header("Location: edit.php?post_type=event&page=scielo-calendar.php");
    }else{
        error_log("[scielo-calendar-plugin] Erro ao enviar mensagem, detalhes: " . $phpmailer->ErrorInfo);
    }
}

?>
