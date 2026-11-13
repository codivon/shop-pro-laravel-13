// validate form with jquery validation plugin
jQuery('#seller-register-level1-form').validate({
    rules: {
        email: {
            required: true,
            email: true
        },
        password: {
            required: true
        },
        mobile: {
            required: true,
            regex: "(09)[0-9]{9}"
        }
    },
    messages: {
        email: {
            required: "فیلد ایمیل نمی تواند خالی باشد",
            email: "یک ایمیل صحیح وارد کنید",
        },
        password: "رمز عبور را وارد کنید",
        mobile: {
            required: "فیلد شماره موبایل نمی تواند خالی باشد",
            regex: "شماره همراه باید با ۰۹ شروع شود و ۱۱ رقم باشد",
        },
    }
});
$.validator.addMethod(
    "regex",
    function(value, element, regexp) {
        var re = new RegExp(regexp);
        return this.optional(element) || re.test(value);
    },
    "لطفا یک مقدار معتبر وارد کنید"
);

$('#seller-register-level1-form').submit(function (e) {
    e.preventDefault();
    if ($(this).valid() && !$(this).data('disabled')) {
        var formData = new FormData(this);


        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function (data) {

                window.location.href = data.redirect;
            },
            beforeSend: function (xhr) {
                block('.content-account');
                xhr.setRequestHeader(
                    'X-CSRF-TOKEN',
                    $('meta[name="csrf-token"]').attr('content')
                );
            },
            complete: function () {
                unblock('.content-account');
            },
            cache: false,
            contentType: false,
            processData: false
        });
    }
});
