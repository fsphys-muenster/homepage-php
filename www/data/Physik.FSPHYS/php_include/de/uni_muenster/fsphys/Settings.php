<?php
namespace de\uni_muenster\fsphys;
require_once 'init.php';

class Settings {
	const TYPES = ['int', 'str'];
	private static $cache = [];

	static function get(string $key, $type='str') {
		if (!in_array($type, self::TYPES)) {
			throw new \DomainException("Value of \$type is not valid: $type");
		}
		if (!isset(self::$cache[$type][$key])) {
			$tbl_name = "settings__$type";
			$sql = <<<SQL
			SELECT "value" FROM $tbl_name WHERE "key" = :key;
SQL;
			$query = Util::sql_execute($sql, ['key' => $key]);
			$result = $query->fetch();
			if (!$result) {
				throw new \UnexpectedValueException('Database returned no '
					. "values in table $tbl_name for key $key");
			}
			self::$cache[$type][$key] = $result[0];
		}
		return self::$cache[$type][$key];
	}

	static function set(string $key, $value, $type='str') {
		if (!in_array($type, self::TYPES)) {
			throw new \DomainException("Value of \$type is not valid: $type");
		}
		$tbl_name = "settings__$type";
		$sql = <<<SQL
		INSERT INTO $tbl_name ("key", "value")
			VALUES (:key, :value)
			ON DUPLICATE KEY UPDATE "value" = :value;
SQL;
		Util::sql_execute($sql, ['key' => $key, 'value' => $value]);
		$cache[$type][$key] = $value;
	}
}

