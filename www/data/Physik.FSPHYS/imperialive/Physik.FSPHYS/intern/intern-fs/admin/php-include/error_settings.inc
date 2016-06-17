<?php

const LOG_PATH = '/www/data/Physik.FSPHYS/php_log/php.log';

ini_set('display_errors', false);
ini_set('html_errors', false);
ini_set('log_errors', true);
ini_set('error_log', LOG_PATH);
error_reporting(E_ALL);

?>
