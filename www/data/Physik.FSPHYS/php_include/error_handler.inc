<?php
namespace de\uni_muenster\fsphys;

const LOG_EMAIL = 'simon.may@uni-muenster.de';
// all fatal PHP runtime errors (category “error”)
const E_CAT_ERROR = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR
	| E_USER_ERROR | E_RECOVERABLE_ERROR;
// all PHP warnings
const E_CAT_WARNING = E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING
	| E_USER_WARNING;

function error_type_to_string($errno) {
	static $types = NULL;
	if ($types === NULL) {
		$core_consts = get_defined_constants(true)['Core'];
		foreach ($core_consts as $key => $value) {
			if (strpos($key, 'E_') === 0) {
				$types[$value] = $key;
			}
		}
	}
	return isset($types[$errno]) ? $types[$errno] : "Error #{$errno}";
}

set_exception_handler(function($ex) {
	// send log message as email
	error_log($ex, 1, LOG_EMAIL);
	// log to default PHP error_log
	error_log($ex);
});

// Note that “the following error types cannot be handled with a user-defined
// function: E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR,
// E_COMPILE_WARNING, and most of E_STRICT raised in the file where
// set_error_handler() is called”.
// https://secure.php.net/manual/en/function.set-error-handler.php
set_error_handler(function($errno, $errstr, $errfile, $errline) {
	if (!($errno & error_reporting())) {
		// error code is not included in error_reporting, defer to default
		return false;
	}
	$errno_str = error_type_to_string($errno);
	$ex = new \ErrorException("$errno_str: $errstr", 0, $errno, $errfile,
		$errline);
	// throw fatal errors as ErrorExceptions
	if ($errno & E_CAT_ERROR) {
		throw $ex;
	}
	// send email for warnings
	elseif ($errno & E_CAT_WARNING) {
		error_log($ex, 1, LOG_EMAIL);
	}
	// defer to default handler for warnings and other errors
	return false;
});
