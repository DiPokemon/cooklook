function openModal() {
    document.getElementById("header_search_modal").classList.add("active");
}

function closeModal() {
    document.getElementById("header_search_modal").classList.remove("active");
}

function openModalMobile() {
    document.getElementById("header_search_modal_mob").classList.add("active");
}

function closeModalMobile() {
    document.getElementById("header_search_modal_mob").classList.remove("active");
}

function openCartPanel() {
    document.getElementById("mini_cart_panel").classList.add("active");
}

function closeCartPanel() {
    document.getElementById("mini_cart_panel").classList.remove("active");
}


function openCartPanelMobile() {
    document.getElementById("mini_cart_panel_mob").classList.add("active");
}

function closeCartPanelMobile() {
    document.getElementById("mini_cart_panel_mob").classList.remove("active");
}

jQuery(document).ready(function() {
    jQuery('.recipes_grid').each(function() {
        var heightTitle = 0;        
        var heightDesc = 0;
        var heightCats = 0;

        jQuery(this).find('.recipe_loop-content .recipe_title').each(function() {
            if (jQuery(this).height() > heightTitle) {
                heightTitle = jQuery(this).height();
            }
        });
        jQuery(this).find('.recipe_loop-content .recipe_title').height(heightTitle);

        jQuery(this).find('.recipe_loop-content .recipe_desc').each(function() {
            if (jQuery(this).height() > heightDesc) {
                heightDesc = jQuery(this).height();
            }
        });
        jQuery(this).find('.recipe_loop-content .recipe_desc').height(heightDesc);

        jQuery(this).find('.recipe_loop-content .recipe_ingridients').each(function() {
            if (jQuery(this).height() > heightCats) {
                heightCats = jQuery(this).height();
            }
        });
        jQuery(this).find('.recipe_loop-content .recipe_ingridients').height(heightCats);
    });
});





jQuery(document).ready(function() {
    jQuery('.featured_products .slick-track').each(function() {
        var highestBox = 0;
        jQuery(this).find('.featured_product_wrapper').each(function() {
            if (jQuery(this).height() > highestBox) {
                highestBox = jQuery(this).height();
            }
        });
        jQuery(this).find('.featured_product_wrapper').height(highestBox);
    });
});


document.addEventListener('DOMContentLoaded', function() {
    var showMoreButtons = document.querySelectorAll('.show_more');

    showMoreButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            // Находим все скрытые элементы в том же списке
            var hiddenItems = this.parentElement.querySelectorAll('.hidden');
            hiddenItems.forEach(function(item) {
                item.classList.remove('hidden'); // Удалить класс 'hidden'
                item.classList.add('visible'); // Добавить класс 'visible' для начала анимации
            });
            this.style.display = 'none'; // Скрываем кнопку "Показать еще"
        });
    });


    window.onscroll = function() {
        var header = document.querySelector('.site-header');
        if (window.pageYOffset > 100) {          
          header.classList.add('fixed');
        } else {          
          header.classList.remove('fixed');
        }
    }

    var searchButton = document.querySelector('.header_search');
    var modal_overlay = document.querySelector('.search_modal_overlay');
    var closeModal = document.querySelector('.close_modal');
    var body = document.body

    // Функция для открытия модального окна
    function openModal() {
        modal_overlay.classList.add('active');
        body.style.overflow = 'hidden';
    }

    // Функция для закрытия модального окна
    function closeModalFunction() {
        modal_overlay.classList.remove('active');
        body.style.overflow = '';
    }

    // Слушатель событий для открытия модального окна
    searchButton.addEventListener('click', openModal);

    // Слушатель событий для закрытия модального окна
    closeModal.addEventListener('click', closeModalFunction);

    // Закрыть модальное окно при клике вне его
    window.addEventListener('click', function(event) {
        if (event.target == modal_overlay) {
            closeModalFunction();
        }
    });
});