/* ============================================================
   تعرفه‌های ارسال فروشنده — سانیار (فاز J)
   حالت‌ها از data-saniar-tariffs روی تگ اسکریپت:
   - index  : مودال حذف تعرفه (AJAX + reload)
   - create : فرم ایجاد تعرفه (اعتبارسنجی کلاینت + AJAX)
   - edit   : همان فرم با مقادیر بایندشده
   بدون پلاگین جدید — jQuery + Bootstrap5 موجود قالب
   ============================================================ */
(function () {
    'use strict';

    var $ = window.jQuery;
    if (typeof $ === 'undefined') return;

    /* ================= ابزارهای مشترک (الگوی seller-orders.js) ================= */

    function esc(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function toast(message, type) {
        var $t = $('#saniar-toast');
        if (!$t.length) {
            $t = $('<div id="saniar-toast" class="saniar-toast"></div>').appendTo('body');
        }
        $t.text(message)
            .removeClass('saniar-toast-error saniar-toast-success saniar-toast-warning')
            .addClass(type === 'error' ? 'saniar-toast-error' : (type === 'warning' ? 'saniar-toast-warning' : 'saniar-toast-success'))
            .addClass('saniar-toast-show');
        if ($t.data('timer')) clearTimeout($t.data('timer'));
        $t.data('timer', setTimeout(function () { $t.removeClass('saniar-toast-show'); }, 3600));
    }

    function csrf() {
        return $('meta[name="csrf-token"]').attr('content');
    }

    function ajaxError(xhr, fallback) {
        var msg = fallback || 'خطای ناشناخته';
        if (xhr && xhr.responseJSON) {
            if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
            else if (xhr.responseJSON.errors) {
                var first = Object.keys(xhr.responseJSON.errors)[0];
                if (first) msg = xhr.responseJSON.errors[first][0];
            }
        }
        toast(msg, 'error');
    }

    var scriptTag = document.currentScript ||
        (function () { var s = document.getElementsByTagName('script'); return s[s.length - 1]; })();
    var MODE = scriptTag.getAttribute('data-saniar-tariffs') || '';

    /* ================================================================
       بخش ۱) حالت index — مودال حذف
       ================================================================ */
    function initIndex() {

        $(document).on('click', '.sft-btn-delete', function () {
            $('#sft-tariff-delete-form').attr('action', $(this).data('action') || '#');
        });

        $('#sft-tariff-delete-form').on('submit', function (e) {
            e.preventDefault();
            var form = this;
            var $btn = $(form).find('button[type="submit"]').prop('disabled', true);

            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                data: new FormData(form), /* شامل _token و _method=delete */
                processData: false,
                contentType: false,
                cache: false,
                headers: { 'X-CSRF-TOKEN': csrf() }
            }).done(function (res) {
                if (String(res).trim() === 'success') {
                    $('#sft-delete-modal').modal('hide');
                    toast('تعرفه با موفقیت حذف شد.', 'success');
                    setTimeout(function () { window.location.reload(); }, 700);
                } else {
                    toast('پاسخ نامعتبر از سرور دریافت شد.', 'error');
                    $btn.prop('disabled', false);
                }
            }).fail(function (xhr) {
                if (xhr.status === 419) {
                    toast('نشست شما منقضی شده؛ صفحه را نوسازی کنید.', 'warning');
                } else {
                    ajaxError(xhr, 'خطا در حذف تعرفه');
                }
                $btn.prop('disabled', false);
            });
        });
    }

    /* ================================================================
       بخش ۲) حالت create + edit — فرم تعرفه
       ================================================================ */
    function initForm() {
        var $form = $('#sft-tariff-form');
        if (!$form.length) return;

        var submitting = false;

        $form.on('submit', function (e) {
            e.preventDefault();
            if (submitting) return;

            /* اعتبارسنجی کلاینت */
            var type = $('#sft-type').val();
            var maxWeight = $.trim($('#sft-max-weight').val());
            var shippingCost = $.trim($('#sft-shipping-cost').val());

            if (!type) {
                toast('نوع منطقه ارسالی را انتخاب کنید.', 'error');
                return;
            }
            if (maxWeight === '' || isNaN(Number(maxWeight)) || Number(maxWeight) <= 0) {
                toast('حداکثر وزن (گرم) معتبر وارد کنید.', 'error');
                return;
            }
            if (shippingCost === '' || isNaN(Number(shippingCost)) || Number(shippingCost) < 0) {
                toast('هزینه ارسال (تومان) معتبر وارد کنید.', 'error');
                return;
            }

            submitting = true;
            var $btn = $('#sft-submit-btn').prop('disabled', true);
            var $spinner = $btn.find('.saniar-btn-spinner').removeClass('d-none');
            var done = false;

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: new FormData($form[0]),
                processData: false,
                contentType: false,
                cache: false,
                headers: { 'X-CSRF-TOKEN': csrf() }
            }).done(function (res) {
                if (String(res).trim() === 'success') {
                    done = true; /* گارد دوبل‌سابمیت تا هدایت کامل می‌ماند */
                    window.location.href = $form.data('redirect'); /* توست سشن بعد از هدایت نمایش داده می‌شود */
                } else {
                    toast('پاسخ نامعتبر از سرور دریافت شد.', 'error');
                }
            }).fail(function (xhr) {
                if (xhr.status === 419) {
                    toast('نشست شما منقضی شده؛ صفحه را نوسازی کنید.', 'warning');
                } else {
                    ajaxError(xhr, 'خطا در ذخیره تعرفه');
                }
            }).always(function () {
                submitting = false;
                if (!done) {
                    $btn.prop('disabled', false);
                    $spinner.addClass('d-none');
                }
            });
        });
    }

    $(function () {
        if (MODE === 'index') initIndex();
        if (MODE === 'create' || MODE === 'edit') initForm();
    });
})();
