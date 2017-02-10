<?php
use de\uni_muenster\fsphys;
use de\uni_muenster\fsphys\{CSRF, DB, Localization as Loc, Util,
	Committee, CommitteeEntry, Member, MemberRecord};
require_once 'init.php';

function process_input_data(array $locales): void {
	// CSRF check
	CSRF::check_http_headers();
	// validity check
	$filter_def = [
		'member_id' => FILTER_VALIDATE_INT,
		'row_id' => FILTER_VALIDATE_INT,
		'unloc' => [
			'flags' => FILTER_FORCE_ARRAY,
			'options' => ['default' => [],],
		],
		'delete_member' => FILTER_VALIDATE_INT,
		'delete_row' => FILTER_VALIDATE_INT,
	];
	foreach ($locales as $locale) {
		$filter_def[$locale] = [
			'flags' => FILTER_FORCE_ARRAY,
			'options' => ['default' => [],],
		];
	}
	$data = Util::filter_input_array_defaults(INPUT_POST, $filter_def);
	$object = NULL;
	$delete = Util::keys_set_any($data, 'delete_member', 'delete_row');
	// will data be deleted?
	if (isset($data['delete_member'])) {
		$object = new Member($data['delete_member']);
	}
	elseif (isset($data['delete_row'])) {
		$object = new CommitteeEntry($data['delete_row']);
	}
	// has member data been submitted?
	elseif (isset($data['member_id'])) {
		$object = new Member($data['member_id']);
	}
	// has a committee information row been submitted?
	elseif (isset($data['row_id'])) {
		$object = new CommitteeEntry($data['row_id']);
	}
	// update database if information has been submitted
	if (!$object) return;
	if ($delete) {
		$object->delete();
	}
	elseif ($object->is_new()) {
		$object->create_new($data);
	}
	else {
		$object->set_data_all($data);
	}
}

?>

<article class="module extended">
<div class="module-content">

<?php
fsphys\run_and_catch(function() {
	// organize GET input data
	$member_committee_form = Util::keys_set_any($_GET, 'new_row', 'row');
	$member_form = Util::keys_set_any($_GET, 'new_member', 'member')
		&& !$member_committee_form;
	$get = Util::filter_input_array_defaults(INPUT_GET, [
		'member' => [
			'filter' => FILTER_VALIDATE_INT,
			'options' => ['default' => MemberRecord::ID_NONE,],
		],
		'row' => [
			'filter' => FILTER_VALIDATE_INT,
			'options' => ['default' => MemberRecord::ID_NONE,]
		],
		'new_member' => FILTER_VALIDATE_BOOLEAN,
		'new_row' => FILTER_VALIDATE_BOOLEAN,
	]);
	$member = new Member($get['member']);
	$row = new CommitteeEntry($get['row']);
	// process any POST data
	$locales = Loc::list_locales();
	if ($_POST) {
		process_input_data($locales);
	}
	// =======================================================================
	// show form to input member data
	// =======================================================================
	if ($member_form) {
		if (!$member->is_new()) {
			$mem_data['unloc'] = Util::html_str($member->get_data());
		}
?>
	<form method=post action="<?=Util::this_page_url_path()?>">
		<input name=member_id type=hidden value="<?=$member->get_id()?>">
		<fieldset>
			<legend><?=Loc::get('name', true)?></legend>
			<label class="two columns"><?=Loc::get('forename(s)', true)?>
				<input name=unloc[forenames] type=text required
					value="<?=$mem_data['unloc']['forenames'] ?? ''?>"></label>
			<label class="two columns"><?=Loc::get('surname', true)?>
				<input name=unloc[surname] type=text required
					value="<?=$mem_data['unloc']['surname'] ?? ''?>"></label>
			<label class="two columns"><?=Loc::get('nickname', true)?>
				<input name=unloc[nickname] type=text required
					value="<?=$mem_data['unloc']['nickname'] ?? ''?>"></label>
			<label><?=Loc::get('name for URL', true)?>
			<input name=unloc[name_url] type=text
				value="<?=$mem_data['unloc']['name_url'] ?? ''?>"></label>
		</fieldset>
		<p><label><?=Loc::get('uni email', true)?>
			<input name=unloc[uni_email] type=text required
				value="<?=$mem_data['unloc']['uni_email'] ?? ''?>"></label>	
		<fieldset>
			<legend><?=Loc::get('member timespan', true)?></legend>
			<label class="two columns"><?=Loc::get('from', true)?>
				<input name=unloc[member_start] type=date required
					value="<?=$mem_data['unloc']['member_start'] ?? ''?>">
			</label>
			<label class="two columns"><?=Loc::get('to', true)?>
				<input name=unloc[member_end] type=date
					value="<?=$mem_data['unloc']['member_end'] ?? ''?>">
			</label>
		</fieldset>
		<fieldset>
			<label class="three columns"><?=Loc::get('PGP key', true)?>
				<input name=unloc[pgp_id] type=text
					value="<?=$mem_data['unloc']['pgp_id'] ?? ''?>"></label>
			<label class="three columns"><?=Loc::get('PGP URL', true)?>
				<input name=unloc[pgp_url] type=url
					value="<?=$mem_data['unloc']['pgp_url'] ?? ''?>"></label>
		</fieldset>
<?php
		foreach ($locales as $locale) {
			$mem_data[$locale] = Util::html_str($member->get_data($locale));
?>
		<hr>
		<fieldset>
			<legend><?=Loc::get('data for language', true)?>
				<?=$locale?></legend>
			<p><label><?=Loc::get('title', true)?>
				<input name=<?=$locale?>[title] type=text
					list=fsphys_titles_<?=$locale?>
					value="<?=$mem_data[$locale]['title'] ?? ''?>"></label>
			<p><label><?=Loc::get('duties', true)?>
				<input name=<?=$locale?>[duties] type=text required
					value="<?=$mem_data[$locale]['duties'] ?? ''?>"></label>
			<p><label><?=Loc::get('study program', true)?>
				<input name=<?=$locale?>[program] type=text
					list=fsphys_program_<?=$locale?> required
					value="<?=$mem_data[$locale]['program'] ?? ''?>"></label>
			<p><label><?=Loc::get(
				'additional information (e.g. studies abroad)', true)?>
				<small>(<?=Loc::get('Markdown syntax allowed')?>)</small>
				<textarea name=<?=$locale?>[additional_info]><?=
					$mem_data[$locale]['additional_info'] ?? ''?></textarea>
			</label>

			<datalist id=fsphys_titles_<?=$locale?>>
				<?=Loc::get('MEM_DATALIST_TITLES', false, $locale)?>
			</datalist>
			<datalist id=fsphys_programs_<?=$locale?>>
				<?=Loc::get('MEM_DATALIST_PROGRAMS', false, $locale)?>
			</datalist>
		</fieldset>
<?php
			if (!$member->is_new()) {
				echo $member->format_committee_data(
					function($part, array $data=NULL) {
						$loc_edit = Loc::get('edit', true);
						$loc_edit_data = Loc::get('edit data', true);
						$loc_delete = Loc::get('delete', true);
						switch ($part) {
							case 'header':
								return "<th scope=col>$loc_edit</th>";
								break;
							case 'cell':
								$mem_id = $data['member_id'];
								$row_id = $data['row_id'];
								return <<<HTML
								<td>
									<a href="?row=$row_id"
										title="$loc_edit">🖉</a>
									<button name=delete_row type=submit
										formaction="?member=$mem_id"
										title="$loc_delete"
										value="$row_id">❌</button>
								</td>
HTML;
								break;
						}
					}, $locale);
			}
		}
		if (!$member->is_new()) {
?>
		<hr>
		<p><a class=int href="?member=<?=$member->get_id()
			?>&amp;new_row"><?=Loc::get('new committee entry', true)?></a></p>
<?php
		}
?>
		<input type=submit value="<?=Loc::get('save', true)?>"
			class="six columns">
	</form>
<?php
	}
	// =======================================================================
	// show form to add/edit a committee information row
	// =======================================================================
	elseif ($member_committee_form) {
		if (!$row->is_new()) {
			$com_data_raw = $row->get_data();
			$member = new Member($com_data_raw['member_id']);
			$com_data['unloc'] = Util::html_str($com_data_raw);
		}
		$committees = Committee::list_all();
?>
	<form method=post action="?member=<?=$member->get_id()?>">
		<input name=row_id type=hidden value="<?=$row->get_id()?>">
		<input name=unloc[member_id] type=hidden value="<?=
			$member->get_id()?>">
		<label><?=Loc::get('committee', true)?>
			<select name=unloc[committee_id] required>
<?php
		foreach ($committees as $com) {
			$selected = '';
			if ($com->has_entry($row)) {
				$selected = ' selected';
			}
?>
				<option value="<?=$com->get_id()?>"<?=$selected?>><?=
					$com->get_attr('committee_name')?></option>
<?php
		}
?>
			</select>
		</label>
		<fieldset>
			<legend><?=Loc::get('timespan', true)?></legend>
			<label class="two columns"><?=Loc::get('from', true)?>
				<input name=unloc[start] type=date required
					value="<?=$com_data['unloc']['start'] ?? ''?>"></label>
			<label class="two columns"><?=Loc::get('to', true)?>
				<input name=unloc[end] type=date
					value="<?=$com_data['unloc']['end'] ?? ''?>"></label>
		</fieldset>
<?php
		foreach ($locales as $locale) {
			$com_data[$locale] = Util::html_str($row->get_data($locale));
?>
		<fieldset>
			<legend><?=Loc::get('data for language', true)?>
				<?=$locale?></legend>
			<label class="six columns"><?=
				Loc::get('additional information (e.g. full/deputy member)',
					true)?>
				<input type=text name=<?=$locale?>[info]
					list=fsphys_member_kinds_<?=$locale?>
					value="<?=$com_data[$locale]['info'] ?? ''?>"></label>

			<datalist id=fsphys_member_kinds_<?=$locale?>>
				<?=Loc::get('MEM_DATALIST_MEMBER_KINDS', false, $locale)?>
			</datalist>
		</fieldset>
<?php
		}
?>
		<input type=submit value="<?=Loc::get('save', true)?>"
			class="six columns">
	</form>
<?php
	}
	// =======================================================================
	// show form to add/edit a committee
	// =======================================================================
	// XXX committee form
/*
#			<label class="two columns"><?=
#				Loc::get('name of the new committee', true)?> (<?=$locale?>)
#				<input name=unloc[committee_name] type=text></label>
#<?php
#		foreach ($locales as $locale) {
#?>
#			<label><?=Loc::get('name of the new committee', true)?>
#				(<?=$locale?>)
#				<input name=<?=$locale?>[html] type=text></label>
#<?php
#		}
#?>
*/
	// =======================================================================
	// else: no edit form is being shown, show user list
	// =======================================================================
	else {
?>
	<form method=post action="<?=Util::this_page_url_path()?>">
		<table class=fsphys_mem_memlist>
			<tr>
				<th scope=col><?=Loc::get('name', true)?></th>
				<th scope=col><?=Loc::get('email address', true)?><br>
					(<code>@uni-muenster.de</code>)</th>
				<th scope=col><?=Loc::get('edit', true)?></th>
			</tr>
<?php
		$members = Member::list_all();
		foreach ($members as $member) {
			$mem_row = Util::html_str($member->get_data());
?>
			<tr>
				<td><?=$mem_row['forenames']?> <?=$mem_row['surname']?></td>
				<td><code><?=$mem_row['uni_email']?></code></td>
				<td>
					<a href="?member=<?=$member->get_id()?>"
						title="<?=Loc::get('edit data', true)?>">🖉</a>
					<button name=delete_member type=submit
						title="<?=Loc::get('delete', true);?>"
						value="<?=$member->get_id()?>">❌</button>
				</td>
			</tr>
<?php
		}
?>
		</table>
		<p><a class=int href="?new_member"><?=
				Loc::get('new member', true)?></a></p>
	</form>
<?php
	}
}, Loc::get('members.edit.error_message')); // fsphys\run_and_catch() end
?>

</div>
</article>

