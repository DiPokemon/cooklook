$(document).ready(function() {
    // Инициализация Select2 для селектов с классом .filter_select
    $('.filter_select').select2({
        theme: 'filters_select',
        language: {
            noResults: function() {
                var lang = $('html').attr('lang'); // Получаем текущий язык страницы
                return lang === 'ru-RU' ? "Нет результатов" : "No results found";
            }
        }
    });

    // Обработчик изменения выбора категории
    $('#recipe_category').on('change', function() {
        var selectedCategoryId = $(this).val();
        var subcategorySelect = $('#recipe_subcategory');

        if (selectedCategoryId === '' || selectedCategoryId === 'all') {
            // Если категория не выбрана, делаем подкатегорию неактивной и очищаем ее
            subcategorySelect.prop('disabled', true).empty().append('<option value="">Выберите подкатегорию</option>');
        } else {
            // Иначе делаем подкатегорию активной и отправляем AJAX-запрос для получения подкатегорий
            subcategorySelect.prop('disabled', false).empty().append('<option value="">Загрузка...</option>');

            $.ajax({
                url: filter_obj.ajax_url,
                type: 'GET',
                dataType: 'json',
                data: {
                    action: 'get_subcategories',
                    category_id: selectedCategoryId
                },
                success: function(data) {
                    if (data.success) {
                        subcategorySelect.empty().append(data.data.options).trigger('change');
                    } else {
                        subcategorySelect.empty().append('<option value="">Ошибка при загрузке</option>').trigger('change');
                    }
                },
                error: function() {
                    subcategorySelect.empty().append('<option value="">Ошибка при загрузке</option>').trigger('change');
                }
            });
        }
    });

    // Обработчик отправки формы
    $('#recipe-filter').on('submit', function(event) {
        event.preventDefault();
        updateURLWithFilters();
        filterRecipes();
    });

    // Обработчик клика на кнопке submit
    $('#ingridients_submit').on('click', function() {
        var ingModalOverlay = $('.site-header.fixed');
        var body = $('body');

        ingModalOverlay.removeClass('active');
        body.css({ overflowY: '', paddingRight: '' });

        if (ingModalOverlay.length) {
            ingModalOverlay.css('paddingRight', '');
        }

        $('#recipe-filter').submit();
    });

    // Обработчик нажатия на ссылки пагинации
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        var page = $(this).attr('href').split('paged=')[1];
        updateURLWithFilters(page);
        filterRecipes(page);
    });

    function updateURLWithFilters(page = 1) {
        var selectedCategoryId = $('#recipe_category').val();
        var selectedSubcategoryId = $('#recipe_subcategory').val();
        var selectedRegion = $('#recipe_region').val();
        var selectedIncludeIngredients = $('#include_ingredients').val();
        var selectedExcludeIngredients = $('#exclude_ingredients').val();

        var urlParams = new URLSearchParams(window.location.search);
        if (selectedCategoryId && selectedCategoryId !== 'all') {
            urlParams.set('category_id', selectedCategoryId);
        } else {
            urlParams.delete('category_id');
        }
        if (selectedSubcategoryId && selectedSubcategoryId !== 'all') {
            urlParams.set('subcategory_id', selectedSubcategoryId);
        } else {
            urlParams.delete('subcategory_id');
        }
        if (selectedRegion) {
            urlParams.set('region', selectedRegion);
        } else {
            urlParams.delete('region');
        }
        if (selectedIncludeIngredients) {
            urlParams.set('include_ingredients', selectedIncludeIngredients);
        } else {
            urlParams.delete('include_ingredients');
        }
        if (selectedExcludeIngredients) {
            urlParams.set('exclude_ingredients', selectedExcludeIngredients);
        } else {
            urlParams.delete('exclude_ingredients');
        }
        urlParams.set('paged', page);

        window.history.pushState(null, null, '?' + urlParams.toString());
    }

    function filterRecipes(page = 1) {
        var selectedCategoryId = $('#recipe_category').val();
        var selectedSubcategoryId = $('#recipe_subcategory').val();
        var selectedRegion = $('#recipe_region').val();
        var selectedIncludeIngredients = $('#include_ingredients').val();
        var selectedExcludeIngredients = $('#exclude_ingredients').val();
    
        // Проверка на наличие фильтров
        var hasFilters = selectedCategoryId !== 'all' || selectedSubcategoryId !== 'all' || selectedRegion || selectedIncludeIngredients || selectedExcludeIngredients;
    
        if (!hasFilters && (!filter_obj.current_category || !filter_obj.current_tag)) {
            return; // Если фильтры не выбраны и нет текущей категории или тега, не выполняем запрос
        }
    
        $.ajax({
            url: filter_obj.ajax_url,
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'filter_recipes',
                category_id: selectedCategoryId !== 'all' ? selectedCategoryId : '',
                subcategory_id: selectedSubcategoryId !== 'all' ? selectedSubcategoryId : '',
                region: selectedRegion,
                include_ingredients: selectedIncludeIngredients,
                exclude_ingredients: selectedExcludeIngredients,
                paged: page,
                current_category: filter_obj.current_category, // Передаем текущую категорию
                current_tag: filter_obj.current_tag // Передаем текущий тег
            },
            success: function (data) {
                if (data.success) {
                    $('#response').html(data.data.html).addClass('filtred');
                    $('.pagination').html(data.data.pagination); // Обновляем блок пагинации
                }
            }
        });
    }

    // Инициализация фильтров на основе URL при загрузке страницы
    initializeFiltersFromURL();

    function initializeFiltersFromURL() {
        var urlParams = new URLSearchParams(window.location.search);

        var hasFilterParams = urlParams.has('category_id') || urlParams.has('subcategory_id') || urlParams.has('region') || urlParams.has('include_ingredients') || urlParams.has('exclude_ingredients');

        if (hasFilterParams) {
            $('#recipe_category').val(urlParams.get('category_id')).trigger('change');
            $('#recipe_subcategory').val(urlParams.get('subcategory_id')).trigger('change');
            $('#recipe_region').val(urlParams.get('region')).trigger('change');
            $('#include_ingredients').val(urlParams.get('include_ingredients')).trigger('change');
            $('#exclude_ingredients').val(urlParams.get('exclude_ingredients')).trigger('change');

            filterRecipes(urlParams.get('paged') || 1);
        }
    }
});
