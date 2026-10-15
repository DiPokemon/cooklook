jQuery(function ($) {
	var form = $('#editable-steps-form'), message = $('#manager-message');
	var busy = false, loaded = false;
	function setBusy(value) {
		busy = value;
		form.find('button').prop('disabled', value);
		$('#save-steps').prop('disabled', value || !loaded);
	}
	function failure(xhr) { message.text(xhr.responseJSON && xhr.responseJSON.data || 'Не удалось выполнить запрос. Повторите попытку.'); }
	function load(preserveMessage) {
		if (busy) return;
		var savedMessage = preserveMessage ? message.text() : '';
		message.text(savedMessage);
		loaded = false;
		$('#current-recipe-id').val('');
		$('#recipe-title, #recipe-ingredients, #steps-table-body, #recipe_time').empty();
		$('#recipe-image').prop('hidden', true);
		setBusy(true);
		$.post(cooklookManager.ajax_url, { action: 'get_random_draft_recipe', nonce: cooklookManager.nonce }, null, 'json')
			.done(function (response) {
				if (!response.success) { message.text((savedMessage ? savedMessage + ' ' : '') + response.data); return; }
				var recipe = response.data;
				loaded = true;
				message.text(savedMessage);
				$('#current-recipe-id').val(recipe.ID);
				$('#recipe-title').append($('<a>', { href: recipe.edit_link, target: '_blank', rel: 'noopener' }).text(recipe.title));
				$('#recipe-ingredients').text('Ингредиенты: ' + recipe.ingredients.join(', '));
				if (recipe.image) $('#recipe-image').attr('src', recipe.image).prop('hidden', false);
				var count = Math.max(recipe.steps.length, recipe.original_steps.length, 1);
				for (var index = 0; index < count; index++) {
					var row = $('<tr>');
					$('<td>').text(recipe.original_steps[index] || '').appendTo(row);
					$('<td>').append($('<textarea>', { name: 'steps_texts[]', rows: 5, required: true, css: { width: '100%' } }).val(recipe.steps[index] || '')).appendTo(row);
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
		var data = form.serializeArray();
		data.push({ name: 'action', value: 'save_recipe_steps' }, { name: 'nonce', value: cooklookManager.nonce });
		setBusy(true);
		var saved = false;
		$.post(cooklookManager.ajax_url, data, null, 'json').done(function (response) {
			saved = response.success;
			message.text(response.data);
			if (saved) {
				$('#manager-draft-count').text(Math.max(0, Number($('#manager-draft-count').text()) - 1));
				$('#manager-published-count').text(Number($('#manager-published-count').text()) + 1);
			}
		}).fail(failure).always(function () { setBusy(false); if (saved) load(true); });
	});
	$('#skip-recipe').on('click', function () { load(); });
	load();
});
