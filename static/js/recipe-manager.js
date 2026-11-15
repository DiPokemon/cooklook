jQuery(function ($) {
	var form = $('#editable-steps-form'), message = $('#manager-message');
	var publication = $('#manager-publication');
	var publicationKey = 'cooklook-manager-publication:' + window.location.pathname + ':' + cooklookManager.user_id;
	var busy = false, loaded = false;

	function showPublication(receipt) {
		publication.text(receipt).prop('hidden', !receipt);
	}

	try {
		showPublication(window.sessionStorage.getItem(publicationKey) || '');
	} catch (error) {
		// The page can still show confirmations when session storage is unavailable.
	}

	function setBusy(value) {
		busy = value;
		form.find('button').prop('disabled', value);
		$('#save-steps').prop('disabled', value || !loaded);
	}
	function failure(xhr) { message.text(xhr.responseJSON && xhr.responseJSON.data || 'Не удалось выполнить запрос. Повторите попытку.'); }
	function load() {
		if (busy) return;
		message.empty();
		loaded = false;
		$('#current-recipe-id').val('');
		$('#recipe-title, #recipe-ingredients, #steps-table-body, #recipe_time').empty();
		$('#recipe-image').prop('hidden', true);
		setBusy(true);
		$.post(cooklookManager.ajax_url, { action: 'get_random_draft_recipe', nonce: cooklookManager.nonce }, null, 'json')
			.done(function (response) {
				if (!response.success) { message.text(response.data); return; }
				var recipe = response.data;
				loaded = true;
				$('#current-recipe-id').val(recipe.ID);
				$('#recipe-title')
					.append($('<a>', { href: recipe.edit_link, target: '_blank', rel: 'noopener' }).text(recipe.title))
					.append($('<small>').text(' (ID ' + recipe.ID + ')'));
				$('#recipe-ingredients').text('Ингредиенты: ' + recipe.ingredients.join(', '));
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
			}).fail(failure).always(function () { setBusy(false); });
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
			try {
				window.sessionStorage.setItem(publicationKey, receipt);
			} catch (error) {
				// The confirmation is already visible on the page.
			}
			$('#manager-draft-count').text(Math.max(0, Number($('#manager-draft-count').text()) - 1));
			$('#manager-published-count').text(Number($('#manager-published-count').text()) + 1);
		}).fail(failure).always(function () { setBusy(false); if (saved) load(); });
	});
	$('#skip-recipe').on('click', function () { load(); });
	load();
});
