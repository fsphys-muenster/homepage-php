<?php
namespace de\uni_muenster\fsphys;
require_once 'init.php';

class Util {
	static function localized_table_name(string $table, $locale=NULL): string {
		if ($locale) {
			return $table . '__' . Localization::lang_code($locale);
		}
		return $table;
	}

	static function sql_format_insert(array $data): array {
		$column_names = array_keys($data);
		$column_spec = implode(', ', array_map(function($column_name) {
			return "\"$column_name\"";
		}, $column_names));
		$value_spec = implode(', ', array_map(function($column_name) {
			return ":$column_name";
		}, $column_names));
		return [$column_spec, $value_spec];
	}

	static function sql_format_update(array $data): string {
		$column_names = array_keys($data);
		$update_spec = implode(', ', array_map(function($column_name) {
			return "\"$column_name\" = :$column_name";
		}, $column_names));
		return $update_spec;
	}

	static function sql_execute(string $sql, array $data=[],
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
	static function keys_set_all(array $arr, ...$keys): bool {
		foreach ($keys as $key) {
			if (!isset($arr[$key])) {
				return false;
			}
		}
		return true;
	}

	/*
		Returns true if any of the $keys are set (i.e. non-null) in the array,
		false otherwise.
	*/
	static function keys_set_any(array $arr, ...$keys): bool {
		foreach ($keys as $key) {
			if (isset($arr[$key])) {
				return true;
			}
		}
		return false;
	}

	/*
		Makes a string’s first character uppercase.
		Like PHP’s ucfirst(), but for multibyte strings.
	*/
	static function mb_ucfirst(string $str): string {
		$first_char = mb_strtoupper(mb_substr($str, 0, 1));
		return $first_char . mb_substr($str, 1);
	}
	
	static function string_to_array(string $str): array {
		return preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
	}

	static function starts_with(string $haystack, string $needle): bool {
		return strpos($haystack, $needle) === 0;
	}

	static function this_page_url_path(): string {
		return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	}

	static function htmlspecialchars($data, bool $double_encode=true) {
		// We could use filter_var_array:
		//   filter_var_array($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS)
		// but with that, there is no way to set double_encode to true (or
		// ENT_HTML5).
		// (This is undocumented/incorrectly documented, but the function call
		// above acts like htmlspecialchars() with double_encode=false.)
		// https://secure.php.net/manual/en/filter.filters.sanitize.php
		// https://secure.php.net/manual/en/function.htmlspecialchars.php
		$htmlspecialchars_if_str = function($val) use ($double_encode) {
			$flags = ENT_QUOTES | ENT_HTML5;
			$coding = ini_get('default_charset');
			return is_string($val) ?
				htmlspecialchars($val, $flags, $coding, $double_encode) : $val;
		};
		if (is_array($data)) {
			return array_map($htmlspecialchars_if_str, $data);
		}
		else {
			return $htmlspecialchars_if_str($data);
		}
	}

	static function filter_input_array(int $type, $definition=FILTER_DEFAULT,
		bool $add_empty=true): array {
		$result = filter_input_array($type, $definition, $add_empty);
		if ($result === NULL) {
			self::filter_input_array_workaround($result, $definition,
				$add_empty, false);
		}
		return $result;
	}

	static function filter_input_array_defaults(int $type,
		$definition=FILTER_DEFAULT, bool $add_empty=true): array {
		$result = filter_input_array($type, $definition, $add_empty);
		if ($add_empty && $result) {
			self::filter_set_defaults($result, $definition);
		}
		elseif ($result === NULL) {
			self::filter_input_array_workaround($result, $definition,
				$add_empty, true);
		}
		return $result;
	}

	static function filter_var_array_defaults(array $data,
		$definition=FILTER_DEFAULT, bool $add_empty=true): array {
		$result = filter_var_array($data, $definition, $add_empty);
		if ($add_empty && $result) {
			self::filter_set_defaults($result, $definition);
		}
		return $result;
	}

	private static function filter_set_defaults(array &$data, &$definition):
		void {
		foreach ($data as $key => $value) {
			$default = $definition[$key]['options']['default'] ?? NULL;
			if (!isset($value) && isset($default)) {
				$data[$key] = $default;
			}
		}
	}

	/*
	   workaround for bug: filter_input_array returns NULL if the specified
	   array is empty
	   https://secure.php.net/manual/en/function.filter-input-array.php#97491
	*/
	private static function filter_input_array_workaround(?array &$result,
		&$definition, bool $add_empty, bool $defaults): void {
		if ($add_empty && is_array($definition)) {
			foreach ($definition as $key => $value) {
				$result[$key] = $defaults ?
					($value['options']['default'] ?? NULL) : NULL;
			}
		}
		else {
			$result = [];
		}
	}
}

