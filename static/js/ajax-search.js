document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('ajax-search-input');
    var searchResults = document.getElementById('search_results');
    var popularQueries = document.getElementById('popular_queries');
    var searchForm = document.getElementById('ajax-search-form');
    var timeout = null; // Инициализация переменной для таймаута

    // Функция для выполнения AJAX-запроса
    function fetchSearchResults(searchValue) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/wp-admin/admin-ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (this.status === 200) {
                searchResults.innerHTML = this.responseText;
                handleSearchResults(); 
            } else {
                searchResults.innerHTML = '<p>Search ERROR</p>';
                showPopularQueries(); 
            }
        };
        xhr.onerror = function() {
            searchResults.innerHTML = '<p>Search ERROR</p>';
            showPopularQueries();
        };
        xhr.send('action=ajax_search&search=' + encodeURIComponent(searchValue));
    }

    searchForm.addEventListener('submit', function (event) { 
        event.preventDefault();

        window.location.href = '/?s=' + encodeURIComponent(searchInput.value);
    })

    // Функция для скрытия блока с популярными запросами
    function hidePopularQueries() {
        popularQueries.style.display = 'none';
    }

    // Функция для отображения блока с популярными запросами
    function showPopularQueries() {
        popularQueries.style.display = 'block';
    }

    // Обработка результатов поиска
    function handleSearchResults() {
        if (searchResults.innerHTML.trim() === '') {
            // Нет результатов поиска, отображаем блок с популярными запросами
            showPopularQueries();
        } else {
            // Есть результаты поиска, скрываем блок с популярными запросами
            hidePopularQueries();
        }
    }

    // Обработка ввода в поле поиска
    searchInput.addEventListener('input', function() {
        var searchValue = this.value;
        
        // Очищаем предыдущий таймаут, если он существует
        clearTimeout(timeout);

        // Устанавливаем новый таймаут
        timeout = setTimeout(function() {
            if (searchValue.length > 3) {
                fetchSearchResults(searchValue);
            } else {
                searchResults.innerHTML = '';
                showPopularQueries(); // Показываем блок с популярными запросами при пустом поле поиска
            }
        }, 1000); // Таймаут в 1000 миллисекунд (1 секунда)
    });

    // Обработка клика на элементе списка .popular_search_query
    var popularSearchItems = document.querySelectorAll('.popular_search_query a');
    popularSearchItems.forEach(function (item) {
        item.addEventListener('click', function () {
            var query = item.textContent;
            searchInput.value = query;
            fetchSearchResults(query); // Вызываем функцию с выбранным запросом
        });
    });
});
