<?php
namespace de\uni_muenster\fsphys;
require_once 'init.php';
/*
	Scripts including this file can define a constant LOCALE which determines
	the locale used by default for the localization functions in this file. If
	LOCALE is not set, the default is determined from the file path.
*/

// define() has to be used because const can’t be used in a conditional
// expression (outermost scope only), see
// https://stackoverflow.com/q/2447791/595306
if (!defined('LOCALE')) {
	// can be extended to include other languages, e.g.
	// strpos($_SERVER['PHP_SELF'], '/Physik.FSPHYS/fr/') === 0
	// for French
	if (strpos($_SERVER['PHP_SELF'], '/Physik.FSPHYS/en/') === 0) {
		define('LOCALE', 'en_US');
	}
	// there is no language code in German URLs
	else {
		define('LOCALE', 'de_DE');
	}
}

class Localization {
	private static $cache = [];

	static function get(string $key, bool $capitalize=false, $locale=LOCALE) {
		if (!isset(self::$cache[$locale][$key])) {
			$tbl_name = Util::localized_table_name('localization', $locale);
			$sql = <<<SQL
			SELECT "value" FROM "$tbl_name" WHERE "key" = :key;
SQL;
			$query = Util::sql_execute($sql, ['key' => $key]);
			$result = $query->fetch();
			if (!$result) {
				throw new \UnexpectedValueException('Database returned no '
					. "values in table “{$tbl_name}” for key “{$key}”");
			}
			self::$cache[$locale][$key] = $result[0];
		}
		$value = self::$cache[$locale][$key];
		return $capitalize ? Util::mb_ucfirst($value) : $value;
	}

	static function list_locales(): array {
		static $locales_str = NULL;
		if ($locales_str === NULL) $locales_str = Settings::get('locales');
		return explode(',', $locales_str);
	}

	static function lang_code($locale=LOCALE): string {
		return substr($locale, 0, 2);
	}

	static function url_lang_code($locale=LOCALE): string {
		return (strpos($locale, 'de') === 0 ? '' : self::lang_code($locale))
			. '/';
	}
}

