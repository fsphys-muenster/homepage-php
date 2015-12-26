<?php
include '/www/data/Physik.FSPHYS/imperialive/Physik.FSPHYS/intern/intern-fs/admin/php-include/db-access.inc';
?>

<?php
/*
	Imperia-Module...
*/
?>

<?php
$db = mysql_db_connect();
$sql = 'SELECT * FROM praesenzplan ORDER BY zeit ASC;';
$query = $db->query($sql);
echo <<<HTML
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
			<th>Time</th>
			<th>Monday</th>
			<th>Tuesday</th>
			<th>Wednesday</th>
			<th>Thursday</th>
			<th>Friday</th>
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
mysql_db_close($db);
?>

<?php
/*
	Imperia-Module...
*/
?>

<?php
$db = mysql_db_connect();
$sql = 'SELECT * FROM fepraesenzplan ORDER BY ID ASC;';
$query = $db->query($sql);
echo <<<HTML
	<table style="width: 100%;">
		<colgroup>
		<col style="width: 18%" />
		<col style="width: 18%" />
		<col style="width: 32%" />
		<col style="width: 32%" />
	</colgroup>
	<tr>
		<th>Day of week</th>
		<th>Date</th>
		<th>Time</th>
		<th>Name</th>
	</tr>
HTML;
$i = 0;
while (($row = $query->fetch()) && $i < $row['Anzahl']) {
	$i++;
	echo <<<HTML
	<tr>
		<td>{$row['Wochentag']}</td>
		<td>{$row['Datum']}</td>
		<td>{$row['starttime']} bis {$row['endtime']}</td>
		<td>{$row['name']}</td>
	</tr>
HTML;
}
echo '</table>';
mysql_db_close($db);
?>

<?php
/*
	Imperia-Module...
*/
?>

<!-- Javascript to select the correct schedule tab depending on the date
     (during the semester or outside) -->
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
	var ws_start = new Date(current_year, 9, 7);
	if (ws_start > current_date && !is_ss(current_date)) {
		ws_start.setFullYear(ws_start.getFullYear() - 1);
	}
	var ss_start = new Date(current_year, 3, 7);
	if (ss_start < current_date && !is_ss(current_date)) {
		ss_start.setFullYear(ss_start.getFullYear() + 1);
	}
	var ws_lecture_end = new Date(ws_start.getFullYear() + 1, 1, 10);
	var ss_lecture_end = new Date(ws_start.getFullYear(), 6, 20);
	document.addEventListener('DOMContentLoaded', function() {
		// get tab tags
		var tabs = $("ul.element.tabs").children();
		if ((current_date >= ss_start && current_date < ss_lecture_end)
			|| (current_date >= ws_start && current_date < ws_lecture_end)) {
			$(tabClicks($(tabs.get(0))));
		}
		else {
			$(tabClicks($(tabs.get(1))));
		}
	});
</script>

