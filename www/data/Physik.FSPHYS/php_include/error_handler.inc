<?php
namespace de\uni_muenster\fsphys;
require_once 'localization.inc';

const LOG_EMAIL = 'simon.may@uni-muenster.de';
// all fatal PHP runtime errors (category “error”)
const E_CAT_ERROR = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR
	| E_USER_ERROR | E_RECOVERABLE_ERROR;
// all PHP warnings
const E_CAT_WARNING = E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING
	| E_USER_WARNING;
define('DEFAULT_ERR_MSG', loc_get_str('DEFAULT_ERR_MSG'));

/*
	Convert a numerical error code to its name (e.g. 8 → E_NOTICE).
	https://secure.php.net/manual/en/errorfunc.constants.php
*/
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
	return isset($types[$errno]) ? $types[$errno] : "Error #$errno";
}

/*
	Run the passed function $func and catch any exception it throws. Caught
	exceptions are reported and an error message is output to HTML.
*/
function run_and_catch($func, $error_msg=DEFAULT_ERR_MSG) {
	$catch_ex = function($ex) {
		ob_end_clean();
		mail_and_log($ex);
		echo DEFAULT_ERR_MSG;
	};
	// use output buffering to avoid half-rendered pages
	ob_start();
	try {
		$func();
		ob_end_flush();
	}
	// PHP7: Exception and Error both inherit from Throwable
	// https://secure.php.net/manual/en/language.errors.php7.php
	catch (\Throwable $t) {
		$catch_ex($t);
	}
	// PHP5: Throwable does not exist, Exception is the most basic type
	catch (\Exception $ex) {
		$catch_ex($ex);
	}
}

/*
	Convert information from a “PHP error” to an ErrorException object.
*/
function error_to_exception($errno, $errstr, $errfile, $errline,
	$errcontext=NULL) {
	$errno_str = error_type_to_string($errno);
	$ex = new \ErrorException("$errno_str: $errstr", 0, $errno, $errfile,
		$errline);
	return $ex;
}

/*
	Send an email to LOG_EMAIL and write to the standard PHP error_log about
	$exception.
*/
function mail_and_log(\Throwable $exception) {
	// send log message as email
	error_log($exception, 1, LOG_EMAIL);
	// log to default PHP error_log
	error_log($exception);
}

set_exception_handler(function(\Throwable $exception) {
	mail_and_log($exception);
});

// Note that “the following error types cannot be handled with a user-defined
// function: E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR,
// E_COMPILE_WARNING, and most of E_STRICT raised in the file where
// set_error_handler() is called”.
// https://secure.php.net/manual/en/function.set-error-handler.php
set_error_handler(function($errno, $errstr, $errfile, $errline) {
	// clear last error to avoid double-handling it in the shutdown function
	error_clear_last();
	if (!($errno & error_reporting())) {
		// error code is not included in error_reporting, defer to default
		return false;
	}
	$ex = error_to_exception($errno, $errstr, $errfile, $errline);
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

/*
	Try to detect if PHP is shutting down because of an error which is not
	caught by set_error_handler().
*/
register_shutdown_function(function() {
	$error = error_get_last();
	if ($error['type'] & (E_CAT_ERROR | E_CAT_WARNING)) {
		$ex = error_to_exception($error['type'], $error['message'],
			$error['file'], $error['line']);
		mail_and_log($ex);
	}
});

