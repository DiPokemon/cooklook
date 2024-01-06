document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('ajax-search-input');
    var searchResults = document.getElementById('search_results');

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
        if (searchValue.length > 2) {
            fetchSearchResults(searchValue);
        } else {
            searchResults.innerHTML = '';
        }
    });
});
