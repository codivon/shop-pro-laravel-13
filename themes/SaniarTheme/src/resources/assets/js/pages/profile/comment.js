$('.comment-remove-btn').on('click', function() {
    $('#comment-remove-form').attr('action', $(this).data('action'));
});

