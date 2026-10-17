jQuery(function ($) {
	if (!$('#recipe-filter').length) return;
	var ingredientFields = $('#include_ingredients, #exclude_ingredients');
	ingredientFields.each(function () {
		var field = $(this);
		field.select2({
			width: '100%', closeOnSelect: false, dropdownParent: $(document.body),
			dropdownCssClass: 'cooklook-ingredients-dropdown',
			placeholder: 'Введите название ингредиента',
			minimumInputLength: 2, maximumInputLength: 100, maximumSelectionLength: 20,
			ajax: {
				url: cooklookIngredients.ajax_url, dataType: 'json', delay: 300, timeout: 15000,
				data: function (params) {
					return { action: 'cooklook_search_ingredients', nonce: cooklookIngredients.nonce, term: params.term || '', ingredient_page: params.page || 1 };
				},
				transport: function (options, success, failure) {
					function current() { return field.next('.select2').find('.select2-search__field').val() === options.data.term; }
					// Select2's length guards skip its AJAX cancellation. Ignore a delayed query
					// or response if the reader has shortened/cleared the input or selected a term.
					if (!current()) return { abort: function () {}, status: 0 };
					var request = $.ajax(options);
					request.done(function (response) { if (current()) success(response); });
					request.fail(function () { if (current()) failure(); });
					return request;
				},
				processResults: function (response) { return response.data; }
			},
			language: {
				noResults: function () { return 'Ничего не найдено. Уточните название.'; },
				inputTooShort: function () { return 'Введите не менее 2 символов'; },
				inputTooLong: function () { return 'Введите не более 100 символов'; },
				maximumSelected: function () { return 'Можно выбрать не более 20 ингредиентов'; },
				searching: function () { return 'Поиск ингредиентов…'; },
				loadingMore: function () { return 'Загрузка ингредиентов…'; },
				errorLoading: function () { return 'Не удалось загрузить ингредиенты. Повторите поиск или обновите страницу.'; }
			}
		});
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
		if (selected.indexOf(slug) === -1 && selected.length < 20) {
			// Remote results create options only on selection; popular choices need one too.
			var exists = select.find('option').filter(function () { return this.value === slug; }).length;
			if (!exists) select.append(new Option($(this).text().trim(), slug, false, false));
			select.val(selected.concat(slug)).trigger('change');
		}
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
