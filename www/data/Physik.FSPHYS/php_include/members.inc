<?php
namespace de\uni_muenster\fsphys;
require_once 'error_handler.inc';
require_once 'db_access.inc';

function add_committee($committee_name, $category, $html, $db=NULL,
	$locale=LOCALE) {
	$tbl_name = 'member_committees__';
	$sql = <<<SQL
	INSERT INTO committees ("committee_name") VALUES(:member_name);
SQL;
	$opened_db = mysql_db_connect_if_null($db);
	$query = $db->prepare($sql);
	$query->bindValue(':member_name', $member_name);
	$query->execute();
	if ($opened_db) {
		mysql_db_close($db);
	}
}

function add_member_data($member_name, $row, $db=NULL, $locale=LOCALE) {
	// XXX
	$sql_create_member = <<<'SQL'
	INSERT IGNORE INTO "members" ("member_name") VALUES (:member_name);
SQL;
	$tbl_name = 'member_committees__' . loc_lang_code();
	$sql = <<<SQL
	INSERT INTO "$tbl_name" ("member_id", "committee_id", "timespan", "info")
		VALUES(:member_id, :committee_id, :timespan, :info);
SQL;
	$opened_db = mysql_db_connect_if_null($db);
	// execute query for possible member creation
	$query_create_member = $db->prepare($sql_create_member);
	$query_create_member->bindValue(':member_name', $member_name);
	$query_create_member->execute();
	$query = $db->prepare($sql);
	// execute query to add a data row
	// XXX
	$member_id = member_id_from_name($member_name, $db);
	$query->bindValue(':member_id', $member_id);
	$query->bindValue(':committee_id', $row['committee_id']);
	$query->bindValue(':timespan', $row['timespan']);
	$query->bindValue(':info', $row['info']);
	$query->execute();
	if ($opened_db) {
		mysql_db_close($db);
	}
}

function delete_member_data($row_id, $db=NULL, $locale=LOCALE) {
	$tbl_name = 'member_committees__' . loc_lang_code();
	$sql = <<<SQL
	DELETE FROM $tbl_name WHERE "row_id" = :row_id;
SQL;
	$opened_db = mysql_db_connect_if_null($db);
	$query = $db->prepare($sql);
	$query->bindValue(':row_id', $row_id);
	$query->execute();
	if ($opened_db) {
		mysql_db_close($db);
	}
}

function get_member_data($member_name, $db=NULL, $locale=LOCALE) {
	$entries_tbl_name = 'member_committees__' . loc_lang_code();
	$committees_loc_tbl_name = 'committees__' . loc_lang_code();
	$sql = <<<SQL
	SELECT * FROM
		$entries_tbl_name NATURAL JOIN members NATURAL JOIN committees
			NATURAL JOIN $committees_loc_tbl_name
		WHERE REPLACE(LOWER("member_name"), ' ', '_') = :member_name
		ORDER BY "category", "sort_key", "timespan";
SQL;
	$opened_db = mysql_db_connect_if_null($db);
	$query = $db->prepare($sql);
	$query->bindValue(':member_name', $member_name);
	$query->execute();
	$result = $query->fetchAll();
	if ($opened_db) {
		mysql_db_close($db);
	}
	return $result;
}

function format_member_data($member_name, $db=NULL, $locale=LOCALE) {
	$data = get_member_data($member_name, $db, $locale);
	$result = <<<'HTML'
	<article class="module extended">
		<div class="module-content">
			<table class="fsphys_member">
HTML;
	foreach ($data as $category => $category_data) {
		$category_name = fsphys\loc_get_str("members/committees_$category",
			true);
		$result = <<<HTML
			<tbody>
				<tr>
					<th scope="rowgroup" colspan="3">$category_name</th>
				</tr>
HTML;
		// data is sorted by committee and timespan
		foreach ($category_data as $row) {
			$committe = //XXX;
			$timespan = //XXX;
			$info = //XXX;
			// XXX merge cells into one for multiple rows with the same
			// committee
			$result .= <<<HTML
				<tr>
					<td><strong>$committee</strong></td>
					<td>$timespan</td>
					<td>$info</td>
				</tr>
HTML;
		}
		$result .= '</tbody>';
	}
	$result .= <<<'HTML'
			</table>
		</div>
	</article>
HTML;
	return $result;
}

