<?php
require_once __DIR__ . '/../intern/intern-fs/admin/php-include/php_settings.inc';
require_once __DIR__ . '/../intern/intern-fs/admin/php-include/db_access.inc';
?>

<?php
/*
	Imperia modules...
*/
?>

<?php
$db = mysql_db_connect();

$sql = 'SELECT * FROM praesenzplan ORDER BY zeit ASC;';
$query = $db->query($sql);
echo <<<'HTML'
	<table style="width: 100%;">
		<colgroup>
			<col style="width: 10%;">
			<col style="width: 18%;">
			<col style="width: 18%;">
			<col style="width: 18%;">
			<col style="width: 18%;">
			<col style="width: 18%;">
		</colgroup>
		<tr>
			<th scope="col">Zeit</th>
			<th scope="col">Montag</th>
			<th scope="col">Dienstag</th>
			<th scope="col">Mittwoch</th>
			<th scope="col">Donnerstag</th>
			<th scope="col">Freitag</th>
		</tr>
HTML;
while ($row = $query->fetch()) {
	$time = $row['zeit'];
	echo <<<HTML
		<tr>
			<td>$time</td>
			<td>{$row['montag']}</td>
			<td>{$row['dienstag']}</td>
			<td>{$row['mittwoch']}</td>
			<td>{$row['donnerstag']}</td>
			<td>{$row['freitag']}</td>
		</tr>
HTML;
}
echo '</table>';
?>

<?php
/*
	Imperia modules...
*/
?>

<?php
// get setting for how many rows to display
$sql = 'SELECT value FROM settings WHERE "key" = \'fepraesenzplan.limit\';';
$query = $db->query($sql);
$limit = $query->fetch()[0];
// get and display data
$sql = 'SELECT * FROM fepraesenzplan ORDER BY ID ASC;';
$query = $db->query($sql);
echo <<<'HTML'
	<table style="width: 100%;">
		<colgroup>
			<col style="width: 18%" />
			<col style="width: 18%" />
			<col style="width: 32%" />
			<col style="width: 32%" />
		</colgroup>
		<tr>
			<th scope="col">Wochentag</th>
			<th scope="col">Datum</th>
			<th scope="col">Zeit</th>
			<th scope="col">Name</th>
		</tr>
HTML;
setlocale(LC_TIME, 'de_DE');
$i = 0;
while (($row = $query->fetch()) && $i < $limit) {
	$i++;
	// get localized day of week from date field
	$day_of_week = strftime('%A', strtotime($row['Datum']));
	// convert times to Unix timestamps, then format using date()
	$starttime = date('H:i', strtotime($row['starttime']));
	$endtime = date('H:i', strtotime($row['endtime']));
	echo <<<HTML
		<tr>
			<td>$day_of_week</td>
			<td>{$row['Datum']}</td>
			<td>$starttime bis $endtime</td>
			<td>{$row['name']}</td>
		</tr>
HTML;
}
echo '</table>';

mysql_db_close($db);
?>

<?php
/*
	Imperia modules...
*/
?>

<!-- Javascript to select the correct schedule tab depending on the date
     (during the semester or the semester break) -->
<script type="text/javascript">
	// note: month ranges from 0 to 11 in JS Date objects
	function is_ss(date) {
		var month = date.getMonth();
		// between start of April and end of September
		return month >= 3 && month < 9;
	}
	var current_date = new Date();
	var current_year = current_date.getFullYear();
	// get dates for current and next semester
	// WS lecture start: ≈ 7th October, SS lecture start: ≈ 7th April
	var ws_start = new Date(current_year, 9, 7);
	if (ws_start > current_date && !is_ss(current_date)) {
		ws_start.setFullYear(ws_start.getFullYear() - 1);
	}
	var ss_start = new Date(current_year, 3, 7);
	if (ss_start < current_date && !is_ss(current_date)) {
		ss_start.setFullYear(ss_start.getFullYear() + 1);
	}
	// WS lecture end: ≈ 1st February, SS lecture end: ≈ 20th July
	var ws_lecture_end = new Date(ws_start.getFullYear() + 1, 1, 1);
	var ss_lecture_end = new Date(ws_start.getFullYear(), 6, 20);
	document.addEventListener('DOMContentLoaded', function() {
		// get tab tags
		var tabs = $('ul.element.tabs').children();
		if ((current_date >= ss_start && current_date < ss_lecture_end)
			|| (current_date >= ws_start && current_date < ws_lecture_end)) {
			tabClicks($(tabs.get(0)));
		}
		else {
			tabClicks($(tabs.get(1)));
		}
	});
</script>

