<?php
namespace de\uni_muenster\fsphys;
require_once 'init.inc';
require_once 'util.inc';

const SETTINGS_TYPES = ['int', 'str'];

function get_setting(string $key, $type='str') {
	if (!in_array($type, SETTINGS_TYPES)) {
		throw new \DomainException("Value of \$type is not valid: $type");
	}
	$tbl_name = "settings__$type";
	$sql = <<<SQL
	SELECT "value" FROM $tbl_name WHERE "key" = :key;
SQL;
	$query = DB::prepare($sql);
	$query->bindValue(':key', $key);
	$query->execute();
	$result = $query->fetch();
	if (!$result) {
		throw new \UnexpectedValueException('Database returned no values in '
			. "table $tbl_name for key $key");
	}
	return $result[0];
}

function set_setting(string $key, $value, $type='str') {
	if (!in_array($type, SETTINGS_TYPES)) {
		throw new \DomainException("Value of \$type is not valid: $type");
	}
	$tbl_name = "settings__$type";
	$sql = <<<SQL
	INSERT INTO $tbl_name ("key", "value")
		VALUES (:key, :value)
		ON DUPLICATE KEY UPDATE "value" = :value;
SQL;
	$query = DB::prepare($sql);
	$query->bindValue(':key', $key);
	$query->bindValue(':value', $value);
	$query->execute();
}

