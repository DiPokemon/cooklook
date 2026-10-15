jQuery(function ($) {
	function equalHeight() {
		$('.recipes_grid').each(function () {
			var grid = $(this);
			['.recipe_title', '.recipe_desc', '.recipe_ingridients'].forEach(function (selector) {
				var elements = grid.find('.recipe_loop-content ' + selector).css('height', '');
				if (window.innerWidth <= 768) return;
				var height = 0;
				elements.each(function () { height = Math.max(height, $(this).height()); });
				elements.height(height);
			});
		});
	}
	var queued = false;
	function scheduleHeight() {
		if (queued) return;
		queued = true;
		window.requestAnimationFrame(function () { queued = false; equalHeight(); });
	}
	scheduleHeight();
	$(window).on('resize load', scheduleHeight);
	$(document).on('cooklook:recipes-updated', scheduleHeight);
	$(document).on('click', '.show_more', function () {
		$(this).parent().find('.hidden').removeClass('hidden').addClass('visible');
		$(this).hide();
	});
	$(document).on('click', '.img_block[data-permalink]', function (event) {
		if ($(event.target).closest('a, button, input, select').length) return;
		window.location.assign($(this).attr('data-permalink'));
	});
	var header = document.querySelector('.site-header');
	if (header) window.addEventListener('scroll', function () {
		header.classList.toggle('fixed', window.scrollY > 100);
	}, { passive: true });
});
