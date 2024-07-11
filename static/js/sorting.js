jQuery(document).ready(function($) {
    $('#sort_by').on('change', function() {
        var sortBy = $(this).val();        
        $.ajax({
            url: sorting_ajax.ajax_url, // Эта переменная должна быть определена в вашем PHP коде
            type: 'post',
            data: {
                action: 'sort_posts', // Название экшена
                sort_by: sortBy
            },
            success: function (response) {                
                $('#response').html(response); // Обновление контейнера с записями
            }
        });
    });

    $('#sort_by-mobile').on('change', function() {
        var sortBy = $(this).val();        
        $.ajax({
            url: sorting_ajax, // Эта переменная должна быть определена в вашем PHP коде
            type: 'post',
            data: {
                action: 'sort_posts', // Название экшена
                sort_by: sortBy
            },
            success: function (response) {                
                $('#response').html(response); // Обновление контейнера с записями
            }
        });
    });

    $('#sort_by-mobile').select2();
});

