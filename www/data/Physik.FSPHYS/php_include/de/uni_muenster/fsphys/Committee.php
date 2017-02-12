<?php
namespace de\uni_muenster\fsphys;
require_once 'init.php';

class Committee extends MemberRecord {
	const TABLE_NAME = 'committees';
	
	function __construct(?int $committee_id=MemberRecord::ID_NONE) {
		parent::__construct($committee_id, 'committee_id', self::TABLE_NAME);
	}
	
	protected function check_col_func($locale): callable {
		static $valid_names = NULL;
		if ($valid_names === NULL) {
			$valid_names = [
				false => [
					// table “committees”
					'committee_id', 'category', 'com_sort_key',
				],
				true => [
					// tables “committees__<lang_code>”
					'committee_id', 'committee_name', 'html',
				],
			];
			foreach ($valid_names as $localized => $arr) {
				$valid_names[$localized] = array_flip($arr);
			}
		}
		return $this->create_check_col_func($valid_names, $locale);
	}
	
	protected function process_input_data(array &$data, $locale): void {
		if ($locale) {
			if (!isset($data['html'])) {
				if (Util::keys_set_all($data, 'url', 'committee_name',
					'url_overwrite') && $data['url']) {
					$url = htmlspecialchars($data['url']);
					$name = htmlspecialchars($data['committee_name']);
					$html = <<<HTML
					<a class=ext href="$url">$name</a>
HTML;
					$data['html'] = trim($html);
				}
				elseif ($this->is_new()) {
					$data['html'] = htmlspecialchars(
						$data['committee_name'] ?? 'unnamed');
				}
			}
			unset($data['url']);
			unset($data['url_overwrite']);
		}
	}

	function has_entry(CommitteeEntry $entry): bool {
		return !$entry->is_new()
			&& $this->get_id() == $entry->get_attr('committee_id');
	}

	function get_html($locale=LOCALE): string {
		$committee_id = $this->get_id();
		$tbl_name = $this->localized_table_name($locale);
		$sql = <<<SQL
		SELECT "html" FROM "$tbl_name" WHERE "committee_id" = :committee_id;
SQL;
		$query = Util::sql_execute($sql, ['committee_id' => $committee_id]);
		$result = $query->fetch();
		if (!$result) {
			throw new \UnexpectedValueException('Database returned no values '
				. "in table “{$tbl_name}” for committee_id = $committee_id");
		}
		return $result[0];
	}
	
	static function list_all($locale=LOCALE): array {
		$tbl_name = self::TABLE_NAME;
		$loc_tbl_name = Util::localized_table_name($tbl_name, $locale);
		$sql = <<<SQL
		SELECT * FROM "$tbl_name" NATURAL JOIN "$loc_tbl_name"
			ORDER BY "category", "com_sort_key", "committee_name";
SQL;
		$query = DB::query($sql);
		$result = [];
		while ($row = $query->fetch()) {
			$committee = new Committee($row['committee_id']);
			$committee->data_cache['unloc'] = $row;
			$committee->data_cache[$locale] = $row;
			$result[] = $committee;
		}
		return $result;
	}
}

