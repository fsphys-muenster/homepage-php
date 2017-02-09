<?php
namespace de\uni_muenster\fsphys;

// install PSR-0-compatible class autoloader
// adapted from https://github.com/michelf/php-markdown
// https://github.com/michelf/php-markdown/blob/33e762c73de153918c3582d892d34fa332d2d0c9/Readme.php#L7-L10
(function() {
	function autoload_func(string $extension) {
		return function(string $class) use ($extension): void {
			$re = <<<'RE'
{\\|_(?!.*\\)}
RE;
			$path_prefix = preg_replace($re, DIRECTORY_SEPARATOR,
				ltrim($class, '\\')
			);
			// can use require (instead of require_once) because this will only
			// be called if a class wasn’t found, which won’t happen again
			// after require is done
			require $path . $extension;
		}
	}
	spl_autoload_register(autoload_func('.php'));
	spl_autoload_register(autoload_func('.inc'));
})();

// ensure that error_handler settings are executed
require_once 'error_handler.inc';

