<?php
namespace de\uni_muenster\fsphys;
require_once 'error_handler.inc';
require_once 'db_access.inc';

const SETTINGS_TYPES = ['int', 'str'];

function get_setting($key, $db=NULL, $type='str') {
	if (!in_array($type, SETTINGS_TYPES)) {
		throw new \DomainException("Value of \$type is not valid: $type");
	}
	$tbl_name = "settings__$type";
	$sql = "SELECT value FROM $tbl_name WHERE \"key\" = :key;";
	$opened_db = mysql_db_connect_if_null($db);
	$query = $db->prepare($sql);
	$query->bindValue(':key', $key);
	$query->execute();
	$result = $query->fetch();
	if ($opened_db) {
		mysql_db_close($db);
	}
	if ($result === false) {
		throw new fsphys\DBException("Retrieving value for key $key from "
			. "database table $tbl_name failed");
	}
	return $result[0];
}

