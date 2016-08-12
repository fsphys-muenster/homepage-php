# homepage-php
The code used for the pages written in PHP on https://www.uni-muenster.de/Physik.FSPHYS/.

## Execution
In order for the included files to be found, PHP’s `include_path` .ini setting
must include the path `/www/data/Physik.FSPHYS/php_include`. This is set by the
`.user.ini` file in `/www/data/Physik.FSPHYS/imperialive/Physik.FSPHYS/` and
so will be in effect for all scripts provided that evaluation of `.user.ini`
files is enabled.

## Note on localization
Localization is defined in `localization.inc`, which detects the correct locale
to use from the location of the executed script on the file system.
Alternatively, a script can define the constant `LOCALE` (e.g.
`const LOCALE = 'de_DE'`) *before* including `localization.inc` and set the
default locale used by `localization.inc` in this way.

