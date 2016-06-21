<?php

const INCLUDE_PATH = __DIR__;
const LOG_PATH = '/www/data/Physik.FSPHYS/php_log/php.log';

mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');

// information on php.ini settings/ini_set():
// https://secure.php.net/manual/en/ini.core.php
// https://secure.php.net/manual/en/errorfunc.configuration.php
ini_set('display_errors', false);
ini_set('html_errors', false);
ini_set('log_errors', true);
ini_set('error_log', LOG_PATH);
error_reporting(E_ALL);

set_include_path(INCLUDE_PATH);
ini_set('expose_php', false);

?>
