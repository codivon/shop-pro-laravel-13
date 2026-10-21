/* ============================================================
   AICatalog — دستیار شناور هوشمند (runtime سراسری — بدون ویرایش layout)
   • تولتیپ شناور برای عناصر دارای title یا data-ac-hint
   • ★ دکمهٔ «دستیار هوشمند» همیشه و در «همهٔ» صفحات ادمین ساخته
     می‌شود (محصول، مقاله، صفحه، کامنت و هر بخش دیگری) — بدون نیاز
     به هیچ کدی در آن صفحه.
   • ★ تب «تولید محتوا»: تشخیص خودکار فیلدهای فرم همان صفحه
     (عنوان/خلاصه/توضیح/محتوا/سئو/کلمات کلیدی/پاسخ کامنت) + تولید
     با AI و «درج مستقیم» در همان فیلد (پشتیبانی از CKEditor و
     TinyMCE و ادیتورهای ساده) — برای هر بخش مدیریتی فروشگاه.
   • تب «راهنما»: محتوای اختصاصی div[data-ac-help] یا راهنمای عمومی.
   • تنظیمات از window.AC_HINTS_CFG (تزریق خودکار توسط ماژول):
     { enabled, position, scope, assist, guideUrl, settingsUrl,
       assistUrl, csrf }
   ============================================================ */
(function () {
    'use strict';

    if (window.__AC_HINTS__) { return; }
    window.__AC_HINTS__ = true;

    var CFG = window.AC_HINTS_CFG || {};
    var POSITIONS = ['bottom-left', 'bottom-right', 'bottom-center', 'top-left', 'top-right'];
    var POSITION = POSITIONS.indexOf(String(CFG.position || '')) !== -1 ? CFG.position : 'bottom-left';
    var ASSIST_URL = String(CFG.assistUrl || '') || null;
    var TPL_URL = String(CFG.assistTemplatesUrl || '') || null;   // ★ v1.6.0 — قالب‌های کتابخانه
    var ASSIST_ON = CFG.assist !== false;

    /* ---------- آیکون‌های SVG داخلی (مستقل از نسخهٔ Font Awesome قالب) ---------- */
    var SVG = {
        wand: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 4V2m0 20v-2m5-15.5 1.4-1.4M4.6 19.4 6 18m12.4 1.4 1.4 1.4M2.6 4.6 4 6m9.8 2.2L3 19.6a1.5 1.5 0 0 0 2.1 2.1l10.8-10.8M13 3l1 2 2 1-2 1-1 2-1-2-2-1 2-1 1-2zm7 8 .7 1.3 1.3.7-1.3.7L20 15l-.7-1.3-1.3-.7 1.3-.7L20 11z"/></svg>',
        book: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/></svg>',
        spark: '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l1.9 5.7L19.6 9.6l-5.7 1.9L12 17.2l-1.9-5.7L4.4 9.6l5.7-1.9L12 2zm6.5 10.5l.9 2.6 2.6.9-2.6.9-.9 2.6-.9-2.6-2.6-.9 2.6-.9.9-2.6z"/></svg>',
        check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>'
    };

    function esc(s) {
        return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    /* ================================================================
     *  تولتیپ‌های شناور (بدون تغییر)
     * ================================================================ */
    var tip = null;

    function ensureTip() {
        if (tip) { return tip; }
        tip = document.createElement('div');
        tip.className = 'ac-hint-tip';
        tip.setAttribute('role', 'tooltip');
        document.body.appendChild(tip);
        return tip;
    }

    function contentOf(el) {
        var custom = el.getAttribute('data-ac-hint');
        if (custom && custom.trim()) { return custom.trim(); }

        var title = el.getAttribute('title');
        if (title && title.trim()) {
            el.setAttribute('data-ac-title', title);
            el.removeAttribute('title');
            return title.trim();
        }

        return el.getAttribute('data-ac-title') || '';
    }

    function showTip(el, text) {
        if (!text) { return; }
        var t = ensureTip();
        t.textContent = text;
        t.classList.add('show');
        positionTip(el, t);
    }

    function positionTip(el, t) {
        t.style.visibility = 'hidden';
        t.style.left = '0px';
        t.style.top = '0px';

        var rect = el.getBoundingClientRect();
        var tw = t.offsetWidth, th = t.offsetHeight;
        var vw = window.innerWidth, vh = window.innerHeight;

        var above = rect.top > th + 14;
        var left = rect.left + (rect.width / 2) - (tw * 0.35);
        left = Math.max(8, Math.min(left, vw - tw - 8));

        var top = above ? rect.top - th - 8 : rect.bottom + 8;
        if (top + th > vh - 8) { top = vh - th - 8; }
        if (top < 8) { top = 8; }

        t.style.left = left + 'px';
        t.style.top = top + 'px';
        t.style.visibility = '';
        t.classList.toggle('below', !above);
    }

    function hideTip() {
        if (tip) { tip.classList.remove('show'); }
    }

    document.addEventListener('mouseover', function (e) {
        var el = e.target.closest ? e.target.closest('[data-ac-hint], [title], [data-ac-title]') : null;
        if (!el) { return; }
        showTip(el, contentOf(el));
    });

    document.addEventListener('mouseout', function (e) {
        var el = e.target.closest ? e.target.closest('[data-ac-hint], [title], [data-ac-title]') : null;
        if (el && !(e.relatedTarget && el.contains(e.relatedTarget))) { hideTip(); }
    });

    document.addEventListener('focusin', function (e) {
        var el = e.target.closest ? e.target.closest('[data-ac-hint], [title], [data-ac-title]') : null;
        if (!el) { return; }
        showTip(el, contentOf(el));
    });

    document.addEventListener('focusout', hideTip);
    window.addEventListener('scroll', hideTip, { passive: true });
    window.addEventListener('resize', hideTip, { passive: true });

    /* ================================================================
     *  CSRF — از کانفیگ تزریقی / متا / فرم / کوکی
     * ================================================================ */
    function csrfToken() {
        if (CFG.csrf) { return String(CFG.csrf); }
        var m = document.querySelector('meta[name="csrf-token"], meta[name="_token"]');
        if (m && m.content) { return m.content; }
        var i = document.querySelector('input[name="_token"]');
        if (i && i.value) { return i.value; }
        var mm = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
        if (mm) {
            try { return decodeURIComponent(mm[1]); } catch (e) { return mm[1]; }
        }
        return '';
    }

    function xsrfHeader() {
        // کوکی XSRF-TOKEN لاراول را برای هدر X-XSRF-TOKEN برمی‌گرداند
        var mm = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
        if (mm) {
            try { return decodeURIComponent(mm[1]); } catch (e) { return mm[1]; }
        }
        return null;
    }

    /* ================================================================
     *  تشخیص نوع صفحه و فیلدهای فرم
     * ================================================================ */
    var SKIP_RE = /قیمت|وزن|موجودی|تعداد|کد\s*کالا|sku|slug|لینک|آدرس|ایمیل|تلفن|موبایل|شناسه|تاریخ|ساعت|پسورد|رمز|price|weight|stock|quantity|qty|sku|code|slug|link|url|email|phone|mobile|password|date|time|_token|csrf|max_?file|upload|file\b/i;

    var PATTERNS = [
        { key: 'seo_title',       re: /عنوان\s*سئو|عنوان\s*متا|seo[\s_.-]*title|meta[\s_.-]*title|title[\s_.-]*tag/i },
        { key: 'seo_description', re: /توضیح\s*(ات)?\s*(سئو|متا)|seo[\s_.-]*desc|meta[\s_.-]*desc/i },
        { key: 'keywords',        re: /کلمات?\s*کلیدی|برچسب|کلیدواژه|keywords|meta_keyword|tags\b/i },
        { key: 'reply',           re: /پاسخ|جواب|reply|response/i, textarea: true },
        { key: 'short_description', re: /خلاصه|مقدمه|کوتاه|short[\s_.-]*desc|summary|excerpt|lead|intro/i },
        { key: 'content',         re: /محتوا|متن\s*کامل|content|body|متن\s*مقاله|editor/i, textarea: true },
        { key: 'description',     re: /توضیح|شرح|description|^desc$/i, textarea: true },
        { key: 'title',           re: /عنوان|تیتر|موضوع|subject|^title$|^name$/i }
    ];

    var FIELD_LABELS = {
        title: 'عنوان',
        short_description: 'توضیح کوتاه',
        description: 'توضیحات',
        content: 'محتوا',
        seo_title: 'عنوان سئو',
        seo_description: 'توضیح سئو',
        keywords: 'کلمات کلیدی',
        reply: 'متن پاسخ'
    };

    function detectKind() {
        var p = decodeURIComponent(location.pathname + ' ' + location.search).toLowerCase();
        if (/comment|کامنت|نظر|دیدگاه/.test(p)) { return 'comment'; }
        if (/post|blog|article|مقاله|وبلاگ|بلاگ|news|خبر/.test(p)) { return 'post'; }
        if (/page|صفحه|about|درباره|contact|تماس|terms|قوانین/.test(p)) { return 'page'; }
        if (/product|محصول|catalog|دسته|category/.test(p)) { return 'product'; }
        return 'auto';
    }

    function labelOf(el) {
        // ۱) label[for]
        var id = el.id;
        if (id) {
            var lb = document.querySelector('label[for="' + id.replace(/"/g, '\\"') + '"]');
            if (lb) { return lb.textContent || ''; }
        }
        // ۲) label داخل والد نزدیک (★ v1.5.0: fieldset هم بررسی می‌شود)
        var wrap = el.closest('.form-group, .field, .form-row, .mb-3, .col-md-6, .col-md-12, .col-12, .col-sm-12, .form-line, .form-material, fieldset');
        if (wrap) {
            var wl = wrap.querySelector('label, .control-label, .label, legend');
            if (wl && !wl.contains(el)) { return wl.textContent || ''; }
        }
        // ۳) placeholder / aria-label
        return el.placeholder || el.getAttribute('aria-label') || '';
    }

    function editorOf(el) {
        // CKEditor 4 — نمونه بر اساس id یا name تکست‌اریا
        try {
            if (window.CKEDITOR && window.CKEDITOR.instances) {
                var inst = window.CKEDITOR.instances[el.id] || window.CKEDITOR.instances[el.name];
                if (inst) { return { type: 'ckeditor', inst: inst, id: el.id || el.name }; }
            }
        } catch (e) { /* CKEditor در دسترس نیست */ }
        // TinyMCE
        try {
            if (window.tinymce) {
                var t = (el.id && window.tinymce.get(el.id)) || null;
                if (t) { return { type: 'tinymce', inst: t, id: el.id }; }
            }
        } catch (e) { /* TinyMCE در دسترس نیست */ }
        return null;
    }

    function detectFields(form) {
        var out = {};
        if (!form) { return out; }

        /* ★ v1.5.0 — نشانه‌گذاری دستی: data-ac-field="keywords" بالاترین اولویت */
        Array.prototype.forEach.call(form.querySelectorAll('[data-ac-field]'), function (el) {
            var key = (el.getAttribute('data-ac-field') || '').trim();
            if (!key || el.disabled || el.readOnly) { return; }
            var tag0 = (el.tagName || '').toLowerCase();
            var isTa0 = tag0 === 'textarea' || el.isContentEditable;
            out[key] = {
                el: el, manual: true, hidden: false,
                label: (labelOf(el) || FIELD_LABELS[key] || key).trim().slice(0, 40) || (FIELD_LABELS[key] || key),
                editor: isTa0 ? editorOf(el) : null,
                isTextarea: isTa0
            };
        });

        var els = form.querySelectorAll('input[type=text], input:not([type]), textarea, [contenteditable="true"]');
        Array.prototype.forEach.call(els, function (el) {
            if (el.disabled || el.readOnly) { return; }
            if (el.getAttribute('data-ac-skip')) { return; }   // ★ حذف دستی

            var tag = (el.tagName || '').toLowerCase();
            var sig = [
                el.name || '', el.id || '', el.placeholder || '',
                el.className || '', labelOf(el) || ''
            ].join(' ');

            if (SKIP_RE.test(sig)) { return; }

            /* ★ v1.5.0 — فیلدهای «مخفی» (داخل تب غیرفعال مثل تنظیمات سئو) هم شناسایی می‌شوند؛
               هنگام تکرار کلید، فیلد مرئی اولویت می‌گیرد */
            var hidden = el.offsetParent === null && tag !== 'textarea' && !el.isContentEditable;

            for (var i = 0; i < PATTERNS.length; i++) {
                var p = PATTERNS[i];
                if (p.textarea && tag !== 'textarea' && !el.isContentEditable) { continue; }
                if (p.re.test(sig)) {
                    var existing = out[p.key];
                    if (!existing) {
                        var isTextarea = tag === 'textarea' || el.isContentEditable;
                        out[p.key] = {
                            el: el,
                            hidden: hidden,
                            label: (labelOf(el) || FIELD_LABELS[p.key] || p.key).trim().slice(0, 40) || FIELD_LABELS[p.key],
                            editor: isTextarea ? editorOf(el) : null,
                            isTextarea: isTextarea
                        };
                    } else if (!existing.manual && existing.hidden && !hidden) {
                        var isTa = tag === 'textarea' || el.isContentEditable;
                        existing.el = el;
                        existing.hidden = false;
                        existing.label = (labelOf(el) || FIELD_LABELS[p.key] || p.key).trim().slice(0, 40) || FIELD_LABELS[p.key];
                        existing.editor = isTa ? editorOf(el) : null;
                        existing.isTextarea = isTa;
                    }
                    break;
                }
            }
        });

        return out;
    }

    function pickBestForm() {
        var forms = document.querySelectorAll('form');
        if (!forms.length) { return null; }

        var best = null, bestFields = null, bestScore = -1;
        Array.prototype.forEach.call(forms, function (f) {
            var fields = detectFields(f);
            var score = Object.keys(fields).length;
            if (fields.title) { score += 6; }
            if (fields.description || fields.content) { score += 3; }
            /* ★ v1.5.0 — فرم‌های دارای فیلد سئو برترند (فرم محصول/مقاله/صفحهٔ اپاشاپ) */
            if (fields.seo_title || fields.seo_description) { score += 4; }
            if (fields.keywords) { score += 2; }
            if (f.contains(document.activeElement)) { score += 2; }
            if (score > bestScore) { best = f; bestFields = fields; bestScore = score; }
        });

        if (bestScore <= 0) { return null; }
        return { form: best, fields: bestFields };
    }

    /* ================================================================
     *  ★ v1.5.0 — ویجت تگ‌ها (xoxco + bootstrap-tagsinput) + تب مخفی
     * ================================================================ */

    /** ادیتور را «در لحظهٔ استفاده» دوباره جست‌وجو می‌کند (CKEditor ممکن است بعد از
     *  ساخت پنل ساخته شده باشد) */
    function resolveEditor(f) {
        if (f.editor) { return f.editor; }
        if (f.isTextarea) { f.editor = editorOf(f.el); }
        return f.editor;
    }

    /** تشخیص ویجت تگ‌ها روی فیلد کلمات کلیدی */
    function tagsWidgetOf(el) {
        try {
            var parent = el.parentNode;
            if (!parent) { return null; }
            var w = parent.querySelector('.tagsinput');
            if (w) { return { type: 'xoxco', widget: w, input: el }; }
            if (window.jQuery) {
                var $el = window.jQuery(el);
                var bs = $el.data('bs.tagsinput') || $el.data('tagsinput');
                if (bs && typeof bs.add === 'function') { return { type: 'bootstrap', api: bs }; }
            }
        } catch (e) { /* نادیده */ }
        return null;
    }

    /** افزودن تگ‌ها به ویجت واقعی (کاما فارسی/عربی/انگلیسی جدا) */
    function applyTags(f, text) {
        var w = tagsWidgetOf(f.el);
        if (!w) { return false; }
        try {
            var parts = String(text).split(/[,،،]+/).map(function (s) { return s.trim(); }).filter(Boolean).slice(0, 12);
            if (!parts.length) { return false; }

            if (w.type === 'xoxco') {
                if (window.jQuery && typeof window.jQuery(f.el).addTag === 'function') {
                    parts.forEach(function (p) { window.jQuery(f.el).addTag(p); });
                } else {
                    // fallback: مقدار خام + چیپ‌های ویجت
                    parts.forEach(function (p) { addRawChip(w.widget, p); });
                    f.el.value = parts.join(', ');
                }
            } else if (w.type === 'bootstrap' && w.api) {
                parts.forEach(function (p) { try { w.api.add(p); } catch (e2) { } });
            }
            try {
                f.el.dispatchEvent(new Event('change', { bubbles: true }));
            } catch (e3) { /* نادیده */ }
            return true;
        } catch (e) { return false; }
    }

    /** چیپ خام داخل ویجت xoxco (وقتی addTag در دسترس نیست) */
    function addRawChip(widget, tag) {
        try {
            var chip = document.createElement('span');
            chip.className = 'tag';
            chip.innerHTML = '<span>' + esc(tag) + '&nbsp;&nbsp;</span>';
            var input = widget.querySelector('input, .tagsinput-add input');
            if (input && input.parentNode) {
                widget.insertBefore(chip, input);
            } else {
                widget.appendChild(chip);
            }
        } catch (e) { /* نادیده */ }
    }

    /** اگر فیلد داخل تب غیرفعال باشد، خودکار همان تب فعال می‌شود (Bootstrap 3/4/5) */
    function activateTab(el) {
        try {
            var pane = el.closest('.tab-pane');
            if (!pane || pane.classList.contains('active')) { return; }
            var id = pane.id;
            if (!id) { return; }

            var sel = 'a[href="#' + id + '"], [data-toggle="tab"][href="#' + id + '"], ' +
                '[data-bs-toggle="tab"][href="#' + id + '"], [data-bs-toggle="pill"][href="#' + id + '"], ' +
                'button[data-bs-target="#' + id + '"], button[data-target="#' + id + '"]';
            var link = document.querySelector(sel);
            if (link) {
                link.click();
                showToast('تب «' + (link.textContent || 'مربوطه').trim().slice(0, 30) + '» فعال شد', 'ok');
            }
        } catch (e) { /* نادیده */ }
    }

    /** خواندن متن فعلی فیلد (برای بازنویسی/زمینه) — resolve تنبل ادیتور */
    function readText(f) {
        try {
            var ed = resolveEditor(f);
            if (ed && ed.type === 'ckeditor' && ed.inst) { return ed.inst.getData() || ''; }
            if (ed && ed.type === 'tinymce' && ed.inst) { return ed.inst.getContent() || ''; }
            if (f.el.isContentEditable) { return f.el.innerHTML || ''; }
            return f.el.value || '';
        } catch (e) { return ''; }
    }

    /** ★ v1.5.0 — زمینهٔ هوشمند: مقادیر فعلی فرم به payload اضافه می‌شود */
    function collectContext() {
        if (!detected || !detected.fields) { return ''; }
        var lines = [];
        var f = detected.fields;
        ['title', 'seo_title', 'seo_description', 'keywords', 'short_description'].forEach(function (k) {
            if (f[k]) {
                var v = readText(f[k]).trim();
                if (v) { lines.push((FIELD_LABELS[k] || k) + ': ' + v.slice(0, 160)); }
            }
        });
        // متن دسته/برند انتخابی
        try {
            var sel = document.querySelector('select[name*=category i], select#category, select[name*=brand i], select[name*=postcat i]');
            if (sel && sel.selectedOptions[0]) {
                var v2 = sel.selectedOptions[0].textContent.trim();
                if (v2) { lines.push('انتخاب فعلی (' + (sel.name || sel.id) + '): ' + v2.slice(0, 90)); }
            }
        } catch (e) { /* نادیده */ }
        return lines.slice(0, 8).join('\n').slice(0, 900);
    }

    /* ================================================================
     *  درج متن در فیلد (+ ادیتورها + ویجت تگ‌ها) با افکت
     * ================================================================ */
    function applyText(f, text) {
        var el = f.el;

        // ★ v1.5.0 — فعال‌سازی خودکار تب (اگر فیلد در تب دیگری باشد)
        activateTab(el);

        // ★ فیلد کلمات کلیدی با ویجت تگ‌ها؟ → تگ واقعی اضافه کن
        var key = fieldKeyOf(f);
        if (key === 'keywords' && applyTags(f, text)) {
            flash(f);
            return;
        }

        try {
            var ed = resolveEditor(f);   // ★ resolve تنبل — ادیتورهای دیرساز
            if (ed && ed.type === 'ckeditor' && ed.inst) {
                ed.inst.setData(text);
                if (ed.inst.updateElement) { ed.inst.updateElement(); }
            } else if (ed && ed.type === 'tinymce' && ed.inst) {
                ed.inst.setContent(text);
                if (ed.inst.save) { ed.inst.save(); }
            } else if (el.isContentEditable) {
                el.innerHTML = text;
            } else {
                el.value = text;
                try {
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                } catch (e) { /* مرورگر قدیمی */ }
            }
        } catch (e) {
            try { el.value = text; } catch (e2) { /* نادیده */ }
        }

        flash(f);
    }

    function fieldKeyOf(f) {
        if (!detected || !detected.fields) { return ''; }
        for (var k in detected.fields) {
            if (detected.fields[k] === f) { return k; }
        }
        return '';
    }

    function flash(f) {
        var target = f.el;
        try {
            var ed = resolveEditor(f);
            if (ed && ed.type === 'ckeditor') {
                var frame = ed.inst.container ? ed.inst.container.$ : null;
                var area = frame ? frame.querySelector('.cke_wysiwyg_frame, .cke_contents') : null;
                if (area) { target = area; }
            }
        } catch (e) { /* نادیده */ }
        try {
            target.classList.add('ac-fill-flash');
            setTimeout(function () { target.classList.remove('ac-fill-flash'); }, 2200);
            target.scrollIntoView({ block: 'center', behavior: 'smooth' });
        } catch (e) { /* نادیده */ }
    }

    /* ================================================================
     *  فراخوانی سرور
     * ================================================================ */
    function assistCall(payload, onDone, onErr) {
        if (!ASSIST_URL) {
            onErr('آدرس سرویس تولید در این صفحه در دسترس نیست — یک‌بار صفحه را با Ctrl+F5 رفرش کنید و اگر ادامه داد php artisan optimize:clear را اجرا کنید.');
            return;
        }

        var headers = { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
        var tk = csrfToken();
        if (tk) { headers['X-CSRF-TOKEN'] = tk; }
        var xs = xsrfHeader();
        if (xs) { headers['X-XSRF-TOKEN'] = xs; }

        fetch(ASSIST_URL, { method: 'POST', headers: headers, credentials: 'same-origin', body: JSON.stringify(payload) })
            .then(function (r) {
                if (r.status === 403) { throw new Error('دسترسی مجاز نیست — لایسنس ماژول فعال نیست یا نشست شما منقضی شده (یک‌بار خارج و دوباره وارد شوید).'); }
                if (r.status === 404) { throw new Error('سرویس پیدا نشد — روت‌ها کش شده‌اند: در سرور php artisan optimize:clear را اجرا کنید.'); }
                if (r.status === 429) { throw new Error('سهمیهٔ تولید این ساعت تکمیل شد — کمی بعد دوباره تلاش کنید.'); }
                return r.json();
            })
            .then(function (d) {
                if (d && d.success) { onDone(d); }
                else { onErr((d && d.message) || 'پاسخ نامعتبر از سرور.'); }
            })
            .catch(function (e) {
                onErr(e && e.message ? e.message : 'خطا در ارتباط با سرور — اتصال اینترنت و تنظیمات پرووایدر را بررسی کنید.');
            });
    }

    /* ================================================================
     *  ★ v1.5.0/v1.6.0 — تاریخچه (localStorage) + قالب‌های کتابخانه
     * ================================================================ */
    var HIST_KEY = 'ac_assist_history';
    var HIST_MAX = 25;

    function loadHistory() {
        try { return JSON.parse(localStorage.getItem(HIST_KEY) || '[]'); }
        catch (e) { return []; }
    }

    function saveHistory(h) {
        try { localStorage.setItem(HIST_KEY, JSON.stringify(h)); } catch (e) { /* نادیده */ }
    }

    function pushHistory(fieldKey, label, text) {
        try {
            var h = loadHistory();
            h.unshift({
                t: Date.now(),
                field: fieldKey,
                label: String(label || '').slice(0, 40),
                text: String(text || '').slice(0, 4000)
            });
            saveHistory(h.slice(0, HIST_MAX));
        } catch (e) { /* نادیده */ }
    }

    function timeAgo(ts) {
        var s = Math.max(0, Math.floor((Date.now() - ts) / 1000));
        if (s < 45) { return 'همین حالا'; }
        if (s < 90) { return '۱ دقیقه پیش'; }
        var m = Math.floor(s / 60);
        if (m < 60) { return m.toLocaleString('fa-IR') + ' دقیقه پیش'; }
        var h = Math.floor(m / 60);
        if (h < 24) { return h.toLocaleString('fa-IR') + ' ساعت پیش'; }
        return Math.floor(h / 24).toLocaleString('fa-IR') + ' روز پیش';
    }

    /* قالب‌های کتابخانهٔ ماژول (فقط یک‌بار واکشی می‌شود) */
    var TPLS = null;

    function loadTemplates() {
        if (TPLS !== null || !TPL_URL) { return; }
        var kind = pageKind === 'comment' ? 'comment' : (pageKind === 'auto' ? 'generic' : pageKind);
        fetch(TPL_URL + '?kind=' + encodeURIComponent(kind), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (d) {
            if (!d || !d.success) { TPLS = []; return; }
            TPLS = d.items || [];
            renderTemplatesSelect();
        }).catch(function () { TPLS = []; });
    }

    function renderTemplatesSelect() {
        var sel = document.getElementById('ac-tpl');
        if (!sel || !TPLS || !TPLS.length) { return; }
        var opts = '<option value="">— بدون قالب —</option>';
        TPLS.forEach(function (t) {
            var name = (t.is_generic && pageKind !== 'generic' ? 'عمومی — ' : '') + t.name;
            opts += '<option value="' + t.id + '">' + esc(name) + '</option>';
        });
        sel.innerHTML = opts;
    }

    /** پرامپت قالب انتخاب‌شده با جایگذاری {name}/{title} → topic */
    function selectedTemplatePrompt() {
        var sel = document.getElementById('ac-tpl');
        if (!sel || !sel.value || !TPLS) { return ''; }
        var t = null;
        for (var i = 0; i < TPLS.length; i++) {
            if (String(TPLS[i].id) === String(sel.value)) { t = TPLS[i]; break; }
        }
        if (!t) { return ''; }
        var topic = (document.getElementById('ac-topic') || {}).value || pageTopic() || '';
        var p = String(t.prompt || '')
            .replace(/\{name\}/g, topic)
            .replace(/\{title\}/g, topic);
        return p.replace(/\{[a-z_]+\}/gi, '').trim().slice(0, 3500);
    }

    /* ================================================================
     *  پنل دستیار هوشمند (چهارتبی)
     * ================================================================ */
    var fab = null, panel = null;
    var detected = null;   // { form, fields }
    var pageKind = detectKind();
    var lastGen = null;    // { fieldKey, topic, context } برای «تولید مجدد»

    function pageTopic() {
        // عنوان موضوع: فیلد عنوانِ شناسایی‌شده یا h1 صفحه
        if (detected && detected.fields.title && detected.fields.title.el.value) {
            return detected.fields.title.el.value.trim();
        }
        var h1 = document.querySelector('.content-wrapper h1, main h1, h1');
        if (h1) { return (h1.textContent || '').trim().slice(0, 150); }
        return '';
    }

    function initAssistant() {
        if (CFG.enabled === false) { return; }

        detected = pickBestForm();

        /* ---------- ساخت دکمهٔ شناور ---------- */
        fab = document.createElement('button');
        fab.type = 'button';
        fab.className = 'ac-help-fab pos-' + POSITION;
        fab.innerHTML = SVG.wand;
        fab.setAttribute('aria-label', 'دستیار هوشمند تولید محتوا');
        document.body.appendChild(fab);

        /* ---------- ساخت پنل چهارتبی ---------- */
        panel = document.createElement('div');
        panel.className = 'ac-help-panel pos-' + POSITION;
        panel.setAttribute('role', 'complementary');
        panel.innerHTML =
            '<div class="ac-help-head">' +
            '<span class="ac-help-logo">' + SVG.spark + '</span>' +
            '<b>دستیار هوشمند</b>' +
            '<button type="button" class="ac-help-close" aria-label="بستن">&times;</button>' +
            '</div>' +
            '<div class="ac-tabs">' +
            '<button type="button" class="ac-tab active" data-tab="gen"><i>' + SVG.wand + '</i> تولید</button>' +
            '<button type="button" class="ac-tab" data-tab="seo"><i>' + SVG.check + '</i> سئوچک</button>' +
            '<button type="button" class="ac-tab" data-tab="history"><i>·</i> تاریخچه</button>' +
            '<button type="button" class="ac-tab" data-tab="help"><i>' + SVG.book + '</i> راهنما</button>' +
            '</div>' +
            '<div class="ac-help-body">' +
            '<div class="ac-tabpane active" id="ac-pane-gen">' + genPaneHtml() + '</div>' +
            '<div class="ac-tabpane" id="ac-pane-seo">' + seoPaneHtml() + '</div>' +
            '<div class="ac-tabpane" id="ac-pane-history">' + historyPaneHtml() + '</div>' +
            '<div class="ac-tabpane" id="ac-pane-help">' + helpPaneHtml() + '</div>' +
            '</div>';
        document.body.appendChild(panel);

        bindPanel();
        loadTemplates();   // ★ v1.6.0 — واکشی قالب‌های مرتبط با نوع صفحه
    }

    /* ---------- HTML تب تولید ---------- */
    function genPaneHtml() {
        if (!ASSIST_ON) {
            return '<div class="ac-note">تب تولید در تنظیمات ماژول غیرفعال شده است.</div>';
        }

        var fields = detected ? detected.fields : null;
        var hasFields = fields && Object.keys(fields).length > 0;

        var html = '';

        // حالت کامنت: جعبهٔ متن کامنت + پاسخ هوشمند
        if (pageKind === 'comment') {
            html +=
                '<div class="ac-field-row">' +
                '<label class="ac-lbl">متن کامنت (برای نوشتن پاسخ):</label>' +
                '<textarea id="ac-comment-text" rows="3" placeholder="متن کامنت کاربر را بنویسید یا بچسبانید…"></textarea>' +
                '</div>' +
                '<button type="button" class="ac-gen-btn" id="ac-reply-btn">' + SVG.wand + ' پاسخ هوشمند به کامنت</button>' +
                '<div class="ac-result" id="ac-result" hidden></div>';
            return html;
        }

        if (!ASSIST_URL) {
            html += '<div class="ac-note warn">سرویس تولید در دسترس نیست — php artisan optimize:clear را اجرا و صفحه را رفرش کنید.</div>';
        }

        // موضوع
        html +=
            '<div class="ac-field-row">' +
            '<label class="ac-lbl">موضوع / عنوان:</label>' +
            '<input type="text" id="ac-topic" value="' + esc(pageTopic()) + '" placeholder="مثلاً: گوشی سامسونگ Galaxy A55">' +
            '</div>';

        // نوع محتوا + لحن + طول
        html += '<div class="ac-trio">';
        html += '<div><label class="ac-lbl">نوع</label><select id="ac-kind">' +
            '<option value="auto"' + (pageKind === 'auto' ? ' selected' : '') + '>خودکار</option>' +
            '<option value="product"' + (pageKind === 'product' ? ' selected' : '') + '>محصول</option>' +
            '<option value="post"' + (pageKind === 'post' ? ' selected' : '') + '>مقاله</option>' +
            '<option value="page"' + (pageKind === 'page' ? ' selected' : '') + '>صفحه</option>' +
            '<option value="generic">عمومی</option>' +
            '</select></div>';
        html += '<div><label class="ac-lbl">لحن (۷)</label><select id="ac-tone">' +
            '<option value="professional">حرفه‌ای</option>' +
            '<option value="friendly">صمیمی</option>' +
            '<option value="technical">فنی</option>' +
            '<option value="luxury">لوکس</option>' +
            '<option value="persuasive">فروش‌محور</option>' +
            '<option value="educational">آموزشی</option>' +
            '<option value="humorous">طنزآمیز</option>' +
            '</select></div>';
        html += '<div><label class="ac-lbl">طول</label><select id="ac-length">' +
            '<option value="short">کوتاه</option>' +
            '<option value="medium" selected>متوسط</option>' +
            '<option value="long">بلند</option>' +
            '</select></div>';
        html += '</div>';

        // ★ v1.6.0 — قالب کتابخانه + حالت بازنویسی
        html +=
            '<div class="ac-field-row" style="margin-top:9px;">' +
            '<label class="ac-lbl">قالب از کتابخانهٔ ماژول:</label>' +
            '<select id="ac-tpl"><option value="">در حال بارگذاری قالب‌ها…</option></select>' +
            '</div>' +
            '<label class="ac-rewrite-row" title="متن فعلی فیلد بهبود داده می‌شود (نه از صفر)">' +
            '<input type="checkbox" id="ac-rewrite">' +
            '<span>بازنویسی هوشمند — متن فعلی همان فیلد بهبود داده شود</span>' +
            '</label>';

        // فیلدهای شناسایی‌شده
        if (hasFields) {
            html += '<div class="ac-lbl" style="margin-top:10px;">فیلدهای شناسایی‌شده در این صفحه:</div>';
            html += '<div class="ac-fields-list">';
            Object.keys(fields).forEach(function (key) {
                var f = fields[key];
                var mark = '';
                if (f.hidden) { mark = ' <em>(در تب دیگر)</em>'; }
                else if (key === 'keywords') {
                    var w = tagsWidgetOf(f.el);
                    if (w) { mark = ' <em>(تگ‌ها)</em>'; }
                }
                html +=
                    '<div class="ac-field-chip" data-field="' + key + '">' +
                    '<span class="ac-chip-lbl">' + esc(f.label || FIELD_LABELS[key] || key) + (f.editor || mark.indexOf('ادیتور') !== -1 ? ' <em>(ادیتور)</em>' : '') + mark + '</span>' +
                    '<button type="button" class="ac-chip-btn" data-gen="' + key + '">' + SVG.wand + '</button>' +
                    '</div>';
            });
            html += '</div>';
            html += '<button type="button" class="ac-gen-btn wide" id="ac-gen-all">' + SVG.wand + ' تولید همهٔ فیلدها</button>';
        } else {
            html +=
                '<div class="ac-note">در این صفحه فیلد قابل تشخیصی پیدا نشد — صفحهٔ ویرایش (فرم) را باز کنید؛ ' +
                'یا از حالت «متن آزاد» زیر استفاده کنید.</div>' +
                '<div class="ac-field-row">' +
                '<label class="ac-lbl">چه متنی می‌خواهید؟ (متن آزاد):</label>' +
                '<textarea id="ac-custom-ask" rows="2" placeholder="مثلاً: یک شعار تبلیغاتی برای صفحهٔ اصلی"></textarea>' +
                '</div>' +
                '<button type="button" class="ac-gen-btn wide" id="ac-custom-btn">' + SVG.wand + ' تولید متن</button>';
        }

        // ناحیهٔ نتیجه
        html += '<div class="ac-result" id="ac-result" hidden></div>';

        return html;
    }

    /* ---------- ★ v1.5.0 — HTML تب سئوچک ---------- */
    function seoPaneHtml() {
        if (!detected || !detected.fields || !Object.keys(detected.fields).length) {
            return '<div class="ac-note">در این صفحه فرمی برای تحلیل سئو پیدا نشد — صفحهٔ ویرایش محصول/مقاله/صفحه را باز کنید.</div>';
        }
        return '<div class="ac-seo-score-wrap" id="ac-seo-score-wrap">' +
            '<div class="ac-seo-ring" id="ac-seo-ring"><svg viewBox="0 0 44 44"><circle class="bg" cx="22" cy="22" r="19"></circle>' +
            '<circle class="fg" id="ac-seo-arc" cx="22" cy="22" r="19"></circle></svg>' +
            '<b id="ac-seo-score">—</b></div>' +
            '<div><b style="display:block; margin-bottom:2px;">امتیاز سئوی این فرم</b>' +
            '<small style="color:var(--ac-muted);">بر اساس ۱۰ بررسی زندهٔ عنوان/متاها/تگ‌ها/حجم/چگالی کلیدواژه/Alt تصاویر</small></div>' +
            '</div>' +
            '<div class="ac-seo-checks" id="ac-seo-checks"></div>' +
            '<button type="button" class="ac-gen-btn wide" id="ac-seo-recheck">بازبینی مجدد</button>';
    }

    /* ---------- ★ v1.5.0 — HTML تب تاریخچه ---------- */
    function historyPaneHtml() {
        return '<div class="ac-hist-list" id="ac-hist-list"></div>' +
            '<button type="button" class="ac-gen-btn wide danger" id="ac-hist-clear" style="margin-top:9px;">پاک‌کردن همهٔ تاریخچه</button>';
    }

    /* ---------- HTML تب راهنما ---------- */
    function helpPaneHtml() {
        var src = document.querySelector('[data-ac-help]');
        var title, bodyHtml;

        if (src) {
            title = (src.getAttribute('data-ac-help') || 'راهنمای این صفحه').trim() || 'راهنمای این صفحه';
            var tmp = document.createElement('div');
            while (src.firstChild) { tmp.appendChild(src.firstChild); }
            bodyHtml = tmp.innerHTML;
            if (src.parentNode) { src.parentNode.removeChild(src); }
        } else {
            title = 'راهنمای دستیار';
            bodyHtml =
                '<h6>تولید محتوا روی هر فرم</h6>' +
                '<p>دکمهٔ شناور در «همهٔ» صفحات مدیریت ظاهر می‌شود. در صفحات ویرایش محصول، مقاله، صفحه و کامنت، فیلدهای فرم خودکار تشخیص داده می‌شوند و با یک کلیک، متن هوشمند در همان فیلد درج می‌گردد — سپس با دکمهٔ ذخیرهٔ خود فروشگاه ثبت شود.</p>' +
                '<h6>تولتیپ‌های شناور</h6>' +
                '<p>موس را روی عناصر دارای راهنما نگه دارید (یا با کیبورد روی آن‌ها بروید) تا توضیح شناور نمایش داده شود.</p>' +
                '<h6>موتور کاتالوگ محصولات</h6>' +
                '<p>برای تولید گروهی + گردش کار تأیید روی «محصولات»، از بخش «موتور محتوای هوشمند» در منو استفاده کنید: کاتالوگ ← انتخاب ← تولید ← بررسی ← تأیید.</p>' +
                (CFG.guideUrl
                    ? '<a href="' + esc(CFG.guideUrl) + '" class="ac-help-link">' + SVG.book + ' راهنمای کامل استفاده</a>'
                    : '') +
                (CFG.settingsUrl
                    ? '<a href="' + esc(CFG.settingsUrl) + '" class="ac-help-link">' + SVG.wand + ' تنظیمات دستیار شناور (موقعیت و نمایش)</a>'
                    : '');
        }

        return bodyHtml;
    }

    /* ---------- رفتار پنل ---------- */
    function bindPanel() {
        function close() {
            panel.classList.remove('open');
            fab.classList.remove('active');
        }
        function toggle() {
            var willOpen = !panel.classList.contains('open');
            panel.classList.toggle('open', willOpen);
            fab.classList.toggle('active', willOpen);
            if (willOpen) {
                // بازخوانی موضوع اگر کاربر عنوان را در فرم پر کرده
                var t = document.getElementById('ac-topic');
                if (t && !t.value.trim()) { t.value = pageTopic(); }
            }
        }

        fab.addEventListener('click', function (e) { e.stopPropagation(); toggle(); });
        panel.querySelector('.ac-help-close').addEventListener('click', close);
        document.addEventListener('click', function (e) {
            if (panel.classList.contains('open') && !panel.contains(e.target) && !fab.contains(e.target)) { close(); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { close(); }
        });

        // تب‌ها (چهارتبی)
        panel.querySelectorAll('.ac-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                panel.querySelectorAll('.ac-tab').forEach(function (t2) { t2.classList.remove('active'); });
                tab.classList.add('active');
                var target = tab.getAttribute('data-tab');
                ['gen', 'seo', 'history', 'help'].forEach(function (id) {
                    var pane = document.getElementById('ac-pane-' + id);
                    if (pane) { pane.classList.toggle('active', id === target); }
                });
                if (target === 'seo') { runSeoCheck(); }
                if (target === 'history') { renderHistory(); }
            });
        });

        // ★ بازنویسی: فقط برای تولید تکی معنا دارد — در حالت کامنت خاموش بماند
        var rw = document.getElementById('ac-rewrite');
        if (rw) {
            rw.addEventListener('change', function () {
                if (this.checked && pageKind === 'comment') {
                    this.checked = false;
                    showToast('حالت بازنویسی برای پاسخ کامنت لازم نیست — مستقیم تولید کنید.', 'warn');
                }
            });
        }

        if (!ASSIST_ON) { return; }

        // تولید یک فیلد
        panel.querySelectorAll('[data-gen]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key = btn.getAttribute('data-gen');
                generateField(key);
            });
        });

        // تولید همه
        var allBtn = document.getElementById('ac-gen-all');
        if (allBtn) {
            allBtn.addEventListener('click', function () { generateAll(); });
        }

        // متن آزاد
        var cBtn = document.getElementById('ac-custom-btn');
        if (cBtn) {
            cBtn.addEventListener('click', function () {
                var ask = (document.getElementById('ac-custom-ask').value || '').trim();
                if (!ask) { showToast('ابتدا متن درخواست را بنویسید.', 'warn'); return; }
                callAssistant('custom', ask, null, function (text) {
                    showResult(text, null, 'متن تولیدشده (کپی کنید):');
                }, btnBusy(cBtn));
            });
        }

        // پاسخ کامنت — ★ v1.6.0: با قالب کتابخانه هم کار می‌کند
        var rBtn = document.getElementById('ac-reply-btn');
        if (rBtn) {
            rBtn.addEventListener('click', function () {
                var txt = (document.getElementById('ac-comment-text').value || '').trim();
                if (!txt) { showToast('متن کامنت را بنویسید یا بچسبانید.', 'warn'); return; }
                var replyField = detected && detected.fields.reply ? detected.fields.reply : null;
                callAssistant('reply', null, txt, function (text) {
                    if (replyField) {
                        applyText(replyField, text);
                        showResult(text, replyField, 'درج شد در فیلد پاسخ ✓');
                    } else {
                        showResult(text, null, 'پاسخ تولیدشده (کپی کنید):');
                    }
                }, btnBusy(rBtn));
            });
        }

        // ★ سئوچک: بازبینی مجدد
        var reBtn = document.getElementById('ac-seo-recheck');
        if (reBtn) { reBtn.addEventListener('click', runSeoCheck); }

        // ★ تاریخچه: پاک‌کردن
        var hc = document.getElementById('ac-hist-clear');
        if (hc) {
            hc.addEventListener('click', function () {
                saveHistory([]);
                renderHistory();
                showToast('تاریخچه پاک شد ✓', 'ok');
            });
        }
    }

    function btnBusy(btn) {
        return {
            on: function () { btn.disabled = true; btn.classList.add('busy'); },
            off: function () { btn.disabled = false; btn.classList.remove('busy'); }
        };
    }

    function payloadFor(fieldKey, topic, context) {
        var kindSel = document.getElementById('ac-kind');
        var toneSel = document.getElementById('ac-tone');
        var lenSel = document.getElementById('ac-length');
        var rwChk = document.getElementById('ac-rewrite');

        var payload = {
            kind: kindSel ? kindSel.value : (pageKind === 'comment' ? 'comment' : 'auto'),
            field: fieldKey,
            topic: topic || '',
            context: context || collectContext(),   // ★ v1.5.0 — زمینهٔ هوشمند
            tone: toneSel ? toneSel.value : 'professional',
            length: lenSel ? lenSel.value : 'medium',
            template_prompt: selectedTemplatePrompt()   // ★ v1.6.0 — قالب کتابخانه
        };

        // ★ v1.6.0 — حالت بازنویسی: متن فعلی همان فیلد منبع است
        if (rwChk && rwChk.checked && detected && detected.fields[fieldKey]) {
            var src = readText(detected.fields[fieldKey]);
            if (src && src.trim().length >= 10) {
                payload.mode = 'rewrite';
                payload.source = src.slice(0, 8000);
            }
        }

        return payload;
    }

    function callAssistant(fieldKey, topic, context, onText, busy) {
        if (busy) { busy.on(); }
        var payload = payloadFor(fieldKey, topic, context);
        assistCall(payload, function (d) {
            if (busy) { busy.off(); }
            onText(String(d.text || ''));
        }, function (msg) {
            if (busy) { busy.off(); }
            showToast(msg, 'err');
        });
    }

    function generateField(key) {
        var f = detected && detected.fields[key];
        if (!f) { return; }

        var topic = (document.getElementById('ac-topic').value || pageTopic() || (f.el.value || '')).trim();
        if (!topic) { showToast('ابتدا «موضوع/عنوان» را بنویسید.', 'warn'); return; }

        // ★ v1.6.0 — بازنویسی: اگر متن فعلی کوتاه است خطا بده
        var rw = document.getElementById('ac-rewrite');
        if (rw && rw.checked) {
            var src = readText(f);
            if (!src || src.trim().length < 10) {
                showToast('برای بازنویسی، فیلد باید متن فعلی داشته باشد (حداقل ۱۰ کاراکتر).', 'warn');
                return;
            }
        }

        var btn = panel.querySelector('[data-gen="' + key + '"]');
        lastGen = { fieldKey: key, topic: topic, rewrite: !!(rw && rw.checked) };

        callAssistant(key, topic, null, function (text) {
            applyText(f, text);
            pushHistory(key, f.label || FIELD_LABELS[key], text);   // ★ v1.5.0
            showResult(text, f, 'درج شد در «' + (f.label || FIELD_LABELS[key]) + '» ✓');
            runSeoCheck();   // ★ رندر مجدد سئوچک بعد از تولید
        }, btnBusy(btn));
    }

    function generateAll() {
        if (!detected) { return; }
        var keys = Object.keys(detected.fields);
        if (!keys.length) { return; }

        var allBtn = document.getElementById('ac-gen-all');
        var busy = btnBusy(allBtn);
        busy.on();
        showToast('تولید ' + keys.length + ' فیلد آغاز شد…');

        var i = 0;
        function next() {
            if (i >= keys.length) {
                busy.off();
                showToast('همهٔ فیلدها تولید و درج شد ✓', 'ok');
                return;
            }
            var key = keys[i++];
            var f = detected.fields[key];
            var topic = (document.getElementById('ac-topic').value || pageTopic() || '').trim();
            if (!topic) { busy.off(); showToast('ابتدا «موضوع/عنوان» را بنویسید.', 'warn'); return; }

            var chipBtn = panel.querySelector('[data-gen="' + key + '"]');
            if (chipBtn) { chipBtn.classList.add('busy'); }

            assistCall(payloadFor(key, topic, null), function (d) {
                if (chipBtn) { chipBtn.classList.remove('busy'); chipBtn.classList.add('done'); }
                applyText(f, String(d.text || ''));
                showResult(String(d.text || ''), f, 'درج شد در «' + (f.label || FIELD_LABELS[key]) + '» ✓');
                next();
            }, function (msg) {
                if (chipBtn) { chipBtn.classList.remove('busy'); }
                showToast(msg, 'err');
                next();
            });
        }
        next();
    }

    /* ---------- ★ v1.5.0/v1.7.1 — سئوچک: ۱۰ بررسی زنده + امتیاز ---------- */
    function runSeoCheck() {
        var box = document.getElementById('ac-seo-checks');
        var scoreEl = document.getElementById('ac-seo-score');
        var arcEl = document.getElementById('ac-seo-arc');
        var wrapEl = document.getElementById('ac-seo-score-wrap');
        if (!box || !detected || !detected.fields) {
            if (wrapEl) { wrapEl.style.display = 'none'; }
            return;
        }

        var f = detected.fields;
        var val = function (k) { return f[k] ? readText(f[k]).trim() : ''; };
        var len = function (k) { return (val(k).replace(/<[^>]*>/g, '')).length; };
        var words = function (k) { var t = val(k).replace(/<[^>]*>/g, ' '); return t ? t.split(/\s+/).filter(Boolean).length : 0; };

        var title = val('title') || pageTopic();
        var kw = val('keywords');
        var firstKw = kw.split(/[,،،]/).map(function (s) { return s.trim(); }).filter(Boolean)[0] || '';

        var kind = pageKind === 'auto' ? 'generic' : pageKind;
        var minContent = kind === 'post' ? 250 : (kind === 'page' ? 150 : 80);

        var checks = [
            { ok: title.length >= 5 && title.length <= 70, w: 10, label: 'طول عنوان (' + title.length + ' کاراکتر — مناسب ۵ تا ۷۰)', fix: null },
            { ok: len('seo_title') >= 10 && len('seo_title') <= 60, w: 14, label: 'عنوان سئو (' + len('seo_title') + ' از ۶۰)', fix: 'seo_title' },
            { ok: len('seo_description') >= 30 && len('seo_description') <= 160, w: 14, label: 'توضیحات سئو (' + len('seo_description') + ' از ۱۶۰)', fix: 'seo_description' },
            { ok: kw.split(/[,،،]/).filter(function (s) { return s.trim(); }).length >= 3, w: 10, label: 'تعداد تگ/کلمهٔ کلیدی (حداقل ۳)', fix: 'keywords' },
            { ok: !!firstKw && title.indexOf(firstKw) !== -1, w: 10, label: firstKw ? 'کلمهٔ کلیدی اول در عنوان («' + firstKw.slice(0, 22) + '»)' : 'کلمهٔ کلیدی اول در عنوان (تگی ثبت نشده)', fix: 'keywords' },
            { ok: !!firstKw && val('seo_description').indexOf(firstKw) !== -1, w: 9, label: 'کلمهٔ کلیدی اول در توضیحات سئو', fix: null },
            { ok: len('short_description') >= 50, w: 9, label: 'توضیح کوتاه/خلاصه (حداقل ۵۰ کاراکتر)', fix: 'short_description' },
            { ok: (words('description') + words('content')) >= minContent, w: 11, label: 'حجم محتوا (حداقل ' + minContent + ' کلمه — فعلی: ' + (words('description') + words('content')) + ')', fix: (f.content ? 'content' : 'description') },
            /* ★ v1.7.1 — چگالی کلمهٔ کلیدی اول در متن اصلی */
            (function () {
                var bodyText = (val('description') + ' ' + val('content')).replace(/<[^>]*>/g, ' ');
                var cnt = 0;
                if (firstKw && bodyText) {
                    try {
                        var rx = new RegExp(firstKw.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g');
                        cnt = (bodyText.match(rx) || []).length;
                    } catch (err) { cnt = 0; }
                }
                return { ok: cnt >= 2, w: 7, label: firstKw ? 'چگالی کلمهٔ کلیدی اول در متن (' + cnt.toLocaleString('fa-IR') + ' بار — حداقل ۲)' : 'چگالی کلیدواژه (تگی ثبت نشده)', fix: (f.content ? 'content' : 'description') };
            })(),
            /* ★ v1.7.1 — تصاویر بدون متن Alt */
            (function () {
                var html = (val('description') + ' ' + val('content'));
                var imgs = html.match(/<img\b[^>]*>/gi) || [];
                var noAlt = imgs.filter(function (tag) { return !/\balt\s*=\s*("[^"]+"|'[^']+'|[^\s>]+)/i.test(tag); }).length;
                return imgs.length
                    ? { ok: noAlt === 0, w: 6, label: noAlt > 0 ? 'تصاویر بدون Alt (' + noAlt.toLocaleString('fa-IR') + ' از ' + imgs.length.toLocaleString('fa-IR') + ')' : 'همهٔ تصاویر Alt دارند (' + imgs.length.toLocaleString('fa-IR') + ' تصویر)', fix: null }
                    : { ok: true, w: 6, label: 'تصویری برای بررسی Alt موجود نیست', fix: null };
            })()
        ];

        var totalW = 0, gotW = 0;
        checks.forEach(function (c) { totalW += c.w; if (c.ok) { gotW += c.w; } });
        var score = Math.round((gotW / totalW) * 100);

        if (scoreEl) { scoreEl.textContent = score.toLocaleString('fa-IR'); }
        if (arcEl) {
            var circ = 2 * Math.PI * 19;
            arcEl.style.strokeDasharray = String(circ);
            arcEl.style.strokeDashoffset = String(circ * (1 - score / 100));
        }
        if (wrapEl) {
            wrapEl.classList.remove('ok', 'warn', 'bad');
            wrapEl.classList.add(score >= 75 ? 'ok' : (score >= 45 ? 'warn' : 'bad'));
        }

        box.innerHTML = checks.map(function (c, i) {
            var cls = c.ok ? 'ok' : 'bad';
            var fixBtn = (!c.ok && c.fix && f[c.fix])
                ? '<button type="button" class="ac-seo-fix" data-fix="' + c.fix + '">رفع با AI</button>'
                : '';
            return '<div class="ac-seo-check ' + cls + '">' +
                '<span class="ic">' + (c.ok ? '✓' : '✕') + '</span>' +
                '<span class="lbl">' + esc(c.label) + '</span>' + fixBtn +
                '</div>';
        }).join('');

        // دکمهٔ «رفع با AI» → همان فیلد تولید می‌شود
        box.querySelectorAll('.ac-seo-fix').forEach(function (b) {
            b.addEventListener('click', function () {
                generateField(this.getAttribute('data-fix'));
            });
        });
    }

    /* ---------- ★ v1.5.0 — تاریخچه: رندر + اکشن‌ها ---------- */
    function renderHistory() {
        var list = document.getElementById('ac-hist-list');
        if (!list) { return; }

        var h = loadHistory();
        if (!h.length) {
            list.innerHTML = '<div class="ac-note">هنوز تولیدی ثبت نشده — هر تولیدی که با این دستیار انجام شود اینجا ذخیره می‌شود (۲۵ مورد آخر، فقط در همین مرورگر).</div>';
            return;
        }

        list.innerHTML = h.map(function (item, i) {
            var preview = String(item.text || '').replace(/<[^>]*>/g, ' ').trim().slice(0, 90);
            return '<div class="ac-hist-item" data-idx="' + i + '">' +
                '<div class="ac-hist-top"><b>' + esc(item.label || FIELD_LABELS[item.field] || 'متن') + '</b>' +
                '<small>' + timeAgo(item.t) + '</small></div>' +
                '<div class="ac-hist-prev">' + esc(preview) + '…</div>' +
                '<div class="ac-hist-actions">' +
                (detected && detected.fields[item.field] ? '<button type="button" class="ac-res-btn" data-h="ins">درج مجدد</button>' : '') +
                '<button type="button" class="ac-res-btn" data-h="copy">کپی</button>' +
                '<button type="button" class="ac-res-btn danger" data-h="del">حذف</button>' +
                '</div></div>';
        }).join('');

        list.querySelectorAll('.ac-hist-item').forEach(function (row) {
            var idx = parseInt(row.getAttribute('data-idx'), 10);
            row.querySelectorAll('[data-h]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var h2 = loadHistory();
                    var item = h2[idx];
                    if (!item) { return; }
                    var act = this.getAttribute('data-h');
                    if (act === 'ins' && detected && detected.fields[item.field]) {
                        applyText(detected.fields[item.field], item.text);
                        showToast('درج مجدد انجام شد ✓', 'ok');
                    } else if (act === 'copy') {
                        try {
                            navigator.clipboard.writeText(item.text).then(function () { showToast('کپی شد ✓', 'ok'); });
                        } catch (e) { showToast('کپی ممکن نشد.', 'warn'); }
                    } else if (act === 'del') {
                        h2.splice(idx, 1);
                        saveHistory(h2);
                        renderHistory();
                    }
                });
            });
        });
    }

    /* ---------- نمایش نتیجه (★ v1.5.0: شمارش کلمه + تولید مجدد) ---------- */
    function showResult(text, f, note) {
        var box = document.getElementById('ac-result');
        if (!box) { return; }

        var isHtml = /<(p|h[1-6]|ul|ol|li|strong|em|table)\b/i.test(text);
        var plain = String(text).replace(/<[^>]*>/g, ' ');
        var wc = plain.split(/\s+/).filter(Boolean).length;
        var cc = plain.length;

        box.hidden = false;
        box.innerHTML =
            '<div class="ac-res-note">' + esc(note || '') + '</div>' +
            '<div class="ac-res-meta"><span>' + wc.toLocaleString('fa-IR') + ' کلمه</span><span>' + cc.toLocaleString('fa-IR') + ' کاراکتر</span>' +
            (lastGen ? '<button type="button" class="ac-res-link" id="ac-res-regen">تولید مجدد</button>' : '') + '</div>' +
            '<div class="ac-res-text' + (isHtml ? ' html' : '') + '">' + (isHtml ? text : esc(text)) + '</div>' +
            '<div class="ac-res-actions">' +
            (f ? '<button type="button" class="ac-res-btn" id="ac-res-reapply">درج مجدد در فرم</button>' : '') +
            '<button type="button" class="ac-res-btn" id="ac-res-copy">کپی متن</button>' +
            '</div>';

        var rg = document.getElementById('ac-res-regen');
        if (rg && lastGen) {
            rg.addEventListener('click', function () { generateField(lastGen.fieldKey); });
        }

        var ra = document.getElementById('ac-res-reapply');
        if (ra && f) {
            ra.addEventListener('click', function () { applyText(f, text); });
        }
        var cp = document.getElementById('ac-res-copy');
        if (cp) {
            cp.addEventListener('click', function () {
                try {
                    navigator.clipboard.writeText(text).then(function () { showToast('کپی شد ✓', 'ok'); });
                } catch (e) {
                    showToast('کپی خودکار ممکن نشد — متن را دستی انتخاب و کپی کنید.', 'warn');
                }
            });
        }
    }

    /* ---------- پیام کوتاه (toast) ---------- */
    var toastBox = null;

    function showToast(msg, kind) {
        if (!toastBox) {
            toastBox = document.createElement('div');
            toastBox.className = 'ac-toast-box';
            document.body.appendChild(toastBox);
        }
        var t = document.createElement('div');
        t.className = 'ac-toast ' + (kind || 'info');
        t.textContent = msg;
        toastBox.appendChild(t);
        setTimeout(function () {
            t.classList.add('out');
            setTimeout(function () { if (t.parentNode) { t.parentNode.removeChild(t); } }, 350);
        }, kind === 'err' ? 6000 : 3200);
    }

    /* ================================================================
     *  بوت
     * ================================================================ */
    function boot() {
        initAssistant();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
