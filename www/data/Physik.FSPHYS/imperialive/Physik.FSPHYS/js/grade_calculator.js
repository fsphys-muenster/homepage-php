document.addEventListener('DOMContentLoaded', function() {
	'use strict';


	//var FSPHYS_LOCALE = "de-DE";
	var DATE = '2017-09-11';
	var ER_VERSION = 'WS 2016/2017';
	var ID_PREFIX = 'fsphys_gc_';
	var INPUT_FIELDS = [
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
	]
	var DEFAULT_WEIGHTS = {
			"2016-11-17" : {
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
			"2021-04-12" : {
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
		version : "2016-11-17",

		update: function(user_input) {
			var e = document.getElementById("fsphys_gc_version");
			var version = e.value;

			this.weights = DEFAULT_WEIGHTS[version];

			var weights = this.weights;
			// special cases: Physics I–III, Math II–III and the minor
			var physics_weights;
			if(version == "2016-11-17") {
			 	physics_weights= this.best_of(
				['physics_1', 'physics_2', 'physics_3'], 2, this.weights["physics_X"], user_input);
				var math_weights = this.best_of(
				['math_2', 'math_3'], 1, this.weights["math_X"], user_input);
				for (name in math_weights) {
					weights[name] = math_weights[name];
				}
			}
			else if(version =="2021-04-12") {
			 	physics_weights= this.best_of(
				['physics_1', 'physics_2'], 1, this.weights["physics_X"], user_input);
			}
			for (name in physics_weights) {
				weights[name] = physics_weights[name];
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
				return a.grade - b.grade;
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
		for (var exam in weights) {
			// build minor grade from values starting with 'minor_'
			if (exam.indexOf('minor_') === 0) {
				minor_grade += user_input[exam] * weights[exam];
			}
		}
		minor_grade /= 100;
		user_input.minor = minor_grade;
		minor_grade = minor_grade.toLocaleString(FSPHYS_LOCALE,
			{minimumFractionDigits: 2, maximumFractionDigits: 2});
		// total grade
		total_grade = 0;
		for (var exam in weights) {
			if (exam.indexOf('minor_') !== 0 && user_input[exam] !== undefined) {
				total_grade += user_input[exam] * weights[exam];
			}
		}
		total_grade = (total_grade / 100).toLocaleString(FSPHYS_LOCALE,
			{minimumFractionDigits: 2, maximumFractionDigits: 2});
		// write (possibly updated) weight information to DOM
		for (var exam in exams.weights) {
			var text_el = document.getElementById(ID_PREFIX + exam + '_weight');
			if(text_el !== null)
				text_el.textContent = exams.weights[exam];
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
	//document.getElementById(ID_PREFIX + 'version').textContent = DATE;
	//TODO document.getElementById(ID_PREFIX + 'er_version').textContent = ER_VERSION;
	// update on user input
	var gc_form = document.getElementById(ID_PREFIX + 'form');
	gc_form.addEventListener('input', update);
	// assign action to save button
	document.getElementById(ID_PREFIX + 'save').onclick = write_url_fragment;
	// run update to initialize input_data and for consistency
	update();
});

