<?php
include '/www/data/Physik.FSPHYS/imperialive/Physik.FSPHYS/intern/intern-fs/admin/php-include/functions.inc';
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
	<table class="center" style="width: 100%;">
		<colgroup>
			<col style="width: 10%;">
			<col style="width: 18%;">
			<col style="width: 18%;">
			<col style="width: 18%;">
			<col style="width: 18%;">
			<col style="width: 18%;">
		</colgroup>
		<tr>
			<th>Zeit</th>
			<th>Montag</th>
			<th>Dienstag</th>
			<th>Mittwoch</th>
			<th>Donnerstag</th>
			<th>Freitag</th>
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
	<table class="center" style="width: 100%;">
		<colgroup>
		<col style="width: 18%" />
		<col style="width: 18%" />
		<col style="width: 32%" />
		<col style="width: 32%" />
	</colgroup>
	<tr>
		<th>Wochentag</th>
		<th>Datum</th>
		<th>Zeit</th>
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

