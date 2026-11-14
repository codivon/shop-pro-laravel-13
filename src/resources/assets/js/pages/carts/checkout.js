/**
 * SaniarTheme — صفحه تسویه حساب (carts/checkout)
 * فقط رفتارهای نمایشی مخصوص مارک‌آپ سانیار (Bootstrap 5):
 *  - تولتیپ‌ها (قبل و بعد از جایگزینی AJAX سایدبار/مرسوله‌ها)
 *  - حالت فعال باکس‌های «شیوه پرداخت» (bank-item)
 *  - دکمه «تایید و ادامه ثبت سفارش» که فرم اصلی را (همراه اعتبارسنجی
 *    موجود در js/pages/checkout.js) submit می‌کند.
 *
 * منطق اصلی قیمت‌ها / مرسوله‌ها / تاریخ‌های ارسال در js/pages/checkout.js
 * دست‌نخورده باقی مانده است.
 */
(function ($) {
    'use strict';

    // ---------- تولتیپ‌های سانیار (Bootstrap 5) ----------
    function initTooltips() {
        if (!window.bootstrap || !window.bootstrap.Tooltip) {
            return;
        }
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            window.bootstrap.Tooltip.getOrCreateInstance(el);
        });
    }

    // ---------- باکس‌های شیوه پرداخت ----------
    /* فاز ۱۷۱ — درگاه‌ها حالا کارت‌های col-6/col-md-4 هستند (gateway-slot)؛
       نزدیک‌ترین والد slot/ستون را پیدا کن تا با تغییر گرید هم کار کند */
    function syncGatewayBoxes() {
        $('input[name="gateway"]').each(function () {
            var $box = $(this).closest('.gateway-slot, .wallet-select, .col-12').find('.bank-item');
            if ($(this).prop('checked')) {
                $box.addClass('active');
            } else {
                $box.removeClass('active');
            }
        });
    }

    $(document).on('change', 'input[name="gateway"]', function () {
        syncGatewayBoxes();
    });

    $(document).ready(function () {

        initTooltips();
        syncGatewayBoxes();

        // سایدبار / مرسوله‌ها با AJAX جایگزین می‌شوند؛ تولتیپ‌های جدید را فعال کن
        $(document).ajaxSuccess(function () {
            setTimeout(initTooltips, 60);
        });

        // ---------- هم‌گام‌سازی کلاس disabled دکمه تایید موقعیت نقشه ----------
        // (map-select-scripts.js فقط اتریبیوت disabled را تغییر می‌دهد؛
        //  کلاس .disabled بوت‌استرپ ۵ هم باید هماهنگ شود)
        var nextAddAddressBtn = document.getElementById('next-add-address-btn');
        if (nextAddAddressBtn && window.MutationObserver) {
            var syncDisabledClass = function () {
                nextAddAddressBtn.classList.toggle('disabled', nextAddAddressBtn.hasAttribute('disabled'));
            };
            new MutationObserver(syncDisabledClass)
                .observe(nextAddAddressBtn, { attributes: true, attributeFilter: ['disabled'] });
            syncDisabledClass();
        }

        // ---------- تایید و ادامه ثبت سفارش ----------
        $(document).on('click', '#checkout-link', function (e) {
            e.preventDefault();

            var $form = $('#checkout-form');
            if (!$form.length) {
                return;
            }

            // submit با trigger تا اعتبارسنجی (jquery-validation در js/pages/checkout.js)
            // و بررسی آدرس/روش ارسال اجرا شود.
            $form.trigger('submit');
        });
    });
})(jQuery);
