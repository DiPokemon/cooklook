jQuery(function ($) {
	$('.cooklook-ads-card').each(function () {
		var card = $(this), frame;
		function updateType() {
			var type = card.find('[data-ad-type]').val();
			card.find('[data-ad-fields]').each(function () {
				this.hidden = this.getAttribute('data-ad-fields') !== type;
			});
		}
		function setImage(source, id, url) {
			card.find('[data-ad-image-source]').val(source);
			card.find('[data-ad-image-id]').val(id);
			var preview = card.find('[data-ad-preview]');
			if (url) preview.attr('src', url);
			else preview.removeAttr('src');
			preview.prop('hidden', !url);
			card.find('[data-ad-empty]').prop('hidden', !!url);
		}
		card.find('[data-ad-type]').on('change', updateType);
		updateType();
		card.find('[data-ad-choose]').on('click', function () {
			if (!frame) {
				frame = wp.media({ title: cooklookAdsAdmin.title, button: { text: cooklookAdsAdmin.button }, library: { type: 'image' }, multiple: false });
				frame.on('select', function () {
					var selected = frame.state().get('selection').first();
					if (!selected) return;
					var attachment = selected.toJSON();
					var preview = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
					setImage('media', attachment.id, preview);
					var alt = card.find('input[name$="[alt]"]');
					if (!alt.val() && attachment.alt) alt.val(attachment.alt);
				});
				frame.on('open', function () {
					var selection = frame.state().get('selection');
					selection.reset();
					var id = Number(card.find('[data-ad-image-id]').val());
					if (id) {
						var attachment = wp.media.attachment(id);
						attachment.fetch();
						selection.add(attachment);
					}
				});
			}
			frame.open();
		});
		card.find('[data-ad-remove]').on('click', function () { setImage('none', 0, ''); });
		card.find('[data-ad-default]').on('click', function () { setImage('theme', 0, card.attr('data-default-image')); });
	});
});
