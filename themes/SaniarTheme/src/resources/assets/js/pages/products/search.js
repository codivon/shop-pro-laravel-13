/**
 * SaniarTheme — صفحه نتایج جستجو
 * حفظ تب فعال بعد از رفرش (بوت‌استرپ ۵)
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var STORAGE_KEY = 'activeSearchTab';
        var tabButtons = document.querySelectorAll('#searchTab button[data-bs-toggle="tab"]');

        if (!tabButtons.length) {
            return;
        }

        // ذخیره تب فعال
        tabButtons.forEach(function (button) {
            button.addEventListener('shown.bs.tab', function () {
                try {
                    localStorage.setItem(STORAGE_KEY, button.getAttribute('data-bs-target'));
                } catch (err) { /* ignore */ }
            });
        });

        // بازیابی تب فعال
        var activeTarget = null;
        try {
            activeTarget = localStorage.getItem(STORAGE_KEY);
        } catch (err) { /* ignore */ }

        if (!activeTarget) {
            return;
        }

        tabButtons.forEach(function (button) {
            if (button.getAttribute('data-bs-target') === activeTarget && window.bootstrap && window.bootstrap.Tab) {
                window.bootstrap.Tab.getOrCreateInstance(button).show();
            }
        });
    });
})();
