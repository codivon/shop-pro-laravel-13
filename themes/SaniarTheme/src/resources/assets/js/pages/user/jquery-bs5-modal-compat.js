/**
 * پل jQuery ↔ Bootstrap 5 برای مودال‌ها
 * اسکریپت‌های قدیمی پنل کاربر از $(...).modal('show') استفاده می‌کنند؛
 * در بوت‌استرپ ۵ این متد حذف شده است. این فایل حداقلی آن را با bootstrap.Modal بازسازی می‌کند
 * تا بدون تغییر اسکریپت‌های کارکردی، مودال‌ها به درستی باز شوند.
 */
(function () {
    'use strict';

    if (!window.jQuery || !window.bootstrap || !window.bootstrap.Modal) {
        return;
    }

    if (typeof window.jQuery.fn.modal === 'undefined') {
        window.jQuery.fn.modal = function (action) {
            this.each(function () {
                var instance = window.bootstrap.Modal.getOrCreateInstance(this);
                if (action === 'show') {
                    instance.show();
                } else if (action === 'hide') {
                    instance.hide();
                }
            });
            return this;
        };
    }
})();
