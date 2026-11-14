/**
 * SaniarTheme — theme-header.js
 * منطق هدر: جستجوی زنده (پنل نتایج) + دکمه جستجو
 * بدون هیچ کد JS داخل فایل‌های بلید.
 */
(function () {
    'use strict';

    function initHeaderSearch() {
        /* =========================================================
           فاز ۱۶۳ — جستجوی لحظه‌ای پیشرفتهٔ سانیار
           - فوکوس روی جستجو → پنل «جستجوهای اخیر + پرطرفدار + بنر»
           - تایپ (۲ کاراکتر به بعد) → نتایج زنده: دسته‌بندی/برند/محصول
           - همه از GET /search/suggest (JSON + کش سمت سرور)
           - جستجوهای اخیر در localStorage مرورگر کاربر
           ========================================================= */
        var suggestUrl = (document.querySelector('meta[name="saniar-suggest"]') || {}).content || '/search/suggest';

        /* ---------- ابزارها ---------- */
        function escapeHtml(text) {
            return String(text == null ? '' : text)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function getRecent() {
            try {
                var list = JSON.parse(localStorage.getItem('saniarRecentSearches') || '[]');
                return Array.isArray(list) ? list : [];
            } catch (e) {
                return [];
            }
        }

        function pushRecent(term) {
            term = (term || '').trim();
            if (!term) { return; }
            var list = getRecent().filter(function (t) { return t !== term; });
            list.unshift(term);
            try { localStorage.setItem('saniarRecentSearches', JSON.stringify(list.slice(0, 6))); } catch (e) { /* */ }
        }

        /* ---------- رندر ---------- */
        function chip(term, withIcon) {
            return '<button type="button" class="sr2-chip js-sr2-term" data-term="' + escapeHtml(term) + '">' +
                (withIcon ? '<i class="bi bi-arrow-left-right"></i>' : '') +
                '<span>' + escapeHtml(term) + '</span>' +
                '</button>';
        }

        function resultRow(item, icon, withThumb, contextPrefix) {
            var thumb = withThumb
                ? (item.image
                    ? '<span class="sr2-thumb"><img src="' + escapeHtml(item.image) + '" loading="lazy" alt=""></span>'
                    : '<span class="sr2-thumb sr2-thumb-empty"><i class="bi bi-box"></i></span>')
                : '<span class="sr2-row-icon"><i class="bi ' + icon + '"></i></span>';

            return '<a href="' + escapeHtml(item.url) + '" class="sr2-row">' +
                '<span class="sr2-row-main">' + thumb +
                '<span class="sr2-row-title">' + escapeHtml(item.title) + '</span></span>' +
                (item.context ? '<span class="sr2-row-context">' + escapeHtml((contextPrefix || '') + item.context) + '</span>' : '') +
                '</a>';
        }

        function renderEmptyState(panel, data) {
            var recent = getRecent();
            var popular = (data && data.popular) || [];
            var banner = data && data.banner;
            var html = '';
            var hasSection = false;

            if (recent.length) {
                hasSection = true;
                html += '<div class="sr2-section"><div class="sr2-heading">جستجوهای اخیر</div><div class="sr2-chips">';
                recent.forEach(function (t) { html += chip(t, false); });
                html += '</div></div>';
            }

            if (popular.length) {
                hasSection = true;
                html += '<div class="sr2-section"><div class="sr2-heading">جستجوهای پرطرفدار</div><div class="sr2-chips">';
                popular.forEach(function (t) { html += chip(t, true); });
                html += '</div></div>';
            }

            if (banner) {
                hasSection = true;
                html += '<a href="' + escapeHtml(banner.link || '#') + '" class="sr2-banner">' +
                    '<span class="sr2-banner-text">' +
                    '<span class="sr2-banner-title">' + escapeHtml(banner.title || '') + '</span>' +
                    '<span class="sr2-banner-sub">' + escapeHtml(banner.subtitle || '') + '</span>' +
                    '</span>' +
                    '<i class="bi bi-arrow-left"></i>' +
                    '</a>';
            }

            panel.innerHTML = hasSection ? html : '<div class="sr2-empty">چیزی برای نمایش نیست</div>';
            panel.classList.add('open');
        }

        function renderResults(panel, data, q) {
            var html = '';

            if (data.categories && data.categories.length) {
                html += '<div class="sr2-section"><div class="sr2-heading">دسته‌بندی‌ها</div>';
                data.categories.forEach(function (item) { html += resultRow(item, 'bi-grid', false, 'در فروشی '); });
                html += '</div>';
            }

            if (data.brands && data.brands.length) {
                html += '<div class="sr2-section"><div class="sr2-heading">برندها</div>';
                data.brands.forEach(function (item) {
                    html += resultRow({ title: item.title, url: item.url, context: 'همه کالاهای برند ' + item.title }, 'bi-award', false, '');
                });
                html += '</div>';
            }

            if (data.products && data.products.length) {
                html += '<div class="sr2-section"><div class="sr2-heading">محصولات</div>';
                data.products.forEach(function (item) { html += resultRow(item, 'bi-box', true, 'در فروشی '); });
                html += '</div>';
            }

            if (data.all) {
                html += '<a href="' + escapeHtml(data.all) + '" class="sr2-row sr2-all">' +
                    '<span class="sr2-row-main"><span class="sr2-row-icon"><i class="bi bi-search"></i></span>' +
                    '<span class="sr2-row-title">مشاهده همهٔ نتایج «' + escapeHtml(q) + '»</span></span>' +
                    '<span class="sr2-row-context"><i class="bi bi-arrow-left"></i></span>' +
                    '</a>';
            }

            panel.innerHTML = html || '<div class="sr2-empty">نتیجه‌ای برای «' + escapeHtml(q) + '» یافت نشد</div>';
            panel.classList.add('open');
        }

        /* ---------- اتصال به هر فرم جستجو (دسکتاپ + موبایل) ---------- */
        document.querySelectorAll('.search-form').forEach(function (form) {
            var input = form.querySelector('input[name="q"]');
            if (!input || input.dataset.sr2Bound === '1') { return; }
            input.dataset.sr2Bound = '1';

            var panel = document.createElement('div');
            panel.className = 'saniar-search-panel';
            form.appendChild(panel);

            var hidePanel = function () {
                panel.classList.remove('open');
                panel.innerHTML = '';
            };

            var timer = null;
            var controller = null;

            var fetchSuggest = function (term, done) {
                if (controller && controller.abort) { controller.abort(); }
                if (window.AbortController) { controller = new AbortController(); }

                fetch(suggestUrl + '?q=' + encodeURIComponent(term), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller ? controller.signal : undefined
                })
                    .then(function (res) { return res.ok ? res.json() : {}; })
                    .then(done)
                    .catch(function () { /* لغو یا خطای شبکه — پنل دست نمی‌خورد */ });
            };

            /* فوکوس / کلیک با ورودی خالی → پنل اخیر + پرطرفدار */
            var showIdlePanel = function () {
                if ((input.value || '').trim().length >= 2) { return; }
                fetchSuggest('', function (data) {
                    if ((input.value || '').trim().length >= 2) { return; }
                    renderEmptyState(panel, data);
                });
            };

            input.addEventListener('focus', showIdlePanel);
            input.addEventListener('click', showIdlePanel);

            /* تایپ → نتایج زنده
               فاز ۱۶۹ — دیبانس ۳۰۰ میلی‌ثانیه‌ای:
               تا وقتی کاربر در حال تایپ است هیچ درخواستی زده نمی‌شود؛
               جستجو فقط ۳۰۰ms بعد از آخرین کاراکتر اجرا می‌شود و
               پیام «در حال جستجو…» هم فقط پس از همین مکث نمایش داده می‌شود
               (قبلاً با هر بار فشار دادن کلید فوراً نمایش داده می‌شد). */
            var SEARCH_DEBOUNCE = 300;

            input.addEventListener('input', function () {
                window.clearTimeout(timer);
                var q = (input.value || '').trim();

                if (q.length < 2) {
                    timer = window.setTimeout(showIdlePanel, SEARCH_DEBOUNCE);
                    return;
                }

                timer = window.setTimeout(function () {
                    if ((input.value || '').trim() !== q) { return; }

                    panel.innerHTML = '<div class="sr2-empty"><i class="bi bi-arrow-repeat"></i> در حال جستجو…</div>';
                    panel.classList.add('open');

                    fetchSuggest(q, function (data) {
                        if ((input.value || '').trim() !== q) { return; }
                        renderResults(panel, data, q);
                    });
                }, SEARCH_DEBOUNCE);
            });

            /* کلیک روی چیپ‌ها → جستجوی همان عبارت */
            panel.addEventListener('click', function (e) {
                var chipEl = e.target.closest('.js-sr2-term');
                if (!chipEl) { return; }
                e.preventDefault();
                var term = chipEl.getAttribute('data-term') || '';
                input.value = term;
                pushRecent(term);
                window.location.href = form.getAttribute('action') + '?q=' + encodeURIComponent(term);
            });

            /* ذخیرهٔ عبارت هنگام جستجو */
            form.addEventListener('submit', function () {
                pushRecent(input.value);
            });

            /* بستن با کلیک بیرون */
            document.addEventListener('click', function (e) {
                if (!form.contains(e.target)) { hidePanel(); }
            });

            /* بستن با Escape */
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { hidePanel(); }
            });
        });

        /* ---------- سازگاری با بلوک قدیمی (panel#search-result) ---------- */
        var legacyPanel = document.getElementById('search-result');
        if (legacyPanel) { legacyPanel.remove(); }

        // ---------- دکمه جستجو (اسکریپت قدیمی event را preventDefault می‌کند) ----------
        var desktopForm = document.getElementById('search-form');
        var desktopInput = document.getElementById('search-input');
        var searchBtn = document.getElementById('search-header-btn-search');

        if (searchBtn && desktopForm) {
            searchBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (desktopInput && (desktopInput.value || '').trim().length > 0) {
                    desktopForm.submit();
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

        /* جایگزین: شمارش آیتم‌های محصول در DOM تازه (آیتم‌های دارای تصویر —
           ردیف فاکتور و باکس «سبد خالی» تصویر ندارند) */
        var fresh = document.querySelector('.cart-canvas .cart-canvases');
        if (!count && fresh) {
            count = fresh.querySelectorAll(':scope > .item .image').length;
        }
        if (count < 0) { count = 0; }

        document.querySelectorAll('.header-cart-counter').forEach(function (el) {
            el.textContent = String(count);
        });

        /* اگر سایدبار قبل از جایگزینی باز بود: بک‌دراپ یتیمِ نمونهٔ قدیمی
           پاک می‌شود (فقط در همین حالت ممکن است وجود داشته باشد) و
           سایدبار تازه دوباره باز می‌شود */
        if (wasOpen) {
            document.querySelectorAll('.offcanvas-backdrop').forEach(function (node) {
                node.remove();
            });

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
        /* همگام‌سازی وضعیت اولیهٔ aria با تم اعمال‌شده در theme-init.js */
        var initialDark = document.documentElement.getAttribute('data-theme') === 'dark';

        /* همهٔ کلیدها (دسکتاپ + موبایل) با کلاس بایند می‌شوند — نه id یکتا */
        document.querySelectorAll('.theme-toggle-btn').forEach(function (toggle) {
            toggle.setAttribute('aria-pressed', initialDark ? 'true' : 'false');

            if (toggle.dataset.bound === '1') { return; }
            toggle.dataset.bound = '1';

            toggle.addEventListener('click', function () {
                var root = document.documentElement;
                var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';

                root.setAttribute('data-theme', next);
                try { localStorage.setItem('saniar-theme', next); } catch (e) { /* حافظه در دسترس نیست */ }

                /* همگام‌سازی همهٔ کلیدهای موجود در صفحه */
                document.querySelectorAll('.theme-toggle-btn').forEach(function (btn) {
                    btn.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
                });
            });
        });
    }

    /* =========================================================
       فاز ۱۵۷ — نوار مدیریت بالای سایت (دسترسی سریع ادمین):
       ۱) رفع باگ «منو باز می‌ماند» در تاچ: در صفحه‌های لمسی،
          :hover پس از ضربه باقی می‌ماند. با تاچ روی عنوان، پنل با
          کلاس .touch-open باز/بسته می‌شود و با لمس بیرون بسته می‌شود.
       ۲) blur هنگام خروج موس — برای حالت کلیک/فوکوس.
       ========================================================= */
    function initAdminNavbarFocusFix() {
        var navbar = document.querySelector('.admin-loggedin-navbar');
        if (!navbar || navbar.dataset.focusFixBound === '1') { return; }
        navbar.dataset.focusFixBound = '1';

        var isTouch = window.matchMedia && window.matchMedia('(hover: none)').matches;

        var closeAll = function () {
            navbar.querySelectorAll('.list-item-has-children').forEach(function (li) {
                li.classList.remove('touch-open');
                var link = li.querySelector(':scope > .nav-link');
                if (link && typeof link.blur === 'function') { link.blur(); }
            });
        };

        navbar.querySelectorAll('.list-item-has-children').forEach(function (li) {
            var link = li.querySelector(':scope > .nav-link');

            /* خروج موس از آیتم منودار → فوکوس آزاد شود (دسکتاپ) */
            li.addEventListener('mouseleave', function () {
                if (link && typeof link.blur === 'function') { link.blur(); }
            });

            /* تاچ: ضربهٔ اول باز می‌کند، ضربهٔ دوم می‌بندد */
            if (link && isTouch) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var wasOpen = li.classList.contains('touch-open');
                    closeAll();
                    if (!wasOpen) { li.classList.add('touch-open'); }
                });
            }
        });

        /* کلیک / لمس بیرون نوار → همهٔ پنل‌ها بسته شوند */
        document.addEventListener('click', function (e) {
            if (!navbar.contains(e.target)) { closeAll(); }
        });
        document.addEventListener('touchstart', function (e) {
            if (!navbar.contains(e.target)) { closeAll(); }
        }, { passive: true });

        /* کلید Escape هم پنل‌ها را می‌بندد */
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeAll(); }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initBackToTop();
            initThemeToggle();
            initAdminNavbarFocusFix();
        });
    } else {
        initBackToTop();
        initThemeToggle();
        initAdminNavbarFocusFix();
    }
})();
