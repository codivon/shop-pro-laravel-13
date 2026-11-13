
jQuery('#reset-form').validate({
    rules: {
        'prev_password': {
            required: true,
        },

        'password': {
            required: true,
            minlength: 6
        },

        'password_confirmation': {
            required: true,
            equalTo: "#password"
        },
    },
});


$('#reset-form').submit(function (e) {
    e.preventDefault();

    if ($(this).valid()) {
        var formData = new FormData(this);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function (data) {
                Swal.fire({
                    title: 'رمز عبور شما با موفقیت تغییر کرد',
                    type: 'success',
                    showCancelButton: false,
                    confirmButtonText: 'باشه',
                    closeOnConfirm: false,
                    closeOnCancel: false
                }).then(() => {
                    window.location.href = redirect_url;
                });

            },
            beforeSend: function (xhr) {
                $('.form-ui').block({ message: null });
                xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            },
            complete: function () {
                $('.form-ui').unblock({ message: null });
            },
            cache: false,
            contentType: false,
            processData: false
        });
    }
});
