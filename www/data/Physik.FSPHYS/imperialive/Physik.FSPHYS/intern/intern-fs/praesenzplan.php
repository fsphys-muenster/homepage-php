<?php
include '/www/data/Physik.FSPHYS/imperialive/Physik.FSPHYS/intern/intern-fs/admin/php-include/db-access.inc';
	
function praesenzplan_anzeigen() {
	$db = mysql_db_connect();
	$sql = 'SELECT * FROM praesenzplan ORDER BY zeit ASC;';
	$query = $db->query($sql);
	echo <<<HTML
	<script type="text/javascript">
		function redirect(target) {
			window.location.href = target;
		}
	</script>
	<table class="border center" style="width: 100%;">
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
		$javascript = function($day, $time) {
			return <<<JS
			onMouseover="this.style='background-color: #d9ee83;';" onMouseout="this.style='';" onMouseDown="javascript:redirect('$SELF_PHP?praesenzplan=true&amp;day=$day&amp;time=$time');"
JS;
		};
		echo <<<HTML
		<tr>
			<td>$time</td>
			<td {$javascript('montag', $time)}>{$row['montag']}</td>
			<td {$javascript('dienstag', $time)}>{$row['dienstag']}</td>
			<td {$javascript('mittwoch', $time)}>{$row['mittwoch']}</td>
			<td {$javascript('donnerstag', $time)}>{$row['donnerstag']}</td>
			<td {$javascript('freitag', $time)}>{$row['freitag']}</td>
		</tr>
HTML;
	}
	echo '</table>';
	mysql_db_close($db);
}

function fepraesenzplan_anzeigen() {
	$db = mysql_db_connect();
	$sql = 'SELECT * FROM fepraesenzplan ORDER BY ID ASC;';
	$query = $db->query($sql);
	echo <<<HTML
	<script type="text/javascript">
		function redirect(target) {
			window.location.href = target;
		}
	</script>

	<table class="border center"  style="width: 100%;">
	<colgroup>
		<col style="width: 10%" />
		<col style="width: 20%" />
		<col style="width: 20%" />
		<col style="width: 10%" />
		<col style="width: 10%" />
		<col style="width: 30%" />
	</colgroup>
	<tr>
		<th>Nr.</th>
		<th>Wochentag</th>
		<th>Datum</th>
		<th>Startzeit</th>
		<th>Endzeit</th>
		<th>Name</th>
	</tr>
HTML;
	$javascript = function($col, $id) {
		return <<<JS
		onMouseover="this.style='background-color: #d9ee83;';" onMouseout="this.style='';" onMouseDown="javascript:redirect('$SELF_PHP?fepraesenzplan=true&amp;col=$col&amp;id=$id');"
JS;
	};
	while ($row = $query->fetch()) {
		$id = $row['ID'];
		$nr = $id + 1;
		echo <<<HTML
		<tr>
			<td>$nr</td>
			<td {$javascript('Wochentag', $id)}>{$row['Wochentag']}</td>
			<td {$javascript('Datum', $id)}>{$row['Datum']}</td>
			<td {$javascript('starttime', $id)}>{$row['starttime']}</td>
			<td {$javascript('endtime', $id)}>{$row['endtime']}</td>
			<td {$javascript('name', $id)}>{$row['name']}</td>
		</tr>
HTML;
	}
	echo '</table>';
	mysql_db_close($db);
}

function aendere_pzeit($day, $time, $person) {
	if (!ctype_alnum($day) || $day == 'zeit') {
		return false;
	}
	$db = mysql_db_connect();
	$sql = "UPDATE praesenzplan SET $day = :person WHERE zeit = :time;";
	$query = $db->prepare($sql);
	$query->bindValue(':person', $person);
	$query->bindValue(':time', $time);
	$result = $query->execute();
	mysql_db_close($db);
	return $result;
}

function maske_aendere_pzeit($day, $time) {
	$db = mysql_db_connect();
	$sql = 'SELECT * FROM praesenzplan WHERE zeit = :time;';
	$query = $db->prepare($sql);	
	$query->bindValue(':time', $time);
	$query->execute();
	$row = $query->fetch();
	mysql_db_close($db);
	
	$person = $row[$day];
	echo <<<HTML
	<form method="POST" action="$SELF_PHP?praesenzplan=true">
		<input type="hidden" name="day" value="$day">
		<input type="hidden" name="time" value="$time">
		<div class="center">
			<div style="text-align: center;">
				<input type="text" name="pzeit" size="50" value="$person" />
			</div>
			<div style="text-align: center; margin-bottom: 6ex;">
				<input type="submit" value="Eintragen" />
			</div>
		</div>
	</form>
HTML;
}

function aendere_fepzeit($col, $id, $content) {
	if (!ctype_alnum($col) || $col == 'ID') {
		return false;
	}
	$db = mysql_db_connect();
	$sql = "UPDATE fepraesenzplan SET $col = :content WHERE ID = :id;";
	$query = $db->prepare($sql);
	$query->bindValue(':content', $content);
	$query->bindValue(':id', $id);
	$result = $query->execute();
	mysql_db_close($db);
	return $result;
}

function maske_aendere_fepzeit($col, $id) {
	$db = mysql_db_connect();
	$sql = 'SELECT * FROM fepraesenzplan WHERE ID = :id;';
	$query = $db->prepare($sql);
	$query->bindValue(':id', $id);
	$query->execute();
	$result = $query->fetch();
	mysql_db_close($db);
	$content = $result[$col];

	echo <<<HTML
	<form method="POST" action="$SELF_PHP?fepraesenzplan=true">
		<input type="hidden" name="col" value="$col" />
		<input type="hidden" name="id" value="$id" />
		<div class="center">
			<div style="text-align: center;">
				<input type="text" name="pzeit" size="50" value="$content" />
			</div>
			<div style="text-align: center; margin-bottom: 6ex;">
				<input type="submit" value="Eintragen" />
			</div>
		</div>
	</form>
HTML;
}

function aendere_anzahl($count) {
	$db = mysql_db_connect();
	$sql = 'UPDATE fepraesenzplan SET Anzahl = :count;';
	$query = $db->prepare($sql);
	$query->bindValue(':count', $count);
	$result = $query->execute();
	mysql_db_close($db);
	return $result;
}

function maske_aendere_anzahl() {
	$db = mysql_db_connect();
	$sql = 'SELECT Anzahl FROM fepraesenzplan WHERE ID = 0;';
	$query = $db->query($sql);
	$row = $query->fetch();
	mysql_db_close($db);
	
	$count = $row[0];
	echo <<<HTML
	<form method="POST" action="$SELF_PHP?fepraesenzplan=true">
		<div class="center">
			<div style="text-align: center;">
				Anzahl der anzuzeigenden Termine:
			</div>
			<div style="text-align: center;">
				<input type="text" name="Anzahl" size="10" value="$count" />
			</div>
			<div style="text-align: center; margin-bottom: 6ex;">
				<input type="submit" value="Eintragen" />
			</div>
		</div>
	</form>
HTML;
}
?>

<p style="text-align: center;">
<a href="/Physik.FSPHYS/intern/intern-fs/praesenzplan.php?praesenzplan=true">Präsenzplan</a> |
<a href="/Physik.FSPHYS/intern/intern-fs/praesenzplan.php?fepraesenzplan=true">Ferienpräsenzplan</a></p>

<?php
if ($_GET['praesenzplan']) {
	$show_mask = isset($_GET['day']) && isset($_GET['time']);
	$change_entry = isset($_POST['day']) && isset($_POST['time']) && isset($_POST['pzeit']);
	if ($show_mask) {
		$day = $_GET['day'];
		$time = $_GET['time'];
		$person = maske_aendere_pzeit($day, $time);
	}
	else if ($change_entry) {
		$day = $_POST['day'];
		$time = $_POST['time'];
		$person = $_POST['pzeit'];
		aendere_pzeit($day, $time, $person);
		praesenzplan_anzeigen();
	}
	else {
		praesenzplan_anzeigen();
	}
}
else if ($_GET['fepraesenzplan']) {
	$show_mask = isset($_GET['col']) && isset($_GET['id']);
	$change_entry = isset($_POST['col']) && isset($_POST['id']) && isset($_POST['pzeit']);
	$change_count = isset($_POST['Anzahl']);
	if ($show_mask) {
		$col = $_GET['col'];
		$id = $_GET['id'];
		maske_aendere_fepzeit($col, $id);
	}
	else if ($change_entry) {
		$col = $_POST['col'];
		$id = $_POST['id'];
		$content = $_POST['pzeit'];
		aendere_fepzeit($col, $id, $content);
		fepraesenzplan_anzeigen();
		maske_aendere_anzahl();
	}
	else if ($change_count) {
		$count = $_POST['Anzahl'];
		aendere_anzahl($count);
		fepraesenzplan_anzeigen();
		maske_aendere_anzahl();
	}
	else {
		fepraesenzplan_anzeigen();
		maske_aendere_anzahl();
	}
}
?>

