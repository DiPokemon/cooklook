jQuery(function ($) {
	var blocks = $('.like_dislike_block');
	function render(block, data) {
		block.find('.like_btn').toggleClass('active', data.user_vote === 'like').attr('aria-pressed', String(data.user_vote === 'like'));
		block.find('.dislike_btn').toggleClass('active', data.user_vote === 'dislike').attr('aria-pressed', String(data.user_vote === 'dislike'));
		if (typeof data.rating !== 'undefined') {
			block.find('.recipe_rating').text(data.rating);
			$('.recipe_meta .rating .meta-value').text(data.rating);
		}
	}
	blocks.each(function () {
		var block = $(this);
		var id = block.find('.like_btn').attr('id').split('-')[1];
		block.find('button').prop('disabled', true);
		$.post(rating.ajax_url, { action: 'check_user_vote', nonce: rating.nonce, recipe_id: id }, null, 'json')
			.done(function (response) { if (response.success) render(block, response.data); })
			.always(function () { block.find('button').prop('disabled', false); });
	});
	blocks.on('click', '.like_btn, .dislike_btn', function () {
		var button = $(this), block = button.closest('.like_dislike_block');
		var vote = button.hasClass('like_btn') ? 'like' : 'dislike';
		if (button.hasClass('active')) vote = 'remove_' + vote;
		block.find('button').prop('disabled', true);
		$.ajax({
			url: rating.ajax_url, method: 'POST', dataType: 'json',
			data: { action: 'update_user_vote', nonce: rating.nonce, recipe_id: button.attr('id').split('-')[1], vote_type: vote }
		}).done(function (response) {
			if (response.success) render(block, response.data);
		}).fail(function (xhr) {
			window.alert(xhr.responseJSON && xhr.responseJSON.data || 'Не удалось сохранить голос. Повторите попытку.');
		}).always(function () { block.find('button').prop('disabled', false); });
	});
});
