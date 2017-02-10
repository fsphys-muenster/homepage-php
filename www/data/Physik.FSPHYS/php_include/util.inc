<?php
namespace de\uni_muenster\fsphys;
require_once 'init.inc';

function sql_format_insert(array $data): array {
	$column_names = array_keys($data);
	$column_spec = implode(', ', array_map(function($column_name) {
		return "\"$column_name\"";
	}, $column_names));
	$value_spec = implode(', ', array_map(function($column_name) {
		return ":$column_name";
	}, $column_names));
	return [$column_spec, $value_spec];
}

function sql_format_update(array $data): string {
	$column_names = array_keys($data);
	$update_spec = implode(', ', array_map(function($column_name) {
		return "\"$column_name\" = :$column_name";
	}, $column_names));
	return $update_spec;
}

function sql_execute(string $sql, array $data=[],
	callable $check_col_name=NULL): \PDOStatement {
	$query = DB::prepare($sql);
	foreach ($data as $column_name => $value) {
		if ($check_col_name) {
			$check_col_name($column_name);
		}
		$query->bindValue(":$column_name", $value);
	}
	$query->execute();
	return $query;
}

/*
	Returns true if all $keys are set (i.e. non-null) in the array, false
	otherwise.
*/
function keys_set(array $arr, ...$keys) {
	foreach ($keys as $key) {
		if (!isset($arr[$key])) {
			return false;
		}
	}
	return true;
}

/*
	Makes a string’s first character uppercase.
	Like PHP’s ucfirst(), but for multibyte strings.
*/
function mb_ucfirst(string $str) {
	$first_char = mb_strtoupper(mb_substr($str, 0, 1));
	return $first_char . mb_substr($str, 1);
}

function starts_with(string $haystack, string $needle): bool {
	return strpos($haystack, $needle) === 0;
}

function htmlspecialchars_if_str(): callable {
	return function($value) {
		return is_string($value) ? htmlspecialchars($value,
			ENT_QUOTES | ENT_HTML5) : $value;
	};
}

function this_page_url_path() {
	$url = parse_url($_SERVER['REQUEST_URI']);
	return $url['path'];
}

// XXX
/*
	If not $db: create a new database connection (PDO) in $db.
	Return true if a connection was created, false otherwise.
*/
#function mysql_db_connect_if_null(&$db) {
#	$open_db = !$db;
#	if ($open_db) {
#		$db = mysql_db_connect();
#	}
#	return $open_db;
#}

