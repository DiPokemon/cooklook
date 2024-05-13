jQuery(document).ready(function($) {
    $('.bookmark').click(function(e) {
        e.preventDefault();
        var $this = $(this);
        var recipe_id = $this.data('recipe-id');

        // Проверка существования cookie
        var user_id = getCookie('user_id');
        if (!user_id) {
            user_id = generateUUID(); // Генерация UUID
            setCookie('user_id', user_id, 365); // Установка cookie на 1 год
        }

        $.ajax({
            type: 'POST',
            url: favorite_ajax,
            data: {
                action: 'add_to_favorites',
                recipe_id: recipe_id,
                user_id: user_id
            },
            success: function(response) {
                if(response === 'added') {
                    $this.addClass('added');
                } else {
                    $this.removeClass('added');
                    
                }
            }
        });
    });

    // Функции для работы с cookie
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days*24*60*60*1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "")  + expires + "; path=/";
    }

    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
    }

    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
});
