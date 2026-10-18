/* One Yandex loader and unique containers, including catalog AJAX replacements. */
jQuery(function ($) {
	var sequence = 0;
	var loaderRequested = false;

	function managerReady() {
		return window.Ya && window.Ya.Context && window.Ya.Context.AdvManager && typeof window.Ya.Context.AdvManager.render === 'function';
	}

	function loadYandex() {
		if (loaderRequested || managerReady()) return;
		loaderRequested = true;
		// Reuse a loader previously added through the theme's HEAD/FOOTER fields.
		var existing = Array.prototype.some.call(document.scripts, function (script) {
			return /\/\/yandex\.(ru|com)\/ads\/system\/context\.js(?:[?#]|$)/i.test(script.src);
		});
		if (existing) return;
		var script = document.createElement('script');
		script.src = 'https://yandex.ru/ads/system/context.js';
		script.async = true;
		document.head.appendChild(script);
	}

	function renderAds() {
		var found = false;
		document.querySelectorAll('[data-cooklook-yandex]').forEach(function (element) {
			var blockId = element.getAttribute('data-cooklook-yandex');
			if (element.hasAttribute('data-cooklook-queued') || !/^R-[AI]-[0-9]+-[0-9]+$/.test(blockId)) return;
			found = true;
			element.setAttribute('data-cooklook-queued', '1');
			var id;
			do { id = 'cooklook-rsya-' + (++sequence); } while (document.getElementById(id));
			element.id = id;
			var render = function () {
				// A fast filter change may remove the queued container before the SDK arrives.
				if (!document.documentElement.contains(element) || !managerReady()) return;
				window.Ya.Context.AdvManager.render({ blockId: blockId, renderTo: id });
			};
			if (managerReady()) render();
			else (window.yaContextCb = window.yaContextCb || []).push(render);
		});
		if (found) loadYandex();
	}

	renderAds();
	$(document).on('cooklook:recipes-updated', renderAds);
});
