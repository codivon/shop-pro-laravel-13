/* ═══════════════════════════════════════════════════════════════════
   کیت تب‌بندی تنظیمات — فاز ۱۰۵ (جاوااسکریپت مشترک)
   رفتار: کلیک تب → نمایش پنل هم‌نام + aria + دیپ‌لینک با هش (#tab=کلید)
   هیچ وابستگی‌ای به jQuery/Livewire ندارد؛ چند نمونه در یک صفحه هم
   پشتیبانی می‌شود (هر nav فقط تب‌های داخل خودش را مدیریت می‌کند).
   ═══════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    function activate(tab) {
        var nav = tab.closest('.stab-tabs');
        if (!nav) return;

        var scope = nav.getAttribute('data-stab-scope') ? document.querySelector('[data-stab-root="' + nav.getAttribute('data-stab-scope') + '"]') || document : document;

        nav.querySelectorAll('.stab-tab').forEach(function (t) {
            var on = t === tab;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });

        var target = tab.getAttribute('data-stab');
        scope.querySelectorAll('.stab-pane').forEach(function (pane) {
            pane.classList.toggle('is-active', pane.getAttribute('data-stab-pane') === target);
        });

        /* دیپ‌لینک: هش صفحه به‌روز می‌شود تا بازکردن مجدد/اشتراک لینک همان تب را بیاورد */
        if (history.replaceState) {
            history.replaceState(null, '', '#tab=' + encodeURIComponent(target));
        }
    }

    function init() {
        document.querySelectorAll('.stab-tabs').forEach(function (nav) {
            nav.querySelectorAll('.stab-tab').forEach(function (tab) {
                tab.addEventListener('click', function () { activate(tab); });
            });
        });

        /* بازیابی تب از هش هنگام ورود */
        var match = /^#tab=(.+)$/.exec(location.hash || '');
        if (match) {
            var key = decodeURIComponent(match[1]);
            var wanted = document.querySelector('.stab-tab[data-stab="' + key + '"]');
            if (wanted) { wanted.click(); }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
