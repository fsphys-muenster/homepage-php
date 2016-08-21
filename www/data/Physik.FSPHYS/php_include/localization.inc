<?php
namespace de\uni_muenster\fsphys;
require_once 'error_handler.inc';
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
		'DEFAULT_ERR_MSG' => '<p class="module fsphys_error">'
		. 'Leider ist bei der Erzeugung dieses Teils der Seite ein Fehler '
		. 'aufgetreten. Das kann z.&nbsp;B. daran liegen, dass die benötigte '
		. 'Datenbank zur Zeit nicht verfügbar ist. Versuch’ es am besten '
		. 'später noch einmal oder schreib’ uns eine Nachricht, falls es '
		. 'dringend ist.'
		. '</p>',
		'current office hours' => 'aktuelle Präsenzzeiten',
		'complete office hours schedule' => 'vollständiger Präsenzplan',
		'date' => 'Datum',
		'day_of_week' => 'Wochentag',
		'delete' => 'löschen',
		'delete_confirmation_dialog' => 'Eintrag wirklich löschen?',
		'during the semester' => 'Vorlesungszeit',
		'edit' => 'bearbeiten',
		'end time' => 'End&shy;zeit',
		'enter' => 'eintragen',
		'from' => 'ab',
		'name' => 'Name',
		'new entry' => 'neuer Eintrag',
		'office hours' => 'Präsenzplan',
		'office hours (break)' => 'Ferienpräsenzplan',
		'save show setting' => 'Anzeigeeinstellung speichern',
		'semester break' => 'vorlesungsfreie Zeit',
		'show' => 'zeigen',
		'start time' => 'Start&shy;zeit',
		'time' => 'Zeit',
		'to' => 'bis',
	],
	'en_US' => [
		'DEFAULT_ERR_MSG' => '<p class="fsphys_error">'
		. 'Unfortunately, an error occured while generating this part of the '
		. 'page. One possible reason for this could be that the required '
		. 'database is currently unavailable. Please try again later or send '
		. 'us a message if it’s urgent.'
		. '</p>',
		'current office hours' => 'current office hours',
		'complete office hours schedule' => 'complete office hours schedule',
		'date' => 'date',
		'day_of_week' => 'day of week',
		'delete' => 'delete',
		'delete_confirmation_dialog' => 'Really delete the entry?',
		'during the semester' => 'during the semester',
		'edit' => 'edit',
		'end time' => 'end time',
		'enter' => 'enter',
		'from' => 'from',
		'name' => 'name',
		'new entry' => 'new entry',
		'office hours' => 'office hours',
		'office hours (break)' => 'office hours (break)',
		'save show setting' => 'save show setting',
		'semester break' => 'semester break',
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

function loc_url_lang_code() {
	return (strpos(LOCALE, 'de') === 0 ? '' : substr(LOCALE, 0, 2)) . '/';
}

