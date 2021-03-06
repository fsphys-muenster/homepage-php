<?php
use de\uni_muenster\fsphys;
use de\uni_muenster\fsphys\{DB, Localization as Loc, Util};
require_once 'init.php';
require_once 'office_hours.php';

function html_input_type(string $col): string {
	static $types = NULL;
	if ($types === NULL) {
		$types = [
			'date' => 'date',
			'start_time' => 'time',
			'end_time' => 'time'
		];
	}
	return $types[$col] ?? 'text';
}

function update_times($start_time, $end_time, $time_col, $new_time): void {
	if (!in_array($time_col, ['start_time', 'end_time'])) {
		throw new \DomainException('$time_col must be \'start_time\' or '
			. "'end_time'");
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
	// available in MySQL/MariaDB
	// https://en.wikipedia.org/wiki/Merge_%28SQL%29
	$sql = <<<SQL
	DELETE FROM "office_hours"
		WHERE "$time_col" = :new_time AND "$other_time_col" = :other_time;
	UPDATE "office_hours" SET "$time_col" = :new_time
		WHERE "$time_col" = :old_time AND "$other_time_col" = :other_time;
SQL;
	DB::beginTransaction();
	$result = Util::sql_execute($sql, [
		'old_time' => $old_time,
		'new_time' => $new_time,
		'other_time' => $other_time_col
	]);
	DB::commit();
}

function save_shows(): void {
	$data = $_POST['r'];
	$PK_CONDITION = fsphys\BREAK_PK_CONDITION;
	$sql = <<<SQL
	UPDATE "office_hours_break" SET "show" = :show WHERE $PK_CONDITION;
SQL;
	DB::beginTransaction();
	$query = DB::prepare($sql);
	foreach ($data as $row) {
		if (!is_array($row)) {
			continue;
		}
		// XXX should NULL really be used here?
		$query->bindValue(':day', $row['date'] ?? NULL);
		$query->bindValue(':start_time', $row['start_time'] ?? NULL);
		$query->bindValue(':end_time', $row['end_time'] ?? NULL);
		$query->bindValue(':show', isset($row['show']), \PDO::PARAM_BOOL);
		$query->execute();
	}
	DB::commit();
}
?>

<article class="module extended">
<div class=module-content>

<?php
fsphys\run_and_catch(function() {
?>
	<p style="text-align: center;">
	<a href="?break="><?=Loc::get('office hours', true)?></a> |
	<a href="?break=1"><?=Loc::get('office hours (break)', true)?></a>
	</p>

<?php
if (isset($_GET['break'])) {
	$break = boolval($_GET['break']);
	// determine which state of the page we’re in
	// if $mask_entry is true, $mask_time is also true
	$mask_entry = Util::keys_set_all($_GET, 'day', 'start_time', 'end_time',
		'col');
	$mask_time = Util::keys_set_all($_GET, 'start_time', 'end_time', 'col');
	$mask_new_entry = isset($_GET['new_entry']);
	$change_entry = Util::keys_set_all($_POST, 'day', 'start_time', 'end_time',
		'col', 'val');
	$new_entry = Util::keys_set_all($_POST, 'day', 'start_time', 'end_time',
		'new_entry');
	$save_shows = $break && Util::keys_set_all($_POST, 'save_shows', 'r')
		&& is_array($_POST['r']);
	$delete_entry = $break && Util::keys_set_all($_POST, 'delete', 'r')
		&& is_array($_POST['r']);

	$data_arr_raw = $mask_time ? $_GET : $_POST;
	// apply htmlspecialchars to all GET/POST string input in $data_arr
	$data_arr = Util::htmlspecialchars($data_arr_raw);
	extract($data_arr, EXTR_PREFIX_ALL, 'req');
	// form to edit an existing entry (both)
	if ($mask_time) {
		if ($mask_entry) {
			$req_day = $break ? $req_day : intval($req_day);
			$val = fsphys\office_hours_get($break, $req_day, $req_start_time,
				$req_end_time, $req_col);
		}
		// only $mask_time
		else {
			$req_day = '';
			$val = $data_arr[$req_col] ?? NULL;
		}
		$val = Util::htmlspecialchars($val);
?>
		<form method=post action="?break=<?=$break?>">
			<input type=hidden name=day value="<?=$req_day?>">
			<input type=hidden name=start_time
				value="<?=$req_start_time?>">
			<input type=hidden name=end_time value="<?=$req_end_time?>">
			<input type=hidden name=col value="<?=$req_col?>">
			<label><?=Loc::get('office_hours.edit.enter_value', true)?>:
				<input type=<?=html_input_type($req_col)?> name=val
					size=50 value="<?=$val?>">
			</label>
			<input type=submit value="<?=Loc::get('enter', true)?>">
		</form>
<?php
	}
	// form for creating a new entry (only for break)
	elseif ($mask_new_entry) {
?>
		<form method=post action="?break=<?=$break?>">
			<label><?=Loc::get('date', true)?>:
				<input type=date name=day required>
			</label>
			<label><?=Loc::get('start time', true)?>:
				<input type=time name=start_time required>
			</label>
			<label>
				<?=Loc::get('end time', true)?>:
				<input type=time name=end_time required>
			</label>
			<label><?=Loc::get('name', true)?>:
				<input type=text name=name size=50>
			</label>
			<input type=checkbox name=show value="" id=chk_show>
			<label for=chk_show><?=Loc::get('show', true)?></label>
			<input type=submit name=new_entry
				value="<?=Loc::get('enter', true)?>">
		</form>
<?php
	}
	// else: data has been submitted (POST) or there is no user interaction,
	// i.e. no edit form is being shown
	else {
		// apply submitted data
		if ($change_entry) {
			// for database: use val exactly as POSTed
			$raw_val = $data_arr_raw['val'];
			if ($req_day) {
				fsphys\office_hours_set($break, $req_day, $req_start_time,
					$req_end_time, $req_col, $raw_val);
			}
			// !$req_day means that times are being set in the non-break
			// schedule
			else {
				update_times($req_start_time, $req_end_time, $req_col,
					$raw_val);
			}
		}
		// create submitted entry
		elseif ($new_entry) {
			fsphys\office_hours_insert(true, [
				'date' => $req_day,
				'start_time' => $req_start_time,
				'end_time' => $req_end_time,
				// for database: use text exactly as POSTed
				'name' => $data_arr_raw['name'] ?? '',
				'show' => isset($req_show)
			]);
		}
		// save which entries should be shown
		elseif ($save_shows) {
			save_shows();
		}
		// delete specified entry
		elseif ($delete_entry) {
			$idx = $req_delete;
			if (isset($req_r[$idx])) {
				$row = $req_r[$idx];
				$req_day = $row['date'] ?? NULL;
				$req_start_time = $row['start_time'] ?? NULL;
				$req_end_time = $row['end_time'] ?? NULL;
				fsphys\office_hours_delete($break, $req_day, $req_start_time,
					$req_end_time);
			}
		}
		// show schedule (always do this when no edit form is being shown)
		$options = ['edit_mode' => true];
		if ($break) {
?>
		<form method=post action="?break=<?=$break?>" id=fsphys_oh_form_edit>
			<?=fsphys\office_hours_break_html($options)?>
			<div class=center>
				<input type=submit name=save_shows
					value="<?=Loc::get('save show setting', true)?>">
			</div>
		</form>
		<div class=center>
			<a href="?break=<?=$break?>&amp;new_entry"
				class=fsphys_oh_new_entry><?=Loc::get('new entry', true)?></a>
		</div>
<?php
		}
		else {
			echo fsphys\office_hours_html($options);
		}
	}
}

// XXX use /Physik.FSPHYS/js/form_delete_confirmation.js
?>
<!-- add a JavaScript confirmation dialog for the delete buttons -->
<script type=text/javascript>
document.addEventListener('DOMContentLoaded', function() {
	let save_submitter = function(event) {
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
		oh_form.onsubmit = function() {
			let submitActor = this.submitActor;
			if (submitActor && submitActor.matches('.fsphys_oh_delete')) {
				return window.confirm('<?=
					Loc::get('delete_confirmation_dialog')?>');
			}
		}
	}
});
</script>

<?php
}, Loc::get('members.edit.error_message')); // fsphys\run_and_catch() end
?>

</div>
</article>

