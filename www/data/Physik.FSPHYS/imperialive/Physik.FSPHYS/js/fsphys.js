document.addEventListener('DOMContentLoaded', function() {
	// remove "index.html" or "index.php" as well as file endings in all internal
	// URLs (since they are redirected on the server side to the "clean" versions)
	// anyway
	function clean_links(list) {
		var re = /^(https?:[/][/](www|sso)\.(uni-muenster|wwu)\.de)?[/]Physik.FSPHYS[/](.*?)(index)?\.(html|php)$/;
		for (var i = 0; i < list.length; i++) {
			var l = list[i];
			if (l.tagName.toLowerCase() != 'a') {
				continue;
			}
			var new_href = l.href.replace(re, '/Physik.FSPHYS/$4');
			if (l.href != new_href) {
				l.href = new_href;
			}
		}
	}
	var all_links = document.getElementsByTagName('a');
	clean_links(all_links);
	// since the navigation is dynamically loaded, check for DOM changes and clean
	// any new URLs too
	var observer = new MutationObserver(function(mutations) {
		mutations.forEach(function(mutation) {
			var new_links = mutation.target.getElementsByTagName('a');
			clean_links(new_links);
		});    
	});
	observer.observe(document.body, {childList: true, subtree: true});
});


