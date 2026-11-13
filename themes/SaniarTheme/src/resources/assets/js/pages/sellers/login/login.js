// validate form with jquery validation plugin
jQuery('#seller-register-level1-form').validate({
    rules: {
        username: {
            required: true,
        },
        password: {
            required: true
        },
    },
    messages: {
        username: {
            required: "فیلد ایمیل یا شماره موبایل نمی تواند خالی باشد",
        },
        password: "رمز عبور را وارد کنید",
    }
});


$('#seller-register-level1-form').submit(function (e) {
    e.preventDefault();
    if ($(this).valid() && !$(this).data('disabled')) {
        var formData = new FormData(this);


        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function (data) {
                if (data.status=='error'){
                    showCustomToast(data.message,'error');
                }else if(data.status=="success"){
                    showCustomToast(data.message,'success')
                    setTimeout(function(){
                        window.location.href = data.redirect;
                    }, 800);

                }

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
