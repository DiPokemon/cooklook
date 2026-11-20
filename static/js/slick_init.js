jQuery(function ($) {
	$('.seasonal_recipes').not('.slick-initialized').each(function () {
		var recipes = $(this);
		var controls = recipes.closest('.seasonal_collection').find('.collection_slider-controls');
		recipes.on('init reInit afterChange breakpoint', function (event, slick) {
			controls.prop('hidden', slick.slideCount <= slick.options.slidesToShow);
			controls.find('.collection_slider-count').text((slick.currentSlide + 1) + ' / ' + slick.slideCount);
		});
		recipes.slick({
			infinite: false, autoplay: false, adaptiveHeight: true, dots: false, arrows: true, slidesToShow: 3, slidesToScroll: 1,
			prevArrow: controls.find('.collection_slider-prev'), nextArrow: controls.find('.collection_slider-next'),
			speed: window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 300,
			responsive: [{ breakpoint: 1024, settings: { slidesToShow: 2 } }, { breakpoint: 768, settings: { slidesToShow: 1 } }]
		});
	});
	$('.related_recipes.recipes_slider').not('.slick-initialized').slick({
		infinite: true, autoplay: true, dots: false, arrows: true, slidesToShow: 3, slidesToScroll: 1,
		responsive: [{ breakpoint: 1024, settings: { slidesToShow: 2 } }, { breakpoint: 768, settings: { slidesToShow: 1 } }]
	});
	var slider = $('.categories_grid');
	if (!slider.length) return;
	slider.on('init afterChange', function (event, slick) {
		$('#categories_grid_slider-counter').empty().append($('<span>', { class: 'count_current' }).text(slick.currentSlide + 1), ' / ', $('<span>', { class: 'count_total' }).text(slick.slideCount));
	});
	function resize() {
		if (window.innerWidth < 1024 && !slider.hasClass('slick-initialized')) {
			slider.slick({
				slidesToShow: 3, slidesToScroll: 1, autoplay: true, autoplaySpeed: 3000, arrows: true,
				prevArrow: $('.categories_grid-controls-prev'), nextArrow: $('.categories_grid-controls-next'),
				responsive: [{ breakpoint: 768, settings: { slidesToShow: 2 } }]
			});
		} else if (window.innerWidth >= 1024 && slider.hasClass('slick-initialized')) {
			slider.slick('unslick');
		}
	}
	resize();
	$(window).on('resize', resize);
});
