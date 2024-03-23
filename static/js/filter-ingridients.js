$(document).ready(function() {
    // Инициализация Select2 с передачей списка ингредиентов

    $('#include_ingredients').select2({
        tags: true,
        tokenSeparators: [',', ' '], // Разделители между ингредиентами
        data: availableIngredients.ingredients.map(function(ingredient) {
            return { id: ingredient, text: ingredient };
        }),
        templateSelection: function(data, container) {
            // Создаем свой собственный шаблон с иконкой "remove"
            return $( '<div class="ing_tag">' + data.text + '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M15.2492 4.75852C15.1721 4.68127 15.0805 4.61998 14.9797 4.57816C14.8789 4.53634 14.7708 4.51482 14.6617 4.51482C14.5526 4.51482 14.4445 4.53634 14.3437 4.57816C14.2429 4.61998 14.1513 4.68127 14.0742 4.75852L9.99921 8.82519L5.92421 4.75019C5.84706 4.67303 5.75547 4.61183 5.65466 4.57008C5.55386 4.52833 5.44582 4.50684 5.33671 4.50684C5.2276 4.50684 5.11956 4.52833 5.01876 4.57008C4.91795 4.61183 4.82636 4.67303 4.74921 4.75019C4.67206 4.82734 4.61086 4.91893 4.5691 5.01973C4.52735 5.12054 4.50586 5.22858 4.50586 5.33769C4.50586 5.4468 4.52735 5.55484 4.5691 5.65564C4.61086 5.75644 4.67206 5.84803 4.74921 5.92519L8.82421 10.0002L4.74921 14.0752C4.67206 14.1523 4.61086 14.2439 4.5691 14.3447C4.52735 14.4455 4.50586 14.5536 4.50586 14.6627C4.50586 14.7718 4.52735 14.8798 4.5691 14.9806C4.61086 15.0814 4.67206 15.173 4.74921 15.2502C4.82636 15.3273 4.91795 15.3885 5.01876 15.4303C5.11956 15.472 5.2276 15.4935 5.33671 15.4935C5.44582 15.4935 5.55386 15.472 5.65466 15.4303C5.75547 15.3885 5.84706 15.3273 5.92421 15.2502L9.99921 11.1752L14.0742 15.2502C14.1514 15.3273 14.243 15.3885 14.3438 15.4303C14.4446 15.472 14.5526 15.4935 14.6617 15.4935C14.7708 15.4935 14.8789 15.472 14.9797 15.4303C15.0805 15.3885 15.1721 15.3273 15.2492 15.2502C15.3264 15.173 15.3876 15.0814 15.4293 14.9806C15.4711 14.8798 15.4926 14.7718 15.4926 14.6627C15.4926 14.5536 15.4711 14.4455 15.4293 14.3447C15.3876 14.2439 15.3264 14.1523 15.2492 14.0752L11.1742 10.0002L15.2492 5.92519C15.5659 5.60852 15.5659 5.07519 15.2492 4.75852Z" fill="white"/></svg></div>');
        }
    });
    $('#exclude_ingredients').select2({
        tags: true,
        tokenSeparators: [',', ' '], // Разделители между ингредиентами
        data: availableIngredients.ingredients.map(function(ingredient) {
            return { id: ingredient, text: ingredient };
        })
    });
});

var ingridientsButton = document.querySelector('.ingridients_btn');
var ingModalOverlay = document.querySelector('.ingridients_modal_overlay');
var closeModal = document.getElementById('close_ingridients');
var body = document.body;
var scrollOffset = window.innerWidth - document.documentElement.clientWidth;

// Функция для открытия модального окна
function openIngModal(event) {
    var headerFixed = document.querySelector('.site-header.fixed');
    event.preventDefault();
    ingModalOverlay.classList.add('active');
    body.style.overflowY = 'hidden';
    body.style.paddingRight = scrollOffset + 'px';
    if (headerFixed) { 
        headerFixed.style.paddingRight = scrollOffset + 'px';
    }
}

// Функция для закрытия модального окна
function closeIngModalFunction() {
    var headerFixed = document.querySelector('.site-header.fixed');
    ingModalOverlay.classList.remove('active');
    body.style.overflowY = '';        
    body.style.paddingRight = '';
    if (headerFixed) { 
       headerFixed.style.paddingRight = ''; 
    }
}

// Слушатель событий для открытия модального окна
    ingridientsButton.addEventListener('click', openIngModal);

// Слушатель событий для закрытия модального окна
    closeModal.addEventListener('click', closeIngModalFunction);

// Закрыть модальное окно при клике вне его
    window.addEventListener('click', function(event) {
        if (event.target == ingModalOverlay) {
            closeIngModalFunction();
        }
    });

// function setIncludeIngridients(query) {
//     var ingrientsSelect = document.getElementById("include_ingredients");
//     ingrientsSelect.value = query;
// }




// Добавление ингредиента из популярных включаемых ингредиентов
function setIncludeIngridients(ingredientName) {
    // Найдите поле "Включить ингредиенты" по его ID
    var includeIngredientsSelect = $('#include_ingredients');
    
    // Проверьте, не выбран ли уже этот ингредиент
    if (!includeIngredientsSelect.find('option[value="' + ingredientName + '"]').length) {
        // Создайте новую опцию с выбранным ингредиентом
        var newOption = new Option(ingredientName, ingredientName, true, true);
        
        // Добавьте опцию в поле "Включить ингредиенты"
        includeIngredientsSelect.append(newOption).trigger('change');
    }
}



