document.addEventListener('DOMContentLoaded', function () {
	var input = document.getElementById('ajax-search-input');
	var results = document.getElementById('search_results');
	var popular = document.getElementById('popular_queries');
	if (!input || !results) return;
	var timer, controller, sequence = 0;
	function search() {
		var term = input.value.trim(), current = ++sequence;
		if (controller) controller.abort();
		if (term.length < 2) {
			results.textContent = '';
			if (popular) popular.hidden = false;
			return;
		}
		controller = new AbortController();
		results.setAttribute('aria-busy', 'true');
		fetch(cooklookSearch.ajax_url, {
			method: 'POST', signal: controller.signal, credentials: 'same-origin',
			body: new URLSearchParams({ action: 'ajax_search', nonce: cooklookSearch.nonce, search: term })
		}).then(function (response) {
			if (!response.ok) throw new Error('search');
			return response.text();
		}).then(function (html) {
			if (current !== sequence) return;
			results.innerHTML = html;
			if (popular) popular.hidden = true;
		}).catch(function (error) {
			if (error.name !== 'AbortError' && current === sequence) results.textContent = 'Не удалось выполнить поиск. Повторите попытку.';
		}).finally(function () { if (current === sequence) results.removeAttribute('aria-busy'); });
	}
	input.addEventListener('input', function () {
		clearTimeout(timer);
		sequence++;
		if (controller) controller.abort();
		timer = setTimeout(search, 350);
	});
	if (popular) popular.addEventListener('click', function (event) {
		var link = event.target.closest('.popular_search_item');
		if (!link) return;
		event.preventDefault();
		clearTimeout(timer);
		input.value = link.textContent;
		search();
	});
});
