<?php
namespace de\uni_muenster\fsphys;

// install PSR-0-compatible class autoloader
// adapted from https://github.com/michelf/php-markdown
// https://github.com/michelf/php-markdown/blob/33e762c73de153918c3582d892d34fa332d2d0c9/Readme.php#L7-L10
spl_autoload_register(function(string $class): void {
	$re = <<<'RE'
{\\|_(?!.*\\)}
RE;
	$path_prefix = preg_replace($re, DIRECTORY_SEPARATOR, ltrim($class, '\\'));
	if (stream_resolve_include_path("$path_prefix.php") !== FALSE) {
		// can use require (instead of require_once) because this will only
		// be called if a class wasn’t found, which won’t happen again
		// after require is done
		require "$path_prefix.php";
	}
	else {
		require "$path_prefix.inc";
	}
});

// ensure that error_handler settings are executed
require_once 'error_handler.inc';

