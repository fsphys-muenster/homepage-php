<?php
use de\uni_muenster\fsphys;
use function de\uni_muenster\fsphys\loc_get_str;
require_once 'init.inc';
require_once 'settings.inc';
require_once 'localization.inc';
require_once 'office_hours.inc';

// max. rows to show in preview for office hours during break
const OH_BREAK_MAX_ROWS = 10;
?>

<article class="module short">
<div class=module-content>

<?php
fsphys\run_and_catch(function() {
?>
<h2><?=loc_get_str('current office hours', true)?></h2>

<div class=subhead>
<?php
$dt_now = new \DateTime();
$semester = fsphys\semester_info($dt_now)['during_semester'];
$holidays = fsphys\get_setting('office_hours.start_page_holiday_message',
	'int');
if ($holidays) {
?>
	<?=loc_get_str('holidays', true)?></div>
	<?=loc_get_str('HOLIDAY_MSG')?>
<?php
}
elseif ($semester) {
	// display office hours for the day five hours in the future (so that e.g.
	// the schedule for Tuesday isn’t still displayed at 23:30 Tuesday night)
?>
	<?=loc_get_str('during the semester', true)?></div>
	<div class=fsphys_oh_front_page>
		<?=fsphys\office_hours_html([],
			$dt_now->add(new \DateInterval('PT5H')))?>
	</div>
<?php
}
else {
?>
	<?=loc_get_str('semester break', true)?></div>
	<div class=fsphys_oh_front_page>
		<?=fsphys\office_hours_break_html(['short' => true],
			OH_BREAK_MAX_ROWS)?>
	</div>
<?php
}
?>

<p><a class=int
	href="/Physik.FSPHYS/<?=fsphys\loc_url_lang_code()?>termine/"><?=
		loc_get_str('complete office hours schedule', true)?></a></p>
<?php
}); // fsphys\run_and_catch() end
?>

</div>
</article>

