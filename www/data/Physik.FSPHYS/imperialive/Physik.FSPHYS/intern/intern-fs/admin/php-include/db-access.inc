<?php

function mysql_db_connect() {
	$db_server = 'mysql5.uni-muenster.de';
	$db_name = '*****';
	$db_user = '*****';
	$db_password = '*****';
	try {
		$db = new PDO("mysql:host=$db_server;dbname=$db_name;charset=utf8", $db_user, $db_password);
	} catch (PDOException $ex) {
		throw new RuntimeException('Konnte keine Verbindung zur Datenbank herstellen!');
	}
	return $db;
}

function mysql_db_close(&$db) {
	$db = null;
}

?>

