<?php
namespace de\uni_muenster\fsphys;
require_once 'init.php';

/*
	Connects to the MariaDB database at 127.0.0.1 returning a PDO object.

	Information on PDO:
	https://secure.php.net/manual/en/class.pdo.php
*/
function mysql_db_connect() {
	$ini_path = '/www/data/Physik.FSPHYS/db_access.ini';
	$dsn = parse_ini_file($ini_path);
	if ($dsn === false) {
		throw new \UnexpectedValueException('Could not open/parse .ini file '
			. $ini_path);
	}
	$db = new \PDO(
		"mysql:host={$dsn['db_server']};" .
		"port={$dsn['db_port']};" .
		"dbname={$dsn['db_name']};charset=utf8",
		$dsn['db_user'], $dsn['db_password']
	);
	unset($dsn);
	// throw exceptions on PDO errors instead of silently returning false
	// or similar
	$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	// see https://mariadb.com/kb/en/library/sql-mode/ for information on
	// MariaDB modes
	$db->query("SET sql_mode = 'ANSI,TRADITIONAL';");
	return $db;
}

function mysql_db_close(\PDO &$db) {
	$db = NULL;
}

