<?php
use de\uni_muenster\fsphys;
use de\uni_muenster\fsphys\{Localization as Loc, SemesterInfo, Settings};
require_once 'init.php';
require_once 'office_hours.php';

// max. rows to show in preview for office hours during break
const OH_BREAK_MAX_ROWS = 10;
?>

<article class="module short">
<div class=module-content>

<?php
fsphys\run_and_catch(function() {
?>
<h2><?=Loc::get('current office hours', true)?></h2>

<div class=subhead>
<?php
$dt_now = new \DateTime();
$semester = SemesterInfo::get($dt_now)['during_semester'];
$holidays = Settings::get('office_hours.start_page_holiday_message',
	'int');
if ($holidays) {
?>
	<?=Loc::get('holidays', true)?></div>
	<?=Loc::get('HOLIDAY_MSG')?>
<?php
}
elseif ($semester) {
	// display office hours for the day five hours in the future (so that e.g.
	// the schedule for Tuesday isn’t still displayed at 23:30 Tuesday night)
?>
	<?=Loc::get('during the semester', true)?></div>
	<div class=fsphys_oh_front_page>
		<?=fsphys\office_hours_html([],
			$dt_now->add(new \DateInterval('PT5H')))?>
	</div>
<?php
}
else {
?>
	<?=Loc::get('semester break', true)?></div>
	<div class=fsphys_oh_front_page>
		<?=fsphys\office_hours_break_html(['short' => true],
			OH_BREAK_MAX_ROWS)?>
	</div>
<?php
}
?>

<p><a class=int
	href="/Physik.FSPHYS/<?=Loc::url_lang_code()?>termine/"><?=
		Loc::get('complete office hours schedule', true)?></a></p>
<?php
}); // fsphys\run_and_catch() end
?>

</div>
</article>

