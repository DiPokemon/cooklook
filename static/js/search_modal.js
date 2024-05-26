    var searchButton = document.querySelector('.header_search');
    var searchModalOverlay = document.querySelector('.search_modal_overlay');
    var closeSearchModal = document.getElementById('close_search');
    var closeSearchModalMob = document.getElementById('close_search-mobile');
    var filters = document.getElementById('filters');
    var body = document.body;
    var scrollOffset = window.innerWidth - document.documentElement.clientWidth;
    
    // Функция для закрытия модального окна фильтров
    function closeFiltersModal() {
        if (filters && filters.classList.contains('active')) {
            filters.classList.remove('active');
            body.style.overflowY = '';
        }
    }
    


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
        closeFiltersModal();
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
    closeSearchModalMob.addEventListener('click', closeModalFunction);

    // Закрыть модальное окно при клике вне его
    window.addEventListener('click', function(event) {
        if (event.target == searchModalOverlay) {
            closeModalFunction();
        }
    });

    function setSearchQuery(query) {
        var searchInput = document.getElementById("ajax-search-input");
        searchInput.value = query;
    }