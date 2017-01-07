<?php
namespace de\uni_muenster\fsphys;
require_once 'error_handler.inc';

/*
	Connects to the MySQL database at mysql5.uni-muenster.de returning a PDO
	object.
	
	Information on PDO:
	https://secure.php.net/manual/en/class.pdo.php
*/
function mysql_db_connect() {
	$db_server = 'mysql5.uni-muenster.de';
	$db_name = '*****';
	$db_user = '*****';
	$db_password = '*****';
	$db = new \PDO("mysql:host=$db_server;dbname=$db_name;charset=utf8",
		$db_user, $db_password);
	// throw exceptions on PDO errors instead of silently returning false
	// or similar
	$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
	// see https://dev.mysql.com/doc/refman/en/sql-mode.html for information
	// on MySQL modes
	$db->query("SET sql_mode = 'ANSI,TRADITIONAL';");
	return $db;
}

/*
	If not $db: create a new database connection (PDO) in $db.
	Return true if a connection was created, false otherwise.
*/
function mysql_db_connect_if_null(&$db) {
	$open_db = !$db;
	if ($open_db) {
		$db = mysql_db_connect();
	}
	return $open_db;
}

function mysql_db_close(&$db) {
	$db = NULL;
}

