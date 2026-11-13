
$('#seller-register-checkout').submit(function (e) {
    e.preventDefault();
    if ($(this).valid() && !$(this).data('disabled')) {
        var formData = new FormData(this);

        $.ajax({
            url: $(this).attr('data-check'),
            type: 'POST',
            data: formData,
            success: function (data) {
                 if(data.status=="success"){
                    window.location.href = data.redirect;
                }

            },
            beforeSend: function (xhr) {
                block('.registration-checkout');
                xhr.setRequestHeader(
                    'X-CSRF-TOKEN',
                    $('meta[name="csrf-token"]').attr('content')
                );
            },
            complete: function () {
                unblock('.registration-checkout');
            },
            cache: false,
            contentType: false,
            processData: false
        });
    }
});
