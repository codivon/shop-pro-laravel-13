$(document).on('click', '.show-comment', function() {
    $.ajax({
        url: $(this).data('action'),
        type: 'GET',
        success: function(data) {
            $('#comment-detail').empty();
            $('#comment-detail').append(data);

            $('#show-modal').modal('show');

        },
        beforeSend: function(xhr) {
            block('#main-card');
        },
        complete: function() {
            unblock('#main-card');
        }
    });

});

$('#filter-questions-form select').change(function() {
    $('#filter-questions-form').submit();
});

$(document).on('submit', '#comment-edit-form', function(e) {

    e.preventDefault();

    var form = $(this);
    var formData = new FormData(this);

    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: formData,

        success: function(data) {

            reloadDiv('.list-comments');

            $('#show-modal').modal('hide');
            showCustomToast('تغییرات با موفقیت انجام شد','success')
        },
        beforeSend: function(xhr) {
            block('.comment-show-modal');
            xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
        },
        complete: function() {
            unblock('.comment-show-modal');
        },
        cache: false,
        contentType: false,
        processData: false
    });

});

$(document).on('click', '#edit-comment-btn', function() {
    $('#edit-comment-body').show();
    $('#comment-body').hide();

    autosize(document.querySelectorAll('textarea'));
});

$(document).on('click', '#comment-form-submit-btn', function() {
    $('#comment-edit-form').trigger('submit');
});
