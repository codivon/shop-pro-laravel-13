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

    /* ------------------ شمارشگر تعداد (TouchSpin) ------------------
       فاز ۱۷۳ — به اسکوپ بیرونی منتقل شد تا بعد از «هر» رندر مجدد ناحیهٔ سبد
       (حتی وقتی پاسخ سرور خطای موجودی است) دوباره روی اینپوت‌های تازه اجرا شود.
       قبلاً فقط $(document).ajaxSuccess این کار را می‌کرد که در درخواست‌های
       خطادار اجرا نمی‌شود و به همین دلیل بعد از خطای موجودی، استایل کادر تعداد
       به‌کلی می‌ریخت (اینپوت خام بدون دکمهٔ +/−). */

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

    function initTooltips() {
        if (window.bootstrap && window.bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                window.bootstrap.Tooltip.getOrCreateInstance(el);
            });
        }
    }

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

        /* فاز ۱۷۳ — بعد از رندر مجدد (موفق یا خطای موجودی) شمارشگرها و تولتیپ‌های
           تازه‌رندرشده دوباره فعال شوند */
        initTouchspin();
        initTooltips();
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
                var res = request.responseJSON;
                var errors = res && res.errors;
                if (errors && errors.length) {
                    errors.forEach(function (error) {
                        showCustomToast(error, 'error');
                    });
                } else {
                    showCustomToast('خطایی رخ داده است.', 'error');
                }
                /* فاز ۱۶۲ — سرور در پاسخ خطا وضعیت واقعی سبد را هم می‌فرستد؛
                   شمارشگر/جمع کل به مقدار مجاز برمی‌گردد (عدد غلط نمی‌ماند) */
                if (res && res.area) {
                    applyCartPayload(res);
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
        initTouchspin();

        /* بعد از رندر مجدد ناحیهٔ سبد، شمارشگرها دوباره فعال شوند (حالت موفق) */
        $(document).ajaxSuccess(function () {
            initTouchspin();
        });

        /* تولتیپ‌های سانیار (Bootstrap 5) */
        initTooltips();

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

        /* فاز ۱۶۴ — دکمهٔ «تسویه حساب» (سبد → مرحلهٔ بعد):
           اول تغییرات سبد ذخیره می‌شود (موجودی دوباره کنترل می‌شود)،
           سپس به صفحهٔ پرداخت هدایت می‌شود.
           قبلاً این دکمه در صفحهٔ سبد هیچ کاری نمی‌کرد، چون هندلرهای عمومی فقط
           فرم #checkout-form صفحهٔ پرداخت را تریگر می‌کردند که در صفحهٔ سبد
           وجود ندارد (رفع باگ «وارد مرحله بعد نمیشه»). */
        $(document).on('click', '#checkout-link', function (e) {
            if (!$('#cart-update-form').length) {
                return; /* صفحهٔ پرداخت — هندلر خودش در checkout.js است */
            }

            e.preventDefault();

            var btn = this;
            var redirectUrl = $(btn).data('redirect');
            if (!redirectUrl) return;

            clearTimeout(syncTimer);
            block(btn);
            if ($('.checkout-summary').length) { block('.checkout-summary'); }

            $.ajax({
                url: $('#cart-update-form').attr('action'),
                type: 'POST',
                data: $('#cart-update-form').serialize(),
                success: function (res) {
                    applyCartPayload(res);
                    window.location.href = redirectUrl;
                },
                error: function (request) {
                    var res = request.responseJSON;
                    var errors = res && res.errors;
                    if (errors && errors.length) {
                        errors.forEach(function (error) {
                            showCustomToast(error, 'error');
                        });
                    } else {
                        showCustomToast('خطایی رخ داده است.', 'error');
                    }
                    /* وضعیت واقعی سبد برمی‌گردد تا تعداد/جمع کل درست شود */
                    if (res && res.area) {
                        applyCartPayload(res);
                    }
                },
                complete: function () {
                    unblock(btn);
                    if ($('.checkout-summary').length) { unblock('.checkout-summary'); }
                },
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
                }
            });
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
