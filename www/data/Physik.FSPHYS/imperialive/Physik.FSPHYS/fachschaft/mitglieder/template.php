<?php
use de\uni_muenster\fsphys;
use de\uni_muenster\fsphys\{Localization as Loc, Member, MemberFormatter};
require_once 'init.php';

$mem = NULL;
fsphys\run_and_catch(function() {
	$member = Member::from_url();
	global $mem;
	$mem = new MemberFormatter($member);
?>

<!-- IMPERIA MODULE: CONTACT DATA -->
Name:             <?=$mem->attr('surname')?>
Vorname:          <?=$mem->attr('forenames')?> <?=$mem->attr('nickname')?>
Titel:            <?=$mem->attr('title')?>
Position[de]:     <?=Loc::get('members.duties', true)?> <?=$mem->attr('duties')?>
Position[en]:     <?=Loc::get('members.duties', true)?> <?=$mem->attr('duties')?>
Einrichtung[de]:  <?=Loc::get('members.timespan', true)?> <?=$mem->attr('timespan')?>
Einrichtung[en]:  <?=Loc::get('members.timespan', true)?> <?=$mem->attr('timespan')?>
Abteilung[de]:    <?=Loc::get('members.program', true)?> <?=$mem->attr('program')?>
Abteilung[en]:    <?=Loc::get('members.program', true)?> <?=$mem->attr('program')?>
Land[de]:         <?=$mem->attr('pgp')?>
Land[en]:         <?=$mem->attr('pgp')?>
Uni-interne E-Mail-Adresse: <?=$mem->attr('uni_email')?>
<!-- END IMPERIA MODULE -->

<?php
}); // fsphys\run_and_catch() end

fsphys\run_and_catch(function() {
	global $mem;
	$additional_info = $mem->attr('additional_info');
	$committee_data = $mem->committee_data();
	if (!$additional_info && !$committee_data) return;
?>
<article class="module extended">
<div class="module-content">
	<?=$additional_info?>
	<?=$committee_data?>
</div>
</article>
<?php
}, '<article class="module extended"><div class="module-content">'
	. fsphys\DEFAULT_ERR_MSG
	. '</div></article>'
);
?>

<!-- IMPERIA TEASERS -->
<!--
	The call to member_teaser_start is put in the name fields because the
	contents of these fields are inserted first. Note that the title field (if
	it were used) could come before them. Similarly, the member_teaser_end is
	called in the email field because it is inserted last.
	Also note that both member_teaser_start and member_teaser_end will be
	called several times because the person’s name & title are duplicated in
	the “alt” attribute of the img element and because the content of the email
	field is used both for the URL and the link text.
-->
Kurzfassung der Form WWU_KFSG in der Sprache (de|en):
	Name:         <?php member_teaser_start();?><?=$mem->attr('surname')?>
	Vorname:      <?php member_teaser_start();?><?=$mem->attr('forenames')?> <?=$mem->attr('nickname')?>
	Position:     <?=Loc::get('members.duties', true)?> <?=$mem->attr('duties')?>
	Einrichtung:  <?=Loc::get('members.timespan', true)?> <?=$mem->attr('timespan')?>
	Abteilung:    <?=Loc::get('members.program', true)?> <?=$mem->attr('program')?>
	Land:         <?=$mem->attr('pgp')?>
	Uni-interne E-Mail-Adresse: <?=$mem->attr('uni_email')?><?php member_teaser_end();?>

