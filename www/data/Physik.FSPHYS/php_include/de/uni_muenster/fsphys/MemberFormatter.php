<?php
namespace de\uni_muenster\fsphys;
require_once 'init.php';

class MemberFormatter extends RecordFormatter {
	function __construct(Member $member, $locale=Localization::LOCALE) {
		parent::__construct($member, $locale);
	}
	
	private static function format_abbr(string $attr, string $input): string {
		static $patterns = ['/B\.\s*Sc\./u', '/M\.\s*Sc\./u',
			'/[2Z]FB/u', '/M\.\s*Ed\./u',];
		static $replacements = [
			'program' => [
				'<abbr lang=en title="Bachelor of Science">B.&nbsp;Sc.</abbr>',
				'<abbr lang=en title="Master of Science">M.&nbsp;Sc.</abbr>',
				'<abbr lang=de title="2-Fach-Bachelor">2FB</abbr>',
				'<abbr lang=en title="Master of Education">M.&nbsp;Ed.</abbr>',
			],
			'title' => [
				'B.&nbsp;Sc.', 'M.&nbsp;Sc.', '2FB', 'M.&nbsp;Ed.',
			],
		];
		return preg_replace($patterns, $replacements[$attr], $input);
	}
	
	function attr(string $attr): string {
		$mem = $this->data;
		$loc = $this->locale;
		switch ($attr) {
			case 'program':
			case 'title':
				return self::format_abbr($attr, $mem->get_attr($attr, $loc));
			case 'timespan':
				$start = $mem->get_attr('member_start', $loc);
				$end = $mem->get_attr('member_end', $loc);
				if ($end) {
					// XXX Convert to semester instead of printing raw date
					return "{$start}–$end";
				}
				else {
					$loc_since = Localization::get('members.since', true);
					return "$loc_since $start";
				}
			case 'pgp':
				$pgp_url = htmlspecialchars($mem->get_attr('pgp_url', $loc));
				if (!$pgp_url) return '';
				$pgp_id = htmlspecialchars($mem->get_attr('pgp_id', $loc));
				$loc_pgp_key = Localization::get('members.pgp_key');
				if ($pgp_id) {
					$html = <<<HTML
					<a class=intranet href="$pgp_url">$loc_pgp_key: $pgp_id</a>
HTML;
				}
				else {
					$html = <<<HTML
					<a class=intranet href="$pgp_url">$loc_pgp_key</a>
HTML;
				}
				return trim($html);
			default:
				return parent::attr($attr);
		}
	}

	function committee_data(callable $edit_callback=NULL): string {
		$data = $this->data->get_committee_data($this->locale, true);
		return self::format_committee_data_arr($data, $edit_callback);
	}

	private function format_committee_data_arr(array $data,
		callable $edit_callback=NULL): string {
		if (!$data) {
			return '';
		}
		$edit_class = $edit_callback ? ' fsphys_edit_table' : '';
		$result = <<<HTML
		<table class="fsphys_member_committees$edit_class">
HTML;
		foreach ($data as $category => $category_data) {
			$category_name = Localization::get("members.committees.$category",
				true, $this->locale);
			$edit_header = '';
			if ($edit_callback) {
				$edit_header = $edit_callback('header');
			}
			$result .= <<<HTML
			<tbody>
				<tr>
					<th scope=rowgroup colspan=3>$category_name</th>
					$edit_header
				</tr>
HTML;
			foreach ($category_data as $committee_id => $committee_data) {
				$committee = new Committee($committee_id);
				$com_html = $committee->get_html($this->locale);
				$row_count = count($committee_data);
				$first_row = true;
				// data is sorted by timespan
				foreach ($committee_data as $row) {
					$name_cell = $edit_cell = '';
					$end = $row['end'] ?? Localization::get('today');
					if ($first_row) {
						$name_cell = <<<HTML
						<th scope=row rowspan=$row_count
							class="subhead fix">$com_html</th>
HTML;
					}
					if ($edit_callback) {
						$row_id = $row['row_id'];
						$edit_cell = $edit_callback('cell', [
							'member_id' => $this->data->get_id(),
							'row_id' => $row_id
						]);
					}
					// XXX Convert dates to semester instead of printing as-is
					$result .= <<<HTML
				<tr>
					$name_cell
					<td>{$row['start']}–$end</td>
					<td>{$row['info']}</td>
					$edit_cell
				</tr>
HTML;
					$first_row = false;
				}
			}
			$result .= '</tbody>';
		}
		$result .= '</table>';
		return $result;
	}
}

