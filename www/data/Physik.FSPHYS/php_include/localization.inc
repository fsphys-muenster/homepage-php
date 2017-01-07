<?php
namespace de\uni_muenster\fsphys;
require_once 'error_handler.inc';
require_once 'db_access.inc';
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

/*
	Makes a string’s first character uppercase.
	Like PHP’s ucfirst(), but for multibyte strings.
*/
function mb_ucfirst($str) {
	$first_char = mb_strtoupper(mb_substr($str, 0, 1));
	return $first_char . mb_substr($str, 1);
}

function loc_get_str($key, $capitalize=false, $locale=LOCALE) {
	if (!in_array($type, SETTINGS_TYPES)) {
		throw new \DomainException("Value of \$type is not valid: $type");
	}
	$tbl_name = 'localization__' . loc_lang_code();
	$sql = <<<SQL
	SELECT "value" FROM $tbl_name WHERE "key" = :key;
SQL;
	$opened_db = mysql_db_connect_if_null($db);
	$query = $db->prepare($sql);
	$query->bindValue(':key', $key);
	$query->execute();
	$result = $query->fetch();
	if ($opened_db) {
		mysql_db_close($db);
	}
	return $capitalize ? mb_ucfirst($result[0]) : $result[0];
}

function loc_lang_code($locale=LOCALE) {
	return substr($locale, 0, 2);
}

function loc_url_lang_code($locale=LOCALE) {
	return (strpos($locale, 'de') === 0 ? '' : loc_lang_code($locale)) . '/';
}

