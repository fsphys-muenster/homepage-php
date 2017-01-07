<?php
use de\uni_muenster\fsphys;
require_once 'error_handler.inc';
require_once 'settings.inc';
require_once 'office_hours.inc';

// max. rows to show in preview for office hours during break
const OH_BREAK_MAX_ROWS = 10;
?>

<article class="module short">
<div class="module-content">
<?php
fsphys\run_and_catch(function() {
?>
<h2><?=fsphys\loc_get_str('current office hours', true)?></h2>

<div class="subhead">
<?php
$dt_now = new \DateTime();
$semester = fsphys\semester_info($dt_now)['during_semester'];
$db = fsphys\mysql_db_connect();
$holidays = fsphys\get_setting('office_hours.start_page_holiday_message', $db,
	'int');
if ($holidays) {
?>
	<?=fsphys\loc_get_str('holidays', true)?></div>
	<?=fsphys\loc_get_str('HOLIDAY_MSG')?>
<?php
}
elseif ($semester) {
?>
	<?=fsphys\loc_get_str('during the semester', true)?></div>
	<div class="fsphys_oh_front_page">
		<?=fsphys\office_hours_html($db, [], $dt_now)?>
	</div>
<?php
}
else {
?>
	<?=fsphys\loc_get_str('semester break', true)?></div>
	<div class="fsphys_oh_front_page">
		<?=fsphys\office_hours_break_html($db, ['short' => true],
			OH_BREAK_MAX_ROWS)?>
	</div>
<?php
}
fsphys\mysql_db_close($db);
?>

<p><a class="int"
	href="/Physik.FSPHYS/<?=fsphys\loc_url_lang_code()?>termine/"><?=
		fsphys\loc_get_str('complete office hours schedule', true)?></a></p>
<?php
}); // fsphys\run_and_catch() end
?>
</div>
</article>

