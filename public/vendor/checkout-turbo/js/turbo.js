/*
 * ============================================================================
 *  Checkout Turbo — موتور «خرید سریع» فرانت فروشگاه  (v1.7.0)
 * ============================================================================
 *  - صفحهٔ محصول  : دکمهٔ «خرید سریع» کنار دکمهٔ افزودن به سبد (یا شناور با
 *                   موقعیت دلخواه: پایین/بالا، چپ/راست/وسط)
 *                   ★ v1.7.0: زمان‌بندی نمایش (ساعت کاری) — خارج از بازهٔ
 *                   فعال هیچ دکمه/آیکونی تزریق نمی‌شود (سرور هم رد می‌کند)
 *  - کارت‌های محصول : آیکون برقی خرید سریع (مقاوم در برابر لود lazy)
 *                   ★ v1.2.1: اسکن امن — آیکون فقط روی کارت‌های واقعی؛
 *                   هدر/منو/سایدبار شناور/فوتر ممنوع + سلکتور اختصاصی
 *                   ★ v1.3.0: کلاس منحصربه‌فرد «ct-card» — هر کجا این کلاس
 *                   باشد آیکون می‌نشیند (اولویت مطلق) + حالت «فقط ct-card»
 *                   + آیکون تنها یا آیکون + نوشته
 *  - مودال سفارش سریع (تسویهٔ کامل v1.2.0):
 *      • نام و نام خانوادگی + شماره موبایل (+ پرکردن خودکار کاربر واردشده)
 *      • دفترچهٔ آدرس: انتخاب ۳ آدرس آخر مشتری — ★ v1.3.0: افزودن آدرس
 *        دیگر داخل مودال نیست؛ دکمهٔ «افزودن آدرس در پنل کاربری» (مهمان‌ها
 *        فیلد متنی ساده دارند)
 *      • انتخاب روش ارسال (حمل‌ونقل‌های فروشگاه + هزینه)
 *      • انتخاب روش پرداخت (کیف پول + درگاه‌های فروشگاه)
 *      • تعداد + یادداشت سفارش + محافظ ضد بات
 *      • ★ v1.3.0: پرداخت کیف پول سمت سرور اعتبارسنجی می‌شود (موجودی ناکافی
 *        = رد سفارش) + پیش‌چک سمت کلاینت برای بازخورد فوری
 *      • ★ v1.3.0: بعد از موفقیت، انتقال فوری به صفحهٔ «سفارش ثبت شد»
 *      • ★ v1.6.0: نتیجهٔ درگاهی سه‌حالته + تلاش مجدد پرداخت
 *      • ★ v1.7.0: فیلد کد ملی گیرنده (اختیاری/الزامی + چک‌سام سمت
 *        کلاینت هم‌راستا با سرور) + اعتبارسنجی فوری نام/موبایل + مرحلهٔ
 *        «تأیید موبایل با کد یکبارمصرف» (OTP: ورود کد + ارسال دوباره با
 *        شمارش معکوس + بازگشت به فرم) — پس از تأیید، همان سفارش ثبت می‌شود
 *
 *  بدون هیچ وابستگی (vanilla JS) — در هر قالب اپاشاپ کار می‌کند.
 *  همهٔ رفتار از window.CT_CFG (تزریق‌شده توسط موتور PHP) خوانده می‌شود.
 * ============================================================================
 */
(function () {
    'use strict';

    if (window.__CT_LOADED__) { return; }
    window.__CT_LOADED__ = true;

    var CFG = (typeof window.CT_CFG === 'object' && window.CT_CFG) ? window.CT_CFG : {};
    if (!CFG.enabled) { return; }

    /* ---------------- ابزارهای پایه ---------------- */

    function el(tag, cls, html) {
        var n = document.createElement(tag);
        if (cls) { n.className = cls; }
        if (html !== undefined) { n.innerHTML = html; }
        return n;
    }

    function faDigits(n) {
        try { return Number(n || 0).toLocaleString('fa-IR'); }
        catch (e) { return String(n || 0); }
    }

    // ★ v1.7.0 — چک‌سام کد ملی ایران (سمت کلاینت — هم‌راستا با سرور)
    function validNationalCode(nc) {
        if (!/^\d{10}$/.test(nc)) { return false; }
        if (/^(\d)\1{9}$/.test(nc)) { return false; } // ۰۰۰۰۰۰۰۰۰۰ و …
        var sum = 0;
        for (var i = 0; i < 9; i++) { sum += (nc.charCodeAt(i) - 48) * (10 - i); }
        var r = sum % 11, last = nc.charCodeAt(9) - 48;
        return r < 2 ? (last === r) : (last === 11 - r);
    }

    // ارقام فارسی/عربی → لاتین
    function toEnDigits(s) {
        var fa = '۰۱۲۳۴۵۶۷۸۹', ar = '٠١٢٣٤٥٦٧٨٩';
        return String(s || '')
            .replace(/[۰-۹]/g, function (d) { return fa.indexOf(d); })
            .replace(/[٠-٩]/g, function (d) { return ar.indexOf(d); });
    }

    // استخراج عدد از متن قیمت («۲٫۲۵۰٫۰۰۰ تومان <del>۲٫۸۰۰٫۰۰۰</del>» → 2450000)
    function parsePrice(text) {
        var s = toEnDigits(String(text || ''));

        // فقط ارقام + جداکننده‌ها باقی بمان؛ حروف/واحد پول حذف
        var cleaned = s.replace(/[^0-9٬،٫,\s]/g, ' ');

        // توکن‌ها با «فاصله» جدا می‌شوند (فاصله بین دو قیمتِ مختلف = مرز)
        var tokens = cleaned.split(/\s+/).filter(Boolean);
        var nums = [];
        for (var i = 0; i < tokens.length; i++) {
            var t = tokens[i].replace(/[٬،٫,]/g, '');
            if (/^\d+$/.test(t)) {
                nums.push(parseInt(t, 10));
            } else {
                // توکن ترکیبی (باقی‌مانده جداکننده بین ارقام)
                var parts = t.split(/[٬،٫,]/);
                for (var j = 0; j < parts.length; j++) {
                    if (/^\d+$/.test(parts[j])) { nums.push(parseInt(parts[j], 10)); }
                }
            }
        }
        if (!nums.length) { return 0; }

        // ★ آخرین قیمت منطقی = قیمت جاری (در تخفیف‌ها قیمت قدیمی معمولاً اول است)
        for (var k = nums.length - 1; k >= 0; k--) {
            if (nums[k] >= 100) { return nums[k]; }
        }
        return Math.max.apply(null, nums);
    }

    function csrfToken() {
        if (CFG.csrf) { return CFG.csrf; }
        var m = document.querySelector('meta[name="csrf-token"]');
        if (m && m.content) { return m.content; }
        var i = document.querySelector('input[name="_token"]');
        if (i && i.value) { return i.value; }
        return '';
    }

    function xsrfToken() {
        var m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return m ? decodeURIComponent(m[1]) : null;
    }

    function post(url, body) {
        var headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        };
        var t = csrfToken();
        if (t) { headers['X-CSRF-TOKEN'] = t; }
        var x = xsrfToken();
        if (x) { headers['X-XSRF-TOKEN'] = x; }

        return fetch(url, {
            method: 'POST',
            headers: headers,
            credentials: 'same-origin',
            body: JSON.stringify(body)
        }).then(function (r) {
            return r.json().then(function (j) { return j; }).catch(function () { return {}; });
        });
    }

    function get(url) {
        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            return r.json().then(function (j) { return j; }).catch(function () { return {}; });
        });
    }

    function esc(s) {
        return String(s === null || s === undefined ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    /* ★ v1.8.0 — مسیر مطلق (آدرس نسبی داده‌شده از data-src در پنل ادمین
       اشتباه resolve می‌شد → «تصویر نمایش داده نمی‌شود») */
    function absUrl(u) {
        u = String(u === null || u === undefined ? '' : u).trim();
        if (!u) { return ''; }
        if (/^(https?:)?\/\//i.test(u) || u.charAt(0) === '/' || /^data:/i.test(u)) { return u; }
        try { return new URL(u, location.href).href; } catch (e) { return u; }
    }

    /* ★ v1.8.0 — آدرس واقعی تصویر با اولویت data-src قالب‌های lazy-load:
       در img@src یک placeholder ۱×۱ (data:URI) می‌گذارند و عکس واقعی در
       data-src است — قبلاً src همیشه برنده می‌شد → تصویر blank ذخیره می‌شد */
    function realImgSrc(img) {
        if (!img) { return ''; }
        var attrs = ['data-src', 'data-lazy-src', 'data-lazy', 'data-original', 'data-echo'];
        for (var i = 0; i < attrs.length; i++) {
            var v = img.getAttribute(attrs[i]);
            if (v && !/^data:/i.test(v.trim())) { return absUrl(v.trim()); }
        }
        var s = (img.getAttribute('src') || '').trim();
        if (s && !/^data:/i.test(s)) { return absUrl(s); }
        return absUrl(img.src || ''); // src property همیشه مطلق است (اما ممکن است placeholder باشد)
    }

    /* ---------------- آیکون‌ها (SVG داخلی — مستقل از قالب) ---------------- */

    var ICONS = {
        bolt: '<svg viewBox="0 0 24 24" fill="currentColor" width="1em" height="1em"><path d="M13 2 3 14h7l-1 8 11-13h-8l1-7z"/></svg>',
        cart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><circle cx="9" cy="21" r="1.6"/><circle cx="20" cy="21" r="1.6"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>',
        rocket: '<svg viewBox="0 0 24 24" fill="currentColor" width="1em" height="1em"><path d="M12 2c4 1.5 7 5 7 10 0 2-.4 3.8-1 5.2L15 20h-6l-3-2.8C5.4 15.8 5 14 5 12c0-5 3-8.5 7-10zm0 6a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM9 21h6l-1 2h-4l-1-2z"/></svg>',
        bag: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4H6z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
        check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><path d="M20 6 9 17l-5-5"/></svg>',
        close: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><path d="M18 6 6 18M6 6l12 12"/></svg>',
        minus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><path d="M5 12h14"/></svg>',
        plus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><path d="M12 5v14M5 12h14"/></svg>',
        phone: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.8a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.4-1.3a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>',
        user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        pin: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        note: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>',
        truck: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><path d="M10 17h4V5H2v12h3"/><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5v8h1"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>',
        wallet: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><path d="M20 7H4a2 2 0 0 1 0-4h14v4"/><path d="M20 7a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5"/><path d="M17 13h.01"/></svg>',
        card: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>',
        add: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><path d="M12 5v14M5 12h14"/></svg>',
        ext: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
        spinner: '<svg class="ct-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" width="1.1em" height="1.1em"><path d="M12 3a9 9 0 1 0 9 9"/></svg>',
        layers: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="1em" height="1em"><path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>'
    };

    /* ---------------- استایل‌های پویا (رنگ از تنظیمات) ---------------- */

    function applyTheme() {
        var s = document.documentElement.style;
        try {
            if (CFG.pp && CFG.pp.color) { s.setProperty('--ct-c1', CFG.pp.color); }
            if (CFG.pp && CFG.pp.color2) { s.setProperty('--ct-c2', CFG.pp.color2); }
            if (CFG.card && CFG.card.color) { s.setProperty('--ct-cc', CFG.card.color); }
        } catch (e) { /* محیط استرینکت */ }
    }

    /* ================================================================
     *  ۱) تشخیص صفحهٔ محصول + استخراج داده
     * ================================================================ */

    var P = { id: null, title: '', price: 0, image: '', url: '', variants: '', form: null, cartBtn: null, jsonld: null };

    function findJsonLdProduct() {
        try {
            var scripts = document.querySelectorAll('script[type="application/ld+json"]');
            for (var i = 0; i < scripts.length; i++) {
                var txt = (scripts[i].textContent || '').trim();
                if (!txt || txt.indexOf('Product') === -1) { continue; }
                var d;
                try { d = JSON.parse(txt); } catch (e) { continue; }
                var list = Array.isArray(d) ? d : [d];
                // @graph
                for (var j = 0; j < list.length; j++) {
                    var node = list[j];
                    if (node && node['@graph']) { list = list.concat(node['@graph']); }
                }
                for (var k = 0; k < list.length; k++) {
                    var n = list[k];
                    if (n && (n['@type'] === 'Product' || (Array.isArray(n['@type']) && n['@type'].indexOf('Product') >= 0))) {
                        return n;
                    }
                }
            }
        } catch (e) { /* بی‌خطر */ }
        return null;
    }

    function findCartForm() {
        // ۱) فرم دارای input نام product_id
        var inputs = document.querySelectorAll('input[name="product_id"], input[name*="product_id"]');
        for (var i = 0; i < inputs.length; i++) {
            var f = inputs[i].form || inputs[i].closest('form');
            if (f) { return f; }
        }

        // ۲) فرم با action شامل cart
        var forms = document.querySelectorAll('form[action*="cart" i]');
        if (forms.length) { return forms[0]; }

        // ۳) فرم دارای دکمهٔ «افزودن به سبد»
        var btns = document.querySelectorAll('button, input[type="submit"], a[onclick]');
        var re = /افزودن\s*به\s*سبد|اضافه\s*به\s*سبد|add\s*to\s*cart/i;
        for (var b = 0; b < btns.length; b++) {
            var t = (btns[b].textContent || '') + ' ' + (btns[b].getAttribute('onclick') || '');
            if (re.test(t)) {
                var ff = btns[b].closest('form');
                if (ff) { return ff; }
            }
        }
        return null;
    }

    function findCartButton(form) {
        if (!form) { return null; }
        var re = /سبد|خرید|add\s*to\s*cart|buy|order/i;
        var cands = form.querySelectorAll('button, input[type="submit"], a[role="button"], a.btn');
        var fallback = null;
        for (var i = 0; i < cands.length; i++) {
            var c = cands[i];
            var tag = c.tagName.toLowerCase();
            if (tag === 'a' && !c.getAttribute('onclick') && !c.getAttribute('href')) { continue; }
            var txt = (c.value || '') + ' ' + (c.textContent || '');
            if (!fallback && (tag === 'button' || tag === 'input')) { fallback = c; }
            if (re.test(txt)) { return c; }
        }
        return fallback;
    }

    function extractProductId(form) {
        var idIn = document.querySelector('input[name="product_id"], input[name*="product_id"], [data-product-id]');
        if (idIn) {
            var v = idIn.value || idIn.getAttribute('data-product-id') || '';
            var m = toEnDigits(v).match(/\d+/);
            if (m) { return parseInt(m[0], 10); }
        }
        if (form && form.querySelector) {
            var in2 = form.querySelector('input[name="id"], input[name*="id"]');
            if (in2 && in2.value) {
                var m2 = toEnDigits(in2.value).match(/\d+/);
                if (m2) { return parseInt(m2[0], 10); }
            }
        }
        // از URL
        var um = location.href.match(/\/product\/[^\d\/]*(\d+)/i) || location.href.match(/[?&](?:product[_-]?id|p)=(\d+)/i);
        return um ? parseInt(um[1], 10) : null;
    }

    function extractTitle() {
        var h1 = document.querySelector('h1');
        if (h1 && h1.textContent.trim()) { return h1.textContent.trim(); }
        if (P.jsonld && P.jsonld.name) { return String(P.jsonld.name); }
        var t = document.querySelector('meta[property="og:title"]');
        return t ? t.content : document.title;
    }

    function textOfPriceEl(priceEl) {
        // قیمت قبل از تخفیف (del/strike/s) حذف می‌شود تا قیمت جاری بماند
        try {
            var clone = priceEl.cloneNode(true);
            var olds = clone.querySelectorAll('del, strike, s, [class*="old" i], [class*="compare" i]');
            for (var i = 0; i < olds.length; i++) { olds[i].remove(); }
            return clone.textContent || '';
        } catch (e) {
            return priceEl.textContent || '';
        }
    }

    // یافتن عناصر قیمتی: اول کلاس/id/id itemprop، بعد سیگنال واحد پول فارسی
    function priceElements(scope) {
        var els = scope.querySelectorAll('[class*="price" i], [id*="price" i], [itemprop="price"]');
        if (els.length) { return els; }

        // ★ سیگنال قوی در فروشگاه‌های ایرانی: متن شامل «تومان/ریال» + رقم
        try {
            var cands = scope.querySelectorAll('div, span, p, b, strong, em, small, li');
            var out = [];
            for (var i = 0; i < cands.length; i++) {
                var t = (cands[i].textContent || '').trim();
                if (t.length > 0 && t.length < 80 && /(تومان|ریال|﷼)/.test(t) && /\d/.test(toEnDigits(t))) {
                    out.push(cands[i]);
                    if (out.length >= 3) { break; }
                }
            }
            if (out.length) { return out; }
        } catch (e) { /* بی‌خطر */ }

        return els;
    }

    function extractPrice(form) {
        // ۱) JSON-LD
        try {
            if (P.jsonld && P.jsonld.offers) {
                var of = Array.isArray(P.jsonld.offers) ? P.jsonld.offers[0] : P.jsonld.offers;
                if (of && of.price) { return parseInt(parseFloat(String(of.price)), 10); }
            }
        } catch (e) { /* ادامه */ }

        // ۲) عناصر قیمتی — ناحیهٔ فرم، بعد والد فرم و در نهایت کل صفحه
        //    (بسیاری از قالب‌ها قیمت را بیرون از فرم سبد می‌گذارند)
        var scopes = [form, form ? form.parentElement : null, document.body];
        for (var s = 0; s < scopes.length; s++) {
            var sc = scopes[s];
            if (!sc || !sc.querySelectorAll) { continue; }
            var priceEls = priceElements(sc);
            for (var i = 0; i < priceEls.length; i++) {
                var v = parsePrice(textOfPriceEl(priceEls[i]));
                if (v > 0) { return v; }
            }
        }
        return 0;
    }

    function extractImage(form) {
        // ۱) og:image
        var og = document.querySelector('meta[property="og:image"], meta[name="og:image"]');
        if (og && og.content) { return absUrl(og.content); }
        // ۲) JSON-LD
        if (P.jsonld && P.jsonld.image) {
            var img = Array.isArray(P.jsonld.image) ? P.jsonld.image[0] : P.jsonld.image;
            if (typeof img === 'string') { return absUrl(img); }
            if (img && img.url) { return absUrl(img.url); }
        }
        // ۳) اولین تصویر — ناحیهٔ فرم، والد فرم و در نهایت کل صفحه
        //    ★ v1.8.0 — realImgSrc: اولویت data-src (lazy-load) + حذف placeholder های data:URI
        var scopes = [form, form ? form.parentElement : null, document.body];
        for (var s = 0; s < scopes.length; s++) {
            var sc = scopes[s];
            if (!sc || !sc.querySelectorAll) { continue; }
            var ims = sc.querySelectorAll('img');
            for (var i = 0; i < ims.length; i++) {
                var v = realImgSrc(ims[i]);
                if (v) { return v; }
            }
        }
        return '';
    }

    function extractVariants(form) {
        if (!form) { return ''; }
        var parts = [];
        var sels = form.querySelectorAll('select');
        for (var i = 0; i < sels.length; i++) {
            var sel = sels[i];
            var name = '';
            // برچسب از label مرتبط
            if (sel.id) {
                var lb = form.querySelector('label[for="' + sel.id + '"]');
                if (lb) { name = lb.textContent.trim(); }
            }
            if (!name) { name = sel.getAttribute('name') || 'انتخاب'; }
            var val = sel.selectedIndex >= 0 ? (sel.options[sel.selectedIndex] || {}).text || '' : '';
            if (val) { parts.push(name + ': ' + val); }
        }
        return parts.join('، ');
    }

    function isLikelyProductPage() {
        // سیگنال‌های قوی
        if (findCartForm()) { return true; }
        if (findJsonLdProduct()) { return true; }
        // سیگنال متوسط: الگوی URL + وجود h1
        var pat = (CFG.linkPattern || '/product/').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        try {
            if (new RegExp(pat, 'i').test(location.pathname) && document.querySelector('h1')) { return true; }
        } catch (e) { /* الگوی نامعتبر */ }
        return false;
    }

    /* ================================================================
     *  ۲) دکمهٔ صفحهٔ محصول (با موقعیت شناور دلخواه v1.2.0)
     * ================================================================ */

    function buildPageButton() {
        var pp = CFG.pp || {};
        var icon = ICONS[pp.icon] || ICONS.bolt;

        var btn = el('button', 'ct-btn ct-btn--' + (pp.size || 'md') + ' ct-btn--' + (pp.style || 'gradient'));
        btn.type = 'button';
        btn.innerHTML = '<span class="ct-btn-ic">' + icon + '</span><span class="ct-btn-tx"></span>';
        btn.querySelector('.ct-btn-tx').textContent = pp.text || 'خرید سریع';
        btn.setAttribute('aria-label', pp.text || 'خرید سریع');

        // ★ v1.5.0 — گردی گوشه‌های دکمه (px) — تنظیم «انحنای گوشه‌ها»
        if (typeof pp.radius === 'number' && isFinite(pp.radius)) {
            btn.style.borderRadius = pp.radius + 'px';
        }

        // تمام-عرض در موبایل (قابل تنظیم از پنل)
        if (pp.fullMobile) {
            btn.classList.add('ct-mobile-full');
        }

        btn.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            onQuickBuy('page');
        });

        return btn;
    }

    function injectPageButton() {
        var pp = CFG.pp || {};
        if (!pp.enabled) { return; }

        // ★ v1.5.0 — تزریق در تگ دلخواه با سلکتور (کلاس/آیدی/زیرمجموعه):
        //   هر خط = یک سلکتور CSS. ★ v1.6.0 — از هر سلکتور فقط «اولین»
        //   تگ منطبق دکمه می‌گیرد (اگر کلاس تکراری باشد فقط یک دکمه) و
        //   جای دکمه (اول تگ = prepend / آخر تگ = append) قابل تنظیم است.
        if (pp.position === 'selector') {
            if (injectPageButtonSelector(pp)) {
                ppSelectorDone = true;
                return;
            }
            scheduleSelectorRetry(pp);
            return;
        }

        var btn = buildPageButton();

        // موقعیت شناور — مستقل از فرم + جایگاه دلخواه (v1.2.0)
        if (pp.position === 'float' || !P.cartBtn) {
            injectFloatButton();
            return;
        }

        var parent = P.cartBtn.parentElement || document.body;

        try {
            if (pp.position === 'before-cart') {
                parent.insertBefore(btn, P.cartBtn);
            } else if (pp.position === 'form-end' && P.form) {
                P.form.appendChild(btn);
            } else { // after-cart (پیش‌فرض)
                parent.insertBefore(btn, P.cartBtn.nextSibling);
            }
        } catch (e) {
            // جای تزریق امن: آخر فرم یا body
            (P.form || document.body).appendChild(btn);
        }
    }

    /* ★ v1.5.0 — دکمهٔ شناور (استفادهٔ مجدد برای فالبک) */
    function injectFloatButton() {
        var pp = CFG.pp || {};
        var pos = pp.floatPos || 'bottom-left';
        var fab = el('div', 'ct-fab-wrap ct-fab--' + pos);
        fab.appendChild(buildPageButton());
        document.body.appendChild(fab);
    }

    /* ★ v1.5.0 / v1.6.0 — تزریق دکمه در تگ‌های منطبق با سلکتورها
     *
     * از هر سلکتور فقط اولین تگ منطبق دکمه می‌گیرد (تکرار کلاس → یک دکمه).
     * جای دکمه با pp.placement تعیین می‌شود:
     *   prepend (پیش‌فرض) = اولین فرزند تگ — append = آخرین فرزند تگ.
     * idempotent با کلاس ct-pp-host */
    var ppSelectorDone = false;

    function injectPageButtonSelector(pp) {
        var sels = pp.selector || [];
        if (typeof sels === 'string') { sels = [sels]; }
        if (!sels.length) { return false; }

        var place = pp.placement === 'append' ? 'append' : 'prepend';

        var injected = 0;
        for (var i = 0; i < sels.length; i++) {
            var nodes;
            try { nodes = document.querySelectorAll(String(sels[i]).trim()); } catch (e) { continue; }

            // ★ v1.6.0 — فقط اولین تگ منطبق (اگر کلاس تکراری باشد فقط یک دکمه)
            for (var j = 0; j < nodes.length; j++) {
                var host = nodes[j];
                if (!host || !host.classList) { continue; }
                if (host.classList.contains('ct-pp-host')) { continue; }
                if (host.closest('.ct-root, .ct-fab-wrap, .ct-card-btn')) { continue; }

                host.classList.add('ct-pp-host');
                try {
                    var btn = buildPageButton();
                    if (place === 'append') {
                        host.appendChild(btn);      // ★ آخر تگ
                    } else {
                        host.insertBefore(btn, host.firstChild); // ★ اول تگ
                    }
                    injected++;
                } catch (e) {
                    try { host.appendChild(buildPageButton()); injected++; } catch (e2) { /* بی‌خطر */ }
                }
                break; // فقط اولین تگ منطبق همین سلکتور
            }
        }
        return injected > 0;
    }

    /* ★ v1.5.0 — تلاش مجدد برای سلکتور (قالب‌های داینامیک) — تا ~۱۵ ثانیه
     *    هر ۵۰۰ms؛ بعد از آن فالبک به دکمهٔ شناور تا چیزی نمایش داده شود */
    var ppSelectorTimer = null, ppSelectorTries = 0;

    function scheduleSelectorRetry(pp) {
        if (ppSelectorTimer) { clearTimeout(ppSelectorTimer); }
        ppSelectorTimer = setTimeout(function () {
            ppSelectorTimer = null;
            if (ppSelectorDone) { return; }
            if (injectPageButtonSelector(pp)) {
                ppSelectorDone = true;
                return;
            }
            ppSelectorTries++;
            if (ppSelectorTries < 30) {
                scheduleSelectorRetry(pp);
            } else {
                // فالبک نهایی — شناور تا دکمه هرگز غایب نماند
                injectFloatButton();
            }
        }, 500);
    }

    /* ================================================================
     *  ۳) آیکون کارت‌های محصول — ★ v1.2.1 اسکن امن
     *  ------------------------------------------------------------
     *  ریشهٔ باگ گزارش‌شده: اسکنِ قدیمی «هر» لینک محصولی را می‌گرفت و به
     *  نزدیک‌ترین li/item آیکون می‌چسباند → آیکون داخل هدر، نوار کناری
     *  شناور و منوها می‌افتاد. اسکن جدید چهار لایهٔ محافظ دارد:
     *
     *  ۱) مناطق ممنوعه (chrome): هدر/ناوبری/فوتر/سایدبار/منو/بردکرامب/
     *     مگامنو/دراپ‌داون/نوار ابزار + سلکتورهای اضافهٔ دلخواه ادمین
     *  ۲) سلامت کارت: باید «تصویر» داشته باشد + اندازهٔ منطقی کارت
     *     (نه عنصر متن‌خالی منو، نه کل صفحه) + مرئی باشد
     *  ۳) position:fixed|sticky ممنوع (نوارهای شناور فروشگاه)
     *  ۴) حالت قطعی: سلکتور اختصاصی کارت از تنظیمات (card_selector)
     *     — اگر ادمین آن را پر کرده باشد، فقط همان عناصر علامت می‌خورند
     * ================================================================ */

    var markedCards = [];

    function productIdFromUrl(href) {
        var h = toEnDigits(href || '');
        var m = h.match(/\/product\/[^\d\/]*(\d+)/i)
            || h.match(/\/p\/(\d+)/i)
            || h.match(/[?&](?:product[_-]?id|pid|p)=(\d+)/i)
            || h.match(/\/product\/(\d+)/i);
        return m ? parseInt(m[1], 10) : null;
    }

    function isProductLink(a) {
        var href = a.getAttribute('href') || '';
        if (!href || href === '#' || /^(javascript|mailto|tel):/i.test(href)) { return false; }
        if (a.href === location.href.split('#')[0]) { return false; } // لینک خود صفحه
        var pat = CFG.linkPattern || '/product/';
        try { return href.indexOf(pat) !== -1; } catch (e) { return false; }
    }

    /* ★ v1.2.1 — مناطق «قاب فروشگاه» که آیکون هرگز نباید در آن‌ها ظاهر شود */
    var CHROME_ZONE_SEL = [
        'header', 'nav', 'footer', 'aside',
        '[role="navigation"]', '[role="banner"]', '[role="contentinfo"]', '[role="toolbar"]',
        '[class*="header" i]', '[class*="navbar" i]', '[class*="nav-" i]',
        '[class*="menu" i]', '[class*="mega" i]', '[class*="dropdown" i]',
        '[class*="breadcrumb" i]', '[class*="crumb" i]', '[class*="sidebar" i]',
        '[class*="footer" i]', '[class*="toolbar" i]', '[class*="topbar" i]',
        '[class*="pagination" i]'
    ].join(',');

    function chromeZoneSelector() {
        var extra = (CFG.card && CFG.card.exclude) || '';
        extra = String(extra || '').trim();
        if (!extra) { return CHROME_ZONE_SEL; }
        // سلکتورهای اضافهٔ ادمین — کاما-جدا؛ علامت‌گذاری جداکننده را حفظ می‌کنیم
        var parts = extra.split(',').map(function (x) { return x.trim(); }).filter(Boolean);
        return parts.length ? (CHROME_ZONE_SEL + ',' + parts.join(',')) : CHROME_ZONE_SEL;
    }

    function inChromeZone(node) {
        try {
            return !!node.closest(chromeZoneSelector());
        } catch (e) {
            // سلکتور نامعتبر ادمین — فقط لیست پیش‌فرض
            try { return !!node.closest(CHROME_ZONE_SEL); } catch (e2) { return false; }
        }
    }

    /* ★ v1.2.1 — شمارش لینک‌های محصول داخل یک عنصر
     *    کارت واقعی حداکثر ۲-۳ لینک محصول دارد (تصویر + عنوان + شاید quick-view)؛
     *    کانتینرهای بزرگ (گرید/سکشن/برگه) تعداد بیشتری دارند → کارت نیستند */
    function countProductLinks(scope) {
        var links = scope.querySelectorAll('a[href]');
        var n = 0;
        for (var i = 0; i < links.length; i++) {
            if (isProductLink(links[i])) { n++; }
            if (n > 3) { break; } // برای کارایی، بیشتر از ۳ را فوراً رد کن
        }
        return n;
    }

    /* ★ v1.2.1 — آیا این عنصر «شبیه کارت محصول» است؟ (پنج چک سخت) */
    function looksLikeCard(c) {
        if (!c || !c.querySelectorAll || c === document.body || c === document.documentElement) { return false; }

        // ۱) کارت محصول همیشه تصویر دارد (لینک منو/آیکون سایدبار ندارد)
        if (!c.querySelector('img')) { return false; }

        try {
            var st = getComputedStyle(c);

            // ۲) نوارهای شناور/چسبان ممنوع (سایدبار شناور فروشگاه‌ها)
            if (st.position === 'fixed' || st.position === 'sticky') { return false; }
            if (st.display === 'none' || st.visibility === 'hidden') { return false; }

            // ۳) اندازهٔ منطقی کارت (نه دکمهٔ ریز، نه کل صفحه)
            var r = c.getBoundingClientRect();
            if (r.width < 110 || r.width > 760) { return false; }
            if (r.height < 100 || r.height > 1600) { return false; }
        } catch (e) { return false; }

        // ۴) خودش هم در منطقهٔ ممنوعه نباشد
        if (inChromeZone(c)) { return false; }

        // ۵) ★ تعداد لینک‌های محصول داخلش ≤ ۳ — در غیر این صورت کانتینر است
        //    (مثلاً بنر با لینک محصول که والدش کل main با ۳ کارت است)
        if (countProductLinks(c) > 3) { return false; }
        return true;
    }

    /* ★ v1.2.1 — یافتن کارت واقعی: بالا رفتن از والد‌ها تا اولین کارت سالم
     *    (حداکثر ۱۲ سطح)؛ اگر هیچ والدی کارت نبود → null (بدون آیکون) */
    function findCardFor(anchor) {
        var node = anchor.parentElement;
        var depth = 0;
        while (node && node !== document.body && depth < 12) {
            if (node.classList && node.classList.contains('ct-card-marked')) { return node; }
            if (looksLikeCard(node)) { return node; }
            node = node.parentElement;
            depth++;
        }
        return null;
    }

    function extractCardData(container, anchor) {
        var img = container.querySelector('img');
        var title = '';
        if (img && img.alt) { title = img.alt.trim(); }
        if (!title) {
            var ttl = container.querySelector('[class*="title" i], h2, h3, h4, .title');
            if (ttl) { title = ttl.textContent.trim(); }
        }
        if (!title) { title = (anchor.getAttribute('title') || anchor.textContent || '').trim(); }

        var price = 0;
        var pe = priceElements(container);
        for (var i = 0; i < pe.length; i++) {
            var v = parsePrice(textOfPriceEl(pe[i]));
            if (v > 0) { price = v; break; }
        }

        return {
            id: productIdFromUrl(anchor.href),
            title: title,
            price: price,
            image: realImgSrc(img), // ★ v1.8.0 — lazy-safe + absolute
            url: anchor.href
        };
    }

    function buildCardIcon(data) {
        var cd = CFG.card || {};
        var icon = ICONS[cd.icon] || ICONS.bolt;
        // ★ v1.3.0 — برچسب متنی (تنظیم «آیکون + نوشته»؛ متن از card_text)
        var label = cd.showText ? String(cd.text || cd.tooltip || 'خرید سریع') : '';

        var b = el('button', 'ct-card-btn' + (label ? ' ct-card-btn--txt' : '') + ' ct-pos-' + (cd.position || 'corner-tr'));
        b.type = 'button';
        b.innerHTML = icon + (label ? '<span class="ct-card-tx"></span>' : '');
        if (label) { b.querySelector('.ct-card-tx').textContent = label; }

        // ★ v1.5.0 — گردی گوشه‌های آیکون کارت‌ها (px) — تنظیم «انحنای گوشه‌ها»
        if (typeof cd.radius === 'number' && isFinite(cd.radius)) {
            b.style.borderRadius = cd.radius + 'px';
        }

        var tip = cd.tooltip || 'خرید سریع';
        b.setAttribute('aria-label', tip);
        b.setAttribute('title', tip);

        b.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            onQuickBuy('card', data);
        });

        return b;
    }

    /* ★ v1.3.0 — کلاس منحصربه‌فرد کارت‌ها: ct-card
     *
     * ادمین/قالب این کلاس را روی کارت‌های محصول می‌گذارد → آیکون دقیقاً
     * همان‌جا می‌نشیند (اولویت مطلق در همهٔ حالت‌ها؛ علامت‌گذاری صریح است).
     *     <div class="product-card ct-card"> ... </div>
     * یا با اتریبیوت:      <article data-ct-card> ... </article>
     * داده‌های اختیاری مستقیم روی عنصر:
     *     data-ct-url / data-ct-id / data-ct-title / data-ct-price
     */
    var CT_CARD_SEL = '.ct-card, [data-ct-card]';

    function isVisibleEl(n) {
        try {
            if (!n || !n.getBoundingClientRect) { return false; }
            var st = getComputedStyle(n);
            if (st.display === 'none' || st.visibility === 'hidden') { return false; }
            var r = n.getBoundingClientRect();
            return r.width > 0 && r.height > 0;
        } catch (e) { return false; }
    }

    /* ★ v1.3.0 — استخراج دادهٔ خرید از عنصر ct-card (data-ct-* + محتوا) */
    function cardDataFromEl(card) {
        var a = null;
        var anchors = card.querySelectorAll('a[href]');
        for (var j = 0; j < anchors.length; j++) {
            if (isProductLink(anchors[j])) { a = anchors[j]; break; }
        }

        var directUrl = (card.getAttribute('data-ct-url') || '').trim();

        if (!a && !directUrl) {
            // fallback: اولین لینک غیر-خالی
            for (var k = 0; k < anchors.length; k++) {
                var href = anchors[k].getAttribute('href') || '';
                if (href && href.charAt(0) !== '#') { a = anchors[k]; break; }
            }
            if (!a) { return null; } // نه data-ct-url و نه لینک — قابل خرید نیست
        }

        var data;
        if (a) {
            data = extractCardData(card, a);
        } else {
            var img = card.querySelector('img');
            var ttl = card.querySelector('[class*="title" i], h2, h3, h4, .title');
            data = {
                id: null,
                title: (ttl ? ttl.textContent.trim() : '') || (img && img.alt ? img.alt.trim() : ''),
                price: 0,
                image: realImgSrc(img), // ★ v1.8.0 — lazy-safe + absolute
                url: ''
            };
            var pe = priceElements(card);
            for (var p = 0; p < pe.length; p++) {
                var v = parsePrice(textOfPriceEl(pe[p]));
                if (v > 0) { data.price = v; break; }
            }
        }

        // data-ct-* همیشه بر محتوا اولویت دارد
        var dId = (card.getAttribute('data-ct-id') || '').trim();
        if (dId !== '') { data.id = parseInt(dId.replace(/[^0-9]/g, ''), 10) || null; }
        var dTitle = (card.getAttribute('data-ct-title') || '').trim();
        if (dTitle !== '') { data.title = dTitle; }
        var dPrice = (card.getAttribute('data-ct-price') || '').trim();
        if (dPrice !== '') {
            var pv = parseInt(dPrice.replace(/[^0-9]/g, ''), 10);
            if (pv > 0) { data.price = pv; }
        }
        if (directUrl) { data.url = directUrl; }

        if (!data.url) { return null; }
        return data;
    }

    /* ★ v1.3.0 — اسکن کلاس ct-card — اولویت مطلق (در همهٔ حالت‌ها اجرا می‌شود) */
    function scanManualCards() {
        var nodes;
        try { nodes = document.querySelectorAll(CT_CARD_SEL); } catch (e) { return; }

        for (var i = 0; i < nodes.length; i++) {
            var card = nodes[i];
            if (!card || !card.classList) { continue; }
            if (card.classList.contains('ct-card-marked')) { continue; }
            if (card.closest('.ct-root, .ct-fab-wrap')) { continue; }
            if (!isVisibleEl(card)) { continue; }

            var data = cardDataFromEl(card);
            if (!data) { continue; }

            var pos;
            try { pos = getComputedStyle(card).position; } catch (e) { pos = 'static'; }
            if (pos === 'static') { card.style.position = 'relative'; }

            card.classList.add('ct-card-marked');
            markedCards.push(card);
            card.appendChild(buildCardIcon(data));
        }
    }

    /* ★ v1.5.0 — فهرست سلکتورهای کارت‌ها (هر خط یک سلکتور)
     *
     * سرور آرایهٔ selectors را می‌فرستد (هر خط = یک سلکتور مرکب؛ مثل
     * .product-carousel .owl-item یا .product-carousel>.owl-item).
     * سازگاری با نسخه‌های قدیمی: selector رشته‌ای تک‌خطی هم پشتیبانی می‌شود.
     */
    function cardSelectorList() {
        var cd = CFG.card || {};
        var list = cd.selectors || [];
        if (typeof list === 'string') { list = [list]; }
        if (!list.length) {
            var legacy = String(cd.selector || '').trim();
            if (legacy) {
                // مقدار قدیمی تک‌خطی — اگر چندخطی ذخیره شده باشد خط‌خط جدا کن
                legacy.split(/\n/).forEach(function (line) {
                    line = line.trim();
                    if (line) { list.push(line); }
                });
            }
        }
        return list.slice(0, 20);
    }

    /* ★ v1.2.1 — حالت قطعی: سلکتورهای اختصاصی ادمین (چندتایی) */
    function scanCardsCustom() {
        var sels = cardSelectorList();

        for (var s = 0; s < sels.length; s++) {
            var sel = String(sels[s]).trim();
            if (!sel) { continue; }

            var nodes;
            try { nodes = document.querySelectorAll(sel); } catch (e) { continue; }

            for (var i = 0; i < nodes.length; i++) {
                var card = nodes[i];
                if (!card || !card.classList || card.classList.contains('ct-card-marked')) { continue; }
                if (inChromeZone(card)) { continue; }
                if (card.closest('.ct-root, .ct-fab-wrap')) { continue; }

                // لینک محصول داخل کارت
                var anchors = card.querySelectorAll('a[href]');
                var a = null;
                for (var j = 0; j < anchors.length; j++) {
                    if (isProductLink(anchors[j])) { a = anchors[j]; break; }
                }
                if (!a) { continue; }

                var pos = 'static';
                try { pos = getComputedStyle(card).position; } catch (e) { pos = 'static'; }
                if (pos === 'static') { card.style.position = 'relative'; }

                card.classList.add('ct-card-marked');
                markedCards.push(card);
                card.appendChild(buildCardIcon(extractCardData(card, a)));
            }
        }
    }

    function scanCards() {
        var cd = CFG.card || {};
        if (!cd.enabled) { return; }

        // ★ v1.3.0 — لایهٔ ۰: کلاس منحصربه‌فرد ct-card (اولویت مطلق — هرجا باشد)
        scanManualCards();

        var mode = cd.mode || 'auto';
        var sels = cardSelectorList();

        // حالت «فقط ct-card» — هیچ اسکن خودکاری انجام نمی‌شود
        if (mode === 'class') { return; }

        // ★ v1.5.0 — حالت قطعی: سلکتورهای اختصاصی (چندتایی — هر خط یکی)
        //   در حالت auto هم اگر سلکتوری پر شده باشد فقط همان عناصر علامت می‌خورند
        if (sels.length) {
            scanCardsCustom();
            return;
        }

        // حالت خودکار (heuristic) — با هر چهار لایهٔ محافظ v1.2.1
        var anchors = document.querySelectorAll('a[href]');
        for (var i = 0; i < anchors.length; i++) {
            var a = anchors[i];

            if (!isProductLink(a)) { continue; }
            // داخل ریشهٔ خود ما نیست؟
            if (a.closest('.ct-root, .ct-fab-wrap')) { continue; }

            // ★ لایهٔ ۱ — لینک داخل هدر/منو/سایدبار/فوتر → هرگز آیکون نگیرد
            if (inChromeZone(a)) { continue; }

            // ★ لایهٔ ۲+۳+۴ — کارت باید واقعاً «کارت» باشد
            var card = findCardFor(a);
            if (!card) { continue; }
            if (card.classList.contains('ct-card-marked')) { continue; }

            // کانتینر باید قابل موقعیت‌دهی باشد
            var pos = getComputedStyle(card).position;
            if (pos === 'static') { card.style.position = 'relative'; }

            card.classList.add('ct-card-marked');
            markedCards.push(card);

            var data = extractCardData(card, a);
            card.appendChild(buildCardIcon(data));
        }
    }

    // مقاوم در برابر لود lazy / صفحه‌بندی ajax
    var scanTimer = null;
    function watchDom() {
        if (typeof MutationObserver === 'undefined') { return; }
        var obs = new MutationObserver(function () {
            if (scanTimer) { clearTimeout(scanTimer); }
            scanTimer = setTimeout(function () {
                scanCards();
                // ★ v1.5.0 — قالب‌های داینامیک: تزریق سلکتوری صفحهٔ محصول هم
                //   دوباره امتحان می‌شود تا وقتی موفق شده باشد
                var pp = CFG.pp || {};
                if (pp.enabled && pp.position === 'selector' && !ppSelectorDone) {
                    if (injectPageButtonSelector(pp)) { ppSelectorDone = true; }
                }
            }, 350);
        });
        obs.observe(document.documentElement, { childList: true, subtree: true });
    }

    /* ================================================================
     *  ۴) مودال سفارش سریع — تسویهٔ کامل (v1.2.0)
     * ================================================================ */

    var root = null, modal = null;
    var state = {
        qty: 1, product: null, source: 'page', sending: false, redirectTimer: null,
        // ★ v1.2.0
        opts: null, optsAt: 0, optsLoading: false, optsQueue: [],
        addressId: 0, carrierId: 0, carrierCost: 0, gatewayKey: '', openedAt: 0,
        // ★ v1.4.0 — تنوع‌ها + داده‌های سرور + لاگین
        slug: '', priceId: 0, pData: null, pDataLoading: false, isDownload: false, selAttrs: {},
        // ★ v1.7.0 — مرحلهٔ تأیید موبایل (OTP)
        otpBody: null, otpPhone: '', otpTimer: null, otpLeft: 0
    };

    function buildModal() {
        root = el('div', 'ct-root');
        root.setAttribute('dir', 'rtl');
        root.innerHTML = [
            '<div class="ct-overlay"></div>',
            '<div class="ct-modal" role="dialog" aria-modal="true" aria-label="خرید سریع">',
            '  <div class="ct-m-close">' + ICONS.close + '</div>',
            '  <div class="ct-m-head">',
            '    <div class="ct-m-ic">' + ICONS.bolt + '</div>',
            '    <div class="ct-m-titles"><div class="ct-m-title"></div><div class="ct-m-desc"></div></div>',
            '  </div>',
            '  <div class="ct-m-body">',
            '    <div class="ct-m-product">',
            '      <div class="ct-m-pimg"><img alt="" /></div>',
            '      <div class="ct-m-pinfo">',
            '        <div class="ct-m-pname"></div>',
            '        <div class="ct-m-pvar"></div>',
            '        <div class="ct-m-pprice"></div>',
            '      </div>',
            '    </div>',
            '    <div class="ct-m-qty">',
            '      <span class="ct-m-qty-l">تعداد:</span>',
            '      <button type="button" class="ct-qty-btn ct-qty-minus">' + ICONS.minus + '</button>',
            '      <span class="ct-qty-val">۱</span>',
            '      <button type="button" class="ct-qty-btn ct-qty-plus">' + ICONS.plus + '</button>',
            '    </div>',
            '    <div class="ct-m-fields"></div>',
            '    <div class="ct-m-sec ct-m-sec-var" hidden></div>',
            '    <div class="ct-m-sec ct-m-sec-addr" hidden></div>',
            '    <div class="ct-m-sec ct-m-sec-ship" hidden></div>',
            '    <div class="ct-m-sec ct-m-sec-pay" hidden></div>',
            '    <div class="ct-m-error" hidden></div>',
            '  </div>',
            '  <div class="ct-m-foot">',
            '    <button type="button" class="ct-submit">' + ICONS.bolt + '<span class="ct-submit-tx"></span></button>',
            '    <div class="ct-m-total" hidden></div>',
            '    <div class="ct-m-hint">ثبت سفارش بدون نیاز به ثبت‌نام — پرداخت و ارسال در تماس تأیید می‌شود.</div>',
            '  </div>',
            '  <div class="ct-m-login" hidden>',
            '    <div class="ct-login-ic">' + ICONS.user + '</div>',
            '    <div class="ct-login-t">ورود به حساب کاربری</div>',
            '    <div class="ct-login-d">برای خرید سریع و ثبت سفارش، ابتدا باید وارد حساب کاربری خود شوید.</div>',
            '    <a class="ct-login-btn" href="/login">ورود / ثبت‌نام</a>',
            '    <button type="button" class="ct-login-close" type="button">بستن</button>',
            '  </div>',
            '  <div class="ct-m-otp" hidden>',
            '    <div class="ct-otp-ic">' + ICONS.phone + '</div>',
            '    <div class="ct-otp-t">تأیید شمارهٔ موبایل</div>',
            '    <div class="ct-otp-d"></div>',
            '    <div class="ct-otp-box">',
            '      <input class="ct-otp-input" type="tel" inputmode="numeric" autocomplete="one-time-code" dir="ltr" aria-label="کد تأیید" />',
            '    </div>',
            '    <div class="ct-otp-err" hidden></div>',
            '    <button type="button" class="ct-otp-verify">' + ICONS.check + '<span>تأیید و ثبت سفارش</span></button>',
            '    <div class="ct-otp-row">',
            '      <button type="button" class="ct-otp-resend">ارسال دوبارهٔ کد</button>',
            '      <span class="ct-otp-timer" hidden></span>',
            '    </div>',
            '    <button type="button" class="ct-otp-back">بازگشت و ویرایش اطلاعات</button>',
            '  </div>',
            '  <div class="ct-m-success" hidden>',
            '    <div class="ct-ok-ic">' + ICONS.check + '</div>',
            '    <div class="ct-ok-title"></div>',
            '    <div class="ct-ok-desc"></div>',
            '    <div class="ct-ok-code"><span></span></div>',
            '    <div class="ct-ok-redirect" hidden></div>',
            '    <button type="button" class="ct-ok-close">بستن</button>',
            '  </div>',
            '</div>'
        ].join('');

        document.body.appendChild(root);
        modal = root.querySelector('.ct-modal');

        // بستن
        root.querySelector('.ct-overlay').addEventListener('click', closeModal);
        root.querySelector('.ct-m-close').addEventListener('click', closeModal);
        root.querySelector('.ct-ok-close').addEventListener('click', closeModal);
        // ★ v1.4.0 — دکمهٔ بستن گیت لاگین
        var lgClose = root.querySelector('.ct-login-close');
        if (lgClose) { lgClose.addEventListener('click', closeModal); }

        // ★ v1.7.0 — مرحلهٔ OTP: ورود کد / تأیید / ارسال دوباره / بازگشت
        var otpIn = root.querySelector('.ct-otp-input');
        var otpVerifyBtn = root.querySelector('.ct-otp-verify');
        if (otpIn) {
            otpIn.addEventListener('input', function () {
                // فارسی→لاتین + فقط رقم (حداکثر ۸ رقم = سقف otp_len)
                var v = toEnDigits(otpIn.value).replace(/\D/g, '');
                if (v.length > 8) { v = v.substring(0, 8); }
                if (otpIn.value !== v) { otpIn.value = v; }
            });
            otpIn.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter') { ev.preventDefault(); otpVerifyO(); }
            });
        }
        if (otpVerifyBtn) { otpVerifyBtn.addEventListener('click', otpVerifyO); }
        var otpResendBtn = root.querySelector('.ct-otp-resend');
        if (otpResendBtn) { otpResendBtn.addEventListener('click', otpResendO); }
        var otpBackBtn = root.querySelector('.ct-otp-back');
        if (otpBackBtn) { otpBackBtn.addEventListener('click', function () { hideOtpStep(); }); }
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && root.classList.contains('ct-open')) { closeModal(); }
        });

        // تعداد
        root.querySelector('.ct-qty-minus').addEventListener('click', function () { setQty(state.qty - 1); });
        root.querySelector('.ct-qty-plus').addEventListener('click', function () { setQty(state.qty + 1); });

        // ارسال
        root.querySelector('.ct-submit').addEventListener('click', submitOrder);

        // جلوگیری از اسکرول پس‌زمینه
        root.addEventListener('wheel', function (ev) {
            if (!root.classList.contains('ct-open')) { return; }
            var body = root.querySelector('.ct-m-body');
            var canScroll = body && body.scrollHeight > body.clientHeight;
            if (!canScroll) { ev.preventDefault(); }
        }, { passive: false });
    }

    /* ---------------- فیلدهای پایه (نام/موبایل/یادداشت) ---------------- */

    function buildFields() {
        var m = (CFG.modal || {}).fields || {};
        var wrap = root.querySelector('.ct-m-fields');
        wrap.innerHTML = '';

        var defs = [
            { key: 'name', mode: m.name, label: 'نام و نام خانوادگی', icon: ICONS.user, type: 'text', ph: 'مثلاً علی رضایی' },
            { key: 'phone', mode: m.phone, label: 'شمارهٔ موبایل', icon: ICONS.phone, type: 'tel', ph: '۰۹۱۲۳۴۵۶۷۸۹', ltr: true },
            // ★ v1.7.0 — کد ملی گیرنده (req | opt | off) + چک‌سام
            { key: 'national_code', mode: m.nationalCode, label: 'کد ملی گیرنده', icon: ICONS.card, type: 'tel', ph: '۱۰ رقم — مثلاً ۰۰۱۲۳۴۵۶۷۸۹', ltr: true },
            { key: 'note', mode: m.note, label: 'یادداشت سفارش', icon: ICONS.note, type: 'textarea', ph: 'هر نکته‌ای برای سفارش دارید' }
        ];

        defs.forEach(function (d) {
            if (!d.mode || d.mode === 'off') { return; }

            var field = el('div', 'ct-field' + (d.mode === 'req' ? ' ct-req' : ''));
            field.setAttribute('data-key', d.key);

            var input;
            if (d.type === 'textarea') {
                input = el('textarea', 'ct-input');
                input.rows = 2;
            } else {
                input = el('input', 'ct-input');
                input.type = d.type;
            }
            input.setAttribute('placeholder', d.ph);
            input.setAttribute('dir', d.ltr ? 'ltr' : 'rtl');
            input.setAttribute('data-field', d.key);
            if (d.mode === 'req') { input.setAttribute('aria-required', 'true'); }

            // شماره → فقط ارقام
            if (d.key === 'phone') {
                input.addEventListener('input', function () {
                    // فارسی→لاتین + فقط رقم نگه‌دار
                    var v = toEnDigits(input.value).replace(/\D/g, '');
                    if (v.length > 11) { v = v.substring(0, 11); }
                    if (input.value !== v) { input.value = v; }
                });
            }

            // ★ v1.7.0 — کد ملی → فقط ارقام + حداکثر ۱۰ رقم
            if (d.key === 'national_code') {
                input.setAttribute('inputmode', 'numeric');
                input.addEventListener('input', function () {
                    var v = toEnDigits(input.value).replace(/\D/g, '');
                    if (v.length > 10) { v = v.substring(0, 10); }
                    if (input.value !== v) { input.value = v; }
                });
            }

            var label = el('label', 'ct-f-label');
            label.innerHTML = '<span class="ct-f-ic">' + d.icon + '</span><span class="ct-f-tx"></span>';
            label.querySelector('.ct-f-tx').textContent = d.label + (d.mode === 'req' ? ' *' : '');

            field.appendChild(label);
            field.appendChild(input);
            wrap.appendChild(field);
        });

        // ★ honeypot ضد بات — نامرئی برای انسان، جذاب برای بات
        var hp = el('input', 'ct-hp');
        hp.type = 'text';
        hp.name = 'ct_website';
        hp.setAttribute('tabindex', '-1');
        hp.setAttribute('autocomplete', 'off');
        hp.setAttribute('aria-hidden', 'true');
        wrap.appendChild(hp);
    }

    /* ---------------- داده‌های تسویه (options endpoint) ---------------- */

    /* ---------------- ★ v1.4.0 — گیت لاگین ---------------- */

    function showLoginGate() {
        if (!root) { return; }
        var gate = root.querySelector('.ct-m-login');
        if (!gate) { return; }

        var url = String(CFG.loginUrl || '/login') || '/login';
        gate.querySelector('.ct-login-btn').setAttribute('href', url);

        // فرم مخفی — فقط گیت دیده شود
        root.querySelector('.ct-m-body').style.display = 'none';
        root.querySelector('.ct-m-foot').style.display = 'none';
        gate.hidden = false;
    }

    function hideLoginGate() {
        if (!root) { return; }
        var gate = root.querySelector('.ct-m-login');
        if (gate) { gate.hidden = true; }
        root.querySelector('.ct-m-body').style.display = '';
        root.querySelector('.ct-m-foot').style.display = '';
    }

    /* ---------------- ★ v1.7.0 — مرحلهٔ تأیید موبایل (OTP) ---------------- */

    function otpEndpoints() {
        var e = CFG.endpoints || {};
        var base = String(e.order || '');
        return {
            send:   e.otpSend   || base.replace(/\/order$/, '/otp/send'),
            verify: e.otpVerify || base.replace(/\/order$/, '/otp/verify')
        };
    }

    function otpPanel() {
        return root ? root.querySelector('.ct-m-otp') : null;
    }

    function otpShowErr(msg) {
        var p = otpPanel();
        if (!p) { return; }
        var box = p.querySelector('.ct-otp-err');
        if (!box) { return; }
        box.textContent = msg || '';
        box.hidden = !msg;
    }

    function stopOtpTimer() {
        if (state.otpTimer) {
            clearInterval(state.otpTimer);
            state.otpTimer = null;
        }
        state.otpLeft = 0;
        var p = otpPanel();
        if (p) {
            var tm = p.querySelector('.ct-otp-timer');
            if (tm) { tm.hidden = true; tm.textContent = ''; }
            var rs = p.querySelector('.ct-otp-resend');
            if (rs) { rs.disabled = false; }
        }
    }

    function startOtpTimer(sec) {
        stopOtpTimer();
        var p = otpPanel();
        if (!p) { return; }
        var tm = p.querySelector('.ct-otp-timer');
        var rs = p.querySelector('.ct-otp-resend');
        state.otpLeft = Math.max(0, Math.min(600, (sec | 0)));

        function paint() {
            if (tm) {
                tm.hidden = state.otpLeft <= 0;
                tm.textContent = state.otpLeft > 0
                    ? 'ارسال دوباره تا ' + faDigits(state.otpLeft) + ' ثانیه'
                    : '';
            }
            if (rs) { rs.disabled = state.otpLeft > 0; }
        }

        paint();
        if (state.otpLeft > 0) {
            state.otpTimer = setInterval(function () {
                state.otpLeft--;
                paint();
                if (state.otpLeft <= 0) { stopOtpTimer(); }
            }, 1000);
        }
    }

    /** نمایش مرحلهٔ OTP — پیام سرور (وضعیت ارسال پیامک) + شمارش معکوس ارسال دوباره */
    function showOtpStep(res) {
        var p = otpPanel();
        if (!p) { return; }

        var d = p.querySelector('.ct-otp-d');
        if (d) {
            d.textContent = (res && res.message)
                || ('کد تأیید به شمارهٔ ' + (state.otpPhone || '') + ' پیامک شد — کد را وارد کنید.');
        }

        var otpIn = p.querySelector('.ct-otp-input');
        if (otpIn) {
            var len = (CFG.otp && CFG.otp.len) || 5;
            otpIn.value = '';
            otpIn.placeholder = new Array(Math.max(4, Math.min(8, len)) + 1).join('•');
        }
        otpShowErr('');

        // فرم مخفی — فقط مرحلهٔ کد دیده شود
        root.querySelector('.ct-m-body').style.display = 'none';
        root.querySelector('.ct-m-foot').style.display = 'none';
        p.hidden = false;

        if (otpIn) { setTimeout(function () { otpIn.focus(); }, 80); }
        startOtpTimer((res && res.resend) || ((CFG.otp && CFG.otp.resend) || 90));
    }

    function hideOtpStep() {
        var p = otpPanel();
        if (p) { p.hidden = true; }
        stopOtpTimer();
        otpShowErr('');
        if (root) {
            root.querySelector('.ct-m-body').style.display = '';
            root.querySelector('.ct-m-foot').style.display = '';
        }
    }

    /** تأیید کد → با موفقیت همان سفارش (بدون نیاز به کد مجدد) ثبت می‌شود */
    function otpVerifyO() {
        var p = otpPanel();
        if (!p || state.otpVerifying) { return; }

        var otpIn = p.querySelector('.ct-otp-input');
        var code = toEnDigits(otpIn ? otpIn.value : '').replace(/\D/g, '');
        if (!code) {
            otpShowErr('کد تأیید را وارد کنید.');
            if (otpIn) { otpIn.focus(); }
            return;
        }

        var btn = p.querySelector('.ct-otp-verify');
        var eps = otpEndpoints();
        if (!eps.verify) {
            otpShowErr('اندپوینت تأیید در دسترس نیست — صفحه را نوسازی کنید.');
            return;
        }

        state.otpVerifying = true;
        if (btn) { btn.disabled = true; btn.classList.add('ct-loading'); }

        post(eps.verify, { phone: state.otpPhone, code: code }).then(function (res) {
            state.otpVerifying = false;
            if (btn) { btn.disabled = false; btn.classList.remove('ct-loading'); }

            if (res && res.ok) {
                hideOtpStep();
                var body = state.otpBody;
                state.otpBody = null;
                if (body) {
                    // ★ پرچم تأیید سمت سرور نشسته است — سفارش همین حالا ثبت می‌شود
                    sendOrder(body);
                }
                return;
            }

            otpShowErr((res && res.message) || 'کد نادرست است — دوباره تلاش کنید.');
            if (otpIn) { otpIn.select(); otpIn.focus(); }
        }).catch(function () {
            state.otpVerifying = false;
            if (btn) { btn.disabled = false; btn.classList.remove('ct-loading'); }
            otpShowErr('خطا در بررسی کد — دوباره تلاش کنید.');
        });
    }

    /** ارسال دوبارهٔ کد (فاصلهٔ مجاز سمت سرور کنترل می‌شود) */
    function otpResendO() {
        var p = otpPanel();
        if (!p || state.otpResending) { return; }

        var rs = p.querySelector('.ct-otp-resend');
        var eps = otpEndpoints();
        if (!eps.send) {
            otpShowErr('اندپوینت ارسال در دسترس نیست — صفحه را نوسازی کنید.');
            return;
        }

        state.otpResending = true;
        if (rs) { rs.disabled = true; }

        post(eps.send, { phone: state.otpPhone }).then(function (res) {
            state.otpResending = false;
            if (rs) { rs.disabled = false; }

            if (res && res.ok) {
                var d = p.querySelector('.ct-otp-d');
                if (d) { d.textContent = (res.message || 'کد جدید پیامک شد.') + ' کد را وارد کنید.'; }
                otpShowErr('');
                var otpIn = p.querySelector('.ct-otp-input');
                if (otpIn) { otpIn.value = ''; otpIn.focus(); }
                startOtpTimer((res && res.resend) || ((CFG.otp && CFG.otp.resend) || 90));
                return;
            }

            // فاصلهٔ مجاز نقض شده → تایمر با زمان باقی‌ماندهٔ سرور ادامه می‌یابد
            if (res && !res.ok && typeof res.resend !== 'undefined') {
                otpShowErr(res.message || 'کد قبلی همین حالا ارسال شده — کمی صبر کنید.');
                startOtpTimer(res.resend);
                return;
            }

            otpShowErr((res && res.message) || 'ارسال دوباره ممکن نشد — کمی بعد تلاش کنید.');
        }).catch(function () {
            state.otpResending = false;
            if (rs) { rs.disabled = false; }
            otpShowErr('خطا در ارسال دوباره — دوباره تلاش کنید.');
        });
    }

    /* ---------------- ★ v1.4.0 — slug محصول از URL ---------------- */

    function slugFromUrl(href) {
        try {
            var u = new URL(href, location.href);
            var parts = u.pathname.split('/').filter(Boolean);
            if (!parts.length) { return ''; }
            var last = parts[parts.length - 1];
            if (/^(login|register|cart|checkout|product|products|p)$/i.test(last)) { return ''; }
            return decodeURIComponent(last).replace(/\.(html?|php)$/i, '');
        } catch (e) { return ''; }
    }

    /* ---------------- ★ v1.4.0 — داده‌های محصول از سرور (تنوع‌ها + ارسال) ---------------- */

    function fetchProductData(cb) {
        var url = CFG.endpoints && CFG.endpoints.product;
        if (!url || !state.slug) { cb(null); return; }

        var qs = '?slug=' + encodeURIComponent(state.slug);
        if (state.priceId) { qs += '&price_id=' + parseInt(state.priceId, 10); }
        if (state.addressId) { qs += '&address_id=' + parseInt(state.addressId, 10); }
        // ★ v1.4.1 — تعداد برای وزن و ارسال رایگان مبلغی
        if (state.qty > 1) { qs += '&qty=' + parseInt(state.qty, 10); }

        state.pDataLoading = true;
        get(url + qs).then(function (res) {
            state.pDataLoading = false;
            if (res && res.ok) {
                state.pData = res;
                state.isDownload = res.product && res.product.type === 'download';
                if (res.selected && res.selected.id) {
                    state.priceId = res.selected.id;
                    // انتخاب‌های فعلی تنوع از قیمت انتخاب‌شده
                    state.selAttrs = {};
                    (res.selected.attributes || []).forEach(function (a) {
                        if (a.group) { state.selAttrs[a.group.id] = a.id; }
                    });
                }
                cb(res);
            } else {
                cb(null);
            }
        }).catch(function () {
            state.pDataLoading = false;
            cb(null);
        });
    }

    /** متن تنوع‌های انتخاب‌شده — «رنگ: قرمز / گارانتی: …» */
    function variantText() {
        var parts = [];
        var groups = state.pData && state.pData.groups ? state.pData.groups : [];
        groups.forEach(function (g) {
            var want = state.selAttrs[g.id];
            if (!want) { return; }
            var attr = g.attributes.filter(function (x) { return x.id === want; })[0];
            if (attr) { parts.push(g.name + ': ' + attr.name); }
        });
        return parts.join(' / ');
    }

    /** آیا قیمت با انتخاب‌های فعلی تنوع مطابقت دارد؟ */
    function priceMatchesSel(price, groups) {
        for (var i = 0; i < groups.length; i++) {
            var gid = groups[i].id;
            var want = state.selAttrs[gid];
            if (!want) { continue; }
            var has = null;
            for (var j = 0; j < price.attributes.length; j++) {
                var a = price.attributes[j];
                if (a.group && a.group.id === gid) { has = a.id; break; }
            }
            if (has !== want) { return false; }
        }
        return true;
    }

    /** قیمت مطابق انتخاب فعلی (null = ترکیب وجود ندارد) */
    function matchedPrice() {
        if (!state.pData || !state.pData.prices || !state.pData.groups) { return null; }
        var groups = state.pData.groups;
        var all = state.pData.prices;
        // همهٔ گروه‌ها انتخاب شده‌اند؟
        for (var i = 0; i < groups.length; i++) {
            if (!state.selAttrs[groups[i].id]) { return null; }
        }
        for (var k = 0; k < all.length; k++) {
            if (priceMatchesSel(all[k], groups)) { return all[k]; }
        }
        return null;
    }

    /** آیا این تنوع با بقیهٔ انتخاب‌ها ترکیبی دارد؟ (برای خاموش/روشن کردن گزینه‌ها) */
    function attrCombinable(gid, attrId) {
        if (!state.pData || !state.pData.prices || !state.pData.groups) { return false; }
        var groups = state.pData.groups;
        var prices = state.pData.prices;

        var test = {};
        for (var key in state.selAttrs) {
            if (state.selAttrs.hasOwnProperty(key) && parseInt(key, 10) !== gid) {
                test[key] = state.selAttrs[key];
            }
        }
        test[gid] = attrId;

        for (var i = 0; i < prices.length; i++) {
            var p = prices[i];
            var ok = true;
            for (var j = 0; j < groups.length; j++) {
                var g = groups[j];
                var want = test[g.id];
                if (!want) { continue; }
                var has = null;
                for (var m = 0; m < p.attributes.length; m++) {
                    if (p.attributes[m].group && p.attributes[m].group.id === g.id) {
                        has = p.attributes[m].id; break;
                    }
                }
                if (has !== want) { ok = false; break; }
            }
            // ★ مثل خود فروشگاه (hasAttributeStock): فقط قیمت‌های «موجود» قابل-انتخاب
            if (ok && p.available) { return true; }
        }
        return false;
    }

    /* ---------------- ★ v1.4.0 — بخش تنوع‌های محصول (رنگ/گارانتی/…) ---------------- */

    function renderVariantSection() {
        var sec = root.querySelector('.ct-m-sec-var');
        if (!CFG.showVariants || !state.pData || !state.pData.groups || !state.pData.groups.length) {
            sec.hidden = true;
            return;
        }

        var groups = state.pData.groups;

        var html = '<div class="ct-sec-t"><span class="ct-sec-ic">' + ICONS.layers + '</span><span>انتخاب تنوع</span></div>';
        groups.forEach(function (g) {
            var isColor = g.type === 'color';
            html += '<div class="ct-var-group" data-gid="' + g.id + '">' +
                '<div class="ct-var-gt">' + esc(g.name) + '<span class="ct-var-gsel"></span></div>';

            if (isColor) {
                html += '<div class="ct-var-swatches">';
                g.attributes.forEach(function (a) {
                    var combinable = attrCombinable(g.id, a.id);
                    html += '<button type="button" class="ct-var-sw' +
                        (state.selAttrs[g.id] === a.id ? ' on' : '') +
                        (combinable ? '' : ' off') +
                        '" data-gid="' + g.id + '" data-aid="' + a.id + '"' +
                        ' title="' + esc(a.name) + '" aria-label="' + esc(g.name) + ': ' + esc(a.name) + '"' +
                        (combinable ? '' : ' disabled') +
                        ' style="background-color:' + esc(a.value || '#ccc') + '"></button>';
                });
                html += '</div>';
            } else {
                html += '<div class="ct-var-pills">';
                g.attributes.forEach(function (a) {
                    var combinable = attrCombinable(g.id, a.id);
                    html += '<button type="button" class="ct-var-pill' +
                        (state.selAttrs[g.id] === a.id ? ' on' : '') +
                        (combinable ? '' : ' off') +
                        '" data-gid="' + g.id + '" data-aid="' + a.id + '"' +
                        (combinable ? '' : ' disabled') + '>' + esc(a.name) + '</button>';
                });
                html += '</div>';
            }

            html += '</div>';
        });

        sec.innerHTML = html;
        sec.hidden = false;

        // برچسب انتخاب فعلی هر گروه + متن تنوع زیر عنوان محصول
        groups.forEach(function (g) {
            var want = state.selAttrs[g.id];
            var label = sec.querySelector('.ct-var-group[data-gid="' + g.id + '"] .ct-var-gsel');
            if (label) {
                if (want) {
                    var a = g.attributes.filter(function (x) { return x.id === want; })[0];
                    label.textContent = ' ' + (a ? a.name : '');
                } else {
                    label.textContent = '';
                }
            }
        });
        var pvar = root.querySelector('.ct-m-pvar');
        var vt = variantText();
        pvar.textContent = vt;
        pvar.style.display = vt ? '' : 'none';

        // رویداد انتخاب
        sec.querySelectorAll('.ct-var-sw[data-aid], .ct-var-pill[data-aid]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (btn.disabled) { return; }
                var gid = parseInt(btn.getAttribute('data-gid'), 10) || 0;
                var aid = parseInt(btn.getAttribute('data-aid'), 10) || 0;
                if (!gid || !aid || state.selAttrs[gid] === aid) { return; }

                state.selAttrs[gid] = aid;

                // قیمت مطابق — اگر ترکیب موجود است، price_id را عوض کن و ارسال‌ها را تازه کن
                var mp = matchedPrice();
                if (mp) {
                    state.priceId = mp.id;
                    // سقف تعداد از موجودی
                    if (state.qty > (mp.stock - mp.reserved)) { setQty(Math.max(1, mp.stock - mp.reserved)); }
                    refreshProductData(function () {
                        renderVariantSection();
                        renderTotal();
                    });
                } else {
                    renderVariantSection();
                    renderTotal();
                }
            });
        });

        // وضعیت موجودی تنوع انتخاب‌شده
        var mp = matchedPrice();
        var note = sec.querySelector('.ct-var-note');
        if (!note) {
            note = el('div', 'ct-var-note');
            sec.appendChild(note);
        }
        if (!mp) {
            note.textContent = 'این ترکیب موجود نیست — تنوع دیگری انتخاب کنید.';
            note.classList.add('ct-var-note-err');
            note.hidden = false;
        } else if (!mp.available) {
            note.textContent = 'این تنوع فعلاً موجود نیست.';
            note.classList.add('ct-var-note-err');
            note.hidden = false;
        } else {
            note.hidden = true;
        }
    }

    /** تازه‌سازی داده‌های محصول با انتخاب‌های فعلی (price_id + address_id) */
    function refreshProductData(cb) {
        fetchProductData(function (res) {
            if (res) {
                // به‌روزرسانی نمایش محصول (عنوان/قیمت) + ارسال‌های فروشندهٔ تنوع
                renderVariantSection();
                // ★ v1.4.1 فیکس: opts عبور داده می‌شود تا اگر دادههای
                //   محصول ارسال خالی بود، فالبک آپشن‌ها مانده باشد و بخش «شیوهٔ ارسال» مخفی نشود
                renderShippingSection(state.opts);
            }
            if (cb) { cb(res); }
        });
    }

    function fetchOptions(cb) {
        var url = CFG.endpoints && CFG.endpoints.options;
        if (!url) { cb(null); return; }

        // کش ۳۰ ثانیه‌ای
        if (state.opts && (Date.now() - state.optsAt) < 30000) { cb(state.opts); return; }

        if (state.optsLoading) {
            state.optsQueue.push(cb);
            return;
        }
        state.optsLoading = true;

        get(url).then(function (res) {
            state.optsLoading = false;
            if (res && res.ok) {
                state.opts = res;
                state.optsAt = Date.now();
            }
            cb(state.opts);
            var q = state.optsQueue.splice(0);
            q.forEach(function (f) { f(state.opts); });
        }).catch(function () {
            state.optsLoading = false;
            cb(state.opts);
            var q = state.optsQueue.splice(0);
            q.forEach(function (f) { f(state.opts); });
        });
    }

    /* ---------------- بخش آدرس تحویل ---------------- */

    /* ★ v1.3.0 — دکمهٔ «مدیریت/افزودن آدرس در پنل کاربری» — به‌جای فرم داخل مودال
     * (افزودن آدرس دیگر داخل مودال نیست؛ کاربر باید از پنل خودش اضافه کند) */
    function panelAddressesLink(strong) {
        var url = String((CFG.panelUrl || '/profile/addresses') || '/profile/addresses');
        return '<a class="ct-addr-panel' + (strong ? ' ct-addr-panel-strong' : '') + '" href="' + esc(url) + '" target="_blank" rel="noopener noreferrer">' +
            '<span class="ct-addr-panel-ic">' + (strong ? ICONS.add : ICONS.ext) + '</span>' +
            '<span>' + (strong ? 'افزودن آدرس در پنل کاربری' : 'مدیریت آدرس‌ها در پنل کاربری') + '</span>' +
            '<span class="ct-addr-panel-arrow">' + ICONS.ext + '</span>' +
            '</a>';
    }

    function renderAddressSection(opts) {
        var m = CFG.modal || {};
        var fields = m.fields || {};
        var sec = root.querySelector('.ct-m-sec-addr');

        if (fields.address === 'off') { sec.hidden = true; return; }

        // ★ v1.4.0 — محصول دانلودی → بدون آدرس (مثل checkout خود فروشگاه)
        if (state.isDownload) { sec.hidden = true; state.addressId = 0; return; }

        var loggedIn = !!(opts && opts.user && opts.user.loggedIn);
        var addresses = (opts && opts.addresses && opts.addresses.length && m.addressSaved !== false)
            ? opts.addresses : [];

        var html = '<div class="ct-sec-t"><span class="ct-sec-ic">' + ICONS.pin + '</span><span>آدرس تحویل</span></div>';

        if (loggedIn) {
            /* ★ v1.3.0 — کاربر واردشده: فقط آدرس‌های ذخیرهٔ پنل (بدون «آدرس جدید») */
            if (addresses.length) {
                html += '<div class="ct-addr-list">';
                addresses.forEach(function (a) {
                    html += '<div class="ct-addr' + (a.active ? ' ct-addr-act' : '') + '" data-aid="' + a.id + '" role="button" tabindex="0">' +
                        '<span class="ct-addr-dot"></span>' +
                        '<div class="ct-addr-b">' +
                        '<div class="ct-addr-t"><span class="ct-addr-city"></span>' +
                        (a.active ? '<span class="ct-addr-badge">پیش‌فرض</span>' : '') +
                        '</div>' +
                        '<div class="ct-addr-x"></div>' +
                        '<div class="ct-addr-m"></div>' +
                        '</div></div>';
                });
                html += '</div>';
                html += panelAddressesLink(false);
            } else {
                /* ★ v1.3.0 — بدون آدرس ذخیره‌شده → فقط راهنما + دکمهٔ پنل */
                html += '<div class="ct-addr-empty">' +
                    '<div class="ct-addr-empty-t">شما هنوز آدرسی ثبت نکرده‌اید</div>' +
                    '<div class="ct-addr-empty-d">برای ثبت آدرس جدید، از پنل کاربری خود اقدام کنید؛ سپس اینجا انتخابش کنید.</div>' +
                    '</div>';
                html += panelAddressesLink(true);
            }

            sec.innerHTML = html;
            sec.hidden = false;
        } else {
            /* ★ v1.3.0 — مهمان (بدون پنل): فیلد متنی سادهٔ آدرس */
            html += '<div class="ct-addr-manual"><textarea class="ct-input ct-addr-ta" data-field="address" rows="2" placeholder="استان، شهر، خیابان، پلاک..." dir="rtl"></textarea></div>';
            sec.innerHTML = html;
            sec.hidden = false;
            state.addressId = 0;
        }

        // متن‌ها (XSS-safe)
        var cards = sec.querySelectorAll('.ct-addr[data-aid]');
        cards.forEach(function (c) {
            var id = parseInt(c.getAttribute('data-aid'), 10) || 0;
            if (id > 0) {
                var a = addresses.filter(function (x) { return x.id === id; })[0];
                if (a) {
                    c.querySelector('.ct-addr-city').textContent = [a.province, a.city].filter(Boolean).join('، ') || 'آدرس ذخیره‌شده';
                    c.querySelector('.ct-addr-x').textContent = a.address + (a.building ? '، پلاک ' + a.building : '') + (a.unit ? '، واحد ' + a.unit : '');
                    var meta = [];
                    if (a.postal_code) { meta.push('کدپستی ' + a.postal_code); }
                    if (a.fullname) { meta.push(a.fullname); }
                    c.querySelector('.ct-addr-m').textContent = meta.join(' — ');
                }
            }
        });

        // انتخاب پیش‌فرض: آدرس فعال → اولین (بدون آدرس = هیچ)
        var defId = 0;
        var act = addresses.filter(function (x) { return x.active; })[0];
        if (act) { defId = act.id; }
        else if (addresses.length) { defId = addresses[0].id; }

        if (loggedIn) { selectAddress(defId); }

        function selectAddress(id) {
            var changed = state.addressId !== id;
            state.addressId = id;
            cards.forEach(function (c) {
                c.classList.toggle('on', (parseInt(c.getAttribute('data-aid'), 10) || 0) === id);
            });
            var manual = sec.querySelector('.ct-addr-manual');
            if (manual) { manual.style.display = (id === 0 || !loggedIn) ? '' : 'none'; }

            // ★ v1.4.0 — تغییر آدرس → هزینهٔ ارسال با تعرفهٔ شهر جدید (سرور)
            if (changed && state.pData && state.priceId) {
                refreshProductData(function () { renderTotal(); });
            }
        }

        cards.forEach(function (c) {
            c.addEventListener('click', function () {
                selectAddress(parseInt(c.getAttribute('data-aid'), 10) || 0);
            });
            c.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    selectAddress(parseInt(c.getAttribute('data-aid'), 10) || 0);
                }
            });
        });

        if (fields.address === 'req') { sec.classList.add('ct-sec-req'); }
    }

    /* ---------------- بخش روش ارسال ---------------- */

    function renderShippingSection(opts) {
        var m = CFG.modal || {};
        var sec = root.querySelector('.ct-m-sec-ship');

        // ★ v1.4.0 — محصول دانلودی → بدون ارسال
        if (state.isDownload) {
            sec.hidden = true; state.carrierId = 0; state.carrierCost = 0; renderTotal(); return;
        }

        // ★ v1.4.0 — ارسال‌های مجاز «فروشندهٔ تنوع انتخاب‌شده» با هزینهٔ واقعی
        //   (تعرفهٔ شهر آدرس) — اگر داده‌های محصول در دسترس نبود: fallback آپشن‌ها
        var carriers = [];
        if (state.pData && state.pData.carriers && state.pData.carriers.length) {
            carriers = state.pData.carriers;
        } else if (opts && opts.carriers && m.shipping !== false) {
            carriers = opts.carriers;
        }
        if (!carriers.length || m.shipping === false) { sec.hidden = true; state.carrierId = 0; state.carrierCost = 0; renderTotal(); return; }

        var html = '<div class="ct-sec-t"><span class="ct-sec-ic">' + ICONS.truck + '</span><span>شیوهٔ ارسال</span></div>';
        html += '<div class="ct-optlist">';

        carriers.forEach(function (c) {
            html += '<div class="ct-opt" data-cid="' + c.id + '" data-cost="' + (c.cost || 0) + '" role="button" tabindex="0">' +
                '<span class="ct-opt-dot"></span>' +
                (c.image ? '<img class="ct-opt-img" alt="" loading="lazy" src="' + esc(c.image) + '" onerror="this.style.display=\'none\'">' : '') +
                '<div class="ct-opt-b">' +
                '<div class="ct-opt-t"></div>' +
                (c.description ? '<div class="ct-opt-d"></div>' : '') +
                (c.delivery_text ? '<div class="ct-opt-eta"></div>' : '') +
                '</div>' +
                (c.cost > 0
                    ? '<div class="ct-opt-c">' + faDigits(c.cost) + ' <small>تومان</small></div>'
                    : (c.cod
                        ? '<div class="ct-opt-c ct-cod">پس‌کرایه</div>'
                        : '<div class="ct-opt-c ct-free">رایگان</div>')) +
                '</div>';
        });

        html += '</div>';
        sec.innerHTML = html;
        sec.hidden = false;

        var rows = sec.querySelectorAll('.ct-opt[data-cid]');
        rows.forEach(function (r) {
            var id = parseInt(r.getAttribute('data-cid'), 10) || 0;
            var c = carriers.filter(function (x) { return x.id === id; })[0];
            if (c) {
                r.querySelector('.ct-opt-t').textContent = c.title || 'ارسال';
                if (c.description) { r.querySelector('.ct-opt-d').textContent = c.description; }
                var eta = r.querySelector('.ct-opt-eta');
                if (eta && c.delivery_text) { eta.textContent = c.delivery_text; }
            }
        });

        function selectCarrier(id) {
            state.carrierId = id;
            var row = null;
            rows.forEach(function (r) {
                var on = (parseInt(r.getAttribute('data-cid'), 10) || 0) === id;
                r.classList.toggle('on', on);
                if (on) { row = r; }
            });
            state.carrierCost = row ? (parseInt(row.getAttribute('data-cost'), 10) || 0) : 0;
            renderTotal();
        }

        // پیش‌فرض: اولین روش
        selectCarrier(carriers[0].id);

        rows.forEach(function (r) {
            r.addEventListener('click', function () { selectCarrier(parseInt(r.getAttribute('data-cid'), 10) || 0); });
            r.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    selectCarrier(parseInt(r.getAttribute('data-cid'), 10) || 0);
                }
            });
        });
    }

    /* ---------------- بخش روش پرداخت ---------------- */

    function renderPaymentSection(opts) {
        var m = CFG.modal || {};
        var sec = root.querySelector('.ct-m-sec-pay');

        var gateways = (opts && opts.gateways && m.payment !== false) ? opts.gateways : [];
        // ★ v1.5.0 — کیف پول قابل خاموش‌کردن از تنظیمات (paymentWallet)
        var wallet = (opts && opts.wallet && m.payment !== false && m.paymentWallet !== false
            && opts.wallet.available && opts.wallet.balance > 0 && opts.user && opts.user.loggedIn)
            ? opts.wallet : null;

        if (!gateways.length && !wallet) { sec.hidden = true; state.gatewayKey = ''; return; }

        var html = '<div class="ct-sec-t"><span class="ct-sec-ic">' + ICONS.card + '</span><span>شیوهٔ پرداخت</span></div>';
        html += '<div class="ct-optlist">';

        if (wallet) {
            html += '<div class="ct-opt" data-gk="wallet" role="button" tabindex="0">' +
                '<span class="ct-opt-dot"></span>' +
                '<span class="ct-opt-ic">' + ICONS.wallet + '</span>' +
                '<div class="ct-opt-b">' +
                '<div class="ct-opt-t">پرداخت با کیف پول</div>' +
                '<div class="ct-opt-d">موجودی: ' + faDigits(wallet.balance) + ' تومان</div>' +
                '</div></div>';
        }

        gateways.forEach(function (g) {
            html += '<div class="ct-opt" data-gk="' + esc(g.key) + '" role="button" tabindex="0">' +
                '<span class="ct-opt-dot"></span>' +
                (g.image ? '<img class="ct-opt-img" alt="" loading="lazy" src="' + esc(g.image) + '" onerror="this.style.display=\'none\'">' : '<span class="ct-opt-ic">' + ICONS.card + '</span>') +
                '<div class="ct-opt-b">' +
                '<div class="ct-opt-t">پرداخت اینترنتی</div>' +
                '<div class="ct-opt-d"></div>' +
                '</div></div>';
        });

        html += '</div>';
        sec.innerHTML = html;
        sec.hidden = false;

        var rows = sec.querySelectorAll('.ct-opt[data-gk]');
        rows.forEach(function (r) {
            var k = r.getAttribute('data-gk');
            if (k !== 'wallet') {
                var g = gateways.filter(function (x) { return x.key === k; })[0];
                if (g) {
                    r.querySelector('.ct-opt-t').textContent = 'پرداخت اینترنتی ' + g.name;
                    r.querySelector('.ct-opt-d').textContent = 'آنلاین با تمامی کارت‌های بانکی';
                }
            }
        });

        function selectGateway(k) {
            state.gatewayKey = k;
            rows.forEach(function (r) { r.classList.toggle('on', r.getAttribute('data-gk') === k); });
        }

        // پیش‌فرض: اولین درگاه
        selectGateway(gateways.length ? gateways[0].key : 'wallet');

        rows.forEach(function (r) {
            r.addEventListener('click', function () { selectGateway(r.getAttribute('data-gk')); });
            r.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    selectGateway(r.getAttribute('data-gk'));
                }
            });
        });
    }

    /* ---------------- پرکردن خودکار کاربر واردشده ---------------- */

    function autofill(opts) {
        if (!opts || !opts.user || !opts.user.loggedIn) { return; }
        var u = opts.user;

        var nameIn = root.querySelector('[data-field="name"]');
        if (nameIn && !nameIn.value.trim() && u.name) { nameIn.value = u.name; }

        var phoneIn = root.querySelector('[data-field="phone"]');
        if (phoneIn && !phoneIn.value.trim() && u.phone) {
            var p = toEnDigits(u.phone).replace(/\D/g, '').substring(0, 11);
            phoneIn.value = p;
        }
    }

    /* ---------------- تعداد و جمع کل ---------------- */

    function setQty(n) {
        var max = ((CFG.modal || {}).maxQty) || 10;
        var prev = state.qty;
        state.qty = Math.max(1, Math.min(max, n));
        root.querySelector('.ct-qty-val').textContent = faDigits(state.qty);
        renderTotal();

        // ★ v1.4.1 — تغییر تعداد → وزن عوض می‌شود → هزینهٔ ارسال
        //   (تعرفهٔ وزنی / ارسال رایگان وزنی) با سرور تازه شود (debounce)
        if (state.qty !== prev && state.pData && state.priceId && !state.isDownload) {
            if (state.qtyTimer) { clearTimeout(state.qtyTimer); }
            state.qtyTimer = setTimeout(function () {
                state.qtyTimer = null;
                if (!root || !root.classList.contains('ct-open')) { return; }
                refreshProductData(function () { renderTotal(); });
            }, 400);
        }
    }

    function renderTotal() {
        var m = CFG.modal || {};
        var showQty = m.qty !== false;

        // ★ v1.4.0 — قیمت «تنوع انتخاب‌شده» از سرور (کلاینت معتبر نیست)
        var unit = state.product ? state.product.price : 0;
        var realUnit = 0;
        if (state.pData && state.pData.selected) {
            unit = state.pData.selected.final_price || 0;
            realUnit = state.pData.selected.price || 0;
        }
        var items = unit * state.qty;
        var total = items + state.carrierCost;

        var priceBox = root.querySelector('.ct-m-pprice');
        if (unit > 0) {
            var line = (realUnit > unit ? '<s>' + faDigits(realUnit) + '</s> ' : '') +
                faDigits(unit) + (showQty ? ' × ' + faDigits(state.qty) : '') + ' = <b>' + faDigits(items) + '</b> ' + (CFG.currency || 'تومان');
            priceBox.innerHTML = line;
        } else {
            priceBox.textContent = '';
        }

        // جمع کل نوار پایین (وقتی ارسال انتخاب شده یا دانلودی است)
        var box = root.querySelector('.ct-m-total');
        if (total > 0 && (state.carrierCost > 0 || state.isDownload)) {
            box.hidden = false;
            box.innerHTML = (state.carrierCost > 0
                ? 'کالا ' + faDigits(items) + ' + ارسال ' + faDigits(state.carrierCost)
                : 'مبلغ پرداختی ' + faDigits(items))
                + ' = <b>' + faDigits(total) + ' ' + (CFG.currency || 'تومان') + '</b>';
        } else if (total > 0) {
            box.hidden = false;
            box.innerHTML = 'مبلغ پرداختی <b>' + faDigits(total) + ' ' + (CFG.currency || 'تومان') + '</b>';
        } else {
            box.hidden = true;
        }
    }

    /* ---------------- باز/بستن ---------------- */

    function openModal(source, product) {
        if (!root) { buildModal(); }

        state.product = product;
        state.source = source || 'page';
        state.qty = 1;
        state.openedAt = Date.now();

        // ★ ریست انتخاب‌های تسویه
        state.addressId = 0;
        state.carrierId = 0;
        state.carrierCost = 0;
        state.gatewayKey = '';
        state.opts = null;
        state.optsAt = 0;

        // ★ v1.4.0 — ریست داده‌های تنوع
        state.slug = (product && product.url ? slugFromUrl(product.url) : '') || (P.url ? slugFromUrl(P.url) : '');
        state.priceId = 0;
        state.pData = null;
        state.isDownload = false;
        state.selAttrs = {};

        var md = CFG.modal || {};

        // پر کردن اطلاعات محصول
        root.querySelector('.ct-m-title').textContent = md.title || 'خرید سریع';
        root.querySelector('.ct-m-desc').textContent = md.desc || '';
        root.querySelector('.ct-m-pname').textContent = product.title || 'محصول';
        root.querySelector('.ct-m-pvar').textContent = product.variants || '';
        root.querySelector('.ct-m-pvar').style.display = product.variants ? '' : 'none';

        var img = root.querySelector('.ct-m-pimg img');
        if (product.image) {
            img.src = product.image;
            img.alt = product.title || '';
            root.querySelector('.ct-m-pimg').style.display = '';
        } else {
            root.querySelector('.ct-m-pimg').style.display = 'none';
        }

        // تعداد
        root.querySelector('.ct-m-qty').style.display = md.qty ? '' : 'none';
        setQty(1);

        // فیلدهای پایه + متن دکمه
        buildFields();
        root.querySelector('.ct-submit-tx').textContent = md.submit || 'ثبت سفارش سریع';

        // ★ بخش‌های تسویه — ابتدا مخفی تا آپشن‌ها برسند
        root.querySelector('.ct-m-sec-var').hidden = true;
        root.querySelector('.ct-m-sec-addr').hidden = true;
        root.querySelector('.ct-m-sec-ship').hidden = true;
        root.querySelector('.ct-m-sec-pay').hidden = true;
        hideLoginGate();

        fetchOptions(function (opts) {
            // مودال هنوز باز است و همین محصول است؟
            if (!root.classList.contains('ct-open') || state.product !== product) { return; }

            // ★ v1.4.0 — گیت لاگین: خرید سریع فقط برای کاربران واردشده
            if (CFG.requireLogin !== false && !(opts && opts.user && opts.user.loggedIn)) {
                showLoginGate();
                return;
            }

            // ★ v1.4.0 — داده‌های محصول (تنوع‌ها + ارسال واقعی) از سرور؛
            //   اگر نرسید → fallback به آپشن‌های عمومی (رفتار v1.3)
            fetchProductData(function (pdata) {
                if (!root.classList.contains('ct-open') || state.product !== product) { return; }
                if (pdata) {
                    renderVariantSection();
                }
                renderAddressSection(opts);
                renderShippingSection(opts);
                renderPaymentSection(opts);
                autofill(opts);
                renderTotal();
            });
        });

        // ریست حالت‌ها
        root.querySelector('.ct-m-error').hidden = true;
        root.querySelector('.ct-m-success').hidden = true;
        root.querySelector('.ct-m-body').style.display = '';
        root.querySelector('.ct-m-foot').style.display = '';
        root.querySelector('.ct-m-head').style.display = '';
        var sb = root.querySelector('.ct-submit');
        sb.disabled = false;
        sb.classList.remove('ct-loading');

        root.classList.add('ct-open');
        document.body.classList.add('ct-no-scroll');

        // فوکوس روی اولین فیلد
        setTimeout(function () {
            var first = root.querySelector('.ct-input:not(.ct-hp)');
            if (first) { try { first.focus(); } catch (e) { /* بی‌خطر */ } }
        }, 250);

        // بیکن نرخ تبدیل
        try {
            if (CFG.endpoints && CFG.endpoints.track) {
                post(CFG.endpoints.track, {}).catch(function () { /* بی‌خطر */ });
            }
        } catch (e) { /* بی‌خطر */ }
    }

    function closeModal() {
        if (!root) { return; }
        root.classList.remove('ct-open');
        document.body.classList.remove('ct-no-scroll');

        if (state.redirectTimer) {
            clearInterval(state.redirectTimer);
            clearTimeout(state.redirectTimer);
            state.redirectTimer = null;
        }

        // ★ v1.7.0 — ریست مرحلهٔ OTP (بدنهٔ ذخیره‌شده باطل می‌شود)
        hideOtpStep();
        state.otpBody = null;
        state.otpPhone = '';
        state.otpVerifying = false;
        state.otpResending = false;
    }

    function showErr(msg, fieldKey) {
        var box = root.querySelector('.ct-m-error');
        box.textContent = msg;
        box.hidden = false;

        if (fieldKey) {
            var input = root.querySelector('[data-field="' + fieldKey + '"]');
            if (input) {
                input.classList.add('ct-input-err');
                input.focus();
                setTimeout(function () { input.classList.remove('ct-input-err'); }, 2000);
            }
        }
    }

    /* ---------------- ارسال سفارش ---------------- */

    function submitOrder() {
        if (state.sending) { return; }

        var m = CFG.modal || {};
        var fields = m.fields || {};
        var isPhysical = !state.isDownload; // ★ v1.4.0 — دانلودی = بدون آدرس/ارسال

        // ★ v1.4.0 — تنوع انتخاب‌شده باید موجود باشد
        var mp = matchedPrice();
        if (state.pData && state.pData.groups && state.pData.groups.length) {
            if (!mp) {
                showErr('این ترکیب تنوع موجود نیست — تنوع دیگری انتخاب کنید.');
                return;
            }
            if (!mp.available) {
                showErr('این تنوع فعلاً موجود نیست — تنوع دیگری انتخاب کنید.');
                return;
            }
            if (state.qty > (mp.stock - mp.reserved)) {
                showErr('حداکثر ' + Math.max(0, mp.stock - mp.reserved) + ' عدد از این تنوع موجود است.');
                return;
            }
        }

        var body = {
            // ★ v1.4.0 — رزولوشن سرور-محور: slug + تنوع انتخاب‌شده (price_id)
            product_slug: state.slug || null,
            product_id: state.product ? state.product.id : null,
            price_id: state.priceId || null,
            qty: state.qty,
            variant_text: variantText() || (state.product ? state.product.variants : null),
            source: state.source,
            // فقط برای رکورد اختصاصی ماژول (سفارش واقعی از DB فروشگاه خوانده می‌شود)
            product_title: state.product ? state.product.title : null,
            product_url: state.product ? state.product.url : null,
            product_image: state.product ? state.product.image : null,
            product_price: (state.pData && state.pData.selected ? state.pData.selected.final_price : (state.product ? state.product.price : 0))
        };

        // نام/موبایل/یادداشت (اطلاعاتی — سفارش واقعی از آدرس/پروفایل می‌خواند)
        var inputs = root.querySelectorAll('[data-field]');
        for (var i = 0; i < inputs.length; i++) {
            var key = inputs[i].getAttribute('data-field');
            var val = inputs[i].value.trim();
            body['customer_' + key] = val;
        }

        if (fields.name === 'req' && !(body.customer_name || '').trim()) {
            showErr((CFG.msg && CFG.msg.errRequired) || 'تکمیل این فیلد الزامی است.', 'name');
            return;
        }
        if (fields.phone !== 'off') {
            var ph = toEnDigits(body.customer_phone || '').replace(/\D/g, '');
            if (!/^09\d{9}$/.test(ph)) {
                showErr((CFG.msg && CFG.msg.errPhone) || 'شمارهٔ موبایل معتبر نیست — مانند ۰۹۱۲۳۴۵۶۷۸۹ وارد کنید.', 'phone');
                return;
            }
            body.customer_phone = ph;
        }

        // ★ v1.7.0 — اعتبارسنجی هوشمند سمت کلاینت (هم‌راستا با سرور)
        var val = CFG.validation || {};
        var valOn = val.enabled !== false;
        if (valOn && val.name !== false && fields.name !== 'off') {
            var nmLen = (body.customer_name || '').trim().length;
            if (nmLen > 0 && nmLen < 3) {
                showErr('نام و نام خانوادگی خیلی کوتاه است — حداقل ۳ نویسه.', 'name');
                return;
            }
        }

        // ★ v1.7.0 — کد ملی گیرنده: نرمال‌سازی + چک‌سام (اگر روشن باشد)
        var ncMode = fields.nationalCode || 'off';
        var nc = toEnDigits(body.customer_national_code || '').replace(/\D/g, '');
        body.customer_national_code = nc;
        if (ncMode !== 'off') {
            if (ncMode === 'req' && !nc) {
                showErr('کد ملی گیرنده الزامی است.', 'national_code');
                return;
            }
            if (nc) {
                var ncOk = (valOn && val.national !== false)
                    ? validNationalCode(nc)
                    : /^\d{10}$/.test(nc);
                if (!ncOk) {
                    showErr('کد ملی معتبر نیست — ۱۰ رقم صحیح وارد کنید.', 'national_code');
                    return;
                }
            }
        }

        // ★ v1.4.0 — آدرس: فقط آدرس ذخیرهٔ خود کاربر (بدون فیلد متنی مهمان)
        if (isPhysical && fields.address !== 'off') {
            if (!state.addressId || state.addressId <= 0) {
                showErr('یک آدرس تحویل از دفترچهٔ خودتان انتخاب کنید — برای افزودن آدرس جدید از پنل کاربری اقدام کنید.', null);
                return;
            }
            body.address_id = state.addressId;
        }

        // ★ v1.4.0 — روش ارسال (فقط فیزیکی)
        if (isPhysical) {
            if (!state.carrierId || state.carrierId <= 0) {
                showErr('روش ارسال را انتخاب کنید.', null);
                return;
            }
            body.carrier_id = state.carrierId;
        }

        // ★ v1.4.0 — روش پرداخت
        if (state.gatewayKey) { body.gateway_key = state.gatewayKey; }

        // ★ v1.4.0 — پیش‌چک کیف پول (بازخورد فوری؛ سرور مرجع نهایی است)
        if (state.gatewayKey === 'wallet' && state.opts && state.opts.wallet && state.opts.wallet.available) {
            var unit = state.pData && state.pData.selected ? state.pData.selected.final_price : (state.product ? state.product.price : 0);
            var preTotal = unit * state.qty + state.carrierCost;
            if (preTotal > 0 && state.opts.wallet.balance < preTotal) {
                showErr('موجودی کیف پول کافی نیست — موجودی فعلی: ' + faDigits(state.opts.wallet.balance) +
                    ' تومان، مبلغ سفارش: ' + faDigits(preTotal) + ' تومان. روش دیگری انتخاب کنید.');
                return;
            }
        }

        // ★ ضد بات — honeypot + زمان پر کردن
        var hp = root.querySelector('.ct-hp');
        body.ct_website = hp ? hp.value : '';
        body.ct_ms = state.openedAt ? (Date.now() - state.openedAt) : 0;

        root.querySelector('.ct-m-error').hidden = true;
        sendOrder(body);
    }

    /* ---------------- ★ v1.7.0 — ارسال سفارش (مشترک بین فرم و مرحلهٔ OTP) ---------------- */

    function sendOrder(body) {
        if (state.sending) { return; }
        state.sending = true;

        var sb = root.querySelector('.ct-submit');
        sb.disabled = true;
        sb.classList.add('ct-loading');

        post(CFG.endpoints.order, body).then(function (res) {
            state.sending = false;
            sb.classList.remove('ct-loading');
            sb.disabled = false;

            // ★ v1.4.0 — نشست منقضی شده → گیت لاگین
            if (res && res.needLogin) {
                showErr(res.message || 'برای خرید سریع باید وارد حساب کاربری شوید.');
                showLoginGate();
                return;
            }

            // ★ v1.7.0 — تأیید موبایل با کد یکبارمصرف: مرحلهٔ ورود کد
            if (res && res.needOtp) {
                state.otpBody = body;
                state.otpPhone = String(res.phone || body.customer_phone || '');
                showOtpStep(res);
                return;
            }

            if (res && res.ok) {
                // ★ v1.4.0 — درگاه آنلاین: انتقال مستقیم به بانک
                if (res.pay && res.pay.type === 'url' && res.pay.url) {
                    showSuccess(res); // نمای «در حال انتقال»
                    window.location.href = res.pay.url;
                    return;
                }
                if (res.pay && res.pay.type === 'html' && res.pay.html) {
                    showSuccess(res);
                    setTimeout(function () { gatewayAutoSubmit(res.pay.html); }, 500);
                    return;
                }
                showSuccess(res);
            } else {
                var msg = (res && res.message)
                    || ((res && (res.status === 429 || (typeof res.ok === 'undefined'))) ? ((CFG.msg && CFG.msg.errLimit) || null) : null)
                    || (CFG.msg && CFG.msg.errGeneric)
                    || 'خطا در ثبت سفارش — دوباره تلاش کنید.';
                var fk = res && res.field ? String(res.field).replace('customer_', '') : null;
                showErr(msg, fk);
            }
        }).catch(function () {
            state.sending = false;
            sb.classList.remove('ct-loading');
            sb.disabled = false;
            showErr((CFG.msg && CFG.msg.errGeneric) || 'خطا در ثبت سفارش — دوباره تلاش کنید.');
        });
    }

    /** ★ v1.4.0 — فرم خودکار درگاه (Shetabit render) را در صفحه قرار بده و بفرست */
    function gatewayAutoSubmit(html) {
        try {
            var wrap = document.createElement('div');
            wrap.setAttribute('aria-hidden', 'true');
            wrap.style.cssText = 'position:fixed;left:-99999px;top:0;width:1px;height:1px;overflow:hidden;';
            wrap.innerHTML = html;
            document.body.appendChild(wrap);

            var form = wrap.querySelector('form');
            if (form) {
                form.setAttribute('accept-charset', 'UTF-8');
                form.submit();
                return;
            }

            // بدون فرم — اسکریپت انتقال؟ (اجرای امن نیست) → حذف و خطا
            document.body.removeChild(wrap);
            showErr('در حال انتقال به درگاه… اگر منتقل نشدید، صفحه را نوسازی کنید.');
        } catch (e) {
            showErr('خطا در انتقال به درگاه — دوباره تلاش کنید.');
        }
    }

    function showSuccess(res) {
        root.querySelector('.ct-m-body').style.display = 'none';
        root.querySelector('.ct-m-foot').style.display = 'none';
        root.querySelector('.ct-m-head').style.display = 'none';

        var ok = root.querySelector('.ct-m-success');
        ok.hidden = false;

        root.querySelector('.ct-ok-title').textContent = res.message || ((CFG.msg && CFG.msg.success) || 'سفارش شما ثبت شد!');
        root.querySelector('.ct-ok-desc').textContent = res.desc || ((CFG.msg && CFG.msg.successDesc) || '');

        var codeBox = root.querySelector('.ct-ok-code');
        if (res.code) {
            codeBox.style.display = '';
            codeBox.querySelector('span').textContent = res.code;
        } else {
            codeBox.style.display = 'none';
        }

        // ریدایرکت اختیاری
        var rd = root.querySelector('.ct-ok-redirect');
        var url = res.redirect || (CFG.msg && CFG.msg.redirect) || '';
        var sec = (typeof res.redirectSec === 'number') ? res.redirectSec : ((CFG.msg && CFG.msg.redirectSec) || 0);

        // ★ v1.4.0 — در حال انتقال به درگاه پرداخت
        if (res.pay && (res.pay.url || res.pay.html)) {
            rd.hidden = false;
            rd.textContent = 'در حال انتقال به درگاه پرداخت امن…';
            return;
        }

        if (url && sec > 0) {
            var left = sec;
            rd.hidden = false;
            rd.textContent = 'انتقال به ' + url + ' تا ' + faDigits(left) + ' ثانیه دیگر…';
            state.redirectTimer = setInterval(function () {
                left--;
                if (left <= 0) {
                    clearInterval(state.redirectTimer);
                    state.redirectTimer = null;
                    window.location.href = url;
                    return;
                }
                rd.textContent = 'انتقال تا ' + faDigits(left) + ' ثانیه دیگر…';
            }, 1000);
        } else if (url) {
            // ★ v1.3.0 — انتقال فوری به صفحهٔ «سفارش ثبت شد» (بدون شمارش)
            rd.hidden = false;
            rd.textContent = 'در حال انتقال به صفحهٔ سفارش ثبت‌شده…';
            state.redirectTimer = setTimeout(function () {
                state.redirectTimer = null;
                window.location.href = url;
            }, 800);
        } else {
            rd.hidden = true;
        }
    }

    /* ================================================================
     *  ۵) حالت «سبد + تسویه» (add to cart + checkout)
     * ================================================================ */

    function cartModeGo() {
        var url = CFG.checkoutUrl || '/checkout';
        var wait = CFG.cartWaitMs || 900;

        // کلیک روی دکمهٔ افزودن به سبد خود فروشگاه (هندلرهای ajax قالب فعال می‌شوند)
        var clicked = false;
        if (P.cartBtn) {
            try { P.cartBtn.click(); clicked = true; } catch (e) { /* ادامه */ }
        }
        if (!clicked && P.form) {
            try {
                if (typeof P.form.requestSubmit === 'function') { P.form.requestSubmit(); clicked = true; }
                else { P.form.submit(); clicked = true; }
            } catch (e) { /* ادامه */ }
        }

        setTimeout(function () { window.location.href = url; }, clicked ? wait : 150);
    }

    /* ================================================================
     *  ۶) نقطهٔ ورود کلیک
     * ================================================================ */

    function onQuickBuy(source, cardData) {
        if (CFG.mode === 'cart' && source === 'page') {
            cartModeGo();
            return;
        }

        // حالت سریع (مودال)
        var product;
        if (source === 'card' && cardData) {
            product = {
                id: cardData.id, title: cardData.title, price: cardData.price,
                image: cardData.image, url: cardData.url, variants: ''
            };
        } else {
            product = {
                id: P.id, title: P.title, price: P.price,
                image: P.image, url: location.href.split('#')[0], variants: P.variants
            };
        }

        openModal(source, product);
    }

    /* ================================================================
     *  ۷) بوت
     * ================================================================ */

    function boot() {
        applyTheme();

        // صفحهٔ ادمین هرگز (محافظ دوم در کنار میدل‌ور PHP)
        if (/(^|\/)admin(\/|$)/i.test(location.pathname)) { return; }

        // ★ v1.7.0 — زمان‌بندی نمایش (ساعت کاری): خارج از بازهٔ فعال هیچ
        //   دکمه/آیکونی تزریق نمی‌شود؛ سرور هم سفارش را رد می‌کند (403 + نوت)
        if (CFG.schedule && CFG.schedule.open === false) { return; }

        // صفحهٔ محصول؟
        var isPP = isLikelyProductPage();

        if (isPP) {
            P.jsonld = findJsonLdProduct();
            P.form = findCartForm();
            P.cartBtn = findCartButton(P.form);
            P.id = extractProductId(P.form);
            P.title = extractTitle();
            P.price = extractPrice(P.form);
            P.image = extractImage(P.form);
            P.variants = extractVariants(P.form);

            injectPageButton();

            // دیپ‌لینک ?ct=buy → باز خودکار مودال (مسیر کارت در حالت سبد)
            if (/[?&]ct=buy/.test(location.search)) {
                setTimeout(function () { onQuickBuy('page'); }, 600);
            }
        }

        // آیکون کارت‌ها — در همهٔ صفحات فرانت (صفحهٔ محصول: محصولات مرتبط)
        scanCards();
        watchDom();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
