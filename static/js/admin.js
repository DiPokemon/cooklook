var $doc = $(document);
var $win = $(window);

$doc.ready(function () {
	$win.on('YoastSEO:ready', function () {
		new CarbonFieldsYoast();
	});
});