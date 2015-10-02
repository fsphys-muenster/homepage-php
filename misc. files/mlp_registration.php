<?php
/*
	Anmeldemaske für das Steuerseminar mit MLP.
	Kann für andere Veranstaltungen angepasst werden.
*/
include '/www/data/Physik.FSPHYS/imperialive/Physik.FSPHYS/intern/intern-fs/admin/php-include/db-access.inc';
include '/www/data/Physik.FSPHYS/imperialive/Physik.FSPHYS/intern/intern-fs/admin/php-include/functions.inc';

function success($msg) {
	echo <<<HTML
	<tr>
		<td colspan="2">
			<p class="success">$msg</p>
		</td>
	</tr>
HTML;
}

function error($msg) {
	echo <<<HTML
	<tr>
		<td colspan="2">
			<p class="error">$msg</p>
		</td>
	</tr>
HTML;
}

function confirmation_mail($name, $email) {
	$to = "=?utf-8?b?" . base64_encode($name) . "?= <$email>";
	$headers = 'Content-Type: text/plain; charset=utf-8' . PHP_EOL
		. 'Content-Transfer-Encoding: 8bit' . PHP_EOL
		. 'From: Fachschaft Physik <fsphys@uni-muenster.de>';
	$subject = 'Anmeldung zum Steuerseminar mit MLP';
	$message = <<<MAIL
Hallo $name,

hiermit möchten wir bestätigen, dass du dich verbindlich zum Steuerseminar
mit MLP am 25.06.2015 um 17:30 Uhr angemeldet hast.

-- 
Viele Grüße,
Fachschaftsrat Physik der WWU Münster
c/o Institut für Kernphysik, Wilhelm-Klemm-Str. 9, 48149 Münster

mail: fsphys@uni-muenster.de
fon: +49 251 83-34985
fax: +49 251 83-34962
web: https://www.uni-muenster.de/Physik.FSPHYS
fb: https://www.facebook.com/fsphys
MAIL;
	return mail($to, $subject, $message, $headers);
}

function form_submission() {
	$first_name = $_POST['first_name'];
	$surname = $_POST['surname'];
	$email = $_POST['email'];
	$phone_number = $_POST['phone_number'];
	$study_course = $_POST['study_course'];
	$semester = $_POST['semester'];
	if (empty($semester)) {
		$semester = '0';
	}
	$comment = $_POST['comment'];
	
	$valid = !empty($first_name)
		&& !empty($surname)
		&& filter_var($email, FILTER_VALIDATE_EMAIL)
		&& ctype_digit($semester)
		&& $semester >= 0
		&& strlen(utf8_decode($comment)) <= 10000;
	if ($valid) {
		$db = mysql_db_connect();
		$sql = 'INSERT INTO mlp_steuerseminar (first_name,surname,email,phone_number,study_course,semester,comment) VALUES(:fn,:sn,:email,:pn,:course,:sem,:comm);';
		$query = $db->prepare($sql);
		$query->bindValue(':fn', $first_name);
		$query->bindValue(':sn', $surname);
		$query->bindValue(':email', $email);
		$query->bindValue(':pn', $phone_number);
		$query->bindValue(':course', $study_course);
		$query->bindValue(':sem', $semester);
		$query->bindValue(':comm', $comment);
		$result = $query->execute();
		mysql_db_close($db);
	}
	if ($valid && $result) {
		if (confirmation_mail("$first_name $surname", $email)) {
			success('Du hast dich erfolgreich angemeldet!<br/>'
				. '(Eine Bestätigungs-E-Mail wurde an <span class="email">'
				. $email . '</span> verschickt.)');
		}
		else {
			success('Du hast dich erfolgreich angemeldet!<br/>'
				. 'Eine E-Mail zur Bestätigung konnte allerdings nicht erfolgreich an '
				. '<span class="email">' . $email . '</span> verschickt werden.');
		}
	}
	else {
		error('Bei der Anmeldung ist ein Fehler aufgetreten.');
	}
}
?>

<style type="text/css" media="all">
	#mask > *{
		margin-left: auto;
		margin-right: auto;
	}
	#form table td {
		vertical-align: text-top;
		padding: 2px;
	}
	table.outer {
		width: 75%;
		min-width: 400px;
		max-width: 700px;
		border: 0;
	}
	.required {
		color: #C43B1D;
	}
	.email {
		font-family: monospace;
	}
	.error {
		text-align: center;
		color: #600000;
		border: 2px solid #A00000;
		background-color: #E0C0C0;
		padding: 10px !important;
	}
	.success {
		text-align: center;
		color: #006000;
		border: 2px solid #00A000;
		background-color: #C0E0C0;
		padding: 10px !important;
	}
</style>

<h1 style="color: #a00; text-align: center;">Anmeldung zum Steuerseminar</h1>
<form action="mlp_registration.php" method="post" id="form">
<div id="mask">
	<table class="outer">			
		<tr>
			<td style="width: 250px;"><label for="first_name">Vorname: <span class="required">*</span></label></td>
			<td><input type="text" id="first_name" name="first_name" required size="30" maxlength="60" /></td>
		</tr>
		<tr>
			<td><label for="surname">Nachname: <span class="required">*</span></label></td>
			<td><input type="text" id="surname" name="surname" required size="30" maxlength="60" /></td>
		</tr>
		<tr>
			<td><label for="email">E-Mail-Adresse: <span class="required">*</span>
			<br/>
			<small>Adresse, die regelmäßig abgerufen wird (für aktuelle Informationen).</small></label></td>
			<td><input type="email" id="email" name="email" required size="30" maxlength="60" /></td>
		</tr>
		<tr>
			<td><label for="phone_number">Handynummer:
			<br/>
			<small>(Optional: Wird anonymisiert an MLP für eine SMS-Erinnnerung weitergegeben.)</small></label></td>
			<td><input type="tel" id="phone_number" name="phone_number" size="30" maxlength="60" /></td>
		</tr>
		<tr>
			<td><label for="study_course">Studiengang:</label></td>
			<td><input type="text" id="study_course" name="study_course" size="30" maxlength="60" /></td>
		</tr>
		<tr>
			<td><label for="semester">Semester:</label></td>
			<td><input type="number" min="1" step="1" max="99" id="semester" name="semester" size="20" /></td>
		</tr>

		<tr>
			<td colspan="2"><br/><label for="comment">Platz für Kommentare:</label></td>
		</tr>
		<tr>
			<td colspan="2"><textarea id="comment" name="comment" rows="4" cols="60"></textarea></td>
		</tr>
		<tr>
			<td colspan="2"><label><input type="checkbox" required /> Ich möchte mich hiermit verbindlich zum Seminar (25.06.2015, 17:30 Uhr) anmelden. <span class="required">*</span></label></td>
		</tr>
		<tr>
			<td colspan="2"><label><input type="checkbox" required /> Mir ist bewusst, dass die angegebenen Daten zu Organisationszwecken an MLP weitergegeben werden können. Der Datenschutz wird geachtet und die Daten nur zur Durchführung der Veranstaltung verwendet. <span class="required">*</span></label></td>
		</tr>
		<tr>
			<td colspan="2"><br/><input type="submit" name="submit" value="Anmelden" /></td>
		</tr>
		
<?php
if ($_POST['submit']) {
	form_submission();
}
?>

		<tr>
			<td colspan="2"><hr/><br/>Das Formular wird mit Klick auf „Anmelden“ abgeschickt. Bei erfolgreicher Anmeldung wird eine Bestätigungs-E-Mail an die oben angegebene E-Mail-Adresse verschickt.</td>
		</tr>

		<tr>
			<td colspan="2"><small>Mit <span class="required">*</span> markierte Felder müssen zwingend ausgefüllt werden.</small></td>
		</tr>
	</table>
</div>
</form>

