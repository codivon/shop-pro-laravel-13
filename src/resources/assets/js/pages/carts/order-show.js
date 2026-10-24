/* ============================================================
   صفحه پیگیری سفارش (پنل کاربر) — سانیار
   - انتخاب درگاه برای پرداخت سفارش پرداخت‌نشده (bank-item ها)
   - هدایت به درگاه با GET front.orders.pay?gateway={key}
   ============================================================ */
(function () {
    'use strict';

    var $ = window.jQuery;
    if (!$) return;

    // انتخاب کارت درگاه
    $(document).on('click', '#order-pay-gateways .bank-item', function (e) {
        if ($(e.target).closest('input').length) return;
        var $radio = $(this).find('input[name="pay-gateway"]');
        if ($radio.length && !$radio.prop('checked')) {
            $radio.prop('checked', true).trigger('change');
        }
    });

    $(document).on('change', 'input[name="pay-gateway"]', function () {
        $('#order-pay-gateways .bank-item').removeClass('active');
        $(this).closest('.bank-item').addClass('active');
    });

    // پرداخت
    $(document).on('click', '#order-pay-btn', function () {
        var $checked = $('input[name="pay-gateway"]:checked');
        if (!$checked.length) {
            var $t = $('#saniar-toast');
            if (!$t.length) {
                $t = $('<div id="saniar-toast" class="saniar-toast"></div>').appendTo('body');
            }
            $t.text('ابتدا روش پرداخت را انتخاب کنید.')
                .removeClass('saniar-toast-success')
                .addClass('saniar-toast-error saniar-toast-show');
            clearTimeout($t.data('timer'));
            $t.data('timer', setTimeout(function () { $t.removeClass('saniar-toast-show'); }, 3200));
            return;
        }
        var $btn = $(this).addClass('loading').prop('disabled', true);
        window.location.href = $checked.data('pay-url');
        setTimeout(function () { $btn.removeClass('loading').prop('disabled', false); }, 4000);
    });
})();
