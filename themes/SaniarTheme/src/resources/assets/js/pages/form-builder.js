// اعتبارسنجی فرم با jQuery Validate
$(document).ready(function() {

// آپلود فایل
    $('input[type="file"]').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        let targetId = $(this).attr('id').replace('file-input-', '');
        let $display = $('#file-name-' + targetId);

        if (fileName) {
            $display.text(fileName).addClass('has-file');
        } else {
            $display.text('').removeClass('has-file');
        }
    });

// ارسال فرم با AJAX
    $('#dynamic-form').on('submit', function(e) {
        e.preventDefault();

        if (!$(this).valid()) {
            return false;
        }

        let form = $(this);
        let formData = new FormData(this);
        let submitBtn = $('#submit-btn');
        let originalText = submitBtn.html();
        let resultDiv = $('.form-result');

        submitBtn.html('<i class="fa fa-spinner fa-spin"></i> در حال ارسال...').prop('disabled', true);
        form.addClass('loading');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    resultDiv.html(`
                        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                            <i class="fa fa-check-circle"></i> ${response.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    form[0].reset();
                    $('.file-name-display').text('').removeClass('has-file');

                    setTimeout(function() {
                        resultDiv.html('');
                    }, 5000);
                }
            },
            error: function(xhr) {
                let errorMessage = 'خطایی رخ داده است';

                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    let errors = xhr.responseJSON.errors;
                    let firstError = '';

                    $.each(errors, function(key, value) {
                        firstError = value[0];
                        $('#error-' + key).text(value[0]);
                        $('[name="' + key + '"]').addClass('error');
                        return false;
                    });

                    errorMessage = firstError || 'لطفا اطلاعات را صحیح وارد کنید';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                resultDiv.html(`
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        <i class="fa fa-exclamation-circle"></i> ${errorMessage}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
            },
            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
                form.removeClass('loading');

                setTimeout(function() {
                    resultDiv.html('');
                }, 5000);
            }
        });
    });

// پاک کردن خطا هنگام تایپ
    $('input, textarea, select').on('keyup change', function() {
        $(this).removeClass('error');
        let name = $(this).attr('name');
        if (name) {
            let escapedName = name.replace(/\[/g, '_').replace(/\]/g, '');
            $('#error-' + escapedName).text('');
        }
    });
});
