document.addEventListener("DOMContentLoaded", function () {
    var categorySelect = document.getElementById('recipe_category');
    var subcategorySelect = document.getElementById('recipe_subcategory');
    var form = document.getElementById('recipe-filter');
    var ajaxurl = filter_obj.ajax_url; // Передаем URL до admin-ajax.php в JavaScript

    categorySelect.addEventListener('change', function() {
        var selectedCategoryId = categorySelect.value;
        if (selectedCategoryId === '') {
            subcategorySelect.disabled = true;
            subcategorySelect.innerHTML = '<option value="">Выберите подкатегорию</option>';
        } else {
            subcategorySelect.disabled = false;
            subcategorySelect.innerHTML = '<option value="">Загрузка...</option>';

            // Выполните AJAX-запрос для получения подкатегорий на основе выбранной категории
            var xhr = new XMLHttpRequest();
            xhr.open('GET', ajaxurl + '?action=get_subcategories&category_id=' + selectedCategoryId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        subcategorySelect.innerHTML = data.data.options;
                    } else {
                        subcategorySelect.innerHTML = '<option value="">Ошибка при загрузке</option>';
                    }
                }
            };
            xhr.send();
        }
    });

    document.getElementById("ingridients_submit").addEventListener("click", function() {
        var headerFixed = document.querySelector('.site-header.fixed');
        ingModalOverlay.classList.remove('active');
        body.style.overflowY = '';        
        body.style.paddingRight = '';
        if (headerFixed) { 
           headerFixed.style.paddingRight = ''; 
        }
        form.submit();
    })

    form.addEventListener('submit', function(event) {
        event.preventDefault();       
        
        var selectedCategoryId = categorySelect.value;
        var selectedSubcategoryId = subcategorySelect.value;
        var selectedRegion = document.getElementById('recipe_region').value;
        var selectedIncludeIngredients = document.getElementById('include_ingredients').value;
        var selectedExcludeIngredients = document.getElementById('exclude_ingredients').value;
        var ingSubmitBtn = document.getElementById('ingridients_submit');


        // Выполните AJAX-запрос для фильтрации записей на сервере
        var xhr = new XMLHttpRequest();
        var url = ajaxurl + '?action=filter_recipes&category_id=' + selectedCategoryId + '&subcategory_id=' + selectedSubcategoryId + '&region=' + selectedRegion;

        // Добавьте выбранные ингредиенты к URL
        if (selectedIncludeIngredients.length > 0) {
            url += '&include_ingredients=' + selectedIncludeIngredients.join(',');
        }
        if (selectedExcludeIngredients.length > 0) {
            url += '&exclude_ingredients=' + selectedExcludeIngredients.join(',');
        }

        xhr.open('GET', url, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var data = JSON.parse(xhr.responseText);

                // Обновите список рецептов на странице с использованием данных из ответа
                var responseContainer = document.getElementById('response');
                responseContainer.innerHTML = data.data.html;
            }
        };
        xhr.send();
    });
});
