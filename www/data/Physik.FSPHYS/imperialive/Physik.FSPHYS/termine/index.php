<?php
use de\uni_muenster\fsphys;
require_once 'error_handler.inc';
require_once 'db_access.inc';
require_once 'office_hours.inc';

$db = NULL;
fsphys\run_and_catch(function() {
	global $db;
	$db = fsphys\mysql_db_connect();
	echo fsphys\office_hours_html($db);
});
?>

<?php
/*
	Imperia modules…
*/
?>

<?php
fsphys\run_and_catch(function() {
	global $db;
	echo fsphys\office_hours_break_html($db);
	fsphys\mysql_db_close($db);
});
?>

