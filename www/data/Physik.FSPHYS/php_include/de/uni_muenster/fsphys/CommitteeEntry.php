<?php
namespace de\uni_muenster\fsphys;
require_once 'init.php';

class CommitteeEntry extends MemberRecord {
	const TABLE_NAME = 'member_committees';

	function __construct(?int $row_id=MemberRecord::ID_NONE) {
		parent::__construct($row_id, 'row_id', self::TABLE_NAME);
	}
	
	protected function check_col_func($locale): callable {
		static $valid_names = NULL;
		if ($valid_names === NULL) {
			$valid_names = [
				false => [
					// table “member_committees”
					'row_id', 'member_id', 'committee_id', 'start', 'end',
				],
				true => [
					// tables “member_committees__<lang_code>”
					'row_id', 'info',
				],
			];
			foreach ($valid_names as $localized => $arr) {
				$valid_names[$localized] = array_flip($arr);
			}
		}
		return $this->create_check_col_func($valid_names, $locale);
	}
	
	protected function process_input_data(array &$data, $locale): void {
		if (isset($data['end']) && !$data['end']) {
			unset($data['end']);
		}
	}
}

