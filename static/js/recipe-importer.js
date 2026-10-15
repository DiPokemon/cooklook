jQuery(function ($) {
	var form = $('#recipe-import-form'), output = $('#import-progress'), busy = false;
	form.on('submit', function (event) {
		event.preventDefault();
		if (busy || !this.reportValidity()) return;
		busy = true;
		var source = this, offset = 0, updated = 0, skipped = 0, errors = [];
		var file = source.querySelector('[name="csv_file"]').files[0];
		var nonce = source.querySelector('[name="recipe_import_nonce"]').value;
		form.find('input, button').prop('disabled', true);
		function finish() { busy = false; form.find('input, button').prop('disabled', false); }
		function batch() {
			var data = new FormData();
			data.append('csv_file', file);
			data.append('recipe_import_nonce', nonce);
			data.append('action', 'import_recipes');
			data.append('offset', offset);
			$.ajax({ url: ajaxurl, method: 'POST', data: data, processData: false, contentType: false, dataType: 'json' })
				.done(function (response) {
					if (!response.success) { output.text(response.data); finish(); return; }
					updated += response.data.updated;
					skipped += response.data.skipped;
					errors = errors.concat(response.data.errors);
					offset = response.data.next_offset;
					output.empty().append($('<p>').text('Обработано: ' + offset + ' из ' + response.data.total + '. Обновлено: ' + updated + '. Пропущено: ' + skipped + '.'));
					if (response.data.done) {
						errors.forEach(function (error) { output.append($('<p>').text(error)); });
						finish();
					} else batch();
				}).fail(function (xhr) {
					output.append($('<p>').text(xhr.responseJSON && xhr.responseJSON.data || 'Импорт прерван. Часть строк могла сохраниться; повторный импорт продолжит обновление по recipe_id.'));
					finish();
				});
		}
		batch();
	});
});
