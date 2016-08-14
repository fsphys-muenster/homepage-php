<?php
use de\uni_muenster\fsphys;
require_once 'error_handler.inc';
require_once 'db_access.inc';
require_once 'office_hours.inc';
?>

<?php
/*
	Imperia modules...
*/
?>

<?php
$db = mysql_db_connect();

echo office_hours_html($db);
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

<!-- JavaScript to select the correct schedule tab depending on the date
     (during the semester or the semester break) -->
<script type="text/javascript"
	src="/Physik.FSPHYS/js/office_hours_date_select.js"></script>

