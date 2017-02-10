<?php
namespace de\uni_muenster\fsphys;
require_once 'init.inc';

class SemesterInfo {
	/*
		Returns an array containing information about what semester $date is in:
		[
			'summer_winter' => 'SS' or 'WS',
			'lecture_start' => lecture start date for the semester (DateTime),
			'lecture_end' => lecture end date for the semester (DateTime),
			'during_semester' => if $date is during the semester (between
				lecture_start and lecture_end) or during the break
		]
	*/
	static function get($date): array {
		return self::fallback($date);
	}

	/*
		Fallback function for semester_info() if there is no database information
		about lecture start/end for $date.
	*/
	private static function fallback($date):array {
		$year = $date->format('Y');
		$month = $date->format('n');
		// between 1st of April and end 30th of September
		$is_ss = $month >= 4 && $month < 10;
		// get dates for current and next semester
		// WS lecture start: ≈ 7th October, SS lecture start: ≈ 7th April
		$ws_start = new \DateTime("$year-10-07");
		$ss_start = new \DateTime("$year-04-07");
		// correct year if we are in WS
		if (!$is_ss) {
			if ($ws_start > $date) {
				// subtract 1 year
				$ws_start->sub(new \DateInterval('P1Y'));
			}
			if ($ss_start < $date) {
				// add 1 year
				$ss_start->add(new \DateInterval('P1Y'));
			}
		}
		$ws_start_yr = $ws_start->format('Y');
		$ws_start_yr_nxt = $ws_start_yr + 1;
		// WS lecture end: ≈ 2nd February, SS lecture end: ≈ 20th July
		$ws_lecture_end = new \DateTime("$ws_start_yr_nxt-02-02");
		$ss_lecture_end = new \DateTime("$ws_start_yr-07-20");
		// during semester: true; during break: false
		$semester = ($date >= $ss_start && $date < $ss_lecture_end)
			|| ($date >= $ws_start && $date < $ws_lecture_end);
		$res = [
			'summer_winter' => $is_ss ? 'SS' : 'WS',
			'lecture_start' => $is_ss ? $ss_start : $ws_start,
			'lecture_end' => $is_ss ? $ss_lecture_end : $ws_lecture_end,
			'during_semester' => $semester
		];
		return $res;
	}
}

