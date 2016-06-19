<?php
require_once __DIR__ . '/php_settings.inc';

function mysql_db_connect() {
	$db_server = 'mysql5.uni-muenster.de';
	$db_name = '*****';
	$db_user = '*****';
	$db_password = '*****';
	try {
		$db = new PDO("mysql:host=$db_server;dbname=$db_name;charset=utf8",
			$db_user, $db_password);
	}
	catch (PDOException $ex) {
		throw new RuntimeException('Could not connect to the database!');
	}
	// see https://dev.mysql.com/doc/refman/en/sql-mode.html for information
	// on MySQL modes
	$db->query("SET sql_mode = 'ANSI,TRADITIONAL';");
	return $db;
}

function mysql_db_close(&$db) {
	$db = null;
}

?>
