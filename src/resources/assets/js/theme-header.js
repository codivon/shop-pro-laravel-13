/**
 * SaniarTheme — theme-header.js
 * منطق هدر: جستجوی زنده (پنل نتایج) + دکمه جستجو
 * بدون هیچ کد JS داخل فایل‌های بلید.
 */
(function () {
    'use strict';

    function initHeaderSearch() {
        var form = document.getElementById('search-form');
        var input = document.getElementById('search-input');
        var panel = document.getElementById('search-result');
        var btn = document.getElementById('search-header-btn-search');

        function hidePanel() {
            if (!panel) { return; }
            panel.classList.remove('open', 'd-none');
            panel.innerHTML = '';
        }

        function showPanel(html) {
            if (!panel) { return; }
            // کلاس d-none ممکن است توسط اسکریپت‌های قدیمی اضافه شده باشد
            panel.classList.remove('d-none');
            panel.innerHTML = html;
            panel.classList.add('open');
        }

        // ---------- جستجوی زنده ----------
        if (input && panel) {
            var timer = null;
            var request = null;

            var runSearch = function () {
                var q = (input.value || '').trim();

                if (q.length < 2) {
                    hidePanel();
                    return;
                }

                if (request && request.abort) { request.abort(); }

                showPanel('<div class="sr-empty"><i class="bi bi-arrow-repeat"></i> در حال جستجو…</div>');

                request = fetch('/search', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                    },
                    body: JSON.stringify({ q: q })
                })
                    .then(function (res) { return res.ok ? res.json() : { html: '' }; })
                    .then(function (data) {
                        var html = (data && data.html) ? data.html : '';
                        if ((input.value || '').trim().length < 2) { return; }
                        if (!html.replace(/\s/g, '').length) {
                            showPanel('<div class="sr-empty">نتیجه‌ای یافت نشد</div>');
                        } else {
                            showPanel(html);
                        }
                    })
                    .catch(function () {
                        // درخواست لغو شده یا خطای شبکه — پنل را دست نمی‌زنیم
                    });
            };

            input.addEventListener('keyup', function () {
                window.clearTimeout(timer);
                timer = window.setTimeout(runSearch, 350);
            });

            input.addEventListener('focus', function () {
                if (panel.innerHTML.replace(/\s/g, '').length) {
                    panel.classList.remove('d-none');
                    panel.classList.add('open');
                }
            });

            // بستن با کلیک بیرون از باکس جستجو
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.search-header')) {
                    hidePanel();
                }
            });

            // بستن با Escape
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { hidePanel(); }
            });
        }

        // ---------- دکمه جستجو (اسکریپت قدیمی event را preventDefault می‌کند) ----------
        if (btn && form) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (input && (input.value || '').trim().length > 0) {
                    form.submit();
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeaderSearch);
    } else {
        initHeaderSearch();
    }
})();
