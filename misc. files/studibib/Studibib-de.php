<?php
include '/www/data/Physik.FSPHYS/imperialive/Physik.FSPHYS/intern/intern-fs/admin/php-include/db-access.inc';
include '/www/data/Physik.FSPHYS/imperialive/Physik.FSPHYS/intern/intern-fs/admin/php-include/functions.inc';
?>

	<div class="typotext">
	<?php
			$db = mysql_db_connect() or die ("Konnte keine Verbindung zur Datenbank herstellen");
			$wautoren = $_POST['where_autoren'];
			$wtitel = $_POST['where_titel'];
			$wthemenfeld = $_POST['where_themenfeld'];
			if (strlen($wautoren) == 0){$wautoren=$_GET['where_autoren'];}
			if (strlen($wtitel) == 0){$wtitel=$_GET['where_titel'];}
			if (strlen($wthemenfeld) == 0){$wthemenfeld=$_GET['where_themenfeld'];}
			echo "
			<center><form method=\"POST\" action=\"$SELF_PHP\"><table>
			<tr>
			<td>Autoren</td><td><input type=\"text\" name=\"where_autoren\" size=\"50\" value = $wautoren></td>
			</tr>
			<tr>
			<td>Titel</td><td><input type=\"text\" name=\"where_titel\" size=\"50\" value = {$_POST['where_titel']}></td>
			</tr>
			<tr>
			<td>Themenfeld</td><td><input type=\"text\" name=\"where_themenfeld\" size=\"50\" value = {$_POST['where_themenfeld']}></td>
			</tr>
			<tr align=\"center\">
			<td>&nbsp</td><td><input type=\"submit\" value=\"Suchen\"></td>
			</tr>
			</table></form></center>
			";
					
			echo "<br>" . $wautoren . " " . $wtitel . " " . $wthemenfeld;
			if($_GET['sortby']){
				if ($_GET['sort']){
				$sort = "ORDER BY " . $_GET['sortby'] . " " . $_GET['sort'];}
				else {$sort = "ORDER BY " . $_GET['sortby'] . " ASC";}}
				
			else{
				if ($_GET['sort']){
				$sort = "ORDER BY autoren " . $_GET['sort'];}
				else{$sort = "ORDER BY autoren ASC";} }
	
			if (strlen($wthemenfeld) <> 0){
				$where = "WHERE `themenfeld` LIKE \"%{$_POST['where_themenfeld']}%\"";
			}
			
			if (strlen($wtitel) <> 0){
				$where = "WHERE `titel` LIKE \"%{$_POST['where_titel']}%\"";
			}	
			
			if (strlen($wautoren) <> 0){
				$where = "WHERE `autoren` LIKE \"%{$_POST['where_autoren']}%\"";
			}
		
			$sql = "SELECT * FROM studibib $where $sort;";
			echo "<p>" . $sql . "</p>";
			//echo "<p>" . $_GET['sort'] . " Sortiert nach " . $_GET['sortby'] . "</p>";
			
			if (!$result = $db->query($sql)){
				echo "Datenbankfehler";
				echo $sql;
			}
									
			echo "<center><table rules=\"all\" style=\"{FONT-SIZE: 11px; border: 1px solid #000000; vertical-align:top;}\">";
			echo "<tr>
							<th><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=invnr&sort=ASC>&and;</a><br>Inventarnr.<br><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=invnr&sort=DESC>&or;</a></th>
							<th><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=autoren&sort=ASC>&and;</a><br>Autoren<br><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=invnr&sort=DESC>&or;</a></th>
							<th><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=titel&sort=ASC>&and;</a><br>Titel<br><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=invnr&sort=DESC>&or;</a></th>
							<th><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=standort&sort=ASC>&and;</a><br>Standort<br><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=invnr&sort=DESC>&or;</a></th>
							<th><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=signatur&sort=ASC>&and;</a><br>Signatur<br><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=invnr&sort=DESC>&or;</a></th>
							<th><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=auflage&sort=ASC>&and;</a><br>Auflage<br><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=invnr&sort=DESC>&or;</a></th>
							<th><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=erscheinungsjahr&sort=ASC>&and;</a><br>Erscheinungsjahr<br><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=invnr&sort=DESC>&or;</a></th>
							<th><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=themenfeld&sort=ASC>&and;</a><br>Themenfeld<br><a href=Studibib-de.php?&where_autoren=$wautoren&where_titel=$wtitel&where_themenfeld=$wthemenfeld&sortby=invnr&sort=DESC>&or;</a></th>
						</tr>
						";
			for($i=0; $row = $result->fetch(); $i++){
				if(!$result){
					echo "Datenbankfehler";
				}
				else{
					$invnr = $row[0];
					$autoren = $row[1];
					$titel = $row[2];
					$standort = $row[3];
					$signatur = $row[4];
					$auflage = $row[5];
					$erscheinungsjahr =  $row[8];
					$themenfeld = $row[6];
				
					echo "<tr>
									<td>&nbsp;$invnr</td>
									<td>&nbsp;$autoren</td>
									<td>&nbsp;$titel</td>
									<td>&nbsp;$standort</td>
									<td>&nbsp;$signatur</td>
									<td>&nbsp;$auflage</td>
									<td>&nbsp;$erscheinungsjahr</td>
									<td>&nbsp;$themenfeld</td>
								</tr>";
				
				}
			}
			echo "</table></center>";
	?>
      </div>


