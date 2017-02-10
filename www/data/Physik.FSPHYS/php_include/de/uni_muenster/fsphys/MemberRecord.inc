<?php
namespace de\uni_muenster\fsphys;
require_once 'init.inc';

abstract class MemberRecord {
	const ID_NONE = -1;
	static $valid_names;

	private $id;
	private $id_name;
	private $table_name;
	protected $data_cache = [];

	function __construct(int $id, string $id_name, string $table_name) {
		$this->set_id($id);
		$this->id_name = $id_name;
		$this->table_name = $table_name;
	}
	
	function is_new(): bool {
		return $this->id === self::ID_NONE;
	}
	
	abstract protected function check_col_func($locale): callable;
	
	abstract protected function process_input_data(array &$data, $locale):
		void;
	
	private function set_id(int $id): void {
		$data_cache = [];
		$this->id = $id;
	}
	
	function get_id(): int {
		return $this->id;
	}
	
	protected function create_check_col_func(array $valid_names, $locale):
		callable {
		return function(string $column_name) use ($valid_names, $locale) {
			$table = $this->localized_table_name($locale);
			$localized = $locale !== NULL;
			if (!key_exists($column_name, $valid_names[$localized])) {
				throw new \DomainException("Column name “{$column_name}” is "
					. "not valid for table “{$table}”");
			}
		};
	}
	
	function create_new(array $data): int {
		$locales = Localization::list_locales();
		DB::beginTransaction();
		$this->insert($data['unloc']);
		$new_id = DB::query('SELECT LAST_INSERT_ID();')->fetch()[0];
		foreach ($locales as $locale) {
			$data[$locale][$this->id_name] = $new_id;
			$this->insert($data[$locale], $locale);
		}
		DB::commit();
		$this->set_id($new_id);
		return $new_id;
	}

	private function insert(array $data, $locale=NULL): void {
		$this->process_input_data($data, $locale);
		$tbl_name = $this->localized_table_name($locale);
		[$column_spec, $value_spec] = Util::sql_format_insert($data);
		$sql = <<<SQL
		INSERT INTO "$tbl_name" ($column_spec) VALUES ($value_spec);
SQL;
		Util::sql_execute($sql, $data, $this->check_col_func($locale));
		$data_cache = [];
	}

	private function update(array $data, $locale=NULL): void {
		$this->check_new();
		$this->process_input_data($data, $locale);
		$id_name = $this->id_name;
		$tbl_name = $this->localized_table_name($locale);
		$update_spec = Util::sql_format_update($data);
		$sql = <<<SQL
		UPDATE "$tbl_name" SET $update_spec WHERE "$id_name" = :$id_name;
SQL;
		$data[$id_name] = $this->id;
		Util::sql_execute($sql, $data, $this->check_col_func($locale));
		$data_cache = [];
	}

	function delete(): void {
		if ($this->is_new()) return;
		$id_name = $this->id_name;
		// deletes in “members”, “committees” and “member_committees” cascade to
		// foreign keys
		$sql = <<<SQL
		DELETE FROM "{$this->table_name}" WHERE "$id_name" = :$id_name;
SQL;
		Util::sql_execute($sql, [$this->id_name => $this->id]);
		$this->set_id(self::ID_NONE);
	}
	
	function get_data($locale=NULL): array {
		if ($this->is_new()) return [];
		$idx = $locale ?? 'unloc';
		if (!isset($this->data_cache[$idx])) {
			$loc_clause = '';
			if ($locale) {
				$loc_tbl_name = $this->localized_table_name($locale);
				$loc_clause = "NATURAL JOIN \"$loc_tbl_name\"";
			}
			$sql = <<<SQL
			SELECT * FROM "{$this->table_name}" $loc_clause
				WHERE "{$this->id_name}" = :{$this->id_name};
SQL;
			$query = Util::sql_execute($sql, [$this->id_name => $this->id]);
			$this->data_cache[$idx] = $query->fetch();
		}
		return $this->data_cache[$idx];
	}

	function set_data(array $data, $locale=NULL): void {
		$this->update($data, $locale);
	}
	
	function get_attr(string $name, $locale=NULL) {
		$this->check_new();
		$data = $this->get_data($locale);
		return $data[$name];
	}

	function get_data_all(): array {
		return self::do_all_locales([$this, 'get_data']);
	}

	function set_data_all(array $data): void {
		self::do_all_locales([$this, 'set_data'], $data);
	}
	
	protected function check_new(): void {
		if ($this->is_new()) {
			throw new \BadMethodCallException('Invalid operation on new '
				. '(unassigned) record object (ID = -1)');
		}
	}
	
	protected function localized_table_name($locale=NULL): string {
		return Util::localized_table_name($this->table_name, $locale);
	}
	
	protected static function do_all_locales(callable $method,
		array $data=NULL): array {
		$locales = Localization::list_locales();
		$locales[] = NULL;
		$result = [];
		foreach ($locales as $locale) {
			if ($data === NULL) {
				$result[$locale ?? 'unloc'] = $method($locale);
			}
			else {
				$method($data[$locale ?? 'unloc'], $locale);
			}
		}
		return $result;
	}
}

