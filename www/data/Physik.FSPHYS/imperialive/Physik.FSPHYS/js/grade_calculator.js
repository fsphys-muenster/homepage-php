document.addEventListener('DOMContentLoaded', function() {
	'use strict';


	//var FSPHYS_LOCALE = "de-DE";
	var DATE = '2021-12-18';
	//var GC_VERSION = 'SS 2020/2021';
	var ID_PREFIX = 'fsphys_gc_';
	var INPUT_FIELDS = [
		'er_version',
		'physics_1',
		'physics_2',
		'physics_3',
		'math_2',
		'math_3',
		'minor_1',
		'minor_2',
		'minor_3',
		'minor_1_weight',
		'minor_2_weight',
		'minor_3_weight',
		'quantum_mechanics',
		'signal_processing',
		'computational_physics',
		'structure_of_matter',
		'lab_course_1',
		'lab_course_2',
		'statistical_physics',
		'bachelor_thesis'
	];
	var DEFAULT_WEIGHTS = {
			20161117 : {
				minor:               12,
				quantum_mechanics:    7,
				signal_processing:    7,
				computational_physics:    0,
				structure_of_matter: 12,
				lab_course_1:         0,
				lab_course_2:         9,
				statistical_physics: 10,
				bachelor_thesis:     10,
				physics_X: 11,
				math_X: 11,
			},
			20210412 : {
				minor:               10,
				quantum_mechanics:    7,
				signal_processing:    6,
				computational_physics:   4,
				structure_of_matter: 10,
				lab_course_1:         4,
				lab_course_2:         9,
				statistical_physics: 10,
				bachelor_thesis:     10,
				physics_X: 10,
				physics_3: 10,
				math_2: 5,
				math_3: 5,
			},
	};
	var HINTS = {
		"de-DE": {
			20161117 : {
				physics : "Aus den Modulen <i>Physik&nbsp;I–III</i> werden nur die zwei besten Noten mit einer Gewichtung von jeweils 11&nbsp;% in die Gesamtnote mit einbezogen.",
				math : "Nur die bessere Note der Module <i>Mathematische Grundlagen</i> und <i>Integrationstheorie</i> geht mit 11&nbsp;% in die Gesamtnote ein. (<i>Mathe für Physiker&nbsp;I</i> ist eine Studienleistung, hat also 0&nbsp;%.)",
				minor : "Bei dem Modul <i>Fachübergreifende Studien</i> wird die Gesamtnote je nach Beschreibung in der <a href=\"/Physik/Studieren/Studiengaenge/InfoPhBSc.html\" class=\"ext\" target=\"_blank\">Prüfungsordnung</a> gebildet. (Die Gewichtungen für die einzelnen Prüfungsleistungen müssen eigenhändig eingetragen werden. Z.&nbsp;B. im Fall <i>Informatik</i>: 50–50.)"
			},
			20210412 : {
				physics : "Aus den Modulen <i>Physik&nbsp;I–II</i> werden nur die beste Note mit einer Gewichtung von jeweils 10&nbsp;% in die Gesamtnote mit einbezogen.",
				math : "",
				minor : "Bei dem Modul <i>Fachübergreifende Studien</i> wird die Gesamtnote je nach Beschreibung in der <a href=\"/Physik/Studieren/Studiengaenge/InfoPhBSc.html\" class=\"ext\" target=\"_blank\">Prüfungsordnung</a> gebildet. (Die Gewichtungen für die einzelnen Prüfungsleistungen müssen eigenhändig eingetragen werden. Z.&nbsp;B. im Fall <i>Informatik</i>: 50–50.)"
			}
		},
		"en-US":{
			20161117 : {
				physics : "From the modules <i>Physics&#160;I–III</i>, only the best two grades are included in the total grade, with a weight of 11&#160;% each.",
				math : "Only the best grade from the modules <i>Fundamental Mathematics</i> and <i>Integration Theory</i> enters the total grade with a weight of 11&#160;%. (<i>Math for Physicists&#160;I</i> is not an “exam” according to the regulations, so it counts with 0&#160;%.)",
				minor : "The grade for the module <i>Interdisciplinary Studies</i> is formed, depending on the subject, as described in the <a href=\"/Physik/en/Studieren/Studiengaenge/InfoPhBSc.html\" class=\"ext\" target=\"_blank\">exam regulations</a>. (The weights for the individual exams have to be entered manually. E.&#160;g. in the case of <i>Computer Science</i>: 50–50.)",
			},
			20210412 : {
				physics : "From the modules <i>Physik&nbsp;I–II</i> only the best grade is included in the total grade, with a weight of 10&nbsp;% each.",
				math : "",
				minor : "The grade for the module <i>Interdisciplinary Studies</i> is formed, depending on the subject, as described in the <a href=\"/Physik/en/Studieren/Studiengaenge/InfoPhBSc.html\" class=\"ext\" target=\"_blank\">exam regulations</a>. (The weights for the individual exams have to be entered manually. E.&#160;g. in the case of <i>Computer Science</i>: 50–50.)",
			}
		}
	};

	var total_grade = 0;
	var exams = {
		// weights of the individual module grades towards the total grade
		// (percentages)
		weights: {
				minor:               12,
				quantum_mechanics:    7,
				signal_processing:    7,
				structure_of_matter: 12,
				lab_course_2:         9,
				statistical_physics: 10,
				bachelor_thesis:     10
		},
		hints : {
			physics : "",
			math : "",
			minor : "",
			lab_course : ""
		},
		version : 20161117,

		update: function(user_input) {
			var version = user_input.er_version;

			// Copy dict so the original is not edited lateron
			// Syntax: See e.g. https://stackoverflow.com/a/54460487/8575607
			this.weights = {...DEFAULT_WEIGHTS[version]};
			
			// Hide irrelevant fields
			for (var exam in this.weights) {
				var text_el = document.getElementById(ID_PREFIX + exam + '_weight');
				if(text_el !== null)
					text_el.parentNode.hidden = this.weights[exam] == 0;
			}

			var weights = this.weights;
			// special cases: Physics I–III, Math II–III and the minor
			var physics_weights;
			if(version == 20161117) {
			 	physics_weights= this.best_of(
				['physics_1', 'physics_2', 'physics_3'], 2, this.weights["physics_X"], user_input);
				var math_weights = this.best_of(
				['math_2', 'math_3'], 1, this.weights["math_X"], user_input);
				for (name in math_weights) {
					weights[name] = math_weights[name];
				}
			}
			else if(version ==20210412) {
			 	physics_weights= this.best_of(
				['physics_1', 'physics_2'], 1, this.weights["physics_X"], user_input);
			}
			for (name in physics_weights) {
				weights[name] = physics_weights[name];
			}	
			for (name in HINTS[FSPHYS_LOCALE][version]) {
				this.hints[name] = HINTS[FSPHYS_LOCALE][version][name];
			}
			
			weights.minor_1 = user_input.minor_1_weight;
			weights.minor_2 = user_input.minor_2_weight;
			weights.minor_3 = user_input.minor_3_weight;
		},

		best_of: function(exam_set, count, weight, user_input) {
			var weights = this.weights;
			// turn subset of user_input object into an array of key–value pairs
			// to be able to do sorting
			var grades = exam_set.map(function(key) {
				return {exam: key, grade: user_input[key]};
			});
			grades.sort(function(a, b) {
				var sc = 1
				// grades with values 0 are to be weight less than actual grades
				if(a.grade===0 || b.grade ===0)
					sc = -1
				return (a.grade - b.grade)*sc;
			});
			var weights_set = {};
			grades.forEach(function(el, idx) {
				// exams up to entry number “count” get “weight”, others 0
				weights_set[el.exam] = idx < count ? weight : 0;
			});
			return weights_set;
		}
	};

	function load_url_data() {
		try {
			var url_fragment = decodeURIComponent(location.hash.substring(1));
			user_input = JSON.parse(url_fragment);
			if (!("er_version" in user_input)) {
				user_input["er_version"] = 20161117;
			}
			INPUT_FIELDS.forEach(function(field) {
				var input = document.getElementById(ID_PREFIX + field);
				input.value = user_input[field];
			});
			// update for consistency
			update();
			// set document title to distinguish bookmarks
			var loc = "de-DE";
			switch (FSPHYS_LOCALE) {
				case 'en-US':
					var title_text = 'Saved bachelor’s grade: ';
					loc = "en-US";
					break;
				case 'de-DE':
				default:
					loc = "de-DE";
					var title_text = 'Gespeicherte Bachelor-Note: ';
					break;
			}
			var total_grade_str = total_grade.toLocaleString(loc,
					{minimumFractionDigits: 2, maximumFractionDigits: 2});
			var re = /\[.+\]/;
			if (re.test(document.title)) {
				document.title = document.title.replace(re,
					'[' + title_text + total_grade_str + ']');
			}
			else {
				document.title += ' [' + title_text + total_grade_str + ']';
			}
		}
		catch (e) {
			console.log(e);
		}
	}

	function update() {
		// update user_input
		INPUT_FIELDS.forEach(function(field) {
			var input = document.getElementById(ID_PREFIX + field);
			user_input[field] = Number(input.value);
		});
		// update exam weights
		exams.update(user_input);
		// get minor grade and total grade using weights
		// divide by 100 because of percentages and round to 2 decimal places
		var weights = exams.weights;
		// minor grade
		var minor_grade = 0;
		var minor_weight_sum = 0;
		for (var exam in weights) {
			// build minor grade from values starting with 'minor_'
			if (exam.indexOf('minor_') === 0) {
				minor_grade += user_input[exam] * weights[exam];
				if(user_input[exam] != 0)
					minor_weight_sum +=weights[exam];
			}
		}
		if(minor_weight_sum != 0)
			minor_grade /= minor_weight_sum;
		else
			minor_grade = 0
		user_input.minor = minor_grade;
		minor_grade = minor_grade.toLocaleString(FSPHYS_LOCALE,
			{minimumFractionDigits: 2, maximumFractionDigits: 2});
		// total grade
		total_grade = 0;
		var total_weight_sum =0;
		for (var exam in weights) {
			if (exam.indexOf('minor_') !== 0 && user_input[exam] !== undefined) {
				total_grade += user_input[exam] * weights[exam];
				if(user_input[exam] != 0)
					total_weight_sum += weights[exam];
			}
		}
		if(total_weight_sum != 0)
			total_grade = (total_grade / total_weight_sum)
		else
			total_grade = 0	
		total_grade = total_grade.toLocaleString(FSPHYS_LOCALE,
			{minimumFractionDigits: 2, maximumFractionDigits: 2});
		// write (possibly updated) weight information to DOM
		for (var exam in exams.weights) {
			var text_el = document.getElementById(ID_PREFIX + exam + '_weight');
			if(text_el !== null)
				text_el.textContent = exams.weights[exam];
		}
		// write (possibly updated) hint information to DOM
		for (var hint in exams.hints) {
			var text_el = document.getElementById(ID_PREFIX + hint + '_hint');
			if(text_el !== null)
				text_el.innerHTML= exams.hints[hint];
		}
		// write calculated grades to DOM
		document.getElementById(ID_PREFIX + 'minor').textContent = minor_grade;
		document.getElementById(ID_PREFIX + 'total').textContent = total_grade;
	}

	function write_url_fragment() {
		location.hash = JSON.stringify(user_input);
	}
	
	// initialize user input data
	// manually set minor because there is no element with the same name
	// included in INPUT_FIELDS
	var user_input = {minor: 0};
	var url_fragment_set = Boolean(location.hash);
	// try to parse input data from URL fragment if provided
	if (url_fragment_set) {
		load_url_data();
	}
	// update with changes to URL fragment
	window.addEventListener('hashchange', load_url_data);
	// print version information
	document.getElementById(ID_PREFIX + 'version').textContent = DATE;
	// update on user input
	var gc_form = document.getElementById(ID_PREFIX + 'form');
	gc_form.addEventListener('input', update);
	// assign action to save button
	document.getElementById(ID_PREFIX + 'save').onclick = write_url_fragment;
	// run update to initialize input_data and for consistency
	update();
});

