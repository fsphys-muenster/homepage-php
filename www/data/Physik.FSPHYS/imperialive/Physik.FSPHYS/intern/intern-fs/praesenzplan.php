<?php
use de\uni_muenster\fsphys;
require_once 'error_handler.inc';
require_once 'office_hours.inc';

function dget($dict, $key, $default=NULL) {
	return isset($dict[$key]) ? $dict[$key] : $default;
}

function keys_set($arr, ...$keys) {
	foreach ($keys as $key) {
		if (!isset($arr[$key])) {
			return false;
		}
	}
	return true;
}

function update_times($db, $start_time, $end_time, $time_col, $new_time) {
	if (!in_array($time_col, ['start_time', 'end_time'])) {
		return false;
	}
	if ($time_col == 'start_time') {
		$old_time = $start_time;
		$other_time_col = 'end_time';
	}
	else {
		$old_time = $end_time;
		$other_time_col = 'start_time';
	}
	// overwrite rows that already exist;
	// this could be done with the standard MERGE statement, but this is not
	// available in MySQL
	// https://en.wikipedia.org/wiki/Merge_%28SQL%29
	$sql = <<<SQL
	DELETE FROM office_hours
		WHERE $time_col = :new_time AND $other_time_col = :other_time;
	UPDATE office_hours SET $time_col = :new_time
		WHERE $time_col = :old_time AND $other_time_col = :other_time;
SQL;
	$query = $db->prepare($sql);
	$query->bindValue(':old_time', $old_time);
	$query->bindValue(':new_time', $new_time);
	$query->bindValue(':other_time', $$other_time_col);
	$result = $query->execute();
	return $result;
}

function save_shows($db) {
	$data = $_POST['r'];
	$PK_CONDITION = fsphys\BREAK_PK_CONDITION;
	$sql = <<<SQL
	UPDATE office_hours_break SET "show" = :show WHERE $PK_CONDITION;
SQL;
	$query = $db->prepare($sql);
	foreach ($data as $row) {
		if (!is_array($row)) {
			continue;
		}
		$query->bindValue(':day', dget($row, 'date'));
		$query->bindValue(':start_time', dget($row, 'start_time'));
		$query->bindValue(':end_time', dget($row, 'end_time'));
		$query->bindValue(':show', isset($row['show']), \PDO::PARAM_BOOL);
		$query->execute();
	}
}
?>

<article class="module extended">
<div class="module-content">
	<p style="text-align: center;">
	<a href="?break="><?=fsphys\loc_get_str('office hours', true);?></a> |
	<a href="?break=1"><?=fsphys\loc_get_str('office hours (break)', true);?></a>
	</p>

<?php
if (isset($_GET['break'])) {
	$break = boolval($_GET['break']);
	// determine which state of the page we’re in
	// if $show_mask_entry is true, $show_mask_time is also true
	$show_mask_entry = keys_set($_GET, 'day', 'start_time', 'end_time', 'col');
	$show_mask_time = keys_set($_GET, 'start_time', 'end_time', 'col');
	$show_mask_new_entry = keys_set($_GET, 'new_entry');
	$delete_entry = keys_set($_GET, 'day', 'start_time', 'end_time', 'delete');
	$save_shows = $break && keys_set($_POST, 'save_shows', 'r');
	$change_entry = keys_set($_POST, 'day', 'start_time', 'end_time', 'col',
		'val');
	$new_entry = keys_set($_POST, 'day', 'start_time', 'end_time',
		'new_entry');

	$data_arr = $show_mask_time || $delete_entry ? $_GET : $_POST;
	$data_arr = array_map(function($x) {
		return is_string($x) ? htmlspecialchars($x) : NULL;
	}, $data_arr);
	extract($data_arr, EXTR_PREFIX_ALL, 'req');
	$db = fsphys\mysql_db_connect();
	if ($show_mask_time) {
		if ($show_mask_entry) {
			$req_day = $break ? $req_day : intval($req_day);
			$val = fsphys\office_hours_get($db, $break, $req_day,
				$req_start_time, $req_end_time, $req_col);
		}
		// only $show_mask_time
		else {
			$req_day = '';
			$val = dget($data_arr, $req_col);
		}
		$val = htmlspecialchars($val);
?>
		<form method="post" action="?break=<?=$break?>">
			<input type="hidden" name="day" value="<?=$req_day?>" />
			<input type="hidden" name="start_time"
				value="<?=$req_start_time?>" />
			<input type="hidden" name="end_time" value="<?=$req_end_time?>" />
			<input type="hidden" name="col" value="<?=$req_col?>" />
			<div class="center">
				<input type="text" name="val" size="50"
					value="<?=$val?>" />
				<div style="margin-top: 3ex;">
					<input type="submit" value="Eintragen" />
				</div>
			</div>
		</form>
<?php
	}
	elseif ($show_mask_new_entry) {
?>
		<form method="post" action="?break=<?=$break?>">
			<label>
				<?=fsphys\loc_get_str('date', true)?>:
				<input type="date" name="day" required />
			</label>
			<label>
				<?=fsphys\loc_get_str('start time', true)?>:
				<input type="time" name="start_time" required />
			</label>
			<label>
				<?=fsphys\loc_get_str('end time', true)?>:
				<input type="time" name="end_time" required />
			</label>
			<label>
				<?=fsphys\loc_get_str('name', true)?>:
				<input type="text" name="name" />
			</label>
			<label>
				<input type="checkbox" name="show" value="" />
				<?=fsphys\loc_get_str('show', true)?>
			</label>
			<div class="center" style="margin-top: 3ex;">
				<input type="submit" name="new_entry" value="Eintragen" />
			</div>
		</form>
<?php
	}
	else {
		if ($change_entry) {
			// for database: use val exactly as POSTed
			$raw_val = $_POST['val'];
			if ($req_day) {
				fsphys\office_hours_set($db, $break, $req_day, $req_start_time,
					$req_end_time, $req_col, $raw_val);
			}
			// !$req_day means that times are being set in the non-break
			// schedule
			else {
				update_times($db, $req_start_time, $req_end_time, $req_col,
					$raw_val);
			}
		}
		elseif ($new_entry) {
			fsphys\office_hours_insert($db, true, [
				'date' => $req_day,
				'start_time' => $req_start_time,
				'end_time' => $req_end_time,
				// for database: use text exactly as POSTed
				'name' => dget($_POST, 'name'),
				'show' => isset($req_show)
			]);
		}
		elseif ($save_shows) {
			save_shows($db);
		}
		elseif ($delete_entry) {
			fsphys\office_hours_delete($db, $break, $req_day, $req_start_time,
				$req_end_time);
		}
		// show schedule
		$options = fsphys\DEFAULT_HTML_OPTIONS;
		$options['edit_mode'] = true;
		if ($break) {
			$break_schedule = fsphys\office_hours_break_html($db, $options);
?>
		<form method="post" action="?break=<?=$break?>">
			<?=$break_schedule?>
			<div class="center" style="margin-top: 3ex;">
				<input type="submit" name="save_shows"
					value="<?=fsphys\loc_get_str('save show setting', true)?>"
				/>
			</div>
		</form>
		<div class="center" style="margin-top: 3ex;">
			<a href="?break=<?=$break?>&amp;new_entry">
				<?=fsphys\loc_get_str('new entry', true)?>
			</a>
		</div>
<?php
		}
		else {
			echo fsphys\office_hours_html($db, $options);
		}
	}
	fsphys\mysql_db_close($db);
}
?>

</div>
</article>

