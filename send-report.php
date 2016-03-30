<?php

function send_report($period = 'week'){

    $config = get_option('scieloevent_config');

    $args = array(
        'post_type' => 'event',
        'posts_per_page' => '-1',
        'order_by' => 'meta_value',
        'meta_key' => 'start_timestamp',
        'order' => 'DESC'
    );

    $events = get_posts($args);

    if ($period == 'day'){

        $range_start_date = strtotime(date('Y-m-d'));
        $range_end_date = strtotime(date('Y-m-d'));
    }else{
        $range_start_date = strtotime("previous monday", strtotime(date('Y-m-d')));
        $range_end_date = strtotime("next friday", strtotime(date('Y-m-d')));
    }

    /* debug
    echo "range_start_date: [" . $range_start_date ."]";
    echo "range_end_date: [" . $range_start_date ."]";
    */

    $event_list = '';
    $total_events = 0;
    foreach($events as $event) {

        $start = get_post_meta($event->ID, 'start', true);
    	$end = get_post_meta($event->ID, 'end', true);

        $start_time = ical2date($start);
        $end_time = ical2date($end);

        /* debug
        echo "start_time: [" . $start_time ."]";
        echo "end_time: [" . $end_time ."]";
        */

        if ($start_time >= $range_start_date && $end_time <= $range_end_date){
            $duration = get_post_meta($event->ID, 'duration', true);
        	$location = get_post_meta($event->ID, 'location', true);
        	$organizer = get_post_meta($event->ID, 'organizer', true);

            $event_list .= '<div style="margin: 20px 0 20px 0">';
            $event_list .= '<strong>' . $event->post_title . '</strong><br/>';
            $event_list .= format_ical_date($start);
            $end_date = format_ical_date($end);

            if ($start['day'] == $end['day'] && $start['month'] == $end['month']){
                $event_list .= ' - ' . substr($end_date, strpos($end_date, ' ') + 1);
            }else{
                $event_list .= ' - ' . $end_date;
            }
            $event_list .= '<br/>Onde: ' . $location;
            $event_list .= '</div>';
            $total_events++;

        }

    }
    // if has event list send email
    if ( $event_list != ''){
        send_email($event_list, $period, $total_events);
    }else{
        // register that 0 events are send
        update_option('scieloevent_last_send', time() . '|' . $period . '|0');
        header("Location: edit.php?post_type=event&page=scielo-calendar.php");
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
        $body .= "<p>Segue lista de eventos do dia: </p>";
    }else{
        $subject = "Eventos do semana";
        $body .= "<p>Segue lista de eventos da semana: </p>";
    }
    $phpmailer->Subject = $subject;

    $body .= $content;

    $body .= "<br/>Atenciosamente, <br/>";
    $body .= "SciELO<br/>";

    $phpmailer->AddAddress($config['to_email'], $config['to_name']);

    $phpmailer->MsgHTML($body);

    if ($phpmailer->Send()) {
        update_option('scieloevent_last_send', time() . '|' . $period . '|' . $total_events );
        header("Location: edit.php?post_type=event&page=scielo-calendar.php");
    }else{
        error_log("[scielo-calendar-plugin] Erro ao enviar mensagem, detalhes: " . $phpmailer->ErrorInfo);
    }
}


?>
