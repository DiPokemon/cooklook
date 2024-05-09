jQuery(document).ready(function($) {
    $('.bookmark').click(function(e) {
        e.preventDefault();
        var recipe_id = $(this).data('recipe-id');

        $.ajax({
            type: 'POST',
            url: favorite_ajax,
            data: {
                action: 'add_to_favorites',
                recipe_id: recipe_id
            },
            success: function(response) {
                alert('Страница добавлена в избранное!');
            }
        });
    });
});
