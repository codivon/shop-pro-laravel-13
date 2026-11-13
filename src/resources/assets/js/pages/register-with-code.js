jQuery('#register-with-code-form').validate({
    rules: {
        mobile: {
            required: true
        },
        codenum1: {
            required: true
        },
        codenum2: {
            required: true
        },
        codenum3: {
            required: true
        },
        codenum4: {
            required: true
        },
        codenum5: {
            required: true
        }
    }
});

$(document).ready(function () {
    $('#register-with-code-form').submit(function (e) {
        e.preventDefault();

        if ($(this).valid()) {
            var formData = new FormData(this);
            var form = $(this);

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                success: function (data) {
                    showCustomToast(data.message,'error');
                },

                beforeSend: function (xhr) {
                    block('.form-ui');
                    xhr.setRequestHeader(
                        'X-CSRF-TOKEN',
                        $('meta[name="csrf-token"]').attr('content')
                    );
                },
                complete: function () {
                    unblock('.form-ui');
                },

                cache: false,
                contentType: false,
                processData: false
            });
        }
    });
});
