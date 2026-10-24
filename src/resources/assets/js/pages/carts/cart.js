/* ============================================================
   صفحه سبد خرید — سانیار
   - استپر تعداد (min/max) + برجسته‌سازی دکمه بروزرسانی
   - آپدیت AJAX سبد (PUT /cart) + نمایش خطاهای 406
   - حذف آیتم با مودال تأیید
   - ادامه فرآیند (checkout-link): ابتدا ذخیره مقادیر سپس هدایت به چک‌اوت
   ============================================================ */
(function () {
    'use strict';

    var $ = window.jQuery;
    if (!$) return;

    /* --- toast سبک سانیار (هم‌الگوی products/show.js) --- */
    function toast(message, type) {
        var $t = $('#saniar-toast');
        if (!$t.length) {
            $t = $('<div id="saniar-toast" class="saniar-toast"></div>').appendTo('body');
        }
        $t.text(message)
            .removeClass('saniar-toast-error saniar-toast-success')
            .addClass(type === 'error' ? 'saniar-toast-error' : 'saniar-toast-success')
            .addClass('saniar-toast-show');
        clearTimeout($t.data('timer'));
        $t.data('timer', setTimeout(function () {
            $t.removeClass('saniar-toast-show');
        }, 3200));
    }

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    /* ================= استپر تعداد ================= */
    function clampInput($input) {
        var min = parseInt($input.data('min'), 10);
        var max = parseInt($input.data('max'), 10);
        var val = parseInt($input.val(), 10);

        if (isNaN(val) || val < min) val = min;
        if (!isNaN(max) && val > max) val = max;

        $input.val(val);
        var $counter = $input.closest('.counter');
        $counter.find('[data-dir="up"]').prop('disabled', !isNaN(max) && val >= max);
        $counter.find('[data-dir="down"]').prop('disabled', val <= min);
    }

    function markDirty() {
        $('#update-cart').addClass('needs-update');
    }

    $(document).on('click', '.cart-product-item .counter .btn-counter', function () {
        var $btn = $(this);
        var $input = $btn.closest('.counter').find('.counter-input');
        if ($btn.prop('disabled')) return;

        var val = parseInt($input.val(), 10) || 1;
        var min = parseInt($input.data('min'), 10);
        var max = parseInt($input.data('max'), 10);

        if ($btn.data('dir') === 'up') {
            val = val + 1;
            if (!isNaN(max) && val > max) val = max;
        } else {
            val = val - 1;
            if (isNaN(min) || val < min) val = min;
        }
        $input.val(val);
        clampInput($input);
        markDirty();
    });

    $(document).on('input change', '.cart-product-item .counter .counter-input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
        markDirty();
    });

    $(document).on('blur', '.cart-product-item .counter .counter-input', function () {
        clampInput($(this));
    });

    /* ================= حذف آیتم (مودال) ================= */
    $(document).on('click', '.remove-card', function () {
        $('#delete-form').attr('action', $(this).data('action'));
    });

    /* ================= آپدیت AJAX سبد ================= */
    function updateCart() {
        var dfd = $.Deferred();
        var $form = $('#cart-update-form');
        if (!$form.length) {
            dfd.resolve();
            return dfd.promise();
        }

        var $btn = $('#update-cart').addClass('loading');

        // نکته: PUT /cart متن ساده «success» (یا 406 با JSON) برمی‌گرداند؛
        // dataType نباید json باشد وگرنه پاسخ متنی parseError می‌شود.
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'text',
            headers: { 'X-CSRF-TOKEN': csrfToken() }
        })
            .done(function (res, textStatus, xhr) {
                if (xhr.status === 200 && String(res).trim() === 'success') {
                    dfd.resolve();
                } else {
                    showCartErrors(['بروزرسانی سبد با خطا مواجه شد.']);
                    dfd.reject();
                }
            })
            .fail(function (xhr) {
                if (xhr.status === 406) {
                    var data = null;
                    try { data = JSON.parse(xhr.responseText); } catch (e) { /* noop */ }
                    if (data && data.errors) {
                        showCartErrors(data.errors);
                    } else {
                        showCartErrors(['موجودی برخی کالاها کافی نیست؛ تعداد را اصلاح کنید.']);
                    }
                } else if (xhr.status === 419) {
                    toast('نشست شما منقضی شده؛ صفحه بروزرسانی می‌شود...', 'error');
                    setTimeout(function () { window.location.reload(); }, 1200);
                    return;
                } else {
                    showCartErrors(['خطای غیرمنتظره در بروزرسانی سبد. لطفاً دوباره تلاش کنید.']);
                }
                dfd.reject();
            })
            .always(function () {
                $btn.removeClass('loading');
            });

        return dfd.promise();
    }

    function showCartErrors(errors) {
        var $box = $('#cart-errors');
        if (!$box.length) {
            toast(typeof errors === 'string' ? errors : errors[0], 'error');
            return;
        }
        var $ul = $box.find('ul').empty();
        $.each(errors, function (i, msg) {
            $('<li>').text(msg).appendTo($ul);
        });
        $box.removeClass('d-none');
        if ($box[0].scrollIntoView) {
            $box[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    $('#update-cart').on('click', function () {
        var $inputs = $('.cart-product-item .counter .counter-input');
        $inputs.each(function () { clampInput($(this)); });
        updateCart().done(function () {
            window.location.reload();
        });
    });

    /* ============ ادامه فرآیند: ذخیره سپس هدایت به چک‌اوت ============ */
    $(document).on('click', '#checkout-link', function () {
        if ($('#checkout-form').length) return; // در صفحه چک‌اوت، checkout.js عهده‌دار است

        var $btn = $(this);
        if ($btn.hasClass('loading')) return;
        $btn.addClass('loading');

        updateCart().done(function () {
            window.location.href = $btn.data('redirect');
        }).fail(function () {
            $btn.removeClass('loading');
        });
    });
})();
