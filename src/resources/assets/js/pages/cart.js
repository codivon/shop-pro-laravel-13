/* ============================================================
   cart.js — فاز ۱۶۱ (SaniarTheme)
   صفحهٔ سبد خرید: همهٔ تغییرات با AJAX و بدون رفرش صفحه

   - تغییر تعداد (TouchSpin / ورود دستی) → ذخیرهٔ خودکار با تأخیر
   - حذف محصول (مودال تأیید) → AJAX DELETE
   - پس از هر تغییر: ناحیهٔ سبد + سایدبار + سبد کشویی هدر + شمارنده
     همه با پاسخ سرور جایگزین می‌شوند (بدون window.location)
   ============================================================ */

(function ($) {
    'use strict';

    var syncTimer = null;

    /* ------------------ اعمال پاسخ سرور ------------------ */

    function applyCartPayload(res) {
        if (!res) return;

        /* ناحیهٔ اصلی سبد (آیتم‌ها یا حالت خالی) */
        if (res.area && $('#cart-main-area').length) {
            $('#cart-main-area').replaceWith(res.area);
        }

        /* سایدبار «سفارش شما» */
        if (typeof res.sidebar === 'string') {
            if (res.sidebar && $('#checkout-sidebar').length) {
                $('#checkout-sidebar').replaceWith(res.sidebar);
            } else if (!res.sidebar && $('.checkout-summary').length) {
                /* سبد خالی شد → سایدبار هم حذف شود */
                $('#checkout-sidebar').replaceWith('<span id="checkout-sidebar"></span>');
            }
        }

        /* سبد کشویی هدر + شمارنده */
        if (res.cart) {
            var wasOpen = $('.cart-canvas').hasClass('show');
            $('.cart-canvas').replaceWith(res.cart);
            if (window.SaniarThemeCartSync) {
                window.SaniarThemeCartSync(res.cart, wasOpen);
            }
        }

        /* صفحهٔ پرداخت (اگر سایدبار مشترک باشد): محاسبهٔ مجدد هزینهٔ ارسال */
        if (typeof getFinalPrice === 'function' && $('.consignment-container .consignments').length) {
            try { getFinalPrice(true); } catch (e) {}
        }

        /* تولتیپ‌های تازه‌رندرشده */
        if (window.bootstrap && window.bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                window.bootstrap.Tooltip.getOrCreateInstance(el);
            });
        }
    }

    /* ------------------ ذخیرهٔ تغییرات تعداد ------------------ */

    function syncCart() {
        var $form = $('#cart-update-form');
        if (!$form.length) return;

        block('.cart-product');
        if ($('.checkout-summary').length) { block('.checkout-summary'); }

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            success: function (res) {
                applyCartPayload(res);
                showCustomToast('سبد خرید بروزرسانی شد', 'success');
            },
            error: function (request) {
                var errors = request.responseJSON && request.responseJSON.errors;
                if (errors && errors.length) {
                    errors.forEach(function (error) {
                        showCustomToast(error, 'error');
                    });
                } else {
                    showCustomToast('خطایی رخ داده است.', 'error');
                }
            },
            complete: function () {
                unblock('.cart-product');
                if ($('.checkout-summary').length) { unblock('.checkout-summary'); }
            },
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
            }
        });
    }

    function scheduleCartSync(delay) {
        clearTimeout(syncTimer);
        syncTimer = setTimeout(syncCart, delay || 600);
    }

    /* ------------------ رویدادها ------------------ */

    $(function () {

        /* شمارشگر تعداد (TouchSpin — مطابق قالب سانیار) */
        function initTouchspin() {
            $('#cart-update-form input.cart_quantity').each(function () {
                var $input = $(this);
                if ($input.attr('data-touchspin-initialized')) return;

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
        }
        initTouchspin();

        /* بعد از رندر مجدد ناحیهٔ سبد، شمارشگرها دوباره فعال شوند */
        $(document).ajaxSuccess(function () {
            initTouchspin();
        });

        /* تولتیپ‌های سانیار (Bootstrap 5) */
        if (window.bootstrap && window.bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                window.bootstrap.Tooltip.getOrCreateInstance(el);
            });
        }

        /* تغییر تعداد → ذخیرهٔ خودکار (بدون دکمهٔ بروزرسانی) */
        $(document).on('change', '#cart-update-form input.cart_quantity', function () {
            scheduleCartSync();
        });

        /* دکمهٔ «بروزرسانی سبد خرید» هم بدون رفرش صفحه همان کار را می‌کند */
        $(document).on('click', '#update-cart', function (e) {
            e.preventDefault();
            if (!$('#cart-update-form').length) return;
            clearTimeout(syncTimer);
            syncCart();
        });

        /* ------------------ حذف محصول ------------------ */

        $(document).on('click', '.remove-card', function (e) {
            e.preventDefault();
            $('#delete-form').attr('action', $(this).data('action'));
            if (window.jQuery.fn.modal) {
                jQuery('#delete-modal').modal('show');
            } else if (window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(document.getElementById('delete-modal')).show();
            }
        });

        /* تأیید حذف در مودال → AJAX DELETE بدون رفرش */
        $(document).on('submit', '#delete-form', function (e) {
            e.preventDefault();

            var $form = $(this);
            var action = $form.attr('action');
            if (!action) return;

            block('.cart-product');

            $.ajax({
                url: action,
                type: 'POST',
                data: $form.serialize(),
                success: function (res) {
                    applyCartPayload(res);
                    showCustomToast('محصول از سبد خرید حذف شد', 'success');
                },
                error: function () {
                    showCustomToast('خطایی رخ داده است.', 'error');
                },
                complete: function () {
                    unblock('.cart-product');
                    /* بستن مودال */
                    var modalEl = document.getElementById('delete-modal');
                    if (modalEl && window.bootstrap) {
                        window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    }
                },
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
                }
            });
        });
    });
})(jQuery);
