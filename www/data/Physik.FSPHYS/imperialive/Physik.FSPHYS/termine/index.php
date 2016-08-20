<?php
use de\uni_muenster\fsphys;
require_once 'error_handler.inc';
require_once 'db_access.inc';
require_once 'office_hours.inc';

$db = fsphys\mysql_db_connect();

echo fsphys\office_hours_html($db);
?>

<?php
/*
	Imperia modules…
*/
?>

<?php
echo fsphys\office_hours_break_html($db);

fsphys\mysql_db_close($db);
?>

