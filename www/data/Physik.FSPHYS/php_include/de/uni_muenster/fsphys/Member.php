<?php
namespace de\uni_muenster\fsphys;
require_once 'init.php';

class Member extends MemberRecord {
	const TABLE_NAME = 'members';
	
	function __construct(?int $member_id=MemberRecord::ID_NONE) {
		parent::__construct($member_id, 'member_id', self::TABLE_NAME);
	}
	
	protected function check_col_func($locale): callable {
		static $valid_names = NULL;
		if ($valid_names === NULL) {
			$valid_names = [
				false => [
					// table “members”
					'member_id', 'forenames', 'nickname', 'surname',
					'name_url', 'uni_email', 'member_start', 'member_end',
					'pgp_id', 'pgp_url', 'mem_sort_key',
				],
				true => [
					// tables “members__<lang_code>”
					'member_id', 'title', 'duties', 'program',
					'additional_info',
				],
			];
			foreach ($valid_names as $localized => $arr) {
				$valid_names[$localized] = array_flip($arr);
			}
		}
		return $this->create_check_col_func($valid_names, $locale);
	}
	
	protected function process_input_data(array &$data, $locale): void {
		if (!$locale) {
			if (isset($data['member_end']) && !$data['member_end']) {
				unset($data['member_end']);
			}
			if (!isset($data['mem_sort_key'])) {
				$data['mem_sort_key']
					= "{$data['surname']}, {$data['forenames']}";
			}
		}
	}
	
	function add_committee_entry(Committee $committee, array $data):
		CommitteeEntry {
		$data['member_id'] = $this->get_id();
		$data['committee_id'] = $committee->get_id();
		$new_row = new CommitteeEntry();
		$new_row->create_new($data);
		return $new_row;
	}
	
	/*
		Returns data in the form
		[
			category_0 => [
				committee_0 => [row_0, row_1, …],
				committee_1 => [row_i, …],
				…
			],
			category_1 => [
				committee_0 => [row_j, …],
				committee_1 => [row_k, …],
				…
			],
			…
		]
		(i < j < k)
	*/
	function get_committee_data($locale=LOCALE, bool $group=false): array {
		if ($this->is_new()) {
			return [];
		}
		$entries_tbl = CommitteeEntry::TABLE_NAME;
		$entries_loc_tbl = Util::localized_table_name($entries_tbl, $locale);
		$com_tbl = Committee::TABLE_NAME;
		$com_loc_tbl = Util::localized_table_name($com_tbl, $locale);
		$sql = <<<SQL
		SELECT * FROM
			"$entries_tbl" NATURAL JOIN "$entries_loc_tbl"
				NATURAL JOIN "$com_tbl" NATURAL JOIN "$com_loc_tbl"
			WHERE "member_id" = :member_id
			ORDER BY "category", "com_sort_key", "committee_name",
				"start", "end";
SQL;
		$query = Util::sql_execute($sql, ['member_id' => $this->get_id()]);
		if ($group) {
			$result = [];
			// group array elements by category and committee_id
			while ($row = $query->fetch()) {
				$category = $row['category'];
				$committee = $row['committee_id'];
				$result[$category][$committee][] = $row;
			}
			return $result;
		}
		else {
			return $query->fetchAll() ?? [];
		}
	}
	
	function format_committee_data(callable $edit_callback=NULL,
		$locale=LOCALE): string {
		$data = $this->get_committee_data($locale, true);
		return self::format_committee_data_arr($data, $edit_callback, $locale);
	}

	protected function format_committee_data_arr(array $data,
		callable $edit_callback=NULL, $locale=LOCALE): string {
		if (!$data) {
			return '';
		}
		$edit_class = $edit_callback ? ' fsphys_edit_table' : '';
		$result = <<<HTML
		<table class="fsphys_member_committees$edit_class">
HTML;
		foreach ($data as $category => $category_data) {
			$category_name = Localization::get("members.committees.$category",
				true, $locale);
			$edit_header = '';
			if ($edit_callback) {
				$edit_header = $edit_callback('header');
			}
			$result .= <<<HTML
			<tbody>
				<tr>
					<th scope=rowgroup colspan=3>$category_name</th>
					$edit_header
				</tr>
HTML;
			foreach ($category_data as $committee_id => $committee_data) {
				$committee = new Committee($committee_id);
				$com_html = $committee->get_html($locale);
				$row_count = count($committee_data);
				$first_row = true;
				// data is sorted by timespan
				foreach ($committee_data as $row) {
					$name_cell = $edit_cell = '';
					$end = $row['end'] ?? Localization::get('today');
					if ($first_row) {
						$name_cell = <<<HTML
						<th scope=row rowspan=$row_count
							class="subhead fix">$com_html</th>
HTML;
					}
					if ($edit_callback) {
						$row_id = $row['row_id'];
						$edit_cell = $edit_callback('cell', [
							'member_id' => $this->get_id(), 'row_id' => $row_id
						]);
					}
					$result .= <<<HTML
				<tr>
					$name_cell
					<td>{$row['start']}–$end</td>
					<td>{$row['info']}</td>
					$edit_cell
				</tr>
HTML;
					$first_row = false;
				}
			}
			$result .= '</tbody>';
		}
		$result .= '</table>';
		return $result;
	}
	
	static function list_all(): array {
		$tbl_name = self::TABLE_NAME;
		$sql = <<<SQL
		SELECT * FROM "$tbl_name"
			ORDER BY "mem_sort_key";
SQL;
		$query = DB::query($sql);
		$result = [];
		while ($row = $query->fetch()) {
			$member = new Member($row['member_id']);
			$member->data_cache['unloc'] = $row;
			$result[] = $member;
		}
		return $result;
	}
}

