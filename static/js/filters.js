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

        var selectedCategoryId = $('#recipe_category').val();
        var selectedSubcategoryId = $('#recipe_subcategory').val();
        var selectedRegion = $('#recipe_region').val();
        var selectedIncludeIngredients = $('#include_ingredients').val();
        var selectedExcludeIngredients = $('#exclude_ingredients').val();
        console.log('cat: ' + selectedCategoryId);
        console.log('sub_cat: ' + selectedSubcategoryId);
        console.log('region: ' + selectedRegion);
        console.log('ingINC: ' + selectedIncludeIngredients);
        console.log('ingEX: ' + selectedExcludeIngredients);

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
                exclude_ingredients: selectedExcludeIngredients
            },
            
            success: function (data) {
                
                if (data.success) {
                    $('#response').html(data.data.html);
                }
            }
        });
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
});
