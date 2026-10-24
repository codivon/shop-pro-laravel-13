/* ============================================================
   تیکت‌های پشتیبانی فروشنده — سانیار (فاز J)
   حالت‌ها از data-saniar-tickets روی تگ اسکریپت:
   - create : فرم ثبت تیکت (FormData + فایل) → ریدایرکت به لیست
   - show   : اسکرول گفتگو به پایین + فرم پاسخ (FormData + فایل) → رفرش
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

    /* ارسال فرم تیکت (FormData — فایل دارد) با اسپینر + خطای 419/422 */
    function submitTicketForm($form, clientChecks, successMsg) {
        var $btn = $form.find('button[type="submit"]').prop('disabled', true);
        var restore = $btn.html();
        var $err = $form.find('#sfk-ticket-form-error').addClass('d-none').empty();

        if (clientChecks && clientChecks() === false) {
            $btn.prop('disabled', false);
            return;
        }

        $btn.html('<span class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>در حال ارسال…');

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
                toast(successMsg, 'success');
                setTimeout(function () {
                    // data-redirect: در create → لیست تیکت‌ها؛ در show → همین صفحه (رفرش گفتگو)
                    window.location.href = $form.attr('data-redirect') || window.location.href;
                }, 800);
            })
            .fail(function (xhr) {
                $btn.prop('disabled', false).html(restore);
                if (xhr.status === 419) {
                    toast('نشست شما منقضی شده؛ صفحه را نوسازی کنید.', 'warning');
                    return;
                }
                var msg = 'خطا در ارسال؛ دوباره تلاش کنید.';
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var first = null;
                    $.each(xhr.responseJSON.errors, function (k, v) { first = v[0]; return false; });
                    msg = first || msg;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                $err.text(msg).removeClass('d-none');
            });
    }

    var scriptTag = document.currentScript ||
        (function () { var s = document.getElementsByTagName('script'); return s[s.length - 1]; })();
    var MODE = scriptTag.getAttribute('data-saniar-tickets');

    /* ================================================================
       حالت create — فرم ثبت تیکت
       ================================================================ */
    function initCreate() {
        var $form = $('#sfk-ticket-form');
        if (!$form.length) return;

        $form.on('submit', function (e) {
            e.preventDefault();
            submitTicketForm($form, function () {
                var subject = $.trim($('#sfk-subject').val() || '');
                var message = $.trim($('#sfk-message').val() || '');
                if (!subject) {
                    toast('موضوع تیکت را وارد کنید.', 'warning');
                    $('#sfk-subject').trigger('focus');
                    return false;
                }
                if (!message) {
                    toast('متن پیام را وارد کنید.', 'warning');
                    $('#sfk-message').trigger('focus');
                    return false;
                }
            }, 'تیکت شما ثبت شد.');
        });
    }

    /* ================================================================
       حالت show — اسکرول گفتگو + فرم پاسخ
       ================================================================ */
    function initShow() {
        // اسکرول باکس گفتگو به آخرین پیام
        var $chat = $('#sfk-chat-scroll');
        if ($chat.length) {
            $chat[0].scrollTop = $chat[0].scrollHeight;
        }

        var $form = $('#sfk-reply-form');
        if (!$form.length) return;

        $form.on('submit', function (e) {
            e.preventDefault();
            submitTicketForm($form, function () {
                var message = $.trim($('#sfk-reply-message').val() || '');
                if (!message) {
                    toast('متن پاسخ را وارد کنید.', 'warning');
                    $('#sfk-reply-message').trigger('focus');
                    return false;
                }
            }, 'پاسخ شما ثبت شد.');
        });
    }

    $(function () {
        if (MODE === 'create') initCreate();
        if (MODE === 'show') initShow();
    });
})();
