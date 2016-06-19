<?php
require_once __DIR__ . '/php_settings.inc';
require_once __DIR__ . '/localization.inc';
require_once __DIR__ . '/db_access.inc';

/*
	The special value of the end_time column in the office_hours database
	table signifying that no end time was specified.
*/
const UNSPECIFIED_END_TIME = '00:00:00';
/*
	Map between numerical values and text aliases for the days of the week, as
	used in the ENUM defined in the MySQL database.
*/
const DAYS_OF_WEEK = array(
	1 => 'Monday',
	'Tuesday',
	'Wednesday',
	'Thursday',
	'Friday',
	'Saturday',
	'Sunday',
	'Monday' => 1,
	'Tuesday' => 2,
	'Wednesday' => 3,
	'Thursday' => 4,
	'Friday' => 5,
	'Saturday' => 6,
	'Sunday' => 7
);

/*
	Fallback function for semester_info() if there is no database information
	about lecture start/end for $date.
*/
function semester_info_fallback($date) {
	$year = $date->format('Y');
	$month = $date->format('n');
	// between 1st of April and end 30th of September
	$is_ss = $month >= 4 && $month < 10;
	// get dates for current and next semester
	// WS lecture start: ≈ 7th October, SS lecture start: ≈ 7th April
	$ws_start = new DateTime("$year-10-07");
	if ($ws_start > $date && !$is_ss) {
		// subtract 1 year
		$ws_start->sub(new DateInterval('P1Y'));
	}
	$ss_start = new DateTime("$year-04-07");
	if ($ss_start < $date && !$is_ss) {
		// subtract 1 year
		$ss_start->add(new DateInterval('P1Y'));
	}
	$ws_start_yr = $ws_start->format('Y');
	$ws_start_yr_nxt = $ws_start_yr + 1;
	// WS lecture end: ≈ 2nd February, SS lecture end: ≈ 20th July
	$ws_lecture_end = new DateTime("$ws_start_yr_nxt-02-02");
	$ss_lecture_end = new DateTime("$ws_start_yr-07-20");
	// during semester: true; during break: false
	$semester = ($date >= $ss_start && $date < $ss_lecture_end)
		|| ($date >= $ws_start && $date < $ws_lecture_end);
	
	res = array(
		'summer_winter' => $is_ss ? 'SS' : 'WS',
		'lecture_start' => $is_ss ? $ss_start : $ws_start,
		'lecture_end' => $is_ss ? $ss_end : $ws_end,
		'during_semester' => $semester
	);
	return res;
}

/*
	Returns an array containing information about what semester $date is in:
	array(
		'summer_winter' => 'SS' or 'WS',
		'lecture_start' => lecture start date for the semester (DateTime),
		'lecture_end' => lecture end date for the semester (DateTime),
		'during_semester' => if $date is during the semester (between
			lecture_start and lecture_end) or during the break
	);
*/
function semester_info($date) {
	return semester_info_fallback($date);
}

function valid_day_of_week($day) {
	return key_exists($day, DAYS_OF_WEEK) || in_array($day, DAYS_OF_WEEK);
}

/*
	Returns all entries in the office hours database table as a 2D array:
	$result[day of week (1–7)][{$start_time}_{$end_time}]
	contains the name entered in the schedule for the entry with that day,
	start time and end time. All arrays are sorted by keys in ascending order.
	
	If $day: Only query for and return data for the day $day. $day must be an
	element of the keys or the values of DAYS_OF_WEEK.
	If $db: Use $db as MySQL connection; else: create new MySQL connection.
*/
function office_hours_table($day=NULL, $db=NULL) {
	$open_db = !$db;
	if ($open_db) {
		$db = mysql_db_connect();
	}
	$sql = 'SELECT day+0 as day_num, start_time, end_time, name'
		. ' FROM office_hours';
	if ($day) {
		if (!valid_day_of_week($day)) {
			throw new DomainException('Value of $day is not valid: ' . $day);
		}
		$sql .= ' WHERE day = :day'
	}
	$sql .= ' ORDER BY day_num, start_time, end_time ASC;'
	$query = $db->prepare($sql);
	if ($day) {
		$query->bindValue(':day', $day);
	}
	$query->execute();
	$data = $query->fetchAll();
	if ($open_db) {
		mysql_db_close($db);
	}

	// transfer data from SQL query into 2D array
	$result = array();
	foreach ($data as $row) {
		$day_num = $row['day_num'];
		if (!key_exists($day_num, $result)) {
			$result[$day_num] = array();
		}
		$time_key = $row['start_time'] . '_' . $row['end_time'];
		$result[$day_num][$time_key] = $row['name'];
	}
	return $result;
}

/*
	Returns an array of 2-element arrays [$start_time, $end_time] containing
	all start and end times present in the database. The array is sorted by
	start times first and end times second (e.g. 13:00–14:00 < 13:00–15:00
	< 14:00–15:00).
	
	If $day: Only query for and return data for the day $day. $day must be an
	element of the keys or the values of DAYS_OF_WEEK.
	If $db: Use $db as MySQL connection; else: create new MySQL connection.
*/
function office_hours_times($day=NULL, $db=NULL) {
	$open_db = !$db;
	if ($open_db) {
		$db = mysql_db_connect();
	}
	$sql = 'SELECT DISTINCT start_time, end_time FROM office_hours';
	if ($day) {
		if (!valid_day_of_week($day)) {
			throw new DomainException('Value of $day is not valid: ' . $day);
		}
		$sql .= ' WHERE day = :day';
	}
	$sql .= ' ORDER BY start_time, end_time ASC;';
	$query = $db->prepare($sql);
	if ($day) {
		$query->bindValue(':day', $day);
	}
	$query->execute();
	$data = $query->fetchAll();
	if ($open_db) {
		mysql_db_close($db);
	}
	return $data;
}

/*
	Returns a string containing the HTML table with the full office hours
	schedule.
	
	If $date: The table will only contain the column corresponding to the day
	of the week of $date, or the next Monday if $date is in the weekend.
	$date must be a DateTimeImmutable.
*/
function office_hours_html($date=NULL) {
	// generate HTML table header
	$loc_time = loc_get_str('time', true);
	$result = <<<HTML
	<table style="width: 100%;">
		<tr>
			<th scope="col" style="width: 20%;">$loc_time</th>
HTML;
	$monday_num = DAYS_OF_WEEK['Monday'];
	$friday_num = DAYS_OF_WEEK['Friday'];
	$date_day_of_week = NULL;
	if ($date) {
		// 1 (Monday) to 7 (Sunday)
		$date_day_of_week = $date->format('N');
		// for Saturday and Sunday: show Monday
		if ($date_day_of_week >= DAYS_OF_WEEK['Saturday']) {
			$days_to_monday = 8 - $date_day_of_week;
			// set DateTime to next Monday
			$date = $date->add(new DateInterval("P{$days_to_monday}D"));
		}
		// 'EEEE' →  full day of week in PHP intl; see IntlDateFormatter
		// https://ssl.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details
		$days = array(datefmt_format_object($date, 'EEEE', LOCALE));
	}
	else {
		$days = array();
		// table from Monday to Friday if no date is given
		for ($day_num = $monday_num; $day_num <= $friday_num; $day_num++) {
			$day_of_week = DAYS_OF_WEEK[$day_num];
			$dt_day = new DateTime($day_of_week);
			$days[] = datefmt_format_object($dt_day, 'EEEE', LOCALE);
		}
	}
	foreach ($days as $day_of_week) {
		$result .= <<<HTML
			<th scope="col">$day_of_week</th>
HTML;
	}
	$result .= '</tr>';

	// generate HTML table body
	$db = mysql_db_connect();
	$timetable = office_hours_table($date_day_of_week, $db);
	$times = office_hours_times($date_day_of_week, $db);
	mysql_db_close($db);
	// if !$date: make sure that Monday–Friday are included in $timetable
	if (!$date) {
		for ($day_num = $monday_num; $day_num <= $friday_num; $day_num++) {
			if (!key_exists($day_num)) {
				$timetable[$day_num] = array();
			}
		}
		// restore key order
		ksort($timetable);
	}
	foreach ($times as $time_entry) {
		list($start_time, $end_time) = $time_entry;
		$dt_start_time = date_create($start_time);
		$start_time_show = $dt_start_time->format('H');
		if ($end_time == UNSPECIFIED_END_TIME || !$end_time) {
			$loc_from = loc_get_str('from');
			$time_show = "$loc_from $start_time_show";
		}
		else {
			$dt_end_time = date_create($end_time);
			$end_time_show = $dt_end_time->format('H');
			$time_show = "$start_time_show–$end_time_show";
		}
		$result .= "<tr><td>$time_show</td>";
		$time_key = "{$start_time}_{$end_time}";
		foreach ($timetable as $day_data) {
			$name_show = '';
			if (key_exists($time_key, $day_data)) {
				$name_show = htmlspecialchars($day_data[$time_key]);
			}
			$result .= "<td>$name_show</td>";
		}
		$result .= '</tr>';
	}
	$result .= '</table>';
	return $result;
}

?>
