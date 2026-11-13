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
            min: 1000,
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


