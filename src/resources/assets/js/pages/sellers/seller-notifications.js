/* ============================================================
   پیام‌ها (اعلان‌های) فروشنده — سانیار (فاز J)
   حالت index از data-saniar-notifications روی تگ اسکریپت:
   - نشانه‌گذاری «خوانده‌شده» در اولین باز شدن آیتم (POST) و
     به‌روزرسانی نشان ناخوانده در سایدبار (.notifications-count-number)
   - فیلترهای GET با سابمیت خودکار + دکمه پاک‌کردن
   بدون پلاگین جدید — jQuery + Bootstrap5 موجود قالب
   ============================================================ */
(function () {
    'use strict';

    var $ = window.jQuery;
    if (typeof $ === 'undefined') return;

    /* ================= ابزارهای مشترک (الگوی seller-orders.js) ================= */

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

    var scriptTag = document.currentScript ||
        (function () { var s = document.getElementsByTagName('script'); return s[s.length - 1]; })();
    var MODE = scriptTag.getAttribute('data-saniar-notifications');

    /* ================================================================
       حالت index
       ================================================================ */
    function initIndex() {

        /* --- به‌روزرسانی همهٔ نشان‌های ناخوانده (سایدبار و… ) --- */
        function syncUnreadBadges(count) {
            var n = parseInt(count, 10);
            if (isNaN(n)) return;
            $('.notifications-count-number').each(function () {
                var $b = $(this);
                if (n > 0) {
                    $b.text(n).removeClass('d-none');
                } else {
                    $b.addClass('d-none');
                }
            });
        }

        /* --- نشانه‌گذاری خوانده‌شده در اولین باز شدن آیتم ---
           رویداد Bootstrap5 رویداد بومی CustomEvent است؛ در سطح document
           با addEventListener گوش می‌دهیم (دیلیگیتِ namespaceدارِ jQuery جواب نمی‌دهد). */
        document.addEventListener('show.bs.collapse', function (e) {
            var $item = $(e.target).closest('.sfn-item');
            var $toggle = $item.find('.sfn-toggle');
            if (!$toggle.length || $toggle.attr('data-read') !== 'no' || $toggle.data('marking')) return;

            $toggle.data('marking', true);
            $.ajax({
                url: $toggle.attr('data-action'),
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                dataType: 'json'
            })
                .done(function (res) {
                    if (!res || res.status !== 'success') return;
                    $toggle.attr('data-read', 'yes');
                    syncUnreadBadges(res.count);
                    // مخفی‌کردن نقطهٔ «خوانده‌نشده» + متن وضعیت
                    $item.find('.sfn-unread-dot').addClass('d-none');
                    $item.find('.sfn-read-state')
                        .removeClass('text-danger')
                        .addClass('text-success')
                        .text('خوانده شده');
                })
                .fail(function (xhr) {
                    if (xhr && xhr.status === 419) {
                        toast('نشست شما منقضی شده؛ صفحه را نوسازی کنید.', 'warning');
                    } else {
                        toast('خطا در ثبت وضعیت پیام.', 'error');
                    }
                })
                .always(function () {
                    $toggle.data('marking', false);
                });
        });

        /* --- فیلترها: تغییر هر کنترل → سابمیت GET --- */
        $('#sfn-filter-form').on('change', 'select, input', function () {
            this.form.submit();
        });

        /* --- پاک کردن فیلترها --- */
        $('#sfn-reset-btn').on('click', function () {
            var $form = $('#sfn-filter-form');
            $form.find('select').each(function () {
                $(this).find('option').first().prop('selected', true);
            });
            $form.find('input[type="checkbox"]').prop('checked', false);
            $form.get(0).submit();
        });
    }

    $(function () {
        if (MODE === 'index') initIndex();
    });
})();
