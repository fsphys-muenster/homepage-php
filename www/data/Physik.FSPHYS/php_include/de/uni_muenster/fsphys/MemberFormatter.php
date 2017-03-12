<?php
namespace de\uni_muenster\fsphys;
use Michelf\Markdown;
require_once 'init.php';

class MemberFormatter extends RecordFormatter {
	function __construct(Member $member, $locale=Localization::LOCALE) {
		parent::__construct($member, $locale);
	}

	private function format_abbr(string $attr): string {
		static $patterns = [
			'/B\.\s*Sc\./u',
			'/M\.\s*Sc\./u',
			'/[2Z]FB/u',
			'/M\.\s*Ed\./u',
			'/(^|[^-])\bFK/u',
			'/NaWi-FK/u',
			'/jDPG/u',
		];
		static $replacements = [
			'default' => [
				'<abbr lang=en title="Bachelor of Science">B.&nbsp;Sc.</abbr>',
				'<abbr lang=en title="Master of Science">M.&nbsp;Sc.</abbr>',
				'<abbr lang=de title="2-Fach-Bachelor">2FB</abbr>',
				'<abbr lang=en title="Master of Education">M.&nbsp;Ed.</abbr>',
				'$1<abbr lang=de title="Fachschaftenkonferenz">FK</abbr>',
				'<abbr lang=de title="Naturwissenschaftliche '
					. 'Fachschaftenkonferenz">NaWi-FK</abbr>',
				'<abbr lang=de title="junge Deutsche Physikalische '
					. 'Gesellschaft">jDPG</abbr>',
			],
			'title' => [
				'B.&nbsp;Sc.', 'M.&nbsp;Sc.', '2FB', 'M.&nbsp;Ed.',
				'$1FK', 'NaWi-FK', 'jDPG',
			],
		];
		return preg_replace($patterns, $replacements[$attr]
			?? $replacements['default'], parent::attr($attr));
	}

	private function sanitize_markdown(string $attr): string {
		$raw = parent::attr($attr);
		if (!$raw) return '';
		// escape HTML, but transform “&gt;” back to “>” at the beginning of
		// lines so Markdown blockquotes still work
		$text = preg_replace_callback('/^(\s*&gt;)+/um', function($matches) {
			return str_replace('&gt;', '>', $matches[0]);
		}, $raw);
		$parser = new Markdown;
		// filter URLs to prevent XSS
		$parser->url_filter_func = function(string $url): string {
			$scheme = parse_url($url, PHP_URL_SCHEME);
			if (!$scheme ||
				preg_match('/^(https?|s?ftp|mailto|tel|geo)$/i', $scheme)) {
				return $url;
			}
			else {
				return '';
			}
		};
		return $parser->transform($text);
	}
	
	function attr(string $attr): string {
		$mem = $this->data;
		$loc = $this->locale;
		switch ($attr) {
			case 'additional_info':
				// XXX add class to links depending on internal vs. external
				return $this->sanitize_markdown($attr);
			case 'nickname':
				$nickname = parent::attr('nickname');
				return $nickname ? Localization::enquote($nickname) : '';
			case 'duties':
			case 'program':
			case 'title':
				return $this->format_abbr($attr);
			case 'timespan':
				$loc_since = Localization::get('members.since', true);
				$start = new \DateTime($this->attr_raw('member_start'));
				$end = $this->attr_raw('member_end');
				$end = $end ? new \DateTime($end) : NULL;
				// &#8203; = ZERO WIDTH SPACE
				return SemesterInfo::format_timespan($start, $end, [
					'between' => '–&#8203;', 'no_end_pre' => "$loc_since ",
					'short' => true
				]);
			case 'pgp':
				$pgp_url = parent::attr('pgp_url');
				if (!$pgp_url) return '';
				$pgp_id = parent::attr('pgp_id');
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

	// XXX make this method more object-oriented (using MemberRecord and its
	// subclasses)
	private function format_committee_data_arr(array $data,
		callable $edit_callback=NULL): string {
		if (!$data) {
			return '';
		}
		$locale = $this->locale;
		$edit_class = $edit_callback ? ' fsphys_edit_table' : '';
		$edit_header = '';
		$edit_cols = '';
		if ($edit_callback) {
			$edit_header = $edit_callback('header');
			$edit_cols = $edit_callback('col');
		}
		$result = <<<HTML
		<table class="fsphys_member_committees$edit_class">
			<col><col><col>$edit_cols
HTML;
		foreach ($data as $category => $category_data) {
			$category_name = Localization::get("members.committees.$category",
				true, $locale);
			$result .= <<<HTML
			<tbody>
				<tr>
					<th scope=rowgroup colspan=3
						class=fsphys_subhead_fix>$category_name</th>
					$edit_header
				</tr>
HTML;
			foreach ($category_data as $committee_id => $committee_data) {
				$committee = new Committee($committee_id);
				$com_html = $committee->get_html($locale);
				$row_count = count($committee_data);
				$first_row = true;
				// data is sorted by timespan
				foreach ($committee_data as $row) {
					$loc_today = Localization::get('members.today', false,
						$locale);
					$loc_to = Localization::get('members.to', false, $locale);
					$name_cell = $edit_cell = '';
					// column 1 (committee name)
					if ($first_row) {
						$name_cell = <<<HTML
						<th scope=row rowspan=$row_count
							class=subhead>$com_html</th>
HTML;
					}
					// column 2 (timespan)
					if ($row['timespan_alt']) {
						if ($row['timespan_alt'] == 'full_date') {
							$start = $row['start'];
							$end = $row['end'] ?? $loc_today;
							$timespan = <<<HTML
<time datetime="$start">$start</time> $loc_to <time datetime="$end">$end</time>
HTML;
						}
						else {
							$timespan
								= Util::htmlspecialchars($row['timespan_alt']);
						}
					}
					else {
						$start = new \DateTime($row['start']);
						$end = $row['end'] ? new \DateTime($row['end']) : NULL;
						$timespan = SemesterInfo::format_timespan($start, $end,
							// &#8203; = ZERO WIDTH SPACE
							['locale' => $locale, 'between' => '–<br>',
								'no_end_post' => "–&#8203;$loc_today"]);
					}
					// column 3 (additional information)
					$info = Util::htmlspecialchars($row['info']);
					// column 4 (edit column, optional)
					if ($edit_callback) {
						$row_id = $row['row_id'];
						$edit_cell = $edit_callback('cell', [
							'member_id' => $this->data->get_id(),
							'row_id' => $row_id
						]);
					}
					// output
					$result .= <<<HTML
				<tr>
					$name_cell
					<td>$timespan</td>
					<td>$info</td>
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

