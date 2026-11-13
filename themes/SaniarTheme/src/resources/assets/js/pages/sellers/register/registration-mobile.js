// validate form with jquery validation plugin
jQuery('#seller-register-mobile').validate({
    rules: {
        verify_code: {
            required: true,
            minlength: 5
        },

    },
    messages: {
        verify_code: {
            required: "فیلد کد تایید نمی تواند خالی باشد",
            minlength: "کد تایید را کامل وارد کنید",
        },

    }
});


$('#seller-register-mobile').submit(function (e) {
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
                block('.message-light');
                xhr.setRequestHeader(
                    'X-CSRF-TOKEN',
                    $('meta[name="csrf-token"]').attr('content')
                );
            },
            complete: function () {
                unblock('.message-light');
            },
            cache: false,
            contentType: false,
            processData: false
        });
    }
});
if ($("#countdown-verify-end").length) {
    var $countdownOptionEnd = $("#countdown-verify-end");

    $countdownOptionEnd.countdown({
        date: resend_time * 1000, // 1 minute later
        text: '<span class="day">%s</span><span class="hour">%s</span><span>: %s</span><span>%s</span>',
        end: function() {
            $countdownOptionEnd.html("<a href='" + $('#countdown-verify-end').data('action') + "' class='btn-link-border link-border-verify form-account-link'>ارسال کد</a>");
        }
    });
}

$(document).ready(function() {
    $('.activation-code-input').activationCodeInput({
        number: 5
    })
})
