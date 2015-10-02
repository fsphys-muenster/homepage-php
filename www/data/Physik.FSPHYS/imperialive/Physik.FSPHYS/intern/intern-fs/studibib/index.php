<?php
include '/www/data/Physik.FSPHYS/imperialive/Physik.FSPHYS/intern/intern-fs/admin/php-include/db-access.inc';
include '/www/data/Physik.FSPHYS/imperialive/Physik.FSPHYS/intern/intern-fs/admin/php-include/functions.inc';

if($_GET['addbuch'] == true){
	buch_hinzufuegen($_POST['invnr'],
	$_POST['autoren'],
	$_POST['titel'],
	$_POST['standort'],
	$_POST['signatur'],
	$_POST['auflage'],
	$_POST['themenfeld'],
	$_POST['zustand'],
	$_POST['erscheinungsjahr'],
	$_POST['bn']);
}
else if($_GET['buch'] == true){
	maske_buch_hinzufuegen();
}
?>

<br />
<center>
	<a href="/Physik.FSPHYS/intern/intern-fs/studibib/index.php?buch=true">Bücher für StudiBib eintragen.</a>
</center>

