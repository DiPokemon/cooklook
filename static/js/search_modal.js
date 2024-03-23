var searchButton = document.querySelector('.header_search');
var searchModalOverlay = document.querySelector('.search_modal_overlay');
var closeSearchModal = document.getElementById('close_search');
var body = document.body;
var scrollOffset = window.innerWidth - document.documentElement.clientWidth;

// Функция для открытия модального окна
function openModal(event) {
    var headerFixed = document.querySelector('.site-header.fixed');
    event.preventDefault();
    searchModalOverlay.classList.add('active');
    body.style.overflowY = 'hidden';
    body.style.paddingRight = scrollOffset + 'px';
    if (headerFixed) { 
        headerFixed.style.paddingRight = scrollOffset + 'px';
    }
}

// Функция для закрытия модального окна
function closeModalFunction() {
    var headerFixed = document.querySelector('.site-header.fixed');
    searchModalOverlay.classList.remove('active');
    body.style.overflowY = '';        
    body.style.paddingRight = '';
    if (headerFixed) { 
       headerFixed.style.paddingRight = ''; 
    }
}

// Слушатель событий для открытия модального окна
    searchButton.addEventListener('click', openModal);

// Слушатель событий для закрытия модального окна
    closeSearchModal.addEventListener('click', closeModalFunction);

// Закрыть модальное окно при клике вне его
    window.addEventListener('click', function(event) {
        if (event.target == searchModalOverlay) {
            closeModalFunction();
        }
    });

// document.addEventListener("DOMContentLoaded", function () {
//     var searchInput = document.getElementById("ajax-search-input");
//     var popularSearchItems = document.querySelectorAll(".popular-search-item");

//     popularSearchItems.forEach(function (item) {
//         item.addEventListener("click", function () {
//             var query = item.getAttribute("data-query");
//             searchInput.value = query;
//         });
//     });
// });

function setSearchQuery(query) {
    var searchInput = document.getElementById("ajax-search-input");
    searchInput.value = query;
}