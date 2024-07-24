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

        if (selectedCategoryId === '') {
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
        urlParams.set('category_id', selectedCategoryId);
        urlParams.set('subcategory_id', selectedSubcategoryId);
        urlParams.set('region', selectedRegion);
        urlParams.set('include_ingredients', selectedIncludeIngredients);
        urlParams.set('exclude_ingredients', selectedExcludeIngredients);
        urlParams.set('paged', page);

        window.history.pushState(null, null, '?' + urlParams.toString());
    }

    function filterRecipes(page = 1) {
        var selectedCategoryId = $('#recipe_category').val();
        var selectedSubcategoryId = $('#recipe_subcategory').val();
        var selectedRegion = $('#recipe_region').val();
        var selectedIncludeIngredients = $('#include_ingredients').val();
        var selectedExcludeIngredients = $('#exclude_ingredients').val();

        $.ajax({
            url: filter_obj.ajax_url,
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'filter_recipes',
                category_id: selectedCategoryId,
                subcategory_id: selectedSubcategoryId,
                region: selectedRegion,
                include_ingredients: selectedIncludeIngredients,
                exclude_ingredients: selectedExcludeIngredients,
                paged: page
            },
            
            success: function (data) {
                if (data.success) {
                    $('#response').html(data.data.html);
                }
            }
        });
    }

    // Инициализация фильтров на основе URL при загрузке страницы
    initializeFiltersFromURL();

    function initializeFiltersFromURL() {
        var urlParams = new URLSearchParams(window.location.search);

        $('#recipe_category').val(urlParams.get('category_id')).trigger('change');
        $('#recipe_subcategory').val(urlParams.get('subcategory_id')).trigger('change');
        $('#recipe_region').val(urlParams.get('region')).trigger('change');
        $('#include_ingredients').val(urlParams.get('include_ingredients')).trigger('change');
        $('#exclude_ingredients').val(urlParams.get('exclude_ingredients')).trigger('change');

        filterRecipes(urlParams.get('paged') || 1);
    }
});
