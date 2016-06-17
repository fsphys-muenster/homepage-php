<?php
require_once __DIR__ . '/db-access.inc';

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
		$ws_start->sub(new DateInterval('P1Y'));
	}
	$ss_start = new DateTime("$year-04-07");
	if ($ss_start < $date && !$is_ss) {
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

/*
	Map of numerical values to text aliases for the days of the week, as
	used in the ENUM defined in the MySQL database.
*/
$days_of_week = array(
	1 => 'Monday',
	'Tuesday',
	'Wednesday',
	'Thursday',
	'Friday',
	'Saturday',
	'Sunday'
);

/*
	Returns all entries in the office hours database table as a 2D array:
	$result[day of week (1–7)]['start_time']
	gives a 2-element array containing the end time and the name (keys:
	'end_time' and 'name'). The arrays are sorted by keys in ascending order.
	
	If $day: Only return the slice $result[$day], 1 ≤ $day ≤ 7.
*/
function office_hours_table($day=NULL, $db=NULL) {
	if (!$db) {
		$db = mysql_db_connect();
	}
	$sql = 'SELECT day+0 as day_num, start_time, end_time, name'
		. ' FROM office_hours';
	if ($day) {
		if (!key_exists($day, $days_of_week)) {
			throw new DomainException('Value of $day is not valid: ' . $day);
		}
		$sql .= ' WHERE day = :day'
	}
	$sql .= ';'
	$query = $db->prepare($sql);
	if ($day) {
		$query->bindValue(':day', $day);
	}
	$query->execute();
	$data = $query->fetchAll();
	mysql_db_close($db);

	# create the array entries with keys in ascending order 1–7
	$result = array_fill_keys(array_keys($days_of_week), array());
	# transfer data from SQL query into 2D array
	foreach ($data as $row) {
		$day_num = $row['day_num'];
		$start_time = $row['start_time'];
		$result[$day_num][$start_time] = array(
			'end_time' => $row['end_time'],
			'name' => $row['name']
		);
	}
	# sort start times
	foreach ($result as $day_data) {
		ksort($day_data);
	}
	return $day ? $result[$day] : $result;
}

?>
