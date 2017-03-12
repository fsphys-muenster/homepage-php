// select the correct schedule tab depending on the date
// (during the semester or the semester break)
document.addEventListener('DOMContentLoaded', function() {
	// note: month ranges from 0 to 11 in JS Date objects
	function is_ss(date) {
		let month = date.getMonth();
		// between start of April and end of September
		return month >= 3 && month < 9;
	}
	let current_date = new Date();
	let current_year = current_date.getFullYear();
	// get dates for current and next semester
	// WS lecture start: ≈ 7th October, SS lecture start: ≈ 7th April
	let ws_start = new Date(current_year, 9, 7);
	let ss_start = new Date(current_year, 3, 7);
	// correct WS start year if we are in the second year of WS
	// (January, February or March)
	if (!is_ss(current_date) && current_date.getMonth() < 9) {
		ws_start.setFullYear(current_year - 1);
	}
	// WS lecture end: ≈ 1st February, SS lecture end: ≈ 20th July
	let ws_lecture_end = new Date(ws_start.getFullYear() + 1, 1, 1);
	let ss_lecture_end = new Date(current_year, 6, 20);
	// get tab tags
	let tabs = $('ul.element.tabs').children();
	if ((current_date >= ss_start && current_date < ss_lecture_end)
		|| (current_date >= ws_start && current_date < ws_lecture_end)) {
		tabClicks($(tabs.get(0)));
	}
	else {
		tabClicks($(tabs.get(1)));
	}
});

