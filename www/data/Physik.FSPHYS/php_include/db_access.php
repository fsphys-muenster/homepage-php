<?php
namespace de\uni_muenster\fsphys;
require_once 'init.php';

/*
	Connects to the MariaDB database at 127.0.0.1 returning a PDO object.

	Information on PDO:
	https://secure.php.net/manual/en/class.pdo.php
*/
function mysql_db_connect() {
	$db_server = '127.0.0.1';
	$db_port = 3306;
	$db_name = '*****';
	$db_user = '*****';
	$db_password = '*****';
	$db = new \PDO(
		"mysql:host=$db_server;port=$db_port;dbname=$db_name;charset=utf8",
		$db_user, $db_password
	);
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

