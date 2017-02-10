<?php
namespace de\uni_muenster\fsphys;
require_once 'init.inc';
require_once 'settings.inc';
require_once 'util.inc';
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

function loc_get_str(string $key, bool $capitalize=false, $locale=LOCALE) {
	$tbl_name = 'localization__' . loc_lang_code($locale);
	$sql = <<<SQL
	SELECT "value" FROM "$tbl_name" WHERE "key" = :key;
SQL;
	$query = DB::prepare($sql);
	$query->bindValue(':key', $key);
	$query->execute();
	$result = $query->fetch();
	if (!$result) {
		throw new \UnexpectedValueException('Database returned no values in '
			. "table “{$tbl_name}” for key “{$key}”");
	}
	return $capitalize ? mb_ucfirst($result[0]) : $result[0];
}

function loc_get_locales() {
	static $locales_str = get_setting('locales');
	return explode(',', $locales_str);
}

function loc_lang_code($locale=LOCALE) {
	return substr($locale, 0, 2);
}

function loc_url_lang_code($locale=LOCALE) {
	return (strpos($locale, 'de') === 0 ? '' : loc_lang_code($locale)) . '/';
}

