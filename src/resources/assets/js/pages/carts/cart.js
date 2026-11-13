/**
 * SaniarTheme — صفحه سبد خرید (carts/index)
 * فقط منطق نمایشی/ابتکار صفحه: شمارشگر تعداد (bootstrap-touchspin) و تولتیپ‌ها.
 * منطق اصلی (بروزرسانی سبد با route front.cart و حذف محصول) در js/pages/cart.js
 * که قبلاً وجود داشته، حفظ شده است.
 */
(function ($) {
    'use strict';

    $(document).ready(function () {

        // ---------- شمارشگر تعداد محصولات سبد (TouchSpin — مطابق قالب سانیار) ----------
        $('#cart-update-form input.cart_quantity').each(function () {
            var $input = $(this);

            if ($input.attr('data-touchspin-initialized')) {
                return;
            }

            $input.TouchSpin({
                min: parseFloat($input.attr('min')) || 1,
                max: parseFloat($input.attr('max')) || 100000000,
                step: parseFloat($input.attr('step')) || 1,
                initval: $input.val(),
                decimals: 0,
                boostat: 5,
                maxboostedstep: 10,
                buttondown_class: 'btn-counter waves-effect waves-light',
                buttonup_class: 'btn-counter waves-effect waves-light'
            });

            $input.attr('data-touchspin-initialized', '1');
        });

        // ---------- تولتیپ‌های سانیار (Bootstrap 5) ----------
        if (window.bootstrap && window.bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                window.bootstrap.Tooltip.getOrCreateInstance(el);
            });
        }
    });
})(jQuery);
