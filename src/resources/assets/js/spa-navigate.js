/**
 * سانیار فاز K — ناوبری SPA سراسری (Livewire 4 wire:navigate engine)
 * ------------------------------------------------------------------------
 * wire:navigate روی لینک‌های ساختاری (لوگو/فوتر/نوار موبایل) به‌صورت اتریبیوتی
 * ست شده؛ برای پوشش همهٔ لینک‌های محتوایی (منوها/کارت‌ها) بدون ویرایش ده‌ها
 * ویو، این اسکریپت کلیک‌های لینک داخلی را intercept کرده و از
 * window.Livewire.navigate() (همان موتور wire:navigate) استفاده می‌کند.
 *
 * گاردها:
 *  - فقط کلیک چپ بدون کلیدهای اصلاحی
 *  - href خالی/#/javascript:/tel:/mailto: نادیده
 *  - target=_blank و download نادیده
 *  - لینک‌های بیرونی (مبدأ متفاوت) نادیده
 *  - مسیرهای پنل ادمین و assetهای لایووایر → ناوبری کامل (MPA)
 *  - هر عنصر داخل [data-no-spa] → ناوبری کامل
 *  - e.defaultPrevented → خروج (جلوگیری از دوبار-handle شدن در اجرای مجدد
 *    اسکریپت بعد از هر ناوبری — اسکریپت‌های body بدون data-navigate-once
 *    مجدد اجرا می‌شوند و این listener در هر ناوبری دوباره بسته می‌شود)
 */
(function () {
    'use strict';

    var EXCLUDED_PATHS = /^\/(admin|admin-old|np|livewire)(\/|$)/;

    function shouldHandleNatively(link) {
        if (link.target && link.target !== '_self') return true;
        if (link.hasAttribute('download')) return true;
        if (link.hasAttribute('data-bs-toggle')) return true;
        if (link.closest('[data-no-spa]')) return true;

        var href = link.getAttribute('href');
        if (!href || href === '#') return true;
        if (href.indexOf('javascript:') === 0) return true;
        if (href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) return true;
        if (href.indexOf('sms:') === 0 || href.indexOf('blob:') === 0 || href.indexOf('data:') === 0) return true;

        var url;
        try {
            url = new URL(href, window.location.origin);
        } catch (e) {
            return true;
        }
        if (url.origin !== window.location.origin) return true;
        if (EXCLUDED_PATHS.test(url.pathname)) return true;

        return false;
    }

    document.addEventListener('click', function (e) {
        if (e.defaultPrevented) return;
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        var link = e.target.closest ? e.target.closest('a') : null;
        if (!link) return;

        if (shouldHandleNatively(link)) return;

        // اگر لایووایر هنوز حاضر نیست (مثلاً صفحهٔ پنل کاربر) → رفتار پیش‌فرض
        if (!window.Livewire || typeof window.Livewire.navigate !== 'function') return;

        var url = new URL(link.getAttribute('href'), window.location.origin);

        // صفحهٔ جاری با هش؟ اسکرول نرمال بماند
        if (url.pathname === window.location.pathname &&
            url.search === window.location.search &&
            url.hash) {
            return;
        }

        e.preventDefault();
        window.Livewire.navigate(url.href);
    });
})();
