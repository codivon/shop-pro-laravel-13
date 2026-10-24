/* ============================================================
   پرسش و پاسخ محصولات فروشنده — سانیار (فاز J)
   حالت index از data-saniar-questions روی تگ اسکریپت:
   - باز کردن مودال جزئیات (AJAX GET → HTML رندرشده)
   - سابمیت دیلیگیت فرم تزریق‌شده (#sfq-question-form) با FormData
   - تاگل ویرایش پاسخ + فیلترهای GET با سابمیت خودکار
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

    function firstValidationError(xhr, fallback) {
        var msg = fallback || 'خطا در ارسال؛ دوباره تلاش کنید.';
        if (xhr && xhr.responseJSON && xhr.responseJSON.errors) {
            var first = null;
            $.each(xhr.responseJSON.errors, function (k, v) { first = v[0]; return false; });
            msg = first || msg;
        } else if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
        }
        return msg;
    }

    var scriptTag = document.currentScript ||
        (function () { var s = document.getElementsByTagName('script'); return s[s.length - 1]; })();
    var MODE = scriptTag.getAttribute('data-saniar-questions');

    /* ================================================================
       حالت index
       ================================================================ */
    function initIndex() {

        /* --- باز کردن مودال: GET جزئیات (HTML رندرشده) --- */
        $(document).on('click', '.sfq-show-btn', function () {
            var $btn = $(this);
            var restore = $btn.html();
            $btn.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>');

            $.ajax({
                url: $btn.data('action'),
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .done(function (data) {
                    // data = HTML رندرشدهٔ پارشال questions/show — عیناً درج می‌شود
                    $('#sfq-question-detail').html(data);
                    var modal = new window.bootstrap.Modal(document.getElementById('sfq-show-modal'));
                    modal.show();
                })
                .fail(function (xhr) {
                    if (xhr.status === 404) {
                        toast('این پرسش به یکی از محصولات شما تعلق ندارد.', 'error');
                    } else {
                        toast('خطا در دریافت جزئیات پرسش.', 'error');
                    }
                })
                .always(function () {
                    $btn.prop('disabled', false).html(restore);
                });
        });

        /* --- تاگل ویرایش پاسخ (فرم تزریق‌شده → دیلیگیت) --- */
        $(document).on('click', '.sfq-edit-toggle', function () {
            var $display = $('#sfq-body');
            var $editor = $('#sfq-edit-body');
            $display.addClass('d-none');
            $editor.removeClass('d-none');
            $('#sfq-edit-textarea').trigger('focus');
        });

        /* --- دکمه ذخیره (type=button) → سابمیت فرم --- */
        $(document).on('click', '#sfq-question-submit', function () {
            $('#sfq-question-form').trigger('submit');
        });

        /* --- سابمیت فرم (دیلیگیت — FormData شامل _method=put از @method) --- */
        $(document).on('submit', '#sfq-question-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $('#sfq-question-submit').prop('disabled', true);
            var restore = $btn.html();
            var $err = $('#sfq-question-error').addClass('d-none').empty();

            // اعتبارسنجی کلاینت: پاسخ جدید یا متن ویرایش‌شده نباید خالی باشد
            var $replay = $form.find('textarea[name="replay"]');
            var $body = $form.find('textarea[name="body"]');
            if ($replay.length && !$.trim($replay.val() || '')) {
                toast('متن پاسخ را وارد کنید.', 'warning');
                $replay.trigger('focus');
                $btn.prop('disabled', false);
                return;
            }
            if ($body.length && !$replay.length && !$.trim($body.val() || '')) {
                toast('متن پاسخ نمی‌تواند خالی باشد.', 'warning');
                $body.trigger('focus');
                $btn.prop('disabled', false);
                return;
            }

            $btn.html('<span class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>در حال ذخیره…');

            var fd = new FormData($form[0]);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' }
            })
                .done(function () {
                    var modalEl = document.getElementById('sfq-show-modal');
                    var instance = window.bootstrap.Modal.getInstance(modalEl);
                    if (instance) instance.hide();
                    toast('پاسخ شما ثبت شد و پس از بررسی نمایش داده می‌شود.', 'success');
                    setTimeout(function () { window.location.reload(); }, 800);
                })
                .fail(function (xhr) {
                    $btn.prop('disabled', false).html(restore);
                    if (xhr.status === 419) {
                        toast('نشست شما منقضی شده؛ صفحه را نوسازی کنید.', 'warning');
                        return;
                    }
                    $err.text(firstValidationError(xhr)).removeClass('d-none');
                });
        });

        /* --- فیلترها: تغییر هر کنترل → سابمیت GET --- */
        $('#sfq-filter-form').on('change', 'select', function () {
            this.form.submit();
        });

        /* --- پاک کردن فیلترها --- */
        $('#sfq-reset-btn').on('click', function () {
            $('#sfq-filter-form').find('select').each(function () {
                $(this).find('option').first().prop('selected', true);
            });
            $('#sfq-filter-form').get(0).submit();
        });
    }

    $(function () {
        if (MODE === 'index') initIndex();
    });
})();
