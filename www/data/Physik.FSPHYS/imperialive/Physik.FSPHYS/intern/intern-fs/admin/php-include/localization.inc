<?php
require_once __DIR__ . '/php_settings.inc';

/*
	Scripts including this file can define a constant LOCALE which determines
	the locale used by default for the localization functions in this file. If
	LOCALE is not set, the default is 'de_DE'.
*/
const LOCALE_DEFAULT = 'de_DE';
if (!defined('LOCALE')) {
	const LOCALE = LOCALE_DEFAULT;
}

$LOC_STRINGS = array(
	'de_DE' => array(
		'from' => 'ab',
		'time' => 'Zeit',
	),
	'en_US' => array(
		'from' => 'from',
		'time' => 'time',
	),
);
$LOC_STRINGS['de'] = &$LOC_STRINGS['de_DE'];
$LOC_STRINGS['en'] = &$LOC_STRINGS['en_US'];

/*
	Makes a string’s first character uppercase.
	Like PHP’s ucfirst(), but for multibyte strings.
*/
function mb_ucfirst($str) {
	$first_char = mb_strtoupper(mb_substr($str, 0, 1));
	return $first_char . mb_substr($str, 1);
}

function loc_get_str($key, $capitalize=false, $locale=LOCALE) {
	$str = $LOC_STRINGS[$locale][$key];
	return $capitalize ? mb_ucfirst($str) : $str;
}

?>
