$.validator.addMethod(
    "regex",
    function(value, element, regexp) {
        var re = new RegExp(regexp);
        return this.optional(element) || re.test(value);
    },
    "لطفا یک مقدار معتبر وارد کنید"
);

jQuery('#add-update-address-form').validate({
    rules: {

        'fullname': {
            required: true,
        },
        'mobile': {
            required: true,
            regex: "(09)[0-9]{9}"
        },
        'buildingNumber': {
            required: true,
        },
        'postal_code': {
            required: true,
            digits: true,
            maxlength: 10,
            minlength: 10
        },
        'province_id': {
            required: true,
        },
        'city_id': {
            required: true,
        },
        'address': {
            required: true,
            maxlength: 300,
        },
    },
});

$.validator.addMethod(
    "regex",
    function(value, element, regexp) {
        var re = new RegExp(regexp);
        return this.optional(element) || re.test(value);
    },
    "لطفا یک مقدار معتبر وارد کنید"
);

$('#add-update-address-form').submit(function(e) {
    e.preventDefault();

    if ($(this).valid()) {
        var formData = new FormData(this);
        var btn = $('#submit-btn');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function(data) {

                if (data.action=="store"){
                    Swal.fire({
                        title: 'آدرس با موفقیت اضافه شد!',
                        type: 'success',
                        showCancelButton: false,
                        confirmButtonText: 'باشه',
                        closeOnConfirm: false,
                        closeOnCancel: false
                    }
                    ).then((result) => {
                        window.location.reload();
                    });

                }
                if (data.action=="update"){
                    Swal.fire({
                        title: 'آدرس با موفقیت ویرایش شد!',
                        type: 'success',
                        showCancelButton: false,
                        confirmButtonText: 'باشه',
                        closeOnConfirm: false,
                        closeOnCancel: false
                    }
                    ).then((result) => {
                        window.location.reload();
                    });

                }
                $('#add-edit-address-modal form').trigger('reset');

                $('#add-edit-address-modal').modal('hide');

            },
            beforeSend: function(xhr) {
                block(btn);
                xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
            },
            complete: function() {
                unblock(btn);
            },

            cache: false,
            contentType: false,
            processData: false
        });
    }
});
