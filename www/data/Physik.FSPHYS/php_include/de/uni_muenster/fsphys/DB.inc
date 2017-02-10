<?php
namespace de\uni_muenster\fsphys;
require_once 'init.inc';
require_once 'db_access.inc';

class DB {
	private static $db_instance;

	static function get_instance() {
		if(!self::$db_instance){
			self::$db_instance = mysql_db_connect();
		}
		return self::$db_instance;
	}

	static function close() {
		if(!self::$db_instance){
			mysql_db_close(self::$db_instance);
		}
		self::$db_instance = NULL;
	}

	static function __callStatic($name, $arguments) {
		$db_instance = self::get_instance();
		return $db_instance->$name(...$arguments);
	}
}

