jQuery(function ($) {
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
