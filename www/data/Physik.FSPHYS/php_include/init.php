<?php
namespace de\uni_muenster\fsphys;
const LOG_EMAIL = 'fsphys@uni-muenster.de';
// all fatal PHP runtime errors (category “error”)
const E_CAT_ERROR = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR
	| E_USER_ERROR | E_RECOVERABLE_ERROR;
// all PHP warnings
const E_CAT_WARNING = E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING
	| E_USER_WARNING;

// install PSR-0-compatible class autoloader
// adapted from https://github.com/michelf/php-markdown
// https://github.com/michelf/php-markdown/blob/33e762c73de153918c3582d892d34fa332d2d0c9/Readme.php#L7-L10
spl_autoload_register(function(string $class): void {
	$re = <<<'RE'
{\\|_(?!.*\\)}
RE;
	$path_prefix = preg_replace($re, DIRECTORY_SEPARATOR, ltrim($class, '\\'));
	// can use require (instead of require_once) because this will only
	// be called if a class wasn’t found, which won’t happen again
	// after require is done
	require "$path_prefix.php";
});

// settings for error handling
define(__NAMESPACE__ . '\DEFAULT_ERR_MSG',
	Localization::get('DEFAULT_ERR_MSG'));

/*
	Convert a numerical error code to its name (e.g. 8 → E_NOTICE).
	https://secure.php.net/manual/en/errorfunc.constants.php
*/
function error_type_to_string(int $errno): string {
	static $types = NULL;
	if ($types === NULL) {
		$core_consts = get_defined_constants(true)['Core'];
		foreach ($core_consts as $key => $value) {
			if (strpos($key, 'E_') === 0) {
				$types[$value] = $key;
			}
		}
	}
	return $types[$errno] ?? "Error #$errno";
}

/*
	Run the passed function $func and catch any exception it throws. Caught
	exceptions are reported and an error message is output to HTML.
*/
function run_and_catch(callable $func, string $error_msg=DEFAULT_ERR_MSG,
	bool $log=true): void {
	$catch_ex = function(\Throwable $ex) use ($error_msg, $log): void {
		ob_end_clean();
		if ($log) {
			mail_and_log($ex);
		}
		echo $error_msg;
	};
	// use output buffering to avoid half-rendered pages
	ob_start();
	try {
		$func();
		ob_end_flush();
	}
	catch (\Throwable $t) {
		$catch_ex($t);
	}
}

/*
	Convert information from a “PHP error” to an ErrorException object.
*/
function error_to_exception($errno, $errstr, $errfile, $errline,
	$errcontext=NULL): \ErrorException {
	$errno_str = error_type_to_string($errno);
	$ex = new \ErrorException("$errno_str: $errstr", 0, $errno, $errfile,
		$errline);
	return $ex;
}

/*
	Send an email to LOG_EMAIL and write to the standard PHP error_log about
	$exception.
*/
function mail_and_log(\Throwable $exception): void {
	// send log message as email
	error_log($exception, 1, LOG_EMAIL);
	// log to default PHP error_log
	error_log($exception);
}

/*** Set error & exception handlers ***/
set_exception_handler(function(\Throwable $exception) {
	mail_and_log($exception);
});

// Note that “the following error types cannot be handled with a user-defined
// function: E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR,
// E_COMPILE_WARNING, and most of E_STRICT raised in the file where
// set_error_handler() is called”.
// https://secure.php.net/manual/en/function.set-error-handler.php
// Also see https://secure.php.net/manual/en/language.errors.php7.php for
// error handler vs. exception handler in PHP 7.
set_error_handler(function($errno, $errstr, $errfile, $errline) {
	// clear last error to avoid double-handling it in the shutdown function
	error_clear_last();
	// additional handling if error code is included in error_reporting
	if ($errno & error_reporting()) {
		$ex = error_to_exception($errno, $errstr, $errfile, $errline);
		error_log($ex, 1, LOG_EMAIL);
	}
	// also always defer to default handler
	return false;
});

// Try to detect if PHP is shutting down because of an error which is not
// caught by set_error_handler()
register_shutdown_function(function() {
	$error = error_get_last();
	if ( $error && ($error['type'] & (E_CAT_ERROR | E_CAT_WARNING)) ) {
		$ex = error_to_exception($error['type'], $error['message'],
			$error['file'], $error['line']);
		mail_and_log($ex);
	}
});

