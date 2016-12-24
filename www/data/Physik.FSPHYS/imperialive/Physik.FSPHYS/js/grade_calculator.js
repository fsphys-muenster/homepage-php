document.addEventListener('DOMContentLoaded', function() {
	'use strict';

	let DATE = '2016-11-17';
	let ER_VERSION = 'WS 2016/2017';
	let ID_PREFIX = 'fsphys_gc_';
	let INPUT_FIELDS = [
		'physics_1', 'physics_2', 'physics_3',
		'math_2', 'math_3',
		'minor_1', 'minor_2', 'minor_3',
		'minor_1_weight', 'minor_2_weight', 'minor_3_weight',
		'quantum_mechanics', 'signal_processing',
		'structure_of_matter', 'lab_course_2',
		'statistical_physics', 'bachelor_thesis'
	]
	// initialize user input data
	let user_input = {minor: 0};
	INPUT_FIELDS.forEach(function(el) {
		user_input[el] = 0;
	});
	
	let exams = {
		weights: {
			minor:               12,
			quantum_mechanics:    7,
			signal_processing:    7,
			structure_of_matter: 12,
			lab_course_2:         9,
			statistical_physics: 10,
			bachelor_thesis:     10
		}
	};
	exams.update = function(user_input) {
		let weights = this.weights;
		// special cases: Physics I–III, Math II–III and the minor
		let physics_weights = this.best_of(
			['physics_1', 'physics_2', 'physics_3'], 2, 11, user_input);
		for (name in physics_weights) {
			weights[name] = physics_weights[name];
		}
		let math_weights = this.best_of(
			['math_2', 'math_3'], 1, 11, user_input);
		for (name in math_weights) {
			weights[name] = math_weights[name];
		}
		weights.minor_1 = user_input.minor_1_weight;
		weights.minor_2 = user_input.minor_2_weight;
		weights.minor_3 = user_input.minor_3_weight;
	};
	exams.best_of = function(exam_set, count, weight, user_input) {
		let weights = this.weights;
		// turn subset of user_input object into an array of key–value pairs
		// to be able to do sorting
		let grades = exam_set.map(function(key) {
			return {exam: key, grade: user_input[key]};
		});
		grades.sort(function(a, b) {
			return a.grade - b.grade;
		});
		let weights_set = {};
		grades.forEach(function(el, idx) {
			// exams up to entry number “count” get “weight”, others 0
			weights_set[el.exam] = idx < count ? weight : 0;
		});
		return weights_set;
	};
	
	function update() {
		// update user_input
		INPUT_FIELDS.forEach(function(field) {
			let input = document.getElementById(ID_PREFIX + field);
			user_input[field] = Number(input.value);
		});
		// update exam weights
		exams.update(user_input);
		// get minor grade and total grade using weights
		// divide by 100 because of percentages and round to 2 decimal places
		let weights = exams.weights;
		// minor
		let minor_grade = 0;
		for (let exam in weights) {
			if (exam.indexOf('minor_') === 0) {
				minor_grade += user_input[exam] * weights[exam];
			}
		}
		minor_grade /= 100;
		user_input.minor = minor_grade;
		minor_grade = minor_grade.toLocaleString(FSPHYS_LOCALE,
			{minimumFractionDigits: 2, maximumFractionDigits: 2});
		// total
		let total_grade = 0;
		for (let exam in weights) {
			if (exam.indexOf('minor_') !== 0) {
				total_grade += user_input[exam] * weights[exam];
			}
		}
		total_grade = (total_grade / 100).toLocaleString(FSPHYS_LOCALE,
			{minimumFractionDigits: 2, maximumFractionDigits: 2});
		// write (possibly updated) weight information to DOM
		for (let exam in exams.weights) {
			let text_el = document.getElementById(ID_PREFIX + exam + '_weight');
			text_el.textContent = exams.weights[exam];
		}
		// write calculated grades to DOM
		document.getElementById(ID_PREFIX + 'minor').textContent = minor_grade;
		document.getElementById(ID_PREFIX + 'total').textContent = total_grade;
	}
	
	// print version information
	document.getElementById(ID_PREFIX + 'version').textContent = DATE;
	document.getElementById(ID_PREFIX + 'er_version').textContent = ER_VERSION;
	// update on user input
	let gc_form = document.getElementById(ID_PREFIX + 'form');
	gc_form.addEventListener('input', update);
	update();
});

