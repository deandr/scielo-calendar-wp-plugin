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

        }

    }
    // if has event list send email
    if ( $event_list != ''){
        send_email($event_list, $period);
    }
}

function send_email($content, $period){
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

    $body = "Bom dia, <br/><br/>";
    if ($period == 'day'){
        $subject = "Eventos do dia";
        $body .= "<p>Os eventos do dia são: </p>";
    }else{
        $subject = "Eventos do semana";
        $body .= "<p>Os eventos da semana: </p>";
    }
    $phpmailer->Subject = $subject;

    $body .= $content;

    $body .= "<br/>Atenciosamente, <br/>";
    $body .= "SciELO<br/>";

    $phpmailer->AddAddress($config['to_email'], $config['to_name']);

    $phpmailer->MsgHTML($body);

    if(!$phpmailer->Send()) {
      echo "Ocorreu um erro no envio da mensagem. Informação do erro: " . $phpmailer->ErrorInfo;
    } else {
      echo "Mensagem enviada!";
    }
    die();
}

?>
