<?php

function sync_events($url) {

	date_default_timezone_set( get_option( 'timezone_string' ) );

	$config = array("url" => $url);

	$vcalendar = new vcalendar( $config );
	$vcalendar->parse();

	$properties = array(
		'uid',
		'created',
		'summary',
		'description',
		'dtstart',
		'dtend',
		'duration',
		'location',
		'organizer',
	);

	$count = 0;
	$events = array();
	while($comp = $vcalendar->getComponent()) {

		foreach($properties as $property) {
			if($property == 'organizer') {
				$events[$count][$property] = $comp->getProperty($property, FALSE, TRUE);
			} else {
				$events[$count][$property] = $comp->getProperty($property);
			}
		}

		$count += 1;
	}

	return $events;
}

function format_ical_date($ical_date){
	if ($ical_date['hour'] != ''){
		$format_date = 'd/m H:i';
	}else{
		$format_date = 'd/m';
	}
	$date_time = ical2datetime($ical_date);

	return date($format_date, $date_time);
}

function ical2datetime($ical_date){

	$timezone_offet = get_option('gmt_offset');

	$str_date = $ical_date['year'] . '-' . $ical_date['month'] . '-' . $ical_date['day'];
	if ($ical_date['hour'] != ''){
		$str_date .= ' ' . $ical_date['hour'] . ':' . $ical_date['min'];
	}

	$date_time = strtotime($str_date);

	if ($ical_date['hour'] != ''){
		if ($ical_date['tz'] == 'Z'){
			$date_time += $timezone_offet * 3600;
		}
	}

	return $date_time;
}

function ical2date($ical_date){

	$str_date = $ical_date['year'] . '-' . $ical_date['month'] . '-' . $ical_date['day'];

	$date = strtotime($str_date);

	return $date;
}


?>
