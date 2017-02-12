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
		'committee_id' => FILTER_VALIDATE_INT,
		'row_id' => FILTER_VALIDATE_INT,
		'unloc' => [
			'flags' => FILTER_FORCE_ARRAY,
			'options' => ['default' => [],],
		],
		'delete_member' => FILTER_VALIDATE_INT,
		'delete_committee' => FILTER_VALIDATE_INT,
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
	$delete = Util::keys_set_any($data, 'delete_member', 'delete_committee',
		'delete_row');
	// will data be deleted?
	if (isset($data['delete_member'])) {
		$object = new Member($data['delete_member']);
	}
	elseif (isset($data['delete_committee'])) {
		$object = new Committee($data['delete_committee']);
	}
	elseif (isset($data['delete_row'])) {
		$object = new CommitteeEntry($data['delete_row']);
	}
	// has member data been submitted?
	elseif (isset($data['member_id'])) {
		$object = new Member($data['member_id']);
	}
	// has committee data been submitted?
	elseif (isset($data['committee_id'])) {
		$object = new Committee($data['committee_id']);
	}
	// has a committee information row been submitted?
	elseif (isset($data['row_id'])) {
		$object = new CommitteeEntry($data['row_id']);
	}
	// update database if information has been submitted
	if ($object) {
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
}

?>

<article class="module extended">
<div class="module-content">

<?php
fsphys\run_and_catch(function() {
?>
	<form method=get action="?" id=fsphys_get_same_page hidden></form>
<?php
	// organize GET input data
	$get = Util::filter_input_array(INPUT_GET, [
		'member' => FILTER_VALIDATE_INT,
		'committee' => FILTER_VALIDATE_INT,
		'row' => FILTER_VALIDATE_INT,
		'new_member' => FILTER_VALIDATE_BOOLEAN,
		'new_committee' => FILTER_VALIDATE_BOOLEAN,
		'new_row' => FILTER_VALIDATE_BOOLEAN,
		'list_committees' => FILTER_VALIDATE_BOOLEAN,
	]);
	$committee_list = isset($get['list_committees']);
	$committee_form = Util::keys_set_any($get, 'new_committee', 'committee');
	$member_committee_form = Util::keys_set_any($get, 'new_row', 'row');
	$member_form = Util::keys_set_any($get, 'new_member', 'member')
		&& !$member_committee_form;
	$member = new Member($get['member']);
	$committee = new Committee($get['committee']);
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
				<input name=unloc[nickname] type=text
					value="<?=$mem_data['unloc']['nickname'] ?? ''?>"></label>
			<label><?=Loc::get('members.edit.name_url', true)?>
			<input name=unloc[name_url] type=text required
				pattern="[-_A-Za-z]+"
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
					list=fsphys_programs_<?=$locale?> required
					value="<?=$mem_data[$locale]['program'] ?? ''?>"></label>
			<p><label><?=Loc::get(
				'additional information (e.g. studies abroad)', true)?>
				<small>(<?=Loc::get('Markdown syntax allowed')?>)</small>
				<textarea name=<?=$locale?>[additional_info]><?=
					$mem_data[$locale]['additional_info'] ?? ''?></textarea>
			</label></p>

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
						$loc_delete = Loc::get('delete', true);
						switch ($part) {
						case 'header':
							return "<th scope=col colspan=2>$loc_edit</th>";
						case 'cell':
							$mem_id = $data['member_id'];
							$row_id = $data['row_id'];
							return <<<HTML
							<td class=fsphys_edit_col>
								<button name=row value="$row_id"
									type=submit form=fsphys_get_same_page
									title="$loc_edit"
									class=fsphys_icon>🖉</button></td>
							<td class=fsphys_edit_col>
								<button name=delete_row value="$row_id"
									type=submit formaction="?member=$mem_id"
									title="$loc_delete"
									class=fsphys_delete>❌</button></td>
HTML;
						}
					}, $locale);
			}
		}
		if (!$member->is_new()) {
?>
		<button type=submit name=member value="<?=$member->get_id()?>"
			form=fsphys_new_row><?=Loc::get('new committee entry', true)
		?></button>
<?php
		}
?>
		<input type=submit value="<?=Loc::get('save', true)?>"
			class="six columns">
	</form>
	<form method=get action="?" id=fsphys_new_row hidden>
		<input name=new_row type=hidden></form>
<?php
	}
	elseif ($committee_form) {
	// =======================================================================
	// show form to add/edit a committee
	// =======================================================================
		$selected = [
			'student_body' => '',
			'department' => '',
			'central' => '',
			'other' => '',
		];
		$selected[$committee->get_attr('category') ?? 'other'] = ' selected';
?>
	<form method=post action="?list_committees=">
		<input name=committee_id type=hidden value="<?=$committee->get_id()?>">
		<label><?=Loc::get('members.edit.committee level', true)?>
			<select name=unloc[category] required>
				<option value="student_body"<?=$selected['student_body']?>><?=
					Loc::get('members.edit.committees.student_body')?></option>
				<option value="department"<?=$selected['department']?>><?=
					Loc::get('members.edit.committees.department')?></option>
				<option value="central"<?=$selected['central']?>><?=
					Loc::get('members.edit.committees.central')?></option>
				<option value="other"<?=$selected['other']?>><?=
					Loc::get('members.edit.committees.other')?></option>
			</select></label>
<?php
		foreach ($locales as $locale) {
			$com_data[$locale] = Util::html_str($committee->get_data($locale));
			preg_match('/href="([^"]*)"/u',
				$committee->get_attr('html', $locale), $url_matches);
			$url = $url_matches[1] ?? '';
?>
		<hr>
		<fieldset>
			<legend><?=Loc::get('data for language', true)?>
				<?=$locale?></legend>
			<p><label><?=
				Loc::get('members.edit.name of the committee', true)?>
				<input name=<?=$locale?>[committee_name] type=text required
					value="<?=$com_data[$locale]['committee_name'] ?? ''?>">
			</label>
			<p><label class="six columns"><?=
				Loc::get('members.edit.link for the committee', true)?>
				<input name=<?=$locale?>[url] type=url value="<?=$url?>">
			</label>
			<div class="three columns">
				<input type=checkbox name=<?=$locale?>[url_overwrite]
					<?=$committee->is_new() ? 'checked' : ''?>
					id=chk_url_overwrite_<?=$locale?>>
				<label for=chk_url_overwrite_<?=$locale?>><?=
					Loc::get('members.edit.url_overwrite')?></label>
			</div>
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
	// show form to add/edit a committee information row
	// =======================================================================
	elseif ($member_committee_form) {
		if (!$row->is_new()) {
			$row_data_raw = $row->get_data();
			$member = new Member($row_data_raw['member_id']);
			$row_data['unloc'] = Util::html_str($row_data_raw);
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
		if ($row->is_new()) {
?>
				<option disabled selected hidden><?=
					Loc::get('members.edit.please select', true)?></option>
<?php
		}
		$previous_category = NULL;
		// output select box with all committees
		foreach ($committees as $com) {
			$selected = '';
			if ($com->has_entry($row)) {
				$selected = ' selected';
			}
			$category = $com->get_attr('category');
			if ($category != $previous_category) {
				$previous_category = $category;
?>
				<optgroup label="<?=
					Loc::get("members.edit.committees.$category", true)?>">
<?php
			}
?>
				<option value="<?=$com->get_id()?>"<?=$selected?>><?=
					$com->get_attr('committee_name', Loc::lang_code())
				?></option>
<?php
		} // committee select box end
?>
			</select>
		</label>
		<fieldset>
			<legend><?=Loc::get('timespan', true)?></legend>
			<label class="three columns"><?=Loc::get('from', true)?>
				<input name=unloc[start] type=date required
					value="<?=$row_data['unloc']['start'] ?? ''?>"></label>
			<label class="three columns"><?=Loc::get('to', true)?>
				<input name=unloc[end] type=date
					value="<?=$row_data['unloc']['end'] ?? ''?>"></label>
		</fieldset>
<?php
		foreach ($locales as $locale) {
			$row_data[$locale] = Util::html_str($row->get_data($locale));
?>
		<hr>
		<fieldset>
			<legend><?=Loc::get('data for language', true)?>
				<?=$locale?></legend>
			<label class="six columns"><?=
				Loc::get('additional information (e.g. full/deputy member)',
					true)?>
				<input type=text name=<?=$locale?>[info]
					list=fsphys_member_kinds_<?=$locale?>
					value="<?=$row_data[$locale]['info'] ?? ''?>"></label>

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
	// show list of committees
	// =======================================================================
	elseif ($committee_list) {
?>
	<form method=post action="?list_committees=">
		<table class=fsphys_edit_table>
<?php
		$committees = Committee::list_all();
		$previous_category = NULL;
		foreach ($committees as $com) {
			$category = $com->get_attr('category');
			if ($category != $previous_category) {
				$previous_category = $category;
?>
			<tr>
				<th scope=col><?=
					Loc::get("members.committees.$category", true)?></th>
				<th scope=col colspan=2><?=Loc::get('edit', true)?></th>
			</tr>
<?php
			}
?>
			<tr>
				<td><?=$com->get_attr('html', LOCALE)?></td>
				<td class=fsphys_edit_col><button name=committee
					value="<?=$com->get_id()?>" type=submit
					form=fsphys_get_same_page class=fsphys_icon
					title="<?=Loc::get('edit', true)?>">🖉</button></td>
				<td class=fsphys_edit_col><button name=delete_committee
					value="<?=$com->get_id()?>" type=submit
					class=fsphys_delete
					title="<?=Loc::get('delete', true);?>">❌</button></td>
			</tr>
<?php
		}
?>
		</table>
		<button type=submit form=fsphys_get_same_page><?=
			Loc::get('members.edit.list members', true)?></button>
		<button name=new_committee type=submit form=fsphys_get_same_page><?=
			Loc::get('members.edit.new committee', true)?></button>
	</form>
<?php
	}
	// =======================================================================
	// else: no edit form is being shown, show user list
	// =======================================================================
	else {
?>
	<form method=post action="<?=Util::this_page_url_path()?>">
		<table class=fsphys_edit_table>
			<tr>
				<th scope=col><?=Loc::get('name', true)?></th>
				<th scope=col><?=Loc::get('email address', true)?><br>
					(<code>@uni-muenster.de</code>)</th>
				<th scope=col colspan=2><?=Loc::get('edit', true)?></th>
			</tr>
<?php
		$members = Member::list_all();
		foreach ($members as $member) {
			$mem_row = Util::html_str($member->get_data());
?>
			<tr>
				<td><?=$mem_row['forenames']?> <?=$mem_row['surname']?></td>
				<td><code><?=$mem_row['uni_email']?></code></td>
				<td class=fsphys_edit_col><button name=member
					value="<?=$member->get_id()?>" type=submit
					form=fsphys_get_same_page class=fsphys_icon
					title="<?=Loc::get('edit data', true)?>">🖉</button></td>
				<td class=fsphys_edit_col><button name=delete_member
					value="<?=$member->get_id()?>" type=submit
					class=fsphys_delete
					title="<?=Loc::get('delete', true);?>">❌</button></td>
			</tr>
<?php
		}
?>
		</table>
		<button name=list_committees type=submit form=fsphys_get_same_page><?=
			Loc::get('members.edit.list committees', true)?></button>
		<button name=new_member type=submit form=fsphys_get_same_page><?=
			Loc::get('new member', true)?></button>
	</form>
<?php
	}
}, Loc::get('members.edit.error_message')); // fsphys\run_and_catch() end
?>

</div>
</article>

