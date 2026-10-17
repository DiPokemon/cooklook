jQuery(function ($) {
	var form = $('#recipe-filter');
	if (!form.length) return;
	var pending, subcategories, requestNumber = 0;
	var filters = filter_obj.filters;
	var error = $('<p role="alert" hidden></p>').insertBefore('#response');
	$('.filter_select').select2({
		theme: 'filters_select', width: '100%', dropdownParent: $('#filters'),
		language: { noResults: function () { return 'Ничего не найдено'; } }
	});

	function values() {
		function ingredients(selector) {
			return ($('#' + selector).val() || []).filter(function (item, index, list) {
				return typeof item === 'string' && item !== '' && list.indexOf(item) === index;
			}).sort();
		}
		return {
			category_id: $('#recipe_category').val() || 'all',
			subcategory_id: $('#recipe_subcategory').val() || 'all',
			region: $('#recipe_region').val() || '',
			include_ingredients: ingredients('include_ingredients'),
			exclude_ingredients: ingredients('exclude_ingredients'),
			sort_by: $('#sort_by').val() || 'date'
		};
	}
	function pageURL(state, page) {
		var url = new URL(filter_obj.base_url, window.location.href);
		var keys = ['recipe_category', 'recipe_subcategory', 'recipe_region', 'category_id', 'subcategory_id', 'region', 'sort_by', 'paged', 'page', 'include_ingredients', 'exclude_ingredients'];
		// PHP serializes [] as [0], [1], etc. Remove every form before rebuilding.
		Array.from(url.searchParams.keys()).forEach(function (key) {
			if (keys.indexOf(key.split('[')[0]) !== -1) url.searchParams.delete(key);
		});
		Object.keys(state).forEach(function (key) {
			var value = state[key];
			if (Array.isArray(value)) value.forEach(function (item) { url.searchParams.append(key + '[]', item); });
			else if (value && value !== 'all' && value !== '0' && !(key === 'sort_by' && value === 'date')) url.searchParams.set(key, value);
		});
		if (page > 1) url.searchParams.set('paged', page);
		Object.keys(filter_obj.route_query || {}).forEach(function (key) {
			url.searchParams.set(key, filter_obj.route_query[key]);
		});
		// PHP supplies the permalink pattern, including subdirectory/index.php settings.
		if (filter_obj.pagination) {
			url.searchParams.delete('paged');
			var pattern = filter_obj.pagination;
			var target = new URL(pattern.base.replace('%_%', page > 1 ? pattern.format.replace('%#%', page) : ''), window.location.href);
			url.pathname = target.pathname;
			if (target.searchParams.has('paged')) url.searchParams.set('paged', target.searchParams.get('paged'));
		}
		return url;
	}
	function load(page) {
		page = Math.max(1, Math.floor(Number(page)) || 1);
		var state = values(), url = pageURL(state, page), sequence = ++requestNumber;
		if (pending) pending.abort();
		error.prop('hidden', true);
		$('#response').attr('aria-busy', 'true');
		pending = $.ajax({
			url: filter_obj.ajax_url, dataType: 'json',
			data: $.extend({}, state, {
				action: 'filter_recipes', nonce: filter_obj.nonce, paged: page,
				current_category: filter_obj.current_category, current_tag: filter_obj.current_tag,
				page_url: url.href
			})
		}).done(function (response) {
			if (sequence !== requestNumber) return;
			if (response.success) {
				$('#response').html(response.data.html).addClass('filtred');
				$('.pagination').html(response.data.pagination || '');
				window.history.pushState(null, '', url.href);
				if (filter_obj.page_metadata) {
					var metadata = filter_obj.page_metadata[page > 1 ? 'paged' : 'first'];
					if (metadata.title) document.title = metadata.title.replace('%page%', page);
					if (metadata.description) $('meta[name="description"]').attr('content', metadata.description.replace('%page%', page));
					$('.page-header__breadcrumbs').replaceWith(metadata.breadcrumbs);
					if (page > 1) {
						$('.page-header__breadcrumbs .breadcrumbs__current').text(filter_obj.page_metadata.page_label.replace('%s', page));
						$('.page-header__breadcrumbs .breadcrumbs__link').last().attr('href', pageURL(state, 1).href);
					}
				}
				$('.archive_description').prop('hidden', page > 1);
				$(document).trigger('cooklook:recipes-updated');
			} else error.text(response.data || 'Не удалось загрузить рецепты.').prop('hidden', false);
		}).fail(function (xhr, status) {
			if (status !== 'abort') error.text(xhr.responseJSON && xhr.responseJSON.data || 'Не удалось загрузить рецепты. Повторите попытку.').prop('hidden', false);
		}).always(function () { if (sequence === requestNumber) $('#response').removeAttr('aria-busy'); });
	}
	function loadSubcategories(selected) {
		var category = $('#recipe_category').val();
		var select = $('#recipe_subcategory');
		if (subcategories) subcategories.abort();
		select.empty().append(new Option('Любое блюдо', 'all')).prop('disabled', true).trigger('change.select2');
		if (!category || category === 'all') return;
		subcategories = $.getJSON(filter_obj.ajax_url, { action: 'get_subcategories', nonce: filter_obj.nonce, category_id: category })
			.done(function (response) {
				if (response.success) select.html(response.data.options).val(String(selected || 'all')).prop('disabled', false).trigger('change.select2');
			}).fail(function (xhr, status) {
				if (status !== 'abort') error.text('Не удалось загрузить подкатегории. Повторите выбор категории.').prop('hidden', false);
			});
	}
	$('#recipe_category').val(String(filters.category_id || 'all')).trigger('change.select2');
	$('#recipe_region').val(filters.region).trigger('change.select2');
	$('#include_ingredients').val(filters.include_ingredients).trigger('change');
	$('#exclude_ingredients').val(filters.exclude_ingredients).trigger('change');
	$('#sort_by, #sort_by-mobile').val(filters.sort_by).trigger('change.select2');
	$('#recipe_subcategory').val(String(filters.subcategory_id || 'all')).trigger('change.select2');
	$('#recipe_category').on('change', function () { loadSubcategories(); });
	form.on('submit', function (event) {
		event.preventDefault();
		$('#include_ingredients, #exclude_ingredients, .filter_select').select2('close');
		$('.ingridients_modal_overlay, #filters').removeClass('active');
		$('body').css({ overflowY: '', paddingRight: '' });
		$('.site-header').css('paddingRight', '');
		load(1);
	});
	$('#sort_by, #sort_by-mobile').on('change', function () {
		$('#sort_by, #sort_by-mobile').val($(this).val() || 'date').trigger('change.select2');
		load(1);
	});
	$(document).on('click', '.pagination a', function (event) {
		event.preventDefault();
		var url = new URL(this.href);
		var parts = url.pathname.replace(/\/$/, '').split('/');
		var page = parts[parts.length - 2] === (filter_obj.pagination_base || 'page') ? parts[parts.length - 1] : 1;
		load(url.searchParams.get('paged') || page);
	});
	// Server rendering restores both controls and results on browser history navigation.
	window.addEventListener('popstate', function () { window.location.reload(); });
});
