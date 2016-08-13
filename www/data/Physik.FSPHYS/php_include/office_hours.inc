<?php
namespace de\uni_muenster\fsphys;
require_once 'error_handler.inc';
require_once 'localization.inc';
require_once 'db_access.inc';

/*
	The special value of the end_time column in the office_hours database
	table signifying that no end time was specified.
*/
const UNSPECIFIED_END_TIME = '00:00:00';
/*
	Default options for the functions office_hours_html() and
	office_hours_break_html().
*/
const DEFAULT_HTML_OPTIONS = [
	'edit_mode' => false,
	'time_width' => 5,
	// 'day_width': default undefined or, equivalently,
	// 'day_width' => NULL,
	// 'br_day_of_week_width': default undefined or, equivalently,
	// 'br_day_of_week_width' => NULL,
	// 'br_date_width': default undefined or, equivalently,
	// 'br_date_width' => NULL,
	'br_time_width' => 15,
	// 'br_name_width': default undefined or, equivalently,
	// 'br_name_width' => NULL,
	'short' => false
];
/*
	Attributes (CSS classes) used in the office hours schedule HTML table.
*/
const OH_CSS_PREFIX = 'fsphys_oh_';
const OH_CSS_TABLE_CLASS = OH_CSS_PREFIX . 'table';
const OH_CSS_TABLE_EDIT_CLASS = OH_CSS_PREFIX . 'table_edit';
/*
	SQL conditions to select a certain primary key (semester & break table).
*/
const PK_CONDITION = 'day = :day AND start_time = :start_time'
	. ' AND end_time = :end_time';
const BREAK_PK_CONDITION = '"date" = :day AND start_time = :start_time'
	. ' AND end_time = :end_time';
/*
	The names of the columns in the SQL tables.
*/
const OH_COL_NAMES = ['day', 'start_time', 'end_time', 'name'];
const OH_KEY_COL_NAMES = ['day', 'start_time', 'end_time'];
const OH_BREAK_COL_NAMES = ['date', 'start_time', 'end_time', 'name', 'show'];
const OH_BREAK_KEY_COL_NAMES = ['date', 'start_time', 'end_time'];

/*
	Map between numerical values and text aliases for the days of the week, as
	used in the ENUM defined in the MySQL database.
*/
const DAYS_OF_WEEK = [
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
];

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
	$ws_start = new \DateTime("$year-10-07");
	$ss_start = new \DateTime("$year-04-07");
	// correct year if we are in WS
	if (!$is_ss) {
		if ($ws_start > $date) {
			// subtract 1 year
			$ws_start->sub(new \DateInterval('P1Y'));
		}
		if ($ss_start < $date) {
			// add 1 year
			$ss_start->add(new \DateInterval('P1Y'));
		}
	}
	$ws_start_yr = $ws_start->format('Y');
	$ws_start_yr_nxt = $ws_start_yr + 1;
	// WS lecture end: ≈ 2nd February, SS lecture end: ≈ 20th July
	$ws_lecture_end = new \DateTime("$ws_start_yr_nxt-02-02");
	$ss_lecture_end = new \DateTime("$ws_start_yr-07-20");
	// during semester: true; during break: false
	$semester = ($date >= $ss_start && $date < $ss_lecture_end)
		|| ($date >= $ws_start && $date < $ws_lecture_end);
	$res = [
		'summer_winter' => $is_ss ? 'SS' : 'WS',
		'lecture_start' => $is_ss ? $ss_start : $ws_start,
		'lecture_end' => $is_ss ? $ss_end : $ws_end,
		'during_semester' => $semester
	];
	return $res;
}

/*
	Returns an array containing information about what semester $date is in:
	[
		'summer_winter' => 'SS' or 'WS',
		'lecture_start' => lecture start date for the semester (DateTime),
		'lecture_end' => lecture end date for the semester (DateTime),
		'during_semester' => if $date is during the semester (between
			lecture_start and lecture_end) or during the break
	]
*/
function semester_info($date) {
	return semester_info_fallback($date);
}

function oh_valid_day_of_week($day) {
	return key_exists($day, DAYS_OF_WEEK);
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
function office_hours_table($db=NULL, $day=NULL) {
	$opened_db = mysql_db_connect_if_null($db);
	$sql = 'SELECT day+0 as day_num, start_time, end_time, name'
		. ' FROM office_hours';
	if ($day) {
		if (!oh_valid_day_of_week($day)) {
			throw new \DomainException("Value of \$day is not valid: $day");
		}
		$sql .= ' WHERE day = :day';
	}
	$sql .= ' ORDER BY day_num, start_time, end_time ASC;';
	$query = $db->prepare($sql);
	if ($day) {
		$query->bindValue(':day', $day);
	}
	$query->execute();
	$data = $query->fetchAll();
	if ($opened_db) {
		mysql_db_close($db);
	}

	// transfer data from SQL query into 2D array
	$result = [];
	foreach ($data as $row) {
		$day_num = $row['day_num'];
		if (!key_exists($day_num, $result)) {
			$result[$day_num] = [];
		}
		$time_key = $row['start_time'] . '-' . $row['end_time'];
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
function office_hours_times($db=NULL, $day=NULL) {
	$opened_db = mysql_db_connect_if_null($db);
	$sql = 'SELECT DISTINCT start_time, end_time FROM office_hours';
	if ($day) {
		if (!oh_valid_day_of_week($day)) {
			throw new \DomainException("Value of \$day is not valid: $day");
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
	if ($opened_db) {
		mysql_db_close($db);
	}
	return $data;
}

/*
	Returns all entries in the database table for the office hours during the
	break, ordered by date → start_time → end_time (ascending).

	If $db: Use $db as MySQL connection; else: create new MySQL connection.
*/
function office_hours_break_data($db=NULL) {
	$opened_db = mysql_db_connect_if_null($db);
	$sql = <<<'SQL'
	SELECT * FROM office_hours_break ORDER BY date, start_time, end_time ASC;
SQL;
	$query = $db->query($sql);
	$data = $query->fetchAll();
	if ($opened_db) {
		mysql_db_close($db);
	}
	return $data;
}

function oh_valid_col_name($break, $col) {
	return $break ? in_array($col, OH_BREAK_COL_NAMES)
		: in_array($col, OH_COL_NAMES);
}

function oh_table_data($break) {
	$tbl_name = $break ? 'office_hours_break' : 'office_hours';
	$day_col = $break ? 'date' : 'day';
	return [$tbl_name, $day_col];
}

/*
	$break (bool): Select which MySQL table to use (during semester or break).
	$day: If $break: The value for the 'date' column
	      Else: The value for the 'day' column
	
	Returns false on failure.
*/
function office_hours_get($db, $break, $day, $start_time, $end_time, $col=NULL) {
	if ($col && !oh_valid_col_name($break, $col)) {
		return false;
	}
	list($tbl_name, ) = oh_table_data($break);
	$PK_CONDITION = $break ? BREAK_PK_CONDITION : PK_CONDITION;
	// compare enum integer indices as integers, not as strings
	$day_dtype = is_int($day) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
	$sql = "SELECT * FROM $tbl_name WHERE $PK_CONDITION;";
	$opened_db = mysql_db_connect_if_null($db);
	$query = $db->prepare($sql);
	$query->bindValue(':day', $day, $day_dtype);
	$query->bindValue(':start_time', $start_time);
	$query->bindValue(':end_time', $end_time);
	$query->execute();
	$result = $query->fetch();
	if ($opened_db) {
		mysql_db_close($db);
	}
	if ($col) {
		return $result[$col];
	}
	return $result;
}

function office_hours_insert($db, $break, $row) {
	$col_names_arr = array_keys($row);
	foreach ($col_names_arr as $col) {
		if (!oh_valid_col_name($break, $col)) {
			return false;
		}
	}
	list($tbl_name, ) = oh_table_data($break);
	// enclose column names in double quotes to guard against reserved names
	$col_names = implode(', ', array_map(
		function($x) { return '"' . $x . '"'; }, $col_names_arr));
	// use the appropriate number of '?' placeholders for values
	$val_names = implode(', ', array_fill(0, count($row), '?'));
	$sql = "INSERT INTO $tbl_name ($col_names) VALUES($val_names);";
	$opened_db = mysql_db_connect_if_null($db);
	$query = $db->prepare($sql);
	foreach (array_values($row) as $i => $val) {
		$pdo_dtype = is_bool($val) ? \PDO::PARAM_BOOL : \PDO::PARAM_STR;
		// bindValue() uses 1-indexed positions
		$query->bindValue($i + 1, $val, $pdo_dtype);
	}
	try {
		$result = $query->execute();
	}
	catch (\PDOException $ex) {
		$result = false;
	}
	if ($opened_db) {
		mysql_db_close($db);
	}
	return $result;
}

/*
	$break (bool): Select which MySQL table to use (during semester or break).
	$day: If $break: The value for the 'date' column
	      Else: The value for the 'day' column
	$col: The name of the table column whose value is to be set. A new row
		for this key will be created if needed or the existing row will be
		updated. The new or updated row will have the value $value for the
		column $col.
	
	Returns true on success or false on failure.
*/
function office_hours_set($db, $break, $day, $start_time, $end_time,
	$col, $value) {
	if (!oh_valid_col_name($break, $col)) {
		return false;
	}
	list($tbl_name, $day_col) = oh_table_data($break);
	$key_arr = $break ? OH_BREAK_KEY_COL_NAMES : OH_KEY_COL_NAMES;
	// if setting one of the key columns, the SQL query is slightly different
	$set_key = in_array($col, $key_arr);
	if ($set_key) {
		$PK_CONDITION = $break ? BREAK_PK_CONDITION : PK_CONDITION;
		$sql = "UPDATE $tbl_name SET $col = :value WHERE $PK_CONDITION;";
	}
	// insert row into table or update if row already exists (“upsert”);
	// in standard SQL, this is done with the MERGE statement, but this is not
	// available in MySQL
	// https://en.wikipedia.org/wiki/Merge_%28SQL%29
	else {
		$sql = <<<SQL
		INSERT INTO $tbl_name ($day_col, start_time, end_time, $col)
			VALUES(:day, :start_time, :end_time, :value)
			ON DUPLICATE KEY UPDATE $col = :value;
SQL;
	}
	$opened_db = mysql_db_connect_if_null($db);
	$query = $db->prepare($sql);
	$query->bindValue(':day', $day);
	$query->bindValue(':start_time', $start_time);
	$query->bindValue(':end_time', $end_time);
	$query->bindValue(':value', $value);
	try {
		$result = $query->execute();
	}
	catch (\PDOException $ex) {
		$result = false;
	}
	// if setting a key column and the row didn’t exist, insert instead
	if ($set_key && !$query->rowCount()) {
		$row = [
			$day_col => $day,
			'start_time' => $start_time,
			'end_time' => $end_time
		];
		$row[$col] = $value;
		$result = office_hours_insert($db, $break, $row);
	}
	if ($opened_db) {
		mysql_db_close($db);
	}
	return $result;
}

function office_hours_delete($db, $break, $day, $start_time, $end_time) {
	list($tbl_name, ) = oh_table_data($break);
	$PK_CONDITION = $break ? BREAK_PK_CONDITION : PK_CONDITION;
	$sql = "DELETE FROM $tbl_name WHERE $PK_CONDITION;";
	$opened_db = mysql_db_connect_if_null($db);
	$query = $db->prepare($sql);
	$query->bindValue(':day', $day);
	$query->bindValue(':start_time', $start_time);
	$query->bindValue(':end_time', $end_time);
	try {
		$result = $query->execute();
	}
	catch (\PDOException $ex) {
		$result = false;
	}
	if ($opened_db) {
		mysql_db_close($db);
	}
	return $result;
}

/*
	Returns a string containing the HTML table with the full office hours
	schedule.
	
	If $db: Use $db as MySQL connection; else: create new MySQL connection.
	
	$html_options:
	'edit_mode': Insert id and class attributes to allow the table to be used
	for editing the schedule.
	'day_width': Width of each day column (CSS) in percent;
	if NULL: all day columns will have equal width.
	'time_width': Width of the time column(s) (CSS) in percent (in edit mode,
		both columns have this width, while in normal mode, both columns are
		merged into one whose width is twice this value).
	
	If $date: The table will only contain the column corresponding to the day
	of the week of $date, or the next Monday if $date is in the weekend.
*/
// XXX modify this to work like office_hours_break_html (get rid of the
// office_hours_table stuff)
function office_hours_html($db=NULL, $html_options=DEFAULT_HTML_OPTIONS,
	$date=NULL) {
	extract($html_options);
	// generate HTML table header
	$table_classes = OH_CSS_TABLE_CLASS;
	$time_width_dbl = 2 * $time_width;
	if ($edit_mode) {
		$table_classes .= ' ' . OH_CSS_TABLE_EDIT_CLASS;
		$loc_start_time = loc_get_str('start time', true);
		$loc_end_time = loc_get_str('end time', true);
		$time_header = <<<HTML
<th scope="col" style="width: $time_width%;">$loc_start_time</th>
<th scope="col" style="width: $time_width%;">$loc_end_time</th>
HTML;
	}
	else {
		$loc_time = loc_get_str('time', true);
		$time_header = <<<HTML
<th scope="col" style="width: {$time_width_dbl}%;">$loc_time</th>
HTML;
	}
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
			$date = $date->add(new \DateInterval("P{$days_to_monday}D"));
		}
		// 'EEEE' →  full day of week in PHP intl; see IntlDateFormatter
		// https://secure.php.net/manual/en/class.intldateformatter.php
		// https://ssl.icu-project.org/apiref/icu4c/classSimpleDateFormat.html#details
		$days = [datefmt_format_object($date, 'EEEE', LOCALE)];
	}
	else {
		$days = [];
		// table from Monday to Friday if no date is given
		for ($day_num = $monday_num; $day_num <= $friday_num; $day_num++) {
			$day_of_week = DAYS_OF_WEEK[$day_num];
			$dt_day = new \DateTime($day_of_week);
			$days[] = datefmt_format_object($dt_day, 'EEEE', LOCALE);
		}
	}
	$result = <<<HTML
	<table class="$table_classes">
		<tr>
			$time_header
HTML;
	if (!isset($day_width)) {
		$day_width = (100 - $time_width_dbl) / 5;
	}
	$day_css = <<<CSS
 style="width: $day_width%;"
CSS;
	foreach ($days as $day_of_week) {
		$result .= <<<HTML
			<th scope="col"$day_css>$day_of_week</th>\n
HTML;
	}
	$result .= '</tr>';

	// generate HTML table body
	$opened_db = mysql_db_connect_if_null($db);
	$timetable = office_hours_table($db, $date_day_of_week);
	$times = office_hours_times($db, $date_day_of_week);
	if ($opened_db) {
		mysql_db_close($db);
	}
	// if !$date: make sure that Monday–Friday are included in $timetable
	if (!$date) {
		for ($day_num = $monday_num; $day_num <= $friday_num; $day_num++) {
			if (!key_exists($day_num, $timetable)) {
				$timetable[$day_num] = [];
			}
		}
		// restore key order
		ksort($timetable);
	}
	foreach ($times as $time_entry) {
		list($start_time, $end_time) = $time_entry;
		$dt_start_time = new \DateTime($start_time);
		$start_time_show = $dt_start_time->format('H');
		if ($end_time == UNSPECIFIED_END_TIME || !$end_time) {
			$loc_from = loc_get_str('from');
			$end_time_show = '–';
			$time_show = "$loc_from $start_time_show";
		}
		else {
			$dt_end_time = new \DateTime($end_time);
			$end_time_show = $dt_end_time->format('H');
			$time_show = "{$start_time_show}–$end_time_show";
		}
		if ($edit_mode) {
			$qstr_base = "?break=&start_time=$start_time&end_time=$end_time";
			$qstr_start = htmlspecialchars("$qstr_base&col=start_time");
			$qstr_end = htmlspecialchars("$qstr_base&col=end_time");
			$result .= <<<HTML
		<tr>
			<td><a href="$qstr_start">$start_time_show</a></td>
			<td><a href="$qstr_end">$end_time_show</a></td>
HTML;
		}
		else {
			$result .= "<tr><td>$time_show</td>";
		}
		$time_key = "{$start_time}-{$end_time}";
		foreach ($timetable as $day => $day_data) {
			$name_show = '';
			if (key_exists($time_key, $day_data)) {
				$name_show = htmlspecialchars($day_data[$time_key]);
			}
			if ($edit_mode) {
				$qstr_name = htmlspecialchars("$qstr_base&day=$day&col=name");
				$result .= <<<HTML
			<td><a href="$qstr_name">$name_show</a></td>
HTML;
			}
			else {
				$result .= "<td>$name_show</td>";
			}
		}
		$result .= '</tr>';
	}
	$result .= '</table>';
	return $result;
}

/*
	Returns a string containing the HTML table with the full office hours
	schedule during the break period.
	
	If $db: Use $db as MySQL connection; else: create new MySQL connection.
	
	$html_options:
	'edit_mode': Insert id and class attributes to allow the table to be used
	for editing the schedule.
	'br_*_width': Width of the (*)-column (CSS) in percent
	'br_time_width': Width of the time column(s) (CSS) in percent (in edit
		mode, both columns have this width, while in normal mode, both columns
		are merged into one whose width is twice this value).
	'short': Show “day of week” column if false
*/
function office_hours_break_html($db=NULL, $html_options=DEFAULT_HTML_OPTIONS) {
	extract($html_options);
	// generate HTML table header
	$table_classes = OH_CSS_TABLE_CLASS;
	$loc_day_of_week = loc_get_str('day_of_week', true);
	$day_of_week_css = '';
	if (isset($br_day_of_week_width)) {
		$day_of_week_css = <<<CSS
 style="width: $br_day_of_week_width%;"
CSS;
	}
	$day_of_week_header = $short ? '' : <<<HTML
<th scope="col"$day_of_week_css>$loc_day_of_week</th>
HTML;
	$edit_header = '';
	// for editing: one individual table column for each database column
	// (“edit” column includes “show” value and settings like deleting a row)
	if ($edit_mode) {
		$table_classes .= ' ' . OH_CSS_TABLE_EDIT_CLASS;
		$loc_start_time = loc_get_str('start time', true);
		$loc_end_time = loc_get_str('end time', true);
		$loc_edit = loc_get_str('edit', true);
		$time_header = <<<HTML
<th scope="col" style="width: $br_time_width%;">$loc_start_time</th>
<th scope="col" style="width: $br_time_width%;">$loc_end_time</th>
HTML;
		$edit_header = <<<HTML
<th scope="col">$loc_edit</th>
HTML;
	}
	// for display: only one time column (from … to …)
	else {
		$loc_time = loc_get_str('time', true);
		$br_time_width *= 2;
		$time_header = <<<HTML
<th scope="col" style="width: $br_time_width%;">$loc_time</th>
HTML;
	}
	$date_css = '';
	if (isset($br_date_width)) {
		$date_css = <<<CSS
 style="width: $br_date_width%;"
CSS;
	}
	$name_css = '';
	if (isset($br_name_width)) {
		$name_css = <<<CSS
 style="width: $br_name_width%;"
CSS;
	}
	$loc_date = loc_get_str('date', true);
	$loc_name = loc_get_str('name', true);
	$result = <<<HTML
	<table class="$table_classes">
		<tr>
			$day_of_week_header
			<th scope="col"$date_css>$loc_date</th>
			$time_header
			<th scope="col"$name_css>$loc_name</th>
			$edit_header
		</tr>
HTML;

	// generate HTML table body
	$opened_db = mysql_db_connect_if_null($db);
	$data = office_hours_break_data($db);
	if ($opened_db) {
		mysql_db_close($db);
	}
	$loc_to = loc_get_str('to');
	foreach ($data as $i => $row) {
		list($date, $start_time, $end_time, $name, $show) = $row;
		if (!$show && !$edit_mode) {
			continue;
		}
		$dt_date = new \DateTime($date);
		$dt_start_time = new \DateTime($start_time);
		$dt_end_time = new \DateTime($end_time);
		$start_time_show = $dt_start_time->format('H:i');
		$end_time_show = $dt_end_time->format('H:i');
		if ($short) {
			$time_show = $start_time_show;
			$day_of_week_cell = '';
		}
		else {
			$time_show = "$start_time_show $loc_to $end_time_show";
			$day_of_week = datefmt_format_object($dt_date, 'EEEE', LOCALE);
			$day_of_week_cell = "<td>$day_of_week</td>";
		}
		$date_show = $date;
		$name_show = htmlspecialchars($name);
		$qstr_base = "?break=1&day=$date&start_time=$start_time"
			. "&end_time=$end_time";
		if ($edit_mode) {
			$qstr_date = htmlspecialchars("$qstr_base&col=date");
			$qstr_start = htmlspecialchars("$qstr_base&col=start_time");
			$qstr_end = htmlspecialchars("$qstr_base&col=end_time");
			$qstr_name = htmlspecialchars("$qstr_base&col=name");
			$qstr_delete = htmlspecialchars("$qstr_base&delete");
			$loc_show = loc_get_str('show');
			$checked = $show ? ' checked' : '';
			$date_show = <<<HTML
<a href="$qstr_date">$date_show</a>
HTML;
			$time_cells = <<<HTML
<td><a href="$qstr_start">$start_time_show</a></td>
<td><a href="$qstr_end">$end_time_show</a></td>
HTML;
			$name_show = <<<HTML
<a href="$qstr_name">$name_show</a>
HTML;
			// PHP’s parsing of request data allows easily creating arrays
			// https://secure.php.net/manual/en/reserved.variables.post.php#87650
			$edit_cell = <<<HTML
<td class="editcol">
	<input name="r[{$i}][date]" type="hidden" value="$date" />
	<input name="r[{$i}][start_time]" type="hidden" value="$start_time" />
	<input name="r[{$i}][end_time]" type="hidden" value="$end_time" />
	<label><input name="r[{$i}][show]" type="checkbox"$checked value="" />
		$loc_show</label>
	|
	<a href="$qstr_delete">❌</a>
</td>
HTML;
		}
		else {
			$time_cells = "<td>$time_show</td>";
			$edit_cell = '';
		}
		// append row to HTML table
		$result .= <<<HTML
		<tr>
			$day_of_week_cell
			<td>$date_show</td>
			$time_cells
			<td>$name_show</td>
			$edit_cell
		</tr>
HTML;
	}
	$result .= '</table>';
	return $result;
}

