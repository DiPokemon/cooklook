jQuery(function ($) {
	if (!$('#recipe-filter').length) return;
	var ingredientFields = $('#include_ingredients, #exclude_ingredients');
	ingredientFields.select2({
		width: '100%', closeOnSelect: false, dropdownParent: $(document.body),
		dropdownCssClass: 'cooklook-ingredients-dropdown',
		placeholder: 'Выберите ингредиенты',
		language: { noResults: function () { return 'Ничего не найдено'; } }
	});
	ingredientFields.on('select2:open', function () {
		ingredientFields.not(this).select2('close');
		// Keep Select2's positioning, but escape the scrolling modal's clipping area.
		$('.cooklook-ingredients-dropdown').closest('.select2-container')
			.addClass('cooklook-ingredients-dropdown-layer');
	});
	$('.popular_ingridients-item').on('click', function (event) {
		event.preventDefault();
		var select = $('#include_ingredients'), slug = String($(this).data('ingredient'));
		var selected = select.val() || [];
		if (selected.indexOf(slug) === -1) select.val(selected.concat(slug)).trigger('change');
	});
	$('.clear_ingridients').on('click', function (event) {
		event.preventDefault();
		$('#include_ingredients, #exclude_ingredients').val([]).trigger('change');
	});
	function close() {
		$('#include_ingredients, #exclude_ingredients').select2('close');
		$('.filter_select').select2('close');
		$('.ingridients_modal_overlay, #filters').removeClass('active');
		$('body').css({ overflowY: '', paddingRight: '' });
	}
	function closeIngredients() {
		$('#include_ingredients, #exclude_ingredients').select2('close');
		$('.ingridients_modal_overlay').removeClass('active');
		if (!$('#filters').hasClass('active')) $('body').css({ overflowY: '', paddingRight: '' });
		$('.ingridients_btn').trigger('focus');
	}
	$('.ingridients_btn').on('click', function (event) {
		event.preventDefault();
		$('.search_modal_overlay').removeClass('active');
		$('.ingridients_modal_overlay').addClass('active');
		$('body').css('overflowY', 'hidden');
	});
	$('#mobile_filters-open').on('click', function () { $('#filters').addClass('active'); $('body').css('overflowY', 'hidden'); });
	$('#close_ingridients, #close_ingridients-mobile').on('click', function (event) { event.preventDefault(); closeIngredients(); });
	$('#close_filter-mobile').on('click', function (event) { event.preventDefault(); close(); $('#mobile_filters-open').trigger('focus'); });
	$('.ingridients_modal_overlay').on('click', function (event) { if (event.target === this) closeIngredients(); });
	$(document).on('keydown', function (event) {
		if (event.key !== 'Escape') return;
		if ($('.ingridients_modal_overlay').hasClass('active')) closeIngredients();
		else close();
	});
});
