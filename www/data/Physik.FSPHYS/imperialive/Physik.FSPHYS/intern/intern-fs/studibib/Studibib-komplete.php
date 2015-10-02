<?php
include '/www/data/Physik.FSPHYS/imperialive/Physik.FSPHYS/intern/intern-fs/admin/php-include/db-access.inc';
include '/www/data/Physik.FSPHYS/imperialive/Physik.FSPHYS/intern/intern-fs/admin/php-include/functions.inc';
?>

	<?php
	echo "
	<table width=\"185\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">
	<colgroup><col width=25><col width=\"*\"></colgroup>";
	echo "</table>";
	?>
	
	<td class="E" align="left" valign="top">

	<div class="typotext">
	<?php
			$db = @MYSQL_CONNECT($db_server,$db_user,$db_passwort) or die ("Konnte keine Verbindung zur Datenbank herstellen");
			
			if($_GET['sort']){
				$sort = "ORDER BY " . $_GET['sort'] . " ASC";
			}else{
				$sort = "ORDER BY autoren ASC";
			}
			
			if($_GET['where_titel']){
				$where = "WHERE `titel` LIKE \"%$_GET[where_titel]%\"";
			}
			
			if($_GET['where_themenfeld']){
				$where = "WHERE `themenfeld` LIKE \"%$_GET[where_themenfeld]%\"";
			} 
		
			if($_GET['where_autoren']){
				$where = "WHERE `autoren` LIKE \"%$_GET[where_autoren]%\"";
			}
			
			$sql = "SELECT * FROM studibib $where $sort;";
			/* echo "<p>" . $sql . "</p>"; */
			$db_check = @MYSQL_SELECT_DB($db_name);
			
			if (!$result = mysql_query($sql)){
				echo "datenbankfehler";
				echo $sql;
			}
			$dbclose = (@MYSQL_CLOSE($db));
			echo "<center><table rules=\"all\" style=\"{FONT-SIZE: 11px; border: 1px solid #000000; empty-cells:show; vertical-align:top;}\">";
			echo "<tr>
							<th><a href=Studibib-komplete.php?&where_autoren=$_GET[where_autoren]&where_titel=$_GET[where_titel]&where_themenfeld=$_GET[where_themenfeld]&sort=invnr>Inventarnr.</a></th>
							<th><a href=Studibib-komplete.php?&where_autoren=$_GET[where_autoren]&where_titel=$_GET[where_titel]&where_themenfeld=$_GET[where_themenfeld]&sort=bn>BN-Nummer</a></th>
							<th><a href=Studibib-komplete.php?&where_autoren=$_GET[where_autoren]&where_titel=$_GET[where_titel]&where_themenfeld=$_GET[where_themenfeld]&sort=autoren>Autoren</a></th>
							<th><a href=Studibib-komplete.php?&where_autoren=$_GET[where_autoren]&where_titel=$_GET[where_titel]&where_themenfeld=$_GET[where_themenfeld]&sort=titel>Titel</a></th>
							<th><a href=Studibib-komplete.php?&where_autoren=$_GET[where_autoren]&where_titel=$_GET[where_titel]&where_themenfeld=$_GET[where_themenfeld]&sort=standort>Standort</a></th>
							<th><a href=Studibib-komplete.php?&where_autoren=$_GET[where_autoren]&where_titel=$_GET[where_titel]&where_themenfeld=$_GET[where_themenfeld]&sort=signatur>Signatur</a></th>
							<th><a href=Studibib-komplete.php?&where_autoren=$_GET[where_autoren]&where_titel=$_GET[where_titel]&where_themenfeld=$_GET[where_themenfeld]&sort=auflage>Auflage</a></th>
							<th><a href=Studibib-komplete.php?&where_autoren=$_GET[where_autoren]&where_titel=$_GET[where_titel]&where_themenfeld=$_GET[where_themenfeld]&sort=erscheinungsjahr>Erscheinungsjahr</a></th>
							<th><a href=Studibib-komplete.php?&where_autoren=$_GET[where_autoren]&where_titel=$_GET[where_titel]&where_themenfeld=$_GET[where_themenfeld]&sort=zustand>Zustand</a></th>
							<th><a href=Studibib-komplete.php?&where_autoren=$_GET[where_autoren]&where_titel=$_GET[where_titel]&where_themenfeld=$_GET[where_themenfeld]&sort=themenfeld>Themenfeld</a></th>
						</tr>
						";
			for($i=0; $row = mysql_fetch_row($result); $i++){
				if(!$result){
					echo "Datenbankfehler";
				}
				else{
					$invnr = $row[0];
					$bn = $row[9];
					$autoren = $row[1];
					$titel = $row[2];
					$standort = $row[3];
					$signatur = $row[4];
					$auflage = $row[5];
					$erscheinungsjahr =  $row[8];
					$zustand = $row[7];
					$themenfeld = $row[6];
				
					echo "<tr>
									<td>&nbsp; $invnr</td>
									<td>&nbsp; $bn</td>
									<td>&nbsp; $autoren</td>
									<td>&nbsp; $titel</td>
									<td>&nbsp; $standort</td>
									<td>&nbsp; $signatur</td>
									<td>&nbsp; $auflage</td>
									<td>&nbsp; $erscheinungsjahr</td>
									<td>&nbsp; $zustand</td>
									<td>&nbsp; $themenfeld</td>
								</tr>";
				
				}
			}
			echo "</table></center>";
	?>
      </div>
    </td>

