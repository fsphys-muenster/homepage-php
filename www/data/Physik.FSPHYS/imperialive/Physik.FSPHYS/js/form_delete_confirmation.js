document.addEventListener('DOMContentLoaded', function() {
	let save_submitter = function(event) {
		this.form.submitActor = this;
	}
	let buttons = document.querySelectorAll(
		'form.fsphys input[type=submit], form.fsphys button'
	);
	for (let i = 0; i < buttons.length; i++) {
		buttons[i].onclick = save_submitter;
		buttons[i].onkeypress = save_submitter;
	}
	let forms = document.querySelectorAll('form.fsphys');
	for (let i = 0; i < forms.length; i++) {
		forms[i].onsubmit = function() {
			let submitActor = this.submitActor;
			if (submitActor && submitActor.matches('.fsphys_delete')) {
				// DELETE_CONFIRMATION_DIALOG must be defined
				return window.confirm(DELETE_CONFIRMATION_DIALOG);
			}
		}
	}
});

