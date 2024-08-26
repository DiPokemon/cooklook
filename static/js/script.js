// function openModal() {
//     document.getElementById("header_search_modal").classList.add("active");
// }

// function closeModal() {
//     document.getElementById("header_search_modal").classList.remove("active");
// }

// function openModalMobile() {
//     document.getElementById("header_search_modal_mob").classList.add("active");
// }

// function closeModalMobile() {
//     document.getElementById("header_search_modal_mob").classList.remove("active");
// }

// function openCartPanel() {
//     document.getElementById("mini_cart_panel").classList.add("active");
// }

// function closeCartPanel() {
//     document.getElementById("mini_cart_panel").classList.remove("active");
// }


// function openCartPanelMobile() {
//     document.getElementById("mini_cart_panel_mob").classList.add("active");
// }

// function closeCartPanelMobile() {
//     document.getElementById("mini_cart_panel_mob").classList.remove("active");
// }





jQuery(document).ready(function () {
    function setEqualHeight() {
        if (jQuery(window).width() > 768) {
            jQuery('.recipes_grid').each(function() {
                var heightTitle = 0;        
                var heightDesc = 0;
                var heightCats = 0;
                
                // Находим максимальную высоту для recipe_title
                jQuery(this).find('.recipe_loop-content .recipe_title').each(function() {
                    if (jQuery(this).height() > heightTitle) {
                        heightTitle = jQuery(this).height();
                    }
                });
                jQuery(this).find('.recipe_loop-content .recipe_title').height(heightTitle);

                // Находим максимальную высоту для recipe_desc
                jQuery(this).find('.recipe_loop-content .recipe_desc').each(function() {
                    if (jQuery(this).height() > heightDesc) {
                        heightDesc = jQuery(this).height();
                    }
                });
                jQuery(this).find('.recipe_loop-content .recipe_desc').height(heightDesc);

                // Находим максимальную высоту для recipe_ingridients
                jQuery(this).find('.recipe_loop-content .recipe_ingridients').each(function() {
                    if (jQuery(this).height() > heightCats) {
                        heightCats = jQuery(this).height();
                    }
                });
                jQuery(this).find('.recipe_loop-content .recipe_ingridients').height(heightCats);
            });
        } else {
            // Сбрасываем высоты на авто для экранов меньше 768 пкс
            jQuery('.recipes_grid .recipe_loop-content .recipe_title').css('height', 'auto');
            jQuery('.recipes_grid .recipe_loop-content .recipe_desc').css('height', 'auto');
            jQuery('.recipes_grid .recipe_loop-content .recipe_ingridients').css('height', 'auto');
        }
    }

    // Выполнение функции при загрузке страницы
    setEqualHeight();

    // Выполнение функции при изменении размера окна
    jQuery(window).resize(function() {
        setEqualHeight();
    });

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
    var imgBlocks = document.querySelectorAll('.img_block');

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


    imgBlocks.forEach(function(block) {
        block.addEventListener('click', function() {
            window.location.href = block.getAttribute('data-permalink');
        });

        // Добавляем обработчик клика для кнопки добавления в избранное
        // var bookmarkButton = block.querySelector('.bookmark');
        // if (bookmarkButton) {
        //     bookmarkButton.addEventListener('click', function(event) {
        //         event.stopPropagation(); // Останавливаем всплытие события, чтобы не происходил переход по ссылке
        //         // Логика добавления в избранное здесь
        //     });
        // }
    });

    

});

