jQuery(document).ready(function($) {
    $('.star').click(function() {
        $(this).parent().find('.star').removeClass('selected');
        $(this).addClass('selected');

        var rating = $(this).data('rating');
        $('[name="rating"]').val(rating);
    });

    $('#recipe-rating-form').submit(function(event) {
        event.preventDefault();

        var formData = $(this).serialize();

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: formData + '&action=recipe_rate',
            success: function(response) {
                var data = $.parseJSON(response);
                if (data.success) {
                    $('.recipe-rating').text(data.rating.toFixed(1));
                    $('.star').removeClass('selected'); // Очистка выбора звезд
                    $('[name="rating"]').val(''); // Очистка значения рейтинга в скрытом поле
                } else {
                    alert(data.error);
                }
            }
        });
    });
});