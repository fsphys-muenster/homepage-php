<?php

function maske_buch_hinzufuegen() {
	$anzindexreihen= "SELECT count(*) as anzahl FROM studibib";
	$result= mysql_query($anzindexreihen) or die(mysql_error());
	$row = mysql_fetch_array($result);
	$temp = ++$row['anzahl'];

	echo " <center><form method=\"POST\" action=\"?addbuch=true\"><table>
		 <tr>
		  <td>SB-Nummer</td>
		  <td>BN-Nummer</td>
		  <td>Autoren</td>
		  <td>Titel</td>
		  <td>Standort</td>
		  <td>Signatur</td>
		  <td>Auflage</td>
		  <td>Erscheinungsjahr</td>
		  <td>Zustand</td>
		  <td>Themenfeld</td>
		 </tr>
		 <tr>
		  <td>792</td>
		  <td>1</td>
		  <td>W. Lange</td>
		  <td>Einführung in die Laserphysik</td>
		  <td>Schrank 3</td>
		  <td>11 Lan 792</td>
		  <td>1. Auflage</td>
		  <td>19xx</td>
		  <td>gut</td>
		  <td>Optik, Laser, Nichtlineare Physik, Angewandte Physik</td>
		 </tr>
		 <tr>
		  <td><input type=\"text\" name=\"invnr\"  size=\"11\" value =\" \"></td>		  
		  <td><input type=\"text\" name=\"bn\"  size=\"11\" value =\"$temp\"></td>
                <td><input type=\"text\" name=\"autoren\"   value =\"Autoren\"></td>
                <td><input type=\"text\" name=\"titel\"  value =\"Titel\"></td>
                <td><input type=\"text\" name=\"standort\"  value =\"Schrank 1\"></td>
                <td><input type=\"text\" name=\"signatur\"  value =\"Signatur + InvNr\"></td>
                <td><input type=\"text\" name=\"auflage\"  value =\"x. Auflage\"></td>
                <td><input type=\"text\" name=\"erscheinungsjahr\"  value =\"19xx\"></td>
                <td><input type=\"text\" name=\"zustand\"  value =\"\"></td>
                <td><input type=\"text\" name=\"themenfeld\"  value =\"Liste von Themenfeldern\"></td>
		 </tr>
		 <tr>
		  <td><input type=\"submit\" value=\"Eintragen\"></td>
		 </tr>
		</table></form></center>";
}

function buch_hinzufuegen($invnr, $autoren, $titel, $standort, $signatur, $auflage, $themenfeld, $zustand, $erscheinungsjahr, $bn) {
	$sql = "INSERT INTO `studibib` (`invnr`, `autoren`, `titel`, `standort`, `signatur`, `auflage`, `themenfeld`, `zustand`, `erscheinungsjahr`, `bn`) VALUES (\"$invnr\", \"$autoren\", \"$titel\", \"$standort\", \"$signatur\", \"$auflage\", \"$themenfeld\", \"$zustand\", \"$erscheinungsjahr\", \"$bn\");";
	$db_result = mysql_query($sql);
	$db_check = @MYSQL_SELECT_DB($db_name);
	$result = mysql_query($sql);
	echo "<center>Buch hinzugef&uuml;gt</center>";
}

?>

