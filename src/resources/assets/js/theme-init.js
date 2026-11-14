/* ============================================================
   SaniarTheme — theme-init.js (فاز ۱۵۶)
   ابتدایی‌ساز حالت روز/شب — در <head> و پیش از رندر لود می‌شود
   تا اگر کاربر حالت شب را انتخاب کرده بود، صفحه از همان قاب
   اول تیره رندر شود (بدون چشمک/فلش پس‌زمینهٔ روشن).
   هیچ وابستگی به DOM ندارد؛ فقط attribute روی <html> می‌گذارد.
   ============================================================ */
(function () {
    'use strict';

    var theme = 'light';

    try {
        theme = localStorage.getItem('saniar-theme') === 'dark' ? 'dark' : 'light';
    } catch (e) { /* localStorage در دسترس نیست — حالت پیش‌فرض */ }

    document.documentElement.setAttribute('data-theme', theme);
})();
