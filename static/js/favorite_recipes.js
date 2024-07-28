jQuery(document).ready(function($) {
    $('.bookmark').click(function(e) {
        e.preventDefault();
        var $this = $(this);
        var recipe_id = $this.data('recipe-id');

        $.ajax({
            type: 'POST',
            url: favorite_ajax.ajax_url,
            data: {
                action: 'add_to_favorites',
                recipe_id: recipe_id
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
});
