(function () {
    'use strict';

    // رنگ اصلی از تنظیمات ادمین (data-main-color روی .fw-wrapper)
    document.addEventListener('DOMContentLoaded', function () {
        var wrapper = document.querySelector('.fw-wrapper');
        if (wrapper && wrapper.dataset.mainColor) {
            var root = document.documentElement;
            root.style.setProperty('--fw-primary', wrapper.dataset.mainColor);
            root.style.setProperty('--fw-primary-dark',
                'color-mix(in srgb, ' + wrapper.dataset.mainColor + ' 80%, #000)');
        }
    });
})();

/**
 * SaniarTheme — ویجت شناور تماس (fab/panel/tabs)
 * استخراج‌شده از floating-widget.blade.php
 */
    (function () {
        var fab   = document.getElementById('fw-fab');
        var panel = document.getElementById('fw-panel');
        var tabs  = document.querySelectorAll('.fw-tab-btn');
        var panes = document.querySelectorAll('.fw-tab-pane');

        fab.addEventListener('click', function () {
            fab.classList.toggle('fw-open');
            panel.classList.toggle('fw-panel-open');
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.fw-wrapper')) {
                fab.classList.remove('fw-open');
                panel.classList.remove('fw-panel-open');
            }
        });

        tabs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                tabs.forEach(function (b) { b.classList.remove('fw-tab-active'); });
                panes.forEach(function (p) { p.classList.remove('fw-active'); });
                btn.classList.add('fw-tab-active');
                var target = document.getElementById(btn.getAttribute('data-tab'));
                if (target) target.classList.add('fw-active');
            });
        });
    })();
