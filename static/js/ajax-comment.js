jQuery(document).ready(function($) {
    var commentForm = $('#commentform');

    commentForm.submit(function(event) {
        event.preventDefault();

        $('#comment-success-message').hide();
        $('#comment-error-message').hide();

        var formdata = commentForm.serialize();

        $.ajax({
            type: 'POST',
            url: ajax_comment_params.ajaxurl,
            data: formdata + '&action=ajaxcomments',
            dataType: 'json',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#comment-error-message').html('Ошибка: ' + errorThrown).show();
            },
            success: function(data) {
                if (data.status === 'success') {
                    // Clear the comment form
                    commentForm[0].reset();
                    
                    // Optionally show a success message
                    //$('#comment-success-message').html(data.message).show();

                    // Append the new comment to the comment list
                    if (data.comment_html) {
                        $('.comment-list').append(data.comment_html);
                    }
                } else {
                    $('#comment-error-message').html(data.message).show();
                }
            }
        });
    });
});
