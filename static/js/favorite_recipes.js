jQuery(function ($) {
	$(document).on('click', '.bookmark', function (event) {
		event.preventDefault();
		var button = $(this);
		var id = Number(button.data('recipe-id'));
		var buttons = $('.bookmark[data-recipe-id="' + id + '"]');
		if (buttons.is('[aria-busy="true"]')) return;
		buttons.attr('aria-busy', 'true');
		$.ajax({
			url: favorite_ajax.ajax_url, method: 'POST', dataType: 'json',
			data: { action: 'add_to_favorites', nonce: favorite_ajax.nonce, recipe_id: id, favorite: button.hasClass('added') ? 0 : 1 }
		}).done(function (response) {
			if (response.success) {
				buttons.toggleClass('added', response.data.favorite).attr('aria-pressed', String(response.data.favorite));
			}
		}).fail(function (xhr) {
			window.alert(xhr.responseJSON && xhr.responseJSON.data || 'Не удалось сохранить рецепт. Повторите попытку.');
		}).always(function () { buttons.removeAttr('aria-busy'); });
	});
});
