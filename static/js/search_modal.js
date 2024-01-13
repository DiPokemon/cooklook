var searchButton = document.querySelector('.header_search');
var modal_overlay = document.querySelector('.search_modal_overlay');
var closeModal = document.querySelector('.close_modal');
var body = document.body;
var scrollOffset = window.innerWidth - document.documentElement.clientWidth;


// Функция для открытия модального окна
function openModal(event) {
        var headerFixed = document.querySelector('.site-header.fixed');
        event.preventDefault();
        modal_overlay.classList.add('active');
        body.style.overflowY = 'hidden';
        body.style.paddingRight = scrollOffset + 'px';
        headerFixed.style.paddingRight = scrollOffset + 'px';
        // body.classList.add('modal-open');
    }

// Функция для закрытия модального окна
function closeModalFunction() {
        var headerFixed = document.querySelector('.site-header.fixed');
        modal_overlay.classList.remove('active');
        body.style.overflowY = '';        
        body.style.paddingRight = '';
        headerFixed.style.paddingRight = '';
        // body.classList.remove('modal-open');
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