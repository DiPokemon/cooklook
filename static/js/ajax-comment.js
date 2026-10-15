jQuery(function ($) {
	var form = $('#commentform');
	if (!form.length) return;
	var busy = false;
	form.on('submit', function (event) {
		event.preventDefault();
		if (busy || !this.reportValidity()) return;
		busy = true;
		$('#comment-success-message, #comment-error-message').prop('hidden', true);
		var data = form.serializeArray();
		data.push({ name: 'action', value: 'ajaxcomments' }, { name: 'nonce', value: ajax_comment_params.nonce });
		form.find('[type="submit"]').prop('disabled', true);
		$.ajax({ url: ajax_comment_params.ajaxurl, method: 'POST', dataType: 'json', data: data })
			.done(function (response) {
				if (!response.success) {
					$('#comment-error-message').text(response.data).prop('hidden', false);
					return;
				}
				if (response.data.approved && response.data.url) {
					// Let WordPress position replies and paginate the approved comment correctly.
					var commentUrl = new URL(response.data.url, window.location.href);
					var samePage = commentUrl.href.split('#')[0] === window.location.href.split('#')[0];
					window.location.assign(commentUrl.href);
					// A fragment-only navigation does not fetch the newly saved comment.
					if (samePage) window.location.reload();
					return;
				}
				form.find('#comment').val('');
				$('#cancel-comment-reply-link').trigger('click');
				$('#comment-success-message').text(response.data.message).prop('hidden', false);
			}).fail(function (xhr) {
				$('#comment-error-message').text(xhr.responseJSON && xhr.responseJSON.data || 'Не удалось отправить комментарий. Повторите попытку.').prop('hidden', false);
			}).always(function () { busy = false; form.find('[type="submit"]').prop('disabled', false); });
	});
});
