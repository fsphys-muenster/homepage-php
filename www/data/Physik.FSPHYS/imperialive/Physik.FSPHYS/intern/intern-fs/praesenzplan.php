<?php
use de\uni_muenster\fsphys;
require_once 'error_handler.inc';
require_once 'db_access.inc';
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

function html_input_type($col) {
	static $types = NULL;
	if ($types === NULL) {
		$types = [
			'date' => 'date',
			'start_time' => 'time',
			'end_time' => 'time'
		];
	}
	return dget($types, $col, 'text');
}

function update_times($db, $start_time, $end_time, $time_col, $new_time) {
	if (!in_array($time_col, ['start_time', 'end_time'])) {
		return false;
	}
	if ($time_col === 'start_time') {
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
	$result = true;
	$query = $db->prepare($sql);
	foreach ($data as $row) {
		if (!is_array($row)) {
			continue;
		}
		$query->bindValue(':day', dget($row, 'date'));
		$query->bindValue(':start_time', dget($row, 'start_time'));
		$query->bindValue(':end_time', dget($row, 'end_time'));
		$query->bindValue(':show', isset($row['show']), \PDO::PARAM_BOOL);
		$result &= $query->execute();
	}
	return $result;
}

fsphys\run_and_catch(function() {
?>

<article class="module extended">
<div class="module-content">
	<p style="text-align: center;">
	<a href="?break="><?=fsphys\loc_get_str('office hours', true)?></a> |
	<a href="?break=1"><?=fsphys\loc_get_str('office hours (break)', true)?></a>
	</p>

<?php
if (isset($_GET['break'])) {
	$break = boolval($_GET['break']);
	// determine which state of the page we’re in
	// if $show_mask_entry is true, $show_mask_time is also true
	$show_mask_entry = keys_set($_GET, 'day', 'start_time', 'end_time', 'col');
	$show_mask_time = keys_set($_GET, 'start_time', 'end_time', 'col');
	$show_mask_new_entry = keys_set($_GET, 'new_entry');
	$change_entry = keys_set($_POST, 'day', 'start_time', 'end_time', 'col',
		'val');
	$new_entry = keys_set($_POST, 'day', 'start_time', 'end_time',
		'new_entry');
	$save_shows = $break && keys_set($_POST, 'save_shows', 'r')
		&& is_array($_POST['r']);
	$delete_entry = $break && keys_set($_POST, 'delete', 'r')
		&& is_array($_POST['r']);

	$data_arr_raw = $show_mask_time ? $_GET : $_POST;
	// apply htmlspecialchars to all GET/POST string input in $data_arr
	$data_arr = array_map(function($x) {
		return is_string($x) ? htmlspecialchars($x) : $x;
	}, $data_arr_raw);
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
			<input type="<?=html_input_type($req_col)?>" name="val"
				size="50" value="<?=$val?>" />
			<input type="submit" value="<?=fsphys\loc_get_str('enter', true)?>"
				/>
		</form>
<?php
	}
	elseif ($show_mask_new_entry) {
?>
		<form method="post" action="?break=<?=$break?>">
			<label><?=fsphys\loc_get_str('date', true)?>:
				<input type="date" name="day" required />
			</label>
			<label><?=fsphys\loc_get_str('start time', true)?>:
				<input type="time" name="start_time" required />
			</label>
			<label>
				<?=fsphys\loc_get_str('end time', true)?>:
				<input type="time" name="end_time" required />
			</label>
			<label><?=fsphys\loc_get_str('name', true)?>:
				<input type="text" name="name" size="50" />
			</label>
			<input type="checkbox" name="show" value="" id="chk_show" />
			<label for="chk_show"><?=
				fsphys\loc_get_str('show', true)?></label>
			<input type="submit" name="new_entry"
				value="<?=fsphys\loc_get_str('enter', true)?>" />
		</form>
<?php
	}
	else {
		if ($change_entry) {
			// for database: use val exactly as POSTed
			$raw_val = $data_arr_raw['val'];
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
				'name' => dget($data_arr_raw, 'name'),
				'show' => isset($req_show)
			]);
		}
		elseif ($save_shows) {
			save_shows($db);
		}
		elseif ($delete_entry) {
			$idx = $req_delete;
			if (isset($req_r[$idx])) {
				$row = $req_r[$idx];
				$req_day = dget($row, 'date');
				$req_start_time = dget($row, 'start_time');
				$req_end_time = dget($row, 'end_time');
				fsphys\office_hours_delete($db, $break, $req_day, $req_start_time,
					$req_end_time);
			}
		}
		// show schedule
		$options = ['edit_mode' => true];
		if ($break) {
?>
		<form method="post" action="?break=<?=$break?>"
			id="fsphys_oh_form_edit">
			<?=fsphys\office_hours_break_html($db, $options)?>
			<div class="center">
				<input type="submit" name="save_shows"
					value="<?=fsphys\loc_get_str('save show setting', true)?>"
				/>
			</div>
		</form>
		<div class="center">
			<a href="?break=<?=$break?>&amp;new_entry"
				class="fsphys_oh_new_entry"><?=
				fsphys\loc_get_str('new entry', true)?></a>
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

<!-- add a JavaScript confirmation dialog for the delete buttons -->
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
	let save_submitter = function (event) {
		this.form.submitActor = this;
	}
	let buttons = document.querySelectorAll(
		'#fsphys_oh_form_edit input[type=submit], #fsphys_oh_form_edit button'
	);
	for (let i = 0; i < buttons.length; i++) {
		buttons[i].onclick = save_submitter;
		buttons[i].onkeypress = save_submitter;
	}
	let oh_form = document.querySelector('#fsphys_oh_form_edit');
	if (oh_form) {
		oh_form.onsubmit = function () {
			let submitActor = this.submitActor;
			if (submitActor && submitActor.matches('.fsphys_oh_delete')) {
				return window.confirm('<?=
					fsphys\loc_get_str('delete_confirmation_dialog')?>');
			}
		}
	}
});
</script>

<?php
}); // fsphys\run_and_catch() end
?>

