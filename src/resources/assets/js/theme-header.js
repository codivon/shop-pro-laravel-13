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

    /* =========================================================
       فاز ۱۵۶ — ۱) همگام‌سازی سبد خرید پس از افزودن ای‌جکسی
       صدا زده‌شده از show.js / main.js بعد از replaceWith سایدبار:
         - شمارندهٔ هدر (.header-cart-counter) بلافاصله به‌روز شود
         - بک‌دراپ‌های یتیم (سبب «از دست رفتن استایلِ» سایدبار) پاک شوند
         - اگر سایدبار باز بود، باز بماند
       ========================================================= */
    window.SaniarThemeCartSync = function (cartHtml, wasOpen) {
        var count = 0;

        if (typeof cartHtml === 'string') {
            var match = cartHtml.match(/data-cart-count="(\d+)"/);
            if (match) { count = parseInt(match[1], 10) || 0; }
        } else if (cartHtml && cartHtml.getAttribute) {
            count = parseInt(cartHtml.getAttribute('data-cart-count') || '0', 10) || 0;
        }

        /* جایگزین: شمارش آیتم‌ها در DOM تازه (بدون ردیف فاکتور) */
        var fresh = document.querySelector('.cart-canvas .cart-canvases');
        if (!count && fresh) {
            count = fresh.querySelectorAll(':scope > .item').length;
            if (fresh.querySelector(':scope > .item .factor')) { count -= 1; }
        }
        if (count < 0) { count = 0; }

        document.querySelectorAll('.header-cart-counter').forEach(function (el) {
            el.textContent = String(count);
            el.classList.toggle('d-none', count === 0);
        });

        /* پاک‌سازی بک‌دراپ‌های یتیم — عامل اصلی به‌هم‌ریختن ظاهر سایدبار */
        document.querySelectorAll('.offcanvas-backdrop').forEach(function (node) {
            node.remove();
        });

        /* اگر سایدبار قبل از جایگزینی باز بود، دوباره بازش کن */
        if (wasOpen) {
            var canvas = document.getElementById('offcanvasCart');
            if (canvas && window.bootstrap && window.bootstrap.Offcanvas) {
                window.bootstrap.Offcanvas.getOrCreateInstance(canvas).show();
            }
        }
    };

    /* =========================================================
       فاز ۱۵۶ — ۲) دکمهٔ «برگشت به بالا» — مستطیلی، وسط صفحه
       جایگزین پلاگین go-to-top (دایرهٔ گوشهٔ پایین)
       ========================================================= */
    function initBackToTop() {
        var btn = document.getElementById('back-to-top');
        if (!btn) { return; }

        var toggle = function () {
            if (window.pageYOffset > 350) {
                btn.classList.add('is-visible');
            } else {
                btn.classList.remove('is-visible');
            }
        };

        window.addEventListener('scroll', toggle, { passive: true });
        toggle();

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* =========================================================
       فاز ۱۵۶ — ۳) کلید روز / شب (دارک مود)
       حالت در localStorage ذخیره می‌شود؛ مقداردهی اولیهٔ
       html[data-theme] در theme-init.js (سربین) انجام می‌شود
       تا صفحه هنگام لود چشمک نزند.
       ========================================================= */
    function initThemeToggle() {
        var toggle = document.getElementById('theme-toggle');
        if (!toggle || toggle.dataset.bound === '1') { return; }
        toggle.dataset.bound = '1';

        toggle.addEventListener('click', function () {
            var root = document.documentElement;
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';

            root.setAttribute('data-theme', next);
            try { localStorage.setItem('saniar-theme', next); } catch (e) { /* حافظه در دسترس نیست */ }

            /* همگام‌سازی همهٔ کلیدهای موجود در صفحه */
            document.querySelectorAll('#theme-toggle').forEach(function (btn) {
                btn.classList.toggle('is-dark', next === 'dark');
                btn.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
                btn.setAttribute('title', next === 'dark' ? 'حالت روز' : 'حالت شب');
            });

            try {
                document.dispatchEvent(new CustomEvent('saniar-theme-changed', { detail: { theme: next } }));
            } catch (e) { /* مرورگر قدیمی */ }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initBackToTop();
            initThemeToggle();
        });
    } else {
        initBackToTop();
        initThemeToggle();
    }
})();
