<?php
namespace de\uni_muenster\fsphys;
require_once 'init.inc';
require_once 'util.inc';

/*
	The special value of the end_time column in the office_hours database
	table signifying that no end time was specified.
*/
const UNSPECIFIED_END_TIME = '00:00:00';
/*
	Attributes (CSS classes) used in the office hours schedule HTML table.
*/
const OH_CSS_PREFIX = 'fsphys_oh_';
const OH_CSS_TABLE_CLASS = OH_CSS_PREFIX . 'table';
const OH_CSS_TABLE_EDIT_CLASS = OH_CSS_PREFIX . 'table_edit';
const OH_CSS_TABLE_SHORT_CLASS = OH_CSS_PREFIX . 'short';
const OH_CSS_DAY_COL_CLASS = OH_CSS_PREFIX . 'day_col';
const OH_CSS_TIME_COL_CLASS = OH_CSS_PREFIX . 'time_col';
const OH_CSS_DATE_COL_CLASS = OH_CSS_PREFIX . 'date_col';
const OH_CSS_EDIT_COL_CLASS = OH_CSS_PREFIX . 'edit_col';
const OH_CSS_NON_EDIT_CLASS = OH_CSS_PREFIX . 'non_edit';
const OH_CSS_EDIT_DELETE_CLASS = OH_CSS_PREFIX . 'delete';
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
*/
function office_hours_table($day=NULL): array {
	$where_clause = '';
	if ($day) {
		if (!oh_valid_day_of_week($day)) {
			throw new \DomainException("Value of \$day is not valid: $day");
		}
		$where_clause = 'WHERE "day" = :day';
	}
	$sql = <<<SQL
	SELECT "day"+0 as "day_num", "start_time", "end_time", "name"
		FROM "office_hours"
		$where_clause
		ORDER BY "day_num", "start_time", "end_time" ASC;
SQL;
	$query = DB::prepare($sql);
	if ($day) {
		// compare enum integer indices as integers, not as strings
		$day_dtype = is_int($day) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
		$query->bindValue(':day', $day, $day_dtype);
	}
	$query->execute();
	$data = $query->fetchAll();

	$result = [];
	// transfer data from SQL query into 2D array
	foreach ($data as $row) {
		$day_num = $row['day_num'];
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
*/
function office_hours_times(): array {
	$sql = <<<'SQL'
	SELECT DISTINCT "start_time", "end_time" FROM "office_hours"
		ORDER BY "start_time", "end_time" ASC;
SQL;
	$query = DB::prepare($sql);
	$query->execute();
	$data = $query->fetchAll();
	return $data;
}

/*
	Returns all entries in the database table for the office hours during the
	break, ordered by date → start_time → end_time (ascending).
*/
function office_hours_break_data(int $limit=NULL, $date=NULL) {
	$where_clause = $limit_clause = '';
	if ($limit) {
		if (!$date) {
			// DateTime for yesterday: subtract 1 day
			$date = (new \DateTime())->sub(new \DateInterval('P1D'));
		}
		$where_clause = "WHERE \"date\" >= '{$date->format('Y-m-d')}'";
		$limit_clause = 'LIMIT ' . intval($limit);
	}
	$sql = <<<SQL
	SELECT * FROM "office_hours_break" $where_clause
		ORDER BY "date", "start_time", "end_time" ASC
		$limit_clause;
SQL;
	$query = DB::query($sql);
	$data = $query->fetchAll();
	return $data;
}

function oh_valid_col_name(bool $break, string $col): bool {
	return $break ? in_array($col, OH_BREAK_COL_NAMES)
		: in_array($col, OH_COL_NAMES);
}

function oh_table_info(bool $break): array {
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
function office_hours_get(bool $break, $day, $start_time, $end_time,
	$col=NULL) {
	if ($col && !oh_valid_col_name($break, $col)) {
		return false;
	}
	[$tbl_name, ] = oh_table_info($break);
	$PK_CONDITION = $break ? BREAK_PK_CONDITION : PK_CONDITION;
	$sql = "SELECT * FROM $tbl_name WHERE $PK_CONDITION;";
	$query = DB::prepare($sql);
	// compare enum integer indices as integers, not as strings
	$day_dtype = is_int($day) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
	$query->bindValue(':day', $day, $day_dtype);
	$query->bindValue(':start_time', $start_time);
	$query->bindValue(':end_time', $end_time);
	$query->execute();
	$result = $query->fetch();
	if ($col) {
		return $result[$col];
	}
	return $result;
}

function office_hours_insert(bool $break, $row) {
	$col_names_arr = array_keys($row);
	foreach ($col_names_arr as $col) {
		if (!oh_valid_col_name($break, $col)) {
			throw new \DomainException("Invalid column name: $col");
		}
	}
	[$tbl_name, ] = oh_table_info($break);
	// enclose column names in double quotes to guard against reserved names
	$col_names = implode(', ', array_map(
		function($x) { return '"' . $x . '"'; }, $col_names_arr));
	// use the appropriate number of '?' placeholders for values
	$val_names = implode(', ', array_fill(0, count($row), '?'));
	$sql = "INSERT INTO $tbl_name ($col_names) VALUES ($val_names);";
	$query = DB::prepare($sql);
	foreach (array_values($row) as $i => $val) {
		$pdo_dtype = is_bool($val) ? \PDO::PARAM_BOOL : \PDO::PARAM_STR;
		// bindValue() uses 1-indexed positions
		$query->bindValue($i + 1, $val, $pdo_dtype);
	}
	$query->execute();
}

/*
	$break (bool): Select which MySQL table to use (during semester or break).
	$day: If $break: The value for the 'date' column
	      Else: The value for the 'day' column
	$col: The name of the table column whose value is to be set. A new row
		for this key will be created if needed or the existing row will be
		updated. The new or updated row will have the value $value for the
		column $col.
*/
function office_hours_set(bool $break, $day, $start_time, $end_time, $col,
	$value) {
	if (!oh_valid_col_name($break, $col)) {
		throw new \DomainException("Invalid column name: $col");
	}
	[$tbl_name, $day_col] = oh_table_info($break);
	$key_arr = $break ? OH_BREAK_KEY_COL_NAMES : OH_KEY_COL_NAMES;
	// if setting one of the key columns, the SQL query is slightly different
	$set_key = in_array($col, $key_arr);
	if ($set_key) {
		$PK_CONDITION = $break ? BREAK_PK_CONDITION : PK_CONDITION;
		$sql = <<<SQL
		UPDATE "$tbl_name" SET "$col" = :value WHERE $PK_CONDITION;
SQL;
	}
	// insert row into table or update if row already exists (“upsert”);
	// in standard SQL, this is done with the MERGE statement, but this is not
	// available in MySQL
	// https://en.wikipedia.org/wiki/Merge_%28SQL%29
	else {
		$sql = <<<SQL
		INSERT INTO "$tbl_name" ("$day_col", "start_time", "end_time", "$col")
			VALUES(:day, :start_time, :end_time, :value)
			ON DUPLICATE KEY UPDATE "$col" = :value;
SQL;
	}
	$query = DB::prepare($sql);
	$query->bindValue(':day', $day);
	$query->bindValue(':start_time', $start_time);
	$query->bindValue(':end_time', $end_time);
	$query->bindValue(':value', $value);
	$query->execute();
	// if setting a key column and the row didn’t exist, insert instead
	if ($set_key && !$query->rowCount()) {
		$row = [
			$day_col => $day,
			'start_time' => $start_time,
			'end_time' => $end_time
		];
		$row[$col] = $value;
		office_hours_insert($break, $row);
	}
}

function office_hours_delete(bool $break, $day, $start_time, $end_time): void {
	[$tbl_name, ] = oh_table_info($break);
	$PK_CONDITION = $break ? BREAK_PK_CONDITION : PK_CONDITION;
	$sql = "DELETE FROM \"$tbl_name\" WHERE $PK_CONDITION;";
	$query = DB::prepare($sql);
	$query->bindValue(':day', $day);
	$query->bindValue(':start_time', $start_time);
	$query->bindValue(':end_time', $end_time);
	$query->execute();
}

/*
	Returns a string containing the HTML table with the full office hours
	schedule.
	
	$html_options:
	'edit_mode': Insert id and class attributes to allow the table to be used
	for editing the schedule.
	
	If $date: The table will only contain the column corresponding to the day
	of the week of $date, or the next Monday if $date is in the weekend.
*/
// XXX modify this to work like office_hours_break_html (get rid of the
// office_hours_table stuff)
function office_hours_html(array $html_options=[], \DateTime $date=NULL) {
	extract($html_options);
	$OH_CSS_TIME_COL_CLASS = OH_CSS_TIME_COL_CLASS;
	$OH_CSS_DAY_COL_CLASS = OH_CSS_DAY_COL_CLASS;
	$edit_mode = isset($edit_mode) && $edit_mode;
	// generate HTML table header
	$table_classes = OH_CSS_TABLE_CLASS;
	if ($edit_mode) {
		$table_classes .= ' ' . OH_CSS_TABLE_EDIT_CLASS;
		$loc_start_time = Localization::get('start time', true);
		$loc_end_time = Localization::get('end time', true);
		$time_header = <<<HTML
<th scope=col class="$OH_CSS_TIME_COL_CLASS">$loc_start_time</th>
<th scope=col class="$OH_CSS_TIME_COL_CLASS">$loc_end_time</th>
HTML;
	}
	else {
		$loc_time = Localization::get('time', true);
		$time_header = <<<HTML
<th scope=col class="$OH_CSS_TIME_COL_CLASS">$loc_time</th>
HTML;
	}
	$monday_num = DAYS_OF_WEEK['Monday'];
	$friday_num = DAYS_OF_WEEK['Friday'];
	if ($date) {
		// 1 (Monday) to 7 (Sunday)
		$date_day_of_week = intval($date->format('N'));
		// for Saturday and Sunday: show Monday
		if ($date_day_of_week >= DAYS_OF_WEEK['Saturday']) {
			$days_to_monday = 8 - $date_day_of_week;
			// set DateTime to next Monday
			$date->add(new \DateInterval("P{$days_to_monday}D"));
			$date_day_of_week = intval($date->format('N'));
		}
		// 'EEEE' →  full day of week in PHP intl; see IntlDateFormatter
		// https://secure.php.net/manual/en/class.intldateformatter.php
		// https://ssl.icu-project.org/apiref/icu4c/classSimpleDateFormat.html
		$days = [datefmt_format_object($date, 'EEEE', LOCALE)];
	}
	else {
		$date_day_of_week = NULL;
		// table from Monday to Friday if no date is given
		for ($day_num = $monday_num; $day_num <= $friday_num; $day_num++) {
			$dt_day = new \DateTime(DAYS_OF_WEEK[$day_num]);
			// 'EEEE' →  full localized day of week (see above)
			$days[] = datefmt_format_object($dt_day, 'EEEE', LOCALE);
		}
	}
	$result = <<<HTML
	<table class="$table_classes">
		<tr>
			$time_header
HTML;
	$day_col_single = count($days) == 1 ? ' fsphys_oh_single' : '';
	foreach ($days as $day_name) {
		$result .= <<<HTML
			<th scope=col
				class="$OH_CSS_DAY_COL_CLASS$day_col_single">$day_name</th>\n
HTML;
	}
	$result .= '</tr>';

	// generate HTML table body
	$times = office_hours_times();
	$timetable = office_hours_table($date_day_of_week);
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
		[$start_time, $end_time] = $time_entry;
		$dt_start_time = new \DateTime($start_time);
		$start_time_show = $dt_start_time->format('H');
		if ($end_time === UNSPECIFIED_END_TIME || !$end_time) {
			$loc_from = Localization::get('from');
			$end_time_show = '–';
			$time_show = "$loc_from&nbsp;$start_time_show";
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
			<td><a href="$qstr_start"><span class=fsphys_oh_webkit-fix>$start_time_show</span></a></td>
			<td><a href="$qstr_end"><span class=fsphys_oh_webkit-fix>$end_time_show</span></a></td>
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
			<td><a href="$qstr_name"><span class=fsphys_oh_webkit-fix>$name_show</span></a></td>
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
	
	$html_options:
	  'edit_mode': Insert id and class attributes to allow the table to be used
	    for editing the schedule.
	  'short': Show “day of week” column if false
	$max_rows: How many rows to display (unset/NULL: no limit)
*/
function office_hours_break_html(array $html_options=[], $max_rows=NULL) {
	extract($html_options);
	$OH_CSS_DATE_COL_CLASS = OH_CSS_DATE_COL_CLASS;
	$OH_CSS_TIME_COL_CLASS = OH_CSS_TIME_COL_CLASS;
	$OH_CSS_EDIT_COL_CLASS = OH_CSS_EDIT_COL_CLASS;
	$OH_CSS_NON_EDIT_CLASS = OH_CSS_NON_EDIT_CLASS;
	$OH_CSS_EDIT_DELETE_CLASS = OH_CSS_EDIT_DELETE_CLASS;
	$edit_mode = isset($edit_mode) && $edit_mode;
	$short = isset($short) && $short;
	// generate HTML table header
	$table_classes = OH_CSS_TABLE_CLASS;
	if ($short) {
		$table_classes .= ' ' . OH_CSS_TABLE_SHORT_CLASS;
	}
	$loc_day_of_week = Localization::get('day_of_week', true);
	$day_of_week_header = $short ? '' : <<<HTML
<th scope="col">$loc_day_of_week</th>
HTML;
	// for editing: one individual table column for each database column
	// (“edit” column includes “show” value and settings like deleting a row)
	if ($edit_mode) {
		$table_classes .= ' ' . OH_CSS_TABLE_EDIT_CLASS;
		$loc_start_time = Localization::get('start time', true);
		$loc_end_time = Localization::get('end time', true);
		$loc_edit = Localization::get('edit', true);
		$time_header = <<<HTML
<th scope="col" class="$OH_CSS_TIME_COL_CLASS">$loc_start_time</th>
<th scope="col" class="$OH_CSS_TIME_COL_CLASS">$loc_end_time</th>
HTML;
		$edit_header = <<<HTML
<th scope="col">$loc_edit</th>
HTML;
	}
	// for display: only one time column (from … to …)
	else {
		$loc_time = Localization::get('time', true);
		$time_header = <<<HTML
<th scope=col class="$OH_CSS_TIME_COL_CLASS">$loc_time</th>
HTML;
		$edit_header = '';
	}
	$loc_date = Localization::get('date', true);
	$loc_name = Localization::get('name', true);
	$result = <<<HTML
	<table class="$table_classes">
		<tr>
			$day_of_week_header
			<th scope=col class="$OH_CSS_DATE_COL_CLASS">$loc_date</th>
			$time_header
			<th scope=col>$loc_name</th>
			$edit_header
		</tr>
HTML;

	// generate HTML table body
	$data = office_hours_break_data($max_rows);
	$loc_to = Localization::get('to');
	foreach ($data as $i => $row) {
		[$date, $start_time, $end_time, $name, $show] = $row;
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
			$day_of_week_cell = <<<HTML
<td class="$OH_CSS_NON_EDIT_CLASS">$day_of_week</td>
HTML;
		}
		$date_show = <<<HTML
<time datetime="{$date}T$start_time">$date</time>
HTML;
		$name_show = htmlspecialchars($name);
		$qstr_base = "?break=1&day=$date&start_time=$start_time"
			. "&end_time=$end_time";
		if ($edit_mode) {
			$qstr_date = htmlspecialchars("$qstr_base&col=date");
			$qstr_start = htmlspecialchars("$qstr_base&col=start_time");
			$qstr_end = htmlspecialchars("$qstr_base&col=end_time");
			$qstr_name = htmlspecialchars("$qstr_base&col=name");
			$loc_show = Localization::get('show');
			$loc_delete = Localization::get('delete', true);
			$checked = $show ? 'checked' : '';
			$date_show = <<<HTML
<a href="$qstr_date"><span class=fsphys_oh_webkit-fix>$date_show</span></a>
HTML;
			$time_cells = <<<HTML
<td><a href="$qstr_start"><span class=fsphys_oh_webkit-fix>$start_time_show</span></a></td>
<td><a href="$qstr_end"><span class=fsphys_oh_webkit-fix>$end_time_show</span></a></td>
HTML;
			$name_show = <<<HTML
<a href="$qstr_name"><span class=fsphys_oh_webkit-fix>$name_show</span></a>
HTML;
			// PHP’s parsing of request data allows easily creating arrays
			// https://secure.php.net/manual/en/reserved.variables.post.php#87650
			$edit_cell = <<<HTML
<td class="$OH_CSS_EDIT_COL_CLASS">
	<div>
		<input name="r[$i][date]" type=hidden value="$date" />
		<input name="r[$i][start_time]" type=hidden value="$start_time" />
		<input name="r[$i][end_time]" type=hidden value="$end_time" />
		<input name="r[$i][show]" id="r[$i][show]" type=checkbox $checked
			value="" />
		<label for="r[$i][show]">$loc_show</label>
		<button name=delete type=submit title="$loc_delete"
			class="$OH_CSS_EDIT_DELETE_CLASS" value="$i"><span
			class=fsphys_oh_webkit-fix>❌</span></button>
	</div>
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

