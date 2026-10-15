jQuery(function ($) {
	$(window).on('YoastSEO:ready', function () {
		if (typeof CarbonFieldsYoast === 'function') new CarbonFieldsYoast();
	});
});
