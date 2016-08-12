<?php
namespace de\uni_muenster\fsphys;
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

$LOC_STRINGS = [
	'de_DE' => [
		'date' => 'Datum',
		'day_of_week' => 'Wochentag',
		'edit' => 'bearbeiten',
		'end time' => 'End&shy;zeit',
		'from' => 'ab',
		'name' => 'Name',
		'new entry' => 'neuer Eintrag',
		'office hours' => 'Präsenzplan',
		'office hours (break)' => 'Ferienpräsenzplan',
		'save show setting' => 'Anzeigeeinstellung speichern',
		'show' => 'zeigen',
		'start time' => 'Start&shy;zeit',
		'time' => 'Zeit',
		'to' => 'bis',
	],
	'en_US' => [
		'date' => 'date',
		'day_of_week' => 'day of week',
		'edit' => 'edit',
		'end time' => 'end time',
		'from' => 'from',
		'name' => 'name',
		'new entry' => 'new entry',
		'office hours' => 'office hours',
		'office hours (break)' => 'office hours (break)',
		'save show setting' => 'save show setting',
		'show' => 'show',
		'start time' => 'start time',
		'time' => 'time',
		'to' => 'to',
	],
];
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
	global $LOC_STRINGS;
	$str = $LOC_STRINGS[$locale][$key];
	return $capitalize ? mb_ucfirst($str) : $str;
}

