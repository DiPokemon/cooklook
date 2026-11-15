jQuery(function ($) {
	var form = $('#editable-steps-form'), message = $('#manager-message');
	var publication = $('#manager-publication');
	var retryAction = $('#manager-retry-action'), restartAction = $('#manager-restart-action');
	var scope = window.location.pathname + ':' + cooklookManager.user_id;
	var publicationKey = 'cooklook-manager-publication:' + scope;
	var cursorKey = 'cooklook-manager-after-id:' + scope;
	var hasServerPublication = typeof cooklookManager.last_publication === 'string';
	var busy = false, loaded = false, afterId = 0;

	function showPublication(receipt) {
		publication.text(receipt).prop('hidden', !receipt);
	}

	if (hasServerPublication) showPublication(cooklookManager.last_publication);
	try {
		if (!hasServerPublication) showPublication(window.sessionStorage.getItem(publicationKey) || '');
		var storedAfterId = Number(window.sessionStorage.getItem(cursorKey));
		if (Number.isSafeInteger(storedAfterId) && storedAfterId > 0) afterId = storedAfterId;
	} catch (error) {
		// The current tab can still work when session storage is unavailable.
	}

	function rememberPosition(recipeId) {
		afterId = Number(recipeId);
		try {
			window.sessionStorage.setItem(cursorKey, String(afterId));
		} catch (error) {
			// The position remains available until this page is reloaded.
		}
	}

	function setBusy(value) {
		busy = value;
		form.find('button').prop('disabled', value);
		$('#save-steps, #skip-recipe').prop('disabled', value || !loaded);
	}
	function failure(xhr, fallback) { message.text(xhr.responseJSON && xhr.responseJSON.data || fallback); }
	function load() {
		if (busy) return;
		message.text('Загрузка черновика…');
		retryAction.prop('hidden', true);
		restartAction.prop('hidden', true);
		loaded = false;
		$('#current-recipe-id').val('');
		$('#recipe-title, #recipe-ingredients, #steps-table-body, #recipe_time').empty();
		$('#recipe-image').prop('hidden', true);
		setBusy(true);
		$.post(cooklookManager.ajax_url, { action: 'get_random_draft_recipe', nonce: cooklookManager.nonce, after_id: afterId }, null, 'json')
			.done(function (response) {
				if (response && response.success === false) { message.text(response.data); return; }
				var recipe = response && response.success === true && response.data;
				if (recipe && recipe.pass_complete) {
					message.text(recipe.message);
					restartAction.prop('hidden', false);
					return;
				}
				if (!recipe || !(Number(recipe.ID) > afterId)) {
					message.text('Не удалось подтвердить загрузку следующего черновика.');
					retryAction.prop('hidden', false);
					return;
				}
				loaded = true;
				message.empty();
				$('#current-recipe-id').val(recipe.ID);
				$('#recipe-title')
					.append($('<a>', { href: recipe.edit_link, target: '_blank', rel: 'noopener' }).text(recipe.title))
					.append($('<small>').text(' (ID ' + recipe.ID + ')'));
				$('#recipe-ingredients').text(recipe.ingredients.length ? 'Ингредиенты: ' + recipe.ingredients.join(', ') : 'Ингредиенты не указаны.');
				if (recipe.image) $('#recipe-image').attr('src', recipe.image).prop('hidden', false);
				var count = Math.max(recipe.steps.length, recipe.original_steps.length, 1);
				for (var index = 0; index < count; index++) {
					var row = $('<tr>');
					$('<td>').text(recipe.original_steps[index] || '').appendTo(row);
					$('<td>').append($('<textarea>', { name: 'steps_texts[]', rows: 5, required: true, 'aria-label': 'Шаг ' + (index + 1) }).val(recipe.steps[index] || '')).appendTo(row);
					$('#steps-table-body').append(row);
				}
				[['recipe_time', 'Время приготовления', recipe.time], ['recipe_prep', 'Время подготовки', recipe.prep]].forEach(function (field) {
					$('#recipe_time').append($('<label>').text(field[1] + ' (мин.) ').append($('<input>', { type: 'number', name: field[0], min: 0, max: 525600, required: true }).val(field[2])));
				});
				if (recipe.url) $('#recipe_time').append($('<a>', { href: recipe.url, target: '_blank', rel: 'noopener noreferrer' }).text('Оригинальный рецепт'));
			}).fail(function (xhr) {
				failure(xhr, 'Не удалось загрузить черновик.');
				if (xhr.status !== 401 && xhr.status !== 403) retryAction.prop('hidden', false);
			}).always(function () { setBusy(false); });
	}
	form.on('submit', function (event) {
		event.preventDefault();
		if (busy || !loaded || !this.reportValidity()) return;
		var recipeId = $('#current-recipe-id').val();
		var recipeTitle = $('#recipe-title a').text();
		var data = form.serializeArray();
		data.push({ name: 'action', value: 'save_recipe_steps' }, { name: 'nonce', value: cooklookManager.nonce });
		message.empty();
		setBusy(true);
		var saved = false;
		$.post(cooklookManager.ajax_url, data, null, 'json').done(function (response) {
			saved = response.success;
			if (!saved) {
				message.text(response.data);
				return;
			}
			var receipt = response.data + ' ID ' + recipeId + ' — ' + recipeTitle;
			showPublication(receipt);
			if (!hasServerPublication) {
				try {
					window.sessionStorage.setItem(publicationKey, receipt);
				} catch (error) {
					// The confirmation is already visible on the page.
				}
			}
			rememberPosition(recipeId);
			$('#manager-draft-count').text(Math.max(0, Number($('#manager-draft-count').text()) - 1));
			$('#manager-published-count').text(Number($('#manager-published-count').text()) + 1);
		}).fail(function (xhr) {
			failure(xhr, 'Не удалось подтвердить публикацию рецепта.');
		}).always(function () { setBusy(false); if (saved) load(); });
	});
	$('#skip-recipe').on('click', function () {
		if (busy || !loaded) return;
		rememberPosition($('#current-recipe-id').val());
		load();
	});
	$('#retry-recipe').on('click', load);
	$('#restart-recipe-pass').on('click', function () {
		if (busy) return;
		rememberPosition(0);
		load();
	});
	load();
});
