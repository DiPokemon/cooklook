document.addEventListener('DOMContentLoaded', function () {
	var button = document.querySelector('.header_search');
	var overlay = document.querySelector('.search_modal_overlay');
	if (!button || !overlay) return;
	var previous;
	function close() {
		overlay.classList.remove('active');
		document.body.style.overflowY = '';
		document.body.style.paddingRight = '';
		if (previous) previous.focus();
	}
	button.addEventListener('click', function (event) {
		event.preventDefault();
		previous = document.activeElement;
		var filters = document.getElementById('filters');
		if (filters) filters.classList.remove('active');
		overlay.classList.add('active');
		document.body.style.overflowY = 'hidden';
		document.body.style.paddingRight = (window.innerWidth - document.documentElement.clientWidth) + 'px';
		var input = document.getElementById('ajax-search-input');
		if (input) input.focus();
	});
	overlay.querySelectorAll('#close_search, #close_search-mobile').forEach(function (element) { element.addEventListener('click', close); });
	overlay.addEventListener('click', function (event) { if (event.target === overlay) close(); });
	document.addEventListener('keydown', function (event) {
		if (!overlay.classList.contains('active')) return;
		if (event.key === 'Escape') close();
		if (event.key === 'Tab') {
			var controls = Array.from(overlay.querySelectorAll('a, button, input, select, textarea')).filter(function (element) { return element.offsetParent !== null && !element.disabled; });
			if (!controls.length) return;
			var first = controls[0], last = controls[controls.length - 1];
			if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
			else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
		}
	});
});
