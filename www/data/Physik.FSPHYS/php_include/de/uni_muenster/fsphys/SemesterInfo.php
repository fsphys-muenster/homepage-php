<?php
namespace de\uni_muenster\fsphys;
require_once 'init.php';

class SemesterInfo {
	/*
		Returns an array containing information about what semester $date is in:
		[
			'summer_winter' => 'SS' or 'WS',
			'year_str' => semester’s year label (e.g. 2016/2017 for WS),
			'lecture_start' => lecture start date for the semester (DateTime),
			'lecture_end' => lecture end date for the semester (DateTime),
			'during_semester' => if $date is during the semester (between
				lecture_start and lecture_end) or during the break
		]
	*/
	static function get(\DateTimeInterface $date): array {
		// XXX implement database retrieval
		return self::fallback($date);
	}

	static function semester_str(\DateTimeInterface $date, ?bool $short=false,
		$locale=Localization::LOCALE): string {
		$short = $short ? ' (short)' : '';
		// only need 'summer_winter' and 'year_str' ⇒ use fallback
		$info = self::fallback($date);
		if ($info['summer_winter'] == 'SS') {
			$semester_text = Localization::get("summer semester$short", false,
				$locale);
		}
		else {
			$semester_text = Localization::get("winter semester$short", false,
				$locale);
		}
		return "$semester_text&nbsp;{$info['year_str']}";
	}

	static function format_timespan(\DateTimeInterface $start,
		?\DateTimeInterface $end=NULL, array $opt=[]):
		string {
		$opt = filter_var_array($opt, [
			'no_end_pre' => NULL, 'no_end_post' => NULL, 'between' => NULL,
			'short' => FILTER_VALIDATE_BOOLEAN, 'locale' => NULL
		]);
		$locale = $opt['locale'] ?? Localization::LOCALE;
		$between = $opt['between'] ?? '–';
		$start_str = Util::mb_ucfirst(
			self::semester_str($start, $opt['short'], $locale));
		if ($end) {
			$end_str = self::semester_str($end, $opt['short'], $locale);
			return "$start_str$between$end_str";
		}
		else {
			return "{$opt['no_end_pre']}$start_str{$opt['no_end_post']}";
		}
	}

	/*
		Fallback function for get() if there is no database information about
		lecture start/end for $date.
	*/
	private static function fallback(\DateTimeInterface $date): array {
		$year = $date->format('Y');
		$month = $date->format('n');
		// between 1st of April and end 30th of September
		$is_ss = $month >= 4 && $month < 10;
		// get dates for current and next semester
		// WS lecture start: ≈ 7th October, SS lecture start: ≈ 7th April
		$ws_start = new \DateTime("$year-10-07");
		$ss_start = new \DateTime("$year-04-07");
		// correct WS start year if we are in the second year of WS
		// (January, February or March)
		if (!$is_ss && $month < 10) {
			// subtract 1 year
			$ws_start->sub(new \DateInterval('P1Y'));
		}
		$ws_start_yr = $ws_start->format('Y');
		$ws_start_yr_nxt = $ws_start_yr + 1;
		// WS lecture end: ≈ 2nd February, SS lecture end: ≈ 20th July
		$ws_end = new \DateTime("$ws_start_yr_nxt-02-02");
		$ss_end = new \DateTime("$year-07-20");
		// during semester: true; during break: false
		$semester = ($date >= $ss_start && $date < $ss_end)
			|| ($date >= $ws_start && $date < $ws_end);
		$res = [
			'summer_winter' => $is_ss ? 'SS' : 'WS',
			'year_str' => $is_ss ? "$year" : "$ws_start_yr/$ws_start_yr_nxt",
			'lecture_start' => $is_ss ? $ss_start : $ws_start,
			'lecture_end' => $is_ss ? $ss_end : $ws_end,
			'during_semester' => $semester
		];
		return $res;
	}
}

