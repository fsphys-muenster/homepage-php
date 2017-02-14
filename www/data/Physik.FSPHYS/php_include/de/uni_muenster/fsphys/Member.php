<?php
namespace de\uni_muenster\fsphys;
require_once 'init.php';

class Member extends MemberRecord {
	const TABLE_NAME = 'members';
	
	static function from_url(?string $url=NULL): self {
		$url = $url ?? $_SERVER['REQUEST_URI'];
		$path = parse_url($url, PHP_URL_PATH);
		$segments = explode('/', $path);
		$name_url = array_pop($segments);
		$tbl_name = self::TABLE_NAME;
		$sql = <<<SQL
		SELECT "member_id" FROM "$tbl_name" WHERE "name_url" = :name_url;
SQL;
		$sql_result = Util::sql_execute($sql,
			['name_url' => $name_url])->fetch();
		if (!$sql_result) {
			throw new \UnexpectedValueException('Database returned no values '
				. "in table “{$tbl_name}” for name_url = $name_url");
		}
		return new Member($sql_result[0]);
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
	
	function __construct(?int $member_id=self::ID_NONE) {
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
		$new_row = new CommitteeEntry;
		$new_row->create_new($data);
		return $new_row;
	}
	
	/*
		If $group is true: Returns data in the form
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
}

