$('select[name=vat_free]').click(function () {
    var item = this;
    var vat_free = $(item).val()
    if (vat_free == '1') {
        $('#vat_free_div').removeClass('d-none');
    } else if (vat_free == '2') {
        $('#vat_free_div').addClass('d-none');
    }
});

jQuery('#seller-edit-form').validate({
    rules: {
        status: {
            required: true
        },
        status_register: {
            required: true
        },
        status_documents: {
            required: true
        },
        status_work: {
            required: true
        },
        private_business: {
            required: true
        },
        business_name: {
            required: true
        },
        first_name: {
            required: true
        },
        last_name: {
            required: true
        },
        gender: {
            required: true
        },
        birth_day: {
            required: true
        },
        national_identity_number: {
            required: true
        },
        identity_card_number: {
            required: true
        },
        shaba_number: {
            required: true
        },
        email: {
            required: true
        },
        mobile: {
            required: true
        },
        state_id: {
            required: true
        },
        city_id: {
            required: true
        },
        address: {
            required: true
        },
        post_code: {
            required: true
        },
        phone: {
            required: true
        },
        vat_free: {
            required: true
        },
    }
});

$('#seller-edit-form').submit(function (e) {
    e.preventDefault();
    var form = $(this);
    var formData = new FormData(this);
    $('#error-edit-modal').modal('toggle');
    $('.edit-modal-success').click(function (){
        if (form.valid() && !form.data('disabled')) {

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: formData,
                success: function (data) {
                    if (data == 'success') {
                        $('#post-create-form').data('disabled', true);
                        window.location.href = form.data('redirect');
                    }
                },
                beforeSend: function (xhr) {
                    block('.modal-content');
                    xhr.setRequestHeader(
                        'X-CSRF-TOKEN',
                        $('meta[name="csrf-token"]').attr('content')
                    );
                },
                complete: function () {
                    unblock('.modal-content');
                },
                cache: false,
                contentType: false,
                processData: false
            });
        }
    });


});


$('.product-category').select2ToTree({
    rtl: true,
    width: '100%'
});



$('.set-econtract').click(function () {

    var item=$(this);
    var econtract=$('input[name="econtract"]:checked').val();

    if (econtract==1){
        var action=$(item).data('action');
        $.ajax({
            url: action,
            type: 'POST',
            data: {econtract:1},
            success: function (data) {
                if(data.status=="success"){
                    Swal.fire({
                        title: 'با موفقیت ثبت شد!',
                        type: 'success',
                        showCancelButton: false,
                        confirmButtonText: 'باشه',
                        closeOnConfirm: false,
                        closeOnCancel: false
                    }).then((result) => {
                        window.location.reload();
                    });
                    $('#seller-econtracts-modal').modal('toggle');
                }
                else if(data.status=="documents"){
                    showCustomToast('اطلاعات فروشنده تاییده نشده است','error');
                }
            },
            beforeSend: function (xhr) {
                block('#seller-econtracts-modal');
                xhr.setRequestHeader(
                    'X-CSRF-TOKEN',
                    $('meta[name="csrf-token"]').attr('content')
                );
            },
            complete: function () {
                unblock('#seller-econtracts-modal');
            },
            cache: false,
            contentType: false,
            processData: false
        });

    }else if(econtract==0){
        $('#seller-econtracts-modal').modal('toggle')
    }else {
        showCustomToast('یک گزینه را انتخاب کنید','error');
    }


});

$('.submit-dashboard-profile').click(function () {
    $('#seller-edit-form').submit();
})

$('.dashboard-profile .nav-link').click(function () {
    var classes=$(this).attr('class');
    if (classes=="nav-link menu-title show-pass-tab" || classes=="nav-link menu-title show-pass-tab active"){
        $('.dashboard-profile-item-2').addClass('hidden-btn')
    }else{
        $('.dashboard-profile-item-2').removeClass('hidden-btn')
    }

})



$('.show-history').on('click', function () {
    var btn = $(this);

    $.ajax({
        url: btn.data('action'),
        type: 'GET',
        success: function (data) {
            $('#history-detail').empty();
            $('#history-detail').append(data);
            $('#history-show-modal').modal('show');
        },
        beforeSend: function (xhr) {
            block(btn);
        },
        complete: function () {
            unblock(btn);
        },
    });
});

$('.amount-input').attr('autocomplete', 'off');

$(document).on('keyup', '.amount-input', function () {
    if (!$(this).val()) {
        $(this).next('.form-text').remove();
        return;
    }

    if (!$(this).next('.form-text').length) {
        $(this).after('<small class="form-text text-success amount-helper"></small>');
    }

    var text = number_format($(this).val()) + ' تومان';

    $(this).next('.form-text').text(text);
});

$('.amount-input').trigger('keyup');

$('#wallet-create-form').validate({
    rules: {
        amount: {
            required: true,
            max: 500000000,
            min: 10000,
        },
        gateway: {
            required: true,
        },
    },
});
var amount=$('#wallet-withdraw-form').data('amount');
if (amount >= 50000000){
    amount=50000000;
}
$('#wallet-withdraw-form').validate({
    rules: {
        amount: {
            required: true,
            max: amount,
            min: 10000,
        }
    },
});

