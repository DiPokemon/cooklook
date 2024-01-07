document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('ajax-search-input');
    var searchResults = document.getElementById('search_results');
    var timeout = null; // Инициализация переменной для таймаута

    // Функция для выполнения AJAX-запроса
    function fetchSearchResults(searchValue) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/wp-admin/admin-ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (this.status === 200) {
                searchResults.innerHTML = this.responseText;
            } else {
                searchResults.innerHTML = '<p>Ошибка поиска.</p>';
            }
        };
        xhr.onerror = function() {
            searchResults.innerHTML = '<p>Ошибка поиска.</p>';
        };
        xhr.send('action=ajax_search&search=' + encodeURIComponent(searchValue));
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
            }
        }, 1000); // Таймаут в 500 миллисекунд (0.5 секунды)
    });
});
