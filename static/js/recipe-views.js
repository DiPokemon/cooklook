(function () {
	'use strict';
	if (typeof cooklookRecipeView === 'undefined' || !window.fetch) return;
	var config = cooklookRecipeView;
	// The same cached configuration can be shared by visitors. Only page-local state
	// prevents duplicate sends; no cookies, storage identifiers or fingerprinting.
	if (config.started) return;
	config.started = true;
	var sent = false;
	var loaded = document.readyState === 'complete';
	var endpoint;
	try {
		endpoint = new URL(config.ajax_url, window.location.href);
		if (endpoint.origin !== window.location.origin) return;
	} catch (error) { return; }

	function send(nonce, mayRefresh) {
		return window.fetch(endpoint.href, {
			method: 'POST', credentials: 'same-origin', mode: 'same-origin', cache: 'no-store', keepalive: true,
			body: new URLSearchParams({ action: 'cooklook_record_recipe_view', recipe_id: config.recipe_id, nonce: nonce })
		}).then(function (response) {
			// Never retry an ambiguous network failure or a write/lock error: it might double count.
			if (response.status !== 403 || !mayRefresh) return;
			return response.json().then(function (result) {
				if (result.success === false && result.data && result.data.code === 'invalid_nonce' && typeof result.data.nonce === 'string') {
					return send(result.data.nonce, false);
				}
			});
		});
	}
	function countWhenVisible() {
		if (sent || !loaded || document.visibilityState !== 'visible' || document.prerendering) return;
		sent = true;
		document.removeEventListener('visibilitychange', countWhenVisible);
		document.removeEventListener('prerenderingchange', countWhenVisible);
		send(config.nonce, true).catch(function () { /* Counting must not affect the page. */ });
	}
	document.addEventListener('visibilitychange', countWhenVisible);
	document.addEventListener('prerenderingchange', countWhenVisible);
	if (loaded) countWhenVisible();
	else window.addEventListener('load', function () { loaded = true; countWhenVisible(); }, { once: true });
}());
