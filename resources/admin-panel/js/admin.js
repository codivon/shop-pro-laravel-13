/**
 * پنل مدیریت جدید — اسکریپت اصلی
 * Alpine (از Livewire) + تم روشن/تاریک + Toast + CKEditor + Jalali DatePicker + ابزارها
 */

/* ---------- کتابخانه‌های شخص ثالث ---------- */
import 'ckeditor5/ckeditor5.css';
import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css';
import '@majidh1/jalalidatepicker';
import Sortable from 'sortablejs';
import * as echarts from 'echarts/core';
import { BarChart, LineChart, PieChart } from 'echarts/charts';
import { GridComponent, TooltipComponent, LegendComponent, TitleComponent } from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';

echarts.use([BarChart, LineChart, PieChart, GridComponent, TooltipComponent, LegendComponent, TitleComponent, CanvasRenderer]);
window.ApECharts = echarts;

/* ------------------------------------------------------------------
 | تم روشن / تاریک
 | کلاس dark روی <html> — مقدار در کوکی ذخیره می‌شود تا سمت سرور
 | بدون فلاش (FOUC) کلاس درست را رندر کند.
 ------------------------------------------------------------------ */
window.ApTheme = {
    get() {
        const match = document.cookie.match(/(?:^|; )admin-theme=(dark|light)/);
        if (match) return match[1];
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    },

    set(theme) {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        document.cookie = 'admin-theme=' + theme + ';path=/;max-age=31536000;samesite=lax';
        window.dispatchEvent(new CustomEvent('ap-theme-changed', { detail: { theme } }));
    },

    toggle() {
        this.set(this.get() === 'dark' ? 'light' : 'dark');
    },
};

/* ------------------------------------------------------------------
 | Toast
 | ApToast.show('متن', { type: 'success' | 'error' | 'info', duration })
 ------------------------------------------------------------------ */
window.ApToast = {
    show(message, options = {}) {
        window.dispatchEvent(new CustomEvent('ap-toast', {
            detail: {
                message,
                type: options.type ?? 'info',
                duration: options.duration ?? 4000,
                id: Date.now() + Math.random(),
            },
        }));
    },
    success(message, options = {}) { this.show(message, { ...options, type: 'success' }); },
    error(message, options = {}) { this.show(message, { ...options, type: 'error' }); },
    info(message, options = {}) { this.show(message, { ...options, type: 'info' }); },
};

/* ------------------------------------------------------------------
 | فرمت اعداد و مبالغ (تومان)
 ------------------------------------------------------------------ */
window.ApFormat = {
    faDigits(input) {
        const fa = '۰۱۲۳۴۵۶۷۸۹';
        return String(input).replace(/\d/g, (d) => fa[d]);
    },
    number(value) {
        return this.faDigits(Number(value ?? 0).toLocaleString('en-US'));
    },
    toman(value) {
        return this.number(value) + ' تومان';
    },
};

/* محاسبه قیمت نهایی ردیف تنوع (آینه توابع to_round_price/get_discount_price سرور) */
function apRoundPrice(price, discount, wire) {
    let p = parseFloat(price) || 0;
    const d = parseFloat(discount) || 0;

    p = p - p * (d / 100);

    // ضریب واحد پول
    let amount = 1;
    const currencySelect = document.querySelector('select[wire\\:model="currency_id"]');
    if (currencySelect && currencySelect.value) {
        amount = parseFloat(currencySelect.selectedOptions[0]?.dataset.amount || '1') || 1;
    }
    p = p * amount;

    // گرد کردن
    const form = document.querySelector('[data-rounding-default]');
    let roundingAmount = String(wire?.rounding_amount ?? 'default');
    if (roundingAmount === 'default') roundingAmount = form?.dataset.roundingDefault || 'no';

    let roundingType = String(wire?.rounding_type ?? 'default');
    if (roundingType === 'default') roundingType = form?.dataset.roundingTypeDefault || 'close';

    if (['100', '1000', '10000', '100000'].includes(roundingAmount)) {
        const n = parseInt(roundingAmount);
        if (roundingType === 'up') p = Math.ceil(p / n) * n;
        else if (roundingType === 'down') p = Math.floor(p / n) * n;
        else p = Math.round(p / n) * n;
    }

    return Math.round(p);
}
window.apRoundPrice = apRoundPrice;

/* ------------------------------------------------------------------
 | جستجوی منو (سایدبار)
 ------------------------------------------------------------------ */
window.apMenuSearch = {
    norm(s) { return (s || '').replace(/\u200c/g, ' ').toLowerCase().trim(); },
    has(text, q) {
        const query = this.norm(q);
        if (!query) return true;
        return this.norm(text).includes(query);
    },
};

/* ------------------------------------------------------------------
 | Jalali DatePicker
 | فرمت خروجی «YYYY-MM-DD HH:mm:ss» با ارقام انگلیسی (مطابق سرور)
 ------------------------------------------------------------------ */
window.ApDatepicker = {
    start() {
        if (!window.jalaliDatepicker) return;
        window.jalaliDatepicker.startWatch({
            selector: 'input[data-jdp]',
            time: true,
            persianDigits: false,
            separatorChars: {
                date: '-',
                between: ' ',
                time: ':',
                targetDate: '-',
                targetBetween: ' ',
                targetTime: ':',
            },
        });
    },
};

document.addEventListener('DOMContentLoaded', () => ApDatepicker.start());
document.addEventListener('livewire:navigated', () => ApDatepicker.start());

/* ------------------------------------------------------------------
 | CKEditor 5 — ویرایشگر کامل (همه قابلیت‌های پنل قدیمی)
 | <div id="ap-ckeditor" data-editor-value="..."> → جایگزین textarea قدیمی
 | پراپ لایووایر با data-editor-prop قابل تغییر است (پیش‌فرض: description)
 ------------------------------------------------------------------ */

/* آداپتر آپلود تصویر (الگوی رسمی CustomUploadAdapter) — POST به مسیر مدیای پنل */
class ApUploadAdapter {
    constructor(loader) {
        this.loader = loader;
    }

    upload() {
        return this.loader.file.then(
            (file) => new Promise((resolve, reject) => { this._send(resolve, reject, file); }),
        );
    }

    abort() {
        if (this.xhr) this.xhr.abort();
    }

    _send(resolve, reject, file) {
        const prefix = document.querySelector('meta[name="admin-prefix"]')?.content;
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

        if (!prefix) {
            reject(new Error('آدرس آپلود تصویر یافت نشد (meta admin-prefix موجود نیست).'));
            return;
        }

        const xhr = (this.xhr = new XMLHttpRequest());
        xhr.open('POST', `/admin/${prefix}/media/upload`, true);
        xhr.responseType = 'json';
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', token);

        xhr.addEventListener('error', () => reject(new Error('خطا در ارتباط با سرور هنگام آپلود تصویر.')));
        xhr.addEventListener('abort', () => reject(new Error('آپلود تصویر لغو شد.')));
        xhr.addEventListener('load', () => {
            const res = xhr.response;

            if (xhr.status >= 200 && xhr.status < 300 && res && res.url) {
                resolve({ default: res.url });
                return;
            }

            let message = res && res.message;
            if (!message && res && res.errors) {
                const first = Object.values(res.errors)[0];
                message = Array.isArray(first) ? first[0] : first;
            }
            reject(new Error(message || 'آپلود تصویر ناموفق بود.'));
        });

        const body = new FormData();
        body.append('file', file);
        xhr.send(body);
    }
}

window.ApEditor = {
    instance: null,
    prop: 'description',

    async init() {
        const container = document.querySelector('#ap-ckeditor[data-editor-value]:not([data-ap-ready])');

        if (!container) {
            /* صفحه فعلی ویرایشگر ندارد — نمونه قبلی را آزاد کن */
            await this.destroy();
            return;
        }
        if (!window.Livewire) return;

        container.setAttribute('data-ap-ready', '1');
        await this.destroy();

        try {
            const {
                /* پایه */
                ClassicEditor, Essentials, Paragraph, Heading, Undo,
                /* استایل‌های متنی */
                Bold, Italic, Underline, Strikethrough, Code, Subscript, Superscript, RemoveFormat,
                /* فونت، رنگ و هایلایت */
                FontColor, FontBackgroundColor, FontFamily, FontSize, Highlight,
                /* بلوک‌ها و جهت‌ها */
                BlockQuote, CodeBlock, Indent, IndentBlock, Alignment, HorizontalLine, PageBreak,
                /* لیست‌ها */
                List, ListProperties, TodoList,
                /* لینک و نشانک */
                Link, LinkImage, AutoLink, Bookmark,
                /* جدول */
                Table, TableToolbar, TableProperties, TableCellProperties, TableCaption,
                /* تصویر */
                Image, ImageToolbar, ImageUpload, ImageResize, AutoImage, ImageStyle, ImageCaption, PictureEditing,
                /* مدیا و سایر ابزارها */
                MediaEmbed, AutoMediaEmbed, PasteFromOffice, FindAndReplace, SourceEditing, ShowBlocks,
                SpecialCharacters, SpecialCharactersEssentials, HtmlEmbed, WordCount, Fullscreen, Emoji,
                /* فاز ۱۲ — تکمیل شکاف قابلیت‌ها: نشانک/انتخاب همه/استایل‌های آماده/تبدیل خودکار نویسه‌ها/خودفرمت‌کن + GHS برای ذخیره کلاس استایل‌ها */
                SelectAll, Style, TextTransformation, Autoformat, GeneralHtmlSupport,
            } = await import('ckeditor5');

            const { default: faTranslations } = await import('ckeditor5/translations/fa.js');

            const editor = await ClassicEditor.create(container, {
                licenseKey: 'GPL',
                language: 'fa',
                translations: [faTranslations],
                plugins: [
                    Essentials, Paragraph, Heading, Undo,
                    Bold, Italic, Underline, Strikethrough, Code, Subscript, Superscript, RemoveFormat,
                    FontColor, FontBackgroundColor, FontFamily, FontSize, Highlight,
                    BlockQuote, CodeBlock, Indent, IndentBlock, Alignment, HorizontalLine, PageBreak,
                    List, ListProperties, TodoList,
                    Link, LinkImage, AutoLink, Bookmark,
                    Table, TableToolbar, TableProperties, TableCellProperties, TableCaption,
                    Image, ImageToolbar, ImageUpload, ImageResize, AutoImage, ImageStyle, ImageCaption, PictureEditing,
                    MediaEmbed, AutoMediaEmbed, PasteFromOffice, FindAndReplace, SourceEditing, ShowBlocks,
                    SpecialCharacters, SpecialCharactersEssentials, HtmlEmbed, WordCount, Fullscreen, Emoji,
                    SelectAll, Style, TextTransformation, Autoformat, GeneralHtmlSupport,
                ],
                toolbar: [
                    'heading', 'style', '|',
                    'bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript', 'code', '|',
                    'fontColor', 'fontBackgroundColor', 'highlight', 'fontFamily', 'fontSize', '|',
                    'link', 'bookmark', '|',
                    'bulletedList', 'numberedList', 'todoList', 'outdent', 'indent', '|',
                    'alignment', '|',
                    'blockQuote', 'codeBlock', '|',
                    'uploadImage', 'insertTable', 'mediaEmbed', 'htmlEmbed', '|',
                    'specialCharacters', 'horizontalLine', 'pageBreak', '|',
                    'findAndReplace', 'selectAll', 'removeFormat', '|',
                    'showBlocks', 'sourceEditing', '|',
                    'fullscreen', '|',
                    'emoji', '|',
                    'undo', 'redo',
                ],
                heading: {
                    options: [
                        { model: 'paragraph', title: 'متن', class: 'ck-heading_paragraph' },
                        { model: 'heading2', view: 'h2', title: 'تیتر ۲', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: 'h3', title: 'تیتر ۳', class: 'ck-heading_heading3' },
                        { model: 'heading4', view: 'h4', title: 'تیتر ۴', class: 'ck-heading_heading4' },
                    ],
                },
                table: {
                    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'toggleTableCaption', 'tableProperties', 'tableCellProperties'],
                },
                image: {
                    toolbar: ['imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|', 'toggleImageCaption', 'imageTextAlternative', '|', 'linkImage'],
                },
                mediaEmbed: {
                    previewsInData: true,
                    extraProviders: [
                        {
                            name: 'aparat',
                            url: /aparat\.com\/v\/([\w-]+)/,
                            html: (match) => '<iframe src="https://www.aparat.com/video/video/embed/videohash/' + match[1] + '/vt/frame" width="640" height="360" frameborder="0" allowfullscreen></iframe>',
                        },
                    ],
                },
                /* فاز ۱۲ — استایل‌های آماده (کلاس‌ها با پیشوند ap-style-، CSS در انتهای admin.css) */
                style: {
                    definitions: [
                        { name: 'کال‌اوت تیتر ۲', element: 'h2', classes: ['ap-style-callout-h2'] },
                        { name: 'بند هایلایت‌شده', element: 'p', classes: ['ap-style-highlight-marker'] },
                        { name: 'کال‌اوت قرمز', element: 'p', classes: ['ap-style-callout-red'] },
                        { name: 'کد اسنیپت تیره', element: 'pre', classes: ['ap-style-code-dark'] },
                        { name: 'متن کوچک', element: 'p', classes: ['ap-style-small-text'] },
                    ],
                },
            });

            editor.setData(container.dataset.editorValue || '');
            this.instance = editor;
            this.prop = container.dataset.editorProp || 'description';

            /* آداپتر آپلود تصویر */
            editor.plugins.get('FileRepository').createUploadAdapter = (loader) => new ApUploadAdapter(loader);

            // همگام‌سازی محتوا با Livewire
            // فاز ۱۳: Livewire.find(id) در v4 آبجکت مرده برمی‌گرداند (نه کامپوننت زنده) —
            // نتیجه: سینک description بی‌صدا گم می‌شد («ایجاد صفحه جدید کار نمی‌کند»).
            // کامپوننت زنده با تطبیق el پیدا می‌شود.
            const componentEl = container.closest('[wire\\:id]');
            const component = componentEl
                ? (window.Livewire.all().find((c) => c.el === componentEl) || null)
                : null;

            editor.model.document.on('change:data', () => {
                const data = editor.getData();
                if (component) component.$wire[this.prop] = data;
                const input = document.querySelector('#ap-ckeditor-input');
                if (input) input.value = data;
            });

            /* شمارش کلمات/حروف — اگر محل نمایش (#ap-editor-wordcount) وجود داشته باشد */
            const wordcountEl = document.querySelector('#ap-editor-wordcount');
            if (wordcountEl) {
                const wordCount = editor.plugins.get('WordCount');
                const mount = (target) => {
                    if (target && wordCount.output && wordCount.output.parentNode !== target) {
                        target.innerHTML = '';
                        target.appendChild(wordCount.output);
                    }
                };
                mount(wordcountEl);
                wordCount.on('update', () => mount(document.querySelector('#ap-editor-wordcount')));
            }
        } catch (error) {
            console.error('CKEditor init failed:', error);
        }
    },

    /* آزادسازی نمونه قبلی (پس از ناوبری لایووایر) */
    async destroy() {
        const editor = this.instance;
        this.instance = null;
        if (editor) {
            try { await editor.destroy(); } catch (e) { /* نمونه قبلی از بین رفته است */ }
        }
    },
};

document.addEventListener('DOMContentLoaded', () => ApEditor.init());
document.addEventListener('livewire:navigated', () => ApEditor.init());

/* همگام‌سازی محتوای CKEditor پس از دریافت محصول از دیجی‌کالا */
window.Livewire?.hook('message.processed', ({ component }) => {
    if (component.effects?.dispatches?.some((d) => d.name === 'ap-set-description') && window.ApEditor?.instance) {
        window.ApEditor.instance.setData(component[window.ApEditor.prop] || '');
    }
});

/* ------------------------------------------------------------------
 | ECharts — راه‌انداز نمودارها (فارسی/RTL)
 | <div data-chart="..." data-options="JSON"> — مقدار «fn:name» به فرمت‌کننده تبدیل می‌شود
 ------------------------------------------------------------------ */
window.ApChart = {
    instances: {},

    formatters: {
        faNum: (v) => (typeof v === 'number' ? ApFormat.number(v) : ApFormat.faDigits(v ?? '')),
        // فاز ۱۹: formatter برچسب سری (bar label) — echarts به‌جای مقدار، آبجکت params می‌فرستد
        faSeriesLabel: (params) => {
            const v = params && typeof params === 'object' ? (params.value ?? '') : (params ?? '');
            return typeof v === 'number' ? ApFormat.faDigits(v) : ApFormat.faDigits(v);
        },
        faNumCompact: (v) => {
            const n = Number(v) || 0;
            if (Math.abs(n) >= 1e9) return ApFormat.faDigits((n / 1e9).toFixed(1) + 'B');
            if (Math.abs(n) >= 1e6) return ApFormat.faDigits((n / 1e6).toFixed(1) + 'M');
            if (Math.abs(n) >= 1e3) return ApFormat.faDigits((n / 1e3).toFixed(0) + 'K');
            return ApFormat.faDigits(n);
        },
    },

    /* تبدیل رشته‌های «fn:name» به توابع فرمت‌کننده */
    resolveFormatters(value) {
        if (typeof value === 'string' && value.startsWith('fn:')) {
            return this.formatters[value.slice(3)] ?? value;
        }
        if (Array.isArray(value)) {
            return value.map((item) => this.resolveFormatters(item));
        }
        if (value && typeof value === 'object') {
            return Object.fromEntries(Object.entries(value).map(([k, v]) => [k, this.resolveFormatters(v)]));
        }
        return value;
    },

    renderAll() {
        document.querySelectorAll('[data-chart]:not([data-ap-chart])').forEach((el) => {
            el.setAttribute('data-ap-chart', '1');

            let options;
            try {
                options = JSON.parse(el.dataset.options);
            } catch (e) {
                return;
            }

            options = this.resolveFormatters(options);

            const chart = echarts.init(el);
            chart.setOption(options);
            this.instances[el.dataset.chart] = chart;

            window.addEventListener('resize', () => chart.resize());
            window.addEventListener('ap-theme-changed', () => chart.resize());
        });
    },
};

document.addEventListener('DOMContentLoaded', () => ApChart.renderAll());
document.addEventListener('livewire:navigated', () => ApChart.renderAll());
document.addEventListener('livewire:navigated', () => setTimeout(() => window.dispatchEvent(new CustomEvent('ap-recalc')), 300));

/* ------------------------------------------------------------------
 | کامپوننت‌های Alpine
 ------------------------------------------------------------------ */
document.addEventListener('alpine:init', () => {

    /* مدیریت Toast */
    Alpine.data('apToast', () => ({
        items: [],
        init() {
            window.addEventListener('ap-toast', (e) => {
                // فاز ۱۶ — باگ اصلی: t یک آبجکت خام بود و t.visible = true از طریق
                // پروکسی واکنش‌گرای Alpine نمی‌گذشت؛ نتیجه: هیچ توستی هرگز نمایش
                // داده نمی‌شد (visible برای همیشه false می‌ماند). با Alpine.reactive
                // جهش visible اثر واکنشی دارد.
                const t = Alpine.reactive({ ...e.detail, visible: false });
                this.items.push(t);
                requestAnimationFrame(() => { t.visible = true; });
                setTimeout(() => this.remove(t), t.duration);
            });
        },
        remove(toast) {
            toast.visible = false;
            setTimeout(() => {
                this.items = this.items.filter((i) => i !== toast);
            }, 250);
        },
    }));

    /* سایدبار موبایل + حفظ اسکرول و باز بودن منوی فعال */
    Alpine.data('apSidebar', () => ({
        open: false,
        search: '',
        init() {
            window.addEventListener('ap-close-sidebar', () => { this.open = false; });
            // فاز ۱۶: هنگام جستجو در منو، همه گروه‌ها باز شوند تا نتایج زیرمنو دیده شوند
            this.$watch('search', (value) => {
                if (value && value.trim()) {
                    window.dispatchEvent(new CustomEvent('ap-menu-open-all'));
                }
            });
            this.restorePosition();
        },

        /* بعد از هر ناوبری/رفرش: بازگشت به نقطه منوی فعال (یا آخرین اسکرول) */
        restorePosition() {
            this.$nextTick(() => {
                // فاز ۱۶: چند مرحله‌ای — گروه‌های والدِ آیتم فعال خودشان در init باز
                // می‌شوند؛ اینجا فقط اسکرول روی آیتم فعال را تضمین می‌کنیم (با تلاش مجدد
                // چون باز شدن گروه و رندر Alpine ممکن است کمی طول بکشد)
                let attempts = 0;
                const scrollToActive = () => {
                    const nav = this.$refs.nav;
                    if (!nav) return;
                    const active = nav.querySelector('.ap-menu-link.is-active');

                    if (active) {
                        const visible = active.offsetParent !== null && active.getBoundingClientRect().width > 0;
                        if (!visible && attempts < 10) {
                            // گروه والد هنوز باز نشده — تلاش مجدد
                            attempts++;
                            setTimeout(scrollToActive, 60);
                            return;
                        }
                        const navRect = nav.getBoundingClientRect();
                        const r = active.getBoundingClientRect();
                        // اگر آیتم فعال دیده نمی‌شود، به یک‌سوم بالای کادر برسان
                        if (r.top < navRect.top + 8 || r.bottom > navRect.bottom - 8) {
                            const target = Math.max(0, nav.scrollTop + (r.top - navRect.top) - navRect.height / 3);
                            nav.scrollTo({ top: target, behavior: 'smooth' });
                        }
                        try { sessionStorage.removeItem('ap-sidebar-scroll'); } catch (e) {}
                    } else {
                        const saved = parseInt(sessionStorage.getItem('ap-sidebar-scroll') || '0', 10);
                        if (saved > 0) nav.scrollTop = saved;
                    }
                    try { sessionStorage.setItem('ap-sidebar-scroll', String(nav.scrollTop)); } catch (e) {}
                };
                setTimeout(scrollToActive, 100);
            });
        },

        saveScroll() {
            const nav = this.$refs.nav;
            if (!nav) return;
            try { sessionStorage.setItem('ap-sidebar-scroll', String(nav.scrollTop)); } catch (e) {}
        },
    }));

    /* آکاردئون گروه منو */
    Alpine.data('apMenuGroup', (openByDefault = false) => ({
        open: openByDefault,
        init() {
            // فاز ۱۶: اگر آیتم فعال داخل این گروه است، گروه باز بماند
            // (قبلاً همه گروه‌ها بسته می‌شدند و کاربر در صفحه «دسته‌بندی وبلاگ»
            // منوی وبلاگ را بسته می‌دید)
            if (!this.open && this.$el.querySelector('.ap-menu-link.is-active')) {
                this.open = true;
            }
            // هنگام جستجو در منو همه گروه‌ها باز شوند
            window.addEventListener('ap-menu-open-all', () => { this.open = true; });
        },
        toggle() { this.open = !this.open; },
    }));

    /* دکمه تمام‌صفحه */
    Alpine.data('apFullscreen', () => ({
        active: false,
        toggle() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => { this.active = true; }).catch(() => {});
            } else {
                document.exitFullscreen().then(() => { this.active = false; }).catch(() => {});
            }
        },
    }));

    /* Dropdown عمومی */
    Alpine.data('apDropdown', () => ({
        open: false,
        toggle() { this.open = !this.open; },
        close() { this.open = false; },
    }));

    /* ------------------------------------------------------------------
     | مدیریت دسترسی‌ها (فاز ۱۷) — وضعیت انتخاب سمت کلاینت برای UI فوری
     | (شمارنده زنده، کسکید والد/فرزند، جستجو) — ذخیره با یک کامیت ($wire.save(perms))
     ------------------------------------------------------------------ */
    Alpine.data('apPermissions', ({ initial = [], kids = {}, titles = {} }) => ({
        perms: initial.map(Number),
        kidsMap: kids,
        titles,

        match(title) {
            const q = String(this.q ?? '').trim();
            if (!q) return true;
            return String(title ?? '').indexOf(q) !== -1;
        },

        kids(pid) {
            return (this.kidsMap[pid] || []).map(Number);
        },

        isChecked(id) {
            return this.perms.includes(Number(id));
        },

        childCount(pid) {
            return this.kids(pid).filter((id) => this.isChecked(id)).length;
        },

        toggle(id) {
            id = Number(id);
            this.perms = this.isChecked(id)
                ? this.perms.filter((v) => v !== id)
                : [...this.perms, id];
        },

        toggleParent(pid) {
            pid = Number(pid);
            const kids = this.kids(pid);

            if (this.isChecked(pid)) {
                this.perms = this.perms.filter((v) => v !== pid && ! kids.includes(v));
            } else {
                this.perms = [...new Set([...this.perms, pid, ...kids])];
            }
        },

        selectAll() {
            this.perms = Object.keys(this.titles).map(Number);
        },

        clearAll() {
            if (window.confirm('آیا از حذف انتخاب همه دسترسی‌ها مطمئن هستید؟ تا قبل از ذخیره هیچ تغییری اعمال نمی‌شود.')) {
                this.perms = [];
            }
        },

        async save() {
            await this.$wire.save(this.perms);
        },
    }));

    /* ------------------------------------------------------------------
     | فرم فیلترها (فاز ۱۷) — مودال «افزودن مشخصات» با جستجوی زنده
     | پورت add-filterable-modal قدیمی؛ افزودن مستقیم هر مورد با +
     ------------------------------------------------------------------ */
    Alpine.data('apFilterForm', () => ({
        modalOpen: false,

        openModal() {
            this.modalOpen = true;
            this.$nextTick(() => {
                const input = document.getElementById('filterable-search');
                if (input) { input.value = ''; input.dispatchEvent(new Event('input', { bubbles: true })); input.focus(); }
            });
        },

        match(label, query) {
            const q = String(query ?? '').trim().toLowerCase();
            if (!q) return true;
            return String(label ?? '').toLowerCase().includes(q);
        },

        rows() {
            return this.$wire.rows || [];
        },

        added(item) {
            return this.rows().some((r) => r.type === item.type && Number(r.id) === Number(item.id));
        },

        async add(item) {
            if (this.added(item)) return;
            await this.$wire.addFilterableRow(item.type, item.id);
        },
    }));

    /* ------------------------------------------------------------------
     | بازار پکیج‌ها — مودال تأیید نصب (فاز ۱۷ — پورت install-confirm قدیمی)
     | جریان: نصب → بررسی وضعیت خرید/اشتراک (checkPurchaseState) → انتخاب پلن
     | (پیش‌فرض ارزان‌ترین مثل قدیمی) → تأیید → startInstall با planId/اشتراک/لایسنس
     ------------------------------------------------------------------ */
    Alpine.data('pkgMarket', () => ({
        modalOpen: false,
        checking: false,
        busy: false,
        pkg: null,
        mode: 'paid', // free | paid | license | subscription
        plans: [],
        planId: null,
        license: null,
        subscription: null,
        warning: null,
        // مودال نتیجه پرداخت (بعد از بازگشت از درگاه — session payment_result)
        paymentModal: false,

        formatPrice(v) {
            return ApFormat.number(v);
        },

        selectedPlan() {
            return this.plans.find((p) => p.id === this.planId) || null;
        },

        get confirmLabel() {
            if (this.mode === 'license') return 'بررسی و نصب';
            if (this.mode === 'subscription') return 'نصب با اشتراک';
            if (this.mode === 'free') return 'نصب پکیج';
            const plan = this.selectedPlan();
            if (plan && plan.final_price === 0) return 'نصب پکیج';
            return 'پرداخت و نصب';
        },

        buildPlans(raw) {
            const plans = (raw || []).map((plan) => {
                const price = parseInt(plan.price ?? 0) || 0;
                const finalPrice = parseInt(plan.discount_price ?? plan.price ?? 0) || 0;
                const months = parseInt(plan.duration_months ?? 0) || 0;
                const duration = months === 0 ? 'نامحدود'
                    : months < 12 ? months + ' ماه'
                        : (months / 12 === Math.floor(months / 12) ? Math.floor(months / 12) : months / 12) + ' سال';

                return {
                    id: plan.id,
                    name: plan.name || ('پلن #' + plan.id),
                    price,
                    final_price: finalPrice,
                    price_label: ApFormat.number(price),
                    final_label: ApFormat.number(finalPrice),
                    has_discount: plan.discount_price !== null && plan.discount_price !== undefined && plan.discount_price < price,
                    duration,
                    description: plan.description || '',
                    is_one_time: plan.is_one_time === true || plan.is_one_time === 1 || plan.is_one_time === '1',
                    is_cheapest: false,
                };
            });

            // ارزان‌ترین — مثل قدیمی نشان + انتخاب پیش‌فرض
            if (plans.length) {
                const cheapest = plans.reduce((acc, p) => (p.final_price < acc.final_price ? p : acc), plans[0]);
                cheapest.is_cheapest = plans.filter((p) => p.final_price === cheapest.final_price).length > 1;
                this.planId = cheapest.id;
            } else {
                this.planId = null;
            }

            this.plans = plans;
        },

        expiryLabel(expiresAt, days) {
            if (!expiresAt) return ' — نامحدود';
            let label = ' — اعتبار تا ' + expiresAt;
            const d = parseInt(days);
            if (!isNaN(d) && d >= 0) label += ' (' + ApFormat.number(Math.max(d, 0)) + ' روز باقی‌مانده)';
            return label;
        },

        async ask(pkg) {
            this.pkg = pkg;
            this.license = null;
            this.subscription = null;
            this.warning = null;
            this.busy = false;
            this.modalOpen = true;

            // پکیج رایگان — مثل قدیمی مستقیم تأیید
            if (pkg.is_free) {
                this.mode = 'free';
                this.plans = [];
                this.planId = null;
                return;
            }

            this.mode = 'paid';
            this.buildPlans(pkg.active_pricing_plans ?? pkg.pricing_plans ?? pkg.plans ?? []);

            // بررسی خرید/اشتراک (پورت checkPurchase AJAX قدیمی)
            this.checking = true;
            try {
                const state = await this.$wire.checkPurchaseState(pkg.slug);
                if (state && state.mode === 'subscription') {
                    this.mode = 'subscription';
                    this.subscription = {
                        plan_name: state.plan_name,
                        expiry_label: this.expiryLabel(state.expires_at_jalali ?? null, state.days_remaining),
                    };
                } else if (state && state.mode === 'license') {
                    this.mode = 'license';
                    this.license = {
                        expiry_label: this.expiryLabel(state.expires_at_jalali ?? null, state.days_remaining),
                    };
                } else if (state && state.warning) {
                    this.warning = state.warning;
                }
            } catch (e) {
                // خطا در بررسی → جریان عادی خرید مثل قدیمی
            } finally {
                this.checking = false;
            }
        },

        pick(plan) {
            this.planId = plan.id;
            this.warning = null;
        },

        async confirmInstall() {
            if (this.busy) return;
            this.busy = true;
            try {
                if (this.mode === 'subscription') {
                    await this.$wire.startInstall(this.pkg.slug, null, true, false);
                } else if (this.mode === 'license') {
                    await this.$wire.startInstall(this.pkg.slug, null, false, true);
                } else if (this.mode === 'free') {
                    await this.$wire.startInstall(this.pkg.slug);
                } else {
                    await this.$wire.startInstall(this.pkg.slug, this.planId);
                }
                this.modalOpen = false;
            } catch (e) {
                ApToast?.show?.('خطا در ارتباط با سرور.', { type: 'error' });
            } finally {
                this.busy = false;
            }
        },
    }));

    /* فرم محصول — تب‌ها */
    Alpine.data('apProductForm', () => ({
        tab: 'basics',
        recalcAll() {
            document.querySelectorAll('[data-price-row]').forEach((row) => {
                const el = row.querySelector('[data-final-price]');
                if (!el) return;

                const price = parseFloat(row.querySelector('[data-price-input]')?.value) || 0;
                const discount = parseFloat(row.querySelector('[data-discount-input]')?.value) || 0;

                el.textContent = ApFormat.number(apRoundPrice(price, discount, this.$wire));
            });
        },
    }));

    /* محاسبه قیمت نهایی هر ردیف تنوع */
    Alpine.data('apFinalPrice', () => ({
        idx: null,
        init(idx) {
            this.idx = idx;
            this.calc();

            // اتصال مستقیم رویدادها (مقاوم در برابر morph لایووایر)
            this.$el.querySelector('[data-price-input]')?.addEventListener('input', () => this.calc());
            this.$el.querySelector('[data-discount-input]')?.addEventListener('input', () => this.calc());
            window.addEventListener('ap-recalc', () => this.calc());
        },
        calc() {
            const el = this.$el.querySelector('[data-final-price]');
            if (!el) return;

            // خواندن مقادیر از خود ردیف (بدون تاخیر sync لایووایر)
            const price = parseFloat(this.$el.querySelector('[data-price-input]')?.value) || 0;
            const discount = parseFloat(this.$el.querySelector('[data-discount-input]')?.value) || 0;

            el.textContent = ApFormat.number(apRoundPrice(price, discount, this.$wire));
        },
    }));

    /* ورودی تگ‌ها/برچسب‌ها */
    Alpine.data('apTags', () => ({
        items: [],
        draft: '',
        init() {
            this.items = String(this.$el.dataset.initial || '')
                .split(',')
                .map((s) => s.trim())
                .filter(Boolean);
        },
        add() {
            const value = this.draft.trim();
            if (value && !this.items.includes(value)) this.items.push(value);
            this.draft = '';
        },
        remove(index) {
            this.items.splice(index, 1);
        },
    }));

    /* ------------------------------------------------------------------
     | انتخابگر درختی دسته‌بندی (فاز ۱۲) — تک/چند انتخابی + جستجو + پیشنهاد پرکاربرد
     | مقدار با $wire.set(prop, value) (deferred) سینک می‌شود؛ تغییر سمت سرور
     | (مثل پرکردن خودکار) هم با $wire.$watch به UI منعکس می‌شود.
     ------------------------------------------------------------------ */
    Alpine.data('apCategoryPicker', (config = {}) => ({
        prop: config.prop || 'category_id',
        multiple: !!config.multiple,
        tree: config.tree || [],
        popular: config.popular || [],
        open: false,
        query: '',
        expanded: {},
        value: null, // number | number[] | null
        titles: {}, // id -> title

        init() {
            const flat = {};
            const walk = (nodes, trail) => (nodes || []).forEach((n) => {
                flat[n.id] = (trail.length ? trail.join(' ← ') + ' ← ' : '') + n.title;
                walk(n.children, trail.concat([n.title]));
            });
            walk(this.tree, []);
            this.titles = flat;

            const current = this.$wire.get(this.prop);
            this.value = this.multiple
                ? Array.from(current || [])
                : (current || null);

            // تغییر سمت سرور (پرکردن خودکار و ...) باید در UI دیده شود
            this.$wire.$watch(this.prop, (v) => {
                this.value = this.multiple ? Array.from(v || []) : (v || null);
            });
        },

        selectedIds() {
            return this.multiple ? (this.value || []) : (this.value ? [this.value] : []);
        },

        selectedTitles() {
            return this.selectedIds().map((id) => this.titles[id] || ('#' + id));
        },

        isSelected(id) {
            return this.selectedIds().includes(Number(id));
        },

        toggleOpen() {
            this.open = !this.open;
            if (this.open) this.query = '';
        },

        close() {
            this.open = false;
        },

        expandPath(id) {
            // پدرهای یک گره را باز کن (با ساختار درخت)
            const path = [];
            const find = (nodes, trail) => (nodes || []).some((n) => {
                if (Number(n.id) === Number(id)) { path.push(...trail); return true; }
                return find(n.children, trail.concat([n.id]));
            });
            find(this.tree, []);
            path.forEach((pid) => { this.expanded[pid] = true; });
        },

        choose(id) {
            id = Number(id);
            if (this.multiple) {
                const list = this.selectedIds().slice();
                const idx = list.indexOf(id);
                if (idx >= 0) list.splice(idx, 1); else list.push(id);
                this.value = list;
                this.$wire.set(this.prop, list);
            } else {
                // انتخاب مجدد همان گزینه = لغو انتخاب
                this.value = this.value === id ? null : id;
                this.$wire.set(this.prop, this.value);
                this.open = false;
            }
        },

        removeAt(id) {
            if (this.multiple) {
                this.value = this.selectedIds().filter((v) => v !== Number(id));
                this.$wire.set(this.prop, this.value);
            } else {
                this.value = null;
                this.$wire.set(this.prop, null);
            }
        },

        clear() {
            this.value = this.multiple ? [] : null;
            this.$wire.set(this.prop, this.value);
        },

        /* جستجو: گره اگر خودش یا یکی از فرزندانش با عبارت بخورد نمایش داده می‌شود */
        matches(node) {
            if (!this.query) return true;
            const q = this.query.trim().toLowerCase();
            if (!q) return true;
            if (String(node.title || '').toLowerCase().includes(q)) return true;
            return (node.children || []).some((c) => this.matches(c));
        },

        shouldExpand(node) {
            if (this.query && this.query.trim()) return true; // هنگام جستجو همه باز
            return !!this.expanded[node.id];
        },

        hasChildren(node) {
            return (node.children || []).length > 0;
        },
    }));
});

/* ------------------------------------------------------------------
 | فاز ۱۳ — درگ‌انددراپ تو‌درتو با SortableJS (جانشین درگ دستی HTML5)
 | هم‌تجربه کتابخانه nestable پنل قبلی: جابه‌جایی هم‌سطح + فرزند شدن
 | (کشیدن روی لیست خالی/فرزندان گره) + خروج به ریشه (کشیدن به لیست ریشه).
 | استفاده:
 |   <div data-ap-dnd-tree data-dnd-group="menus" data-dnd-save="saveTree"
 |        x-init="$el.__apDndWire = $wire; window.ApDnd && window.ApDnd.init($el)">
 |     <ul data-dnd-list> <li data-dnd-id="…"> <span data-dnd-handle>…</span>
 |         <ul data-dnd-list>…</ul> </li> … </ul>
 |   </div>
 | حالت تخت (بدون تو‌درتو): data-dnd-flat روی ریشه + اکشن آرایه‌ای مثل reorderWidgets.
 ------------------------------------------------------------------ */
window.ApDnd = {
    init(root) {
        if (!root || !root.querySelector) return;
        // نابودی نمونه‌های قبلی (پس از مورف لایووایر یا init تکراری)
        (root.__apDnd || []).forEach((s) => { try { s.destroy(); } catch (e) {} });
        root.__apDnd = [];
        const group = root.getAttribute('data-dnd-group') || 'ap-dnd';
        root.querySelectorAll('[data-dnd-list]').forEach((list) => {
            root.__apDnd.push(new Sortable(list, {
                group: group,
                animation: 150,
                handle: '[data-dnd-handle]',
                ghostClass: 'ap-dnd-ghost',
                chosenClass: 'ap-dnd-chosen',
                /* فاز ۱۳ — درگ مبتنی بر موس/لمس (به‌جای HTML5 native) — سازگاری بهتر
                   با RTL، داخل اسکرول و لمس؛ تجربه نزدیک‌تر به nestable پنل قبلی */
                forceFallback: true,
                fallbackClass: 'ap-dnd-fallback',
                fallbackTolerance: 3,
                fallbackOnBody: true,
                swapThreshold: 0.62,
                emptyInsertThreshold: 16,
                scroll: true,
                scrollSensitivity: 120,
                scrollSpeed: 14,
                onStart: () => root.classList.add('ap-dnd-active'),
                onEnd: () => {
                    root.classList.remove('ap-dnd-active');
                    window.ApDnd.save(root);
                },
            }));
        });
    },

    /* خروجی حالت درختی: [{ id, children: […] }, …] از خود DOM */
    serializeTree(root) {
        const walkList = (listEl) => Array.from(listEl.querySelectorAll(':scope > [data-dnd-id]')).map((item) => {
            const sub = item.querySelector(':scope > [data-dnd-list]');
            return {
                id: Number(item.getAttribute('data-dnd-id')),
                children: sub ? walkList(sub) : [],
            };
        });
        /* فاز ۱۳ — لیست ریشه: اولین data-dnd-list که داخل هیچ گره‌ای نیست
           (لزوماً فرزند مستقیم ریشه نیست — ممکن است داخل wrapper پدینگ باشد) */
        const rootList = Array.from(root.querySelectorAll('[data-dnd-list]'))
            .find((list) => !list.closest('[data-dnd-id]'));
        return rootList ? walkList(rootList) : [];
    },

    save(root) {
        const wire = root.__apDndWire;
        const method = root.getAttribute('data-dnd-save');
        if (!wire || !method || typeof wire[method] !== 'function') return;
        const payload = root.hasAttribute('data-dnd-flat')
            ? Array.from(root.querySelectorAll('[data-dnd-id]')).map((el) => Number(el.getAttribute('data-dnd-id')))
            : window.ApDnd.serializeTree(root);
        try {
            Promise.resolve(wire[method](payload))
                .catch(() => {})
                .then(() => { setTimeout(() => window.ApDnd.init(root), 300); }); // بازسازی نمونه‌ها پس از رندر مجدد
        } catch (e) { /* کامپوننت در حال حذف/جابه‌جایی است */ }
    },

    /* init همه درخت‌های صفحه — برای livewire:navigated */
    initAll() {
        document.querySelectorAll('[data-ap-dnd-tree]').forEach((root) => window.ApDnd.init(root));
    },
};

/* ------------------------------------------------------------------
 | انتخابگر رسانه (File Manager) — فاز ۱۴
 | پس از انتخاب فایل، ویوی fm-button تابع سراسری fmSetLink را صدا می‌زند.
 ------------------------------------------------------------------ */
window.ApMedia = {
    /* مقدار ذخیره‌شده (مسیر نسبی یا URL کامل) → URL قابل نمایش */
    url(value) {
        if (!value) return '';
        const v = String(value).trim();
        if (/^(https?:)?\/\//i.test(v) || v.startsWith('data:') || v.startsWith('blob:')) return v;
        // مسیر نسبی → مطلق بر اساس هاست فعلی (asset() سمت سرور هم همین معنا)
        return window.location.origin + '/' + v.replace(/^\/+/, '');
    },

    /* پیکر فعال — هنگام باز شدن مودال تنظیم می‌شود */
    active: null,

    /* callback مشترک fm-button (حالت popup قدیمی هم از همین استفاده می‌کند) */
    setLink(fileUrl) {
        const target = window.ApMedia.active;
        if (!target) return;
        // پاک‌سازی active توسط خود پیکر انجام می‌شود (بعد از تطبیق uid)
        window.dispatchEvent(new CustomEvent('ap-media-selected', { detail: { url: String(fileUrl || ''), target } }));
    },
};

/* قرارداد قدیمی پنل — ویوی fm-button این تابع را صدا می‌زند */
window.fmSetLink = (url) => window.ApMedia.setLink(url);

/* کامپوننت Alpine انتخابگر رسانه */
window.apMediaPicker = (initial = '') => ({
    uid: Math.random().toString(36).slice(2),
    value: String(initial || ''),
    open: false,
    loading: true,
    imgError: false,

    init() {
        this.$watch('value', (v) => {
            this.imgError = false;
            const input = this.$refs.input;
            if (!input) return;
            if (input.value !== v) {
                input.value = v;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    },

    openManager() {
        window.ApMedia.active = this.uid;
        this.loading = true;
        this.open = true;
    },

    remove() {
        window.ApMedia.active = null;
        this.value = '';
        this.open = false;
    },
});

/* ------------------------------------------------------------------
 | آماده‌سازی Livewire — هوک‌های سراسری (فاز ۱۳)
 ------------------------------------------------------------------ */
(function () {
    const register = () => {
        if (!window.Livewire) return;

        // پیام لحظه‌ای برای خطاهای اعتبارسنجی در همه فرم‌ها (در کنار خطای زیر فیلد)
        // فاز ۱۶: تعداد خطاها هم در پیام نشان داده می‌شود
        let lastErrorToast = 0;
        const showValidationError = (count) => {
            const now = Date.now();
            if (now - lastErrorToast < 1500) return; // ضد تکرار
            lastErrorToast = now;
            const n = ApFormat.faDigits(count || 0);
            const message = count > 1
                ? `${n} خطای اعتبارسنجی وجود دارد — لطفاً خطاهای مشخص‌شده زیر فیلدها را برطرف کنید.`
                : 'خطای اعتبارسنجی — لطفاً خطای مشخص‌شده زیر فیلد را برطرف کنید.';
            window.ApToast && window.ApToast.error(message, { duration: 5000 });
        };

        // ۱) خطای اعتبارسنجی اکشن‌ها: memo.errors در snapshot موفق (HTTP 200 + errors)
        window.Livewire.hook('commit', ({ succeed }) => {
            succeed(({ snapshot }) => {
                const errors = snapshot && snapshot.memo && snapshot.memo.errors;
                if (errors && Object.keys(errors).length) showValidationError(Object.keys(errors).length);
            });
        });

        // ۲) پشتیبان: پاسخ واقعی HTTP 422 (آپلود فایل و …)
        window.Livewire.hook('request', ({ respond }) => {
            respond(({ status }) => {
                if (status === 422) showValidationError(1);
            });
        });

        /* --------------------------------------------------------------
         | فاز ۱۶ — بازخورد لودینگ سراسری
         | هر کلیکی که درخواست Livewire می‌سازد (wire:click / wire:submit)
         | روی خود عنصر کلاس ap-clicking می‌گیرد (اسپینر + محو) و نوار
         | پیشرفت بالای صفحه فعال می‌شود؛ برای wire:navigate هم همین‌طور.
         | نوار فقط برای تعامل کاربر نشان داده می‌شود (نه wire:poll پس‌زمینه).
         -------------------------------------------------------------- */
        let pending = 0;
        let lastUserClickAt = 0;

        const getBar = () => {
            let el = document.getElementById('ap-top-progress');
            if (!el) {
                el = document.createElement('div');
                el.id = 'ap-top-progress';
                el.setAttribute('aria-hidden', 'true');
                document.body.appendChild(el);
            }
            return el;
        };

        /* فاز ۱۹ — لودر گرد سراسری در بالای پنل (هم‌گام با نوار پیشرفت) */
        const getSpinner = () => document.getElementById('ap-nav-spinner');
        const spinnerStart = () => getSpinner()?.classList.add('is-active');
        const spinnerFinish = () => getSpinner()?.classList.remove('is-active');

        /* شروع/ری‌استارت نوار: همیشه از صفر شروع می‌شود (فاز ۱۹ — کلیک دوم = ری‌استارت لودر) */
        const barStart = () => {
            const bar = getBar();
            bar.classList.remove('is-done', 'is-finishing');
            bar.style.transition = 'none';
            bar.style.width = '0';
            void bar.offsetWidth; // reflow — اعمال width=0 بدون ترنزیشن
            bar.style.transition = '';
            bar.classList.add('is-active');
            spinnerStart();
        };
        const barFinish = () => {
            const bar = getBar();
            bar.classList.remove('is-active');
            bar.classList.add('is-finishing');
            spinnerFinish();
            setTimeout(() => { bar.classList.add('is-done'); bar.classList.remove('is-finishing'); }, 180);
        };
        const clearClicking = () => {
            document.querySelectorAll('.ap-clicking').forEach((el) => el.classList.remove('ap-clicking'));
        };

        /* --------------------------------------------------------------
         | فاز ۱۹ (H3) — «آخرین کلیک برنده است»
         | کاربر روی «بنر جدید» کلیک می‌کند و قبل از لود، روی «لیست بنر»:
         | ناوبریِ اول هنوز در جریان است و Livewire آن را کنسل نمی‌کند —
         | هر پاسخی زودتر برسد صفحه را swap می‌کند (پاسخ کندِ اول می‌تواند
         | آخر باشد!).
         |
         | راه‌حل نهایی: promise هر fetch ناوبری (هدر X-Livewire-Navigate)
         | پشت یک «دروازه» قرار می‌گیرد. با شروع هر ناوبری تازه
         | (رویداد alpine:navigate) همه‌ی درخواست‌های ناوبری‌های قبلی با
         | URL متفاوت stale شده و fetch زیرین‌شان abort می‌شود — اما چون
         | promiseِ دروازه‌شده هرگز settle نمی‌شود، Livewire هرگز متوجه
         | abort نمی‌شود (نه swap قدیمی رخ می‌دهد نه fallbackREFETCH
         | whenFailed که در تست اول باعث «زنده‌شدن» صفحه‌ی قدیمی شد!).
         | نتیجه: همیشه آخرین کلیک برنده است.
         -------------------------------------------------------------- */
        const navFetchEntries = new Set();
        let navFetchPatched = false;
        const patchNavFetch = () => {
            if (navFetchPatched) return;
            navFetchPatched = true;
            const originalFetch = window.fetch.bind(window);
            window.fetch = function (input, init) {
                let uri = null;
                try {
                    const headers = init && init.headers;
                    let isNavigate = false;
                    if (headers) {
                        if (typeof Headers !== 'undefined' && headers instanceof Headers) {
                            isNavigate = headers.get('X-Livewire-Navigate') === '1';
                        } else if (typeof headers === 'object') {
                            isNavigate = headers['X-Livewire-Navigate'] === '1' || headers['x-livewire-navigate'] === '1';
                        }
                    }
                    if (!isNavigate) return originalFetch(input, init);

                    uri = typeof input === 'string' ? input : (input && input.url) ?? '';
                    const controller = new AbortController();
                    const theirSignal = init && init.signal;
                    if (theirSignal) {
                        if (theirSignal.aborted) controller.abort();
                        else theirSignal.addEventListener('abort', () => controller.abort(), { once: true });
                    }
                    const entry = { controller, uri, stale: false };
                    navFetchEntries.add(entry);

                    const real = originalFetch(input, { ...(init || {}), signal: controller.signal });
                    // دروازه: ناوبری stale هرگز settle نمی‌شود تا Livewire swap/fallback نزند
                    return new Promise((resolve, reject) => {
                        real.then(
                            (value) => {
                                if (entry.stale) return;
                                navFetchEntries.delete(entry);
                                resolve(value);
                            },
                            (error) => {
                                if (entry.stale) return;
                                navFetchEntries.delete(entry);
                                reject(error);
                            },
                        );
                    });
                } catch (e) { /* هر خطایی → fetch عادی */ }
                return originalFetch(input, init);
            };
        };
        patchNavFetch();

        // با شروع هر ناوبری تازه، درخواست‌های ناوبری‌های قبلی (با URL متفاوت) بی‌اثر می‌شوند
        document.addEventListener('alpine:navigate', (event) => {
            let currentUrl = null;
            try {
                const d = event.detail && event.detail.url;
                currentUrl = d ? new URL(d.href ?? String(d), window.location.href).href : null;
            } catch (e) { /* ignore */ }
            navFetchEntries.forEach((entry) => {
                if (currentUrl && entry.uri && new URL(entry.uri, window.location.href).href === currentUrl) return;
                entry.stale = true;
                try { entry.controller.abort(); } catch (e) { /* ignore */ }
                navFetchEntries.delete(entry);
            });
        });

        // کلیک روی لینک ناوبری — نوار/لودر فوراً از نو شروع می‌شود (فاز ۱۹)
        document.addEventListener('click', (event) => {
            if (!event.target.closest('a[wire\\:navigate]')) return;
            barStart();
        }, true);

        // ثبت کلیک فقط روی عناصر تعاملی واقعی (دکمه/لینک/سابمیت) — فاز ۱۷
        // قبلاً [wire:submit] خودِ <form> را می‌گرفت و کلیک روی اینپوت/متن داخل فرم،
        // کل فرم را محو و اسپینردار می‌کرد؛ حالا فقط دکمه ارسال اسپینر می‌گیرد.
        // عناصر غیردکمه‌ای با wire:click (مثل هدر آکاردئون) هم دیگر اسپینر نمی‌گیرند —
        // نوار پیشرفت بالای صفحه همچنان وضعیت درخواست را نشان می‌دهد.
        const BUSY_SELECTOR = [
            'button[wire\\:click]',
            'a[wire\\:click]',
            '[role="button"][wire\\:click]',
            'label[wire\\:click]',
            'form[wire\\:submit] button[type="submit"]',
            'form[wire\\:submit] button:not([type])',
            // دکمه‌های بیرون از فرم با اتریبیوت form= (مثل صفحه تنظیمات)
            'button[type="submit"][form]',
        ].join(', ');
        document.addEventListener('click', (event) => {
            const target = event.target.closest(BUSY_SELECTOR);
            if (!target) return;
            // اگر دکمه با form= به فرم متصل است، آن فرم باید wire:submit داشته باشد
            const formId = target.getAttribute('form');
            if (formId && ! document.getElementById(formId)?.hasAttribute('wire:submit')) return;
            // دکمه‌های disabled قبلاً wire:loading.attr=disabled دارند — کافی است
            if (target.disabled) return;
            lastUserClickAt = Date.now();
            clearClicking();
            target.classList.add('ap-clicking');
            // ایمنی: اگر commit پاسخ نداد، اسپینر حداکثر ۸ ثانیه می‌ماند
            setTimeout(() => target.classList.remove('ap-clicking'), 8000);
        }, true);

        // ناوبری wire:navigate — جدا از کامیت‌ها
        document.addEventListener('livewire:navigating', barStart);
        document.addEventListener('livewire:navigated', () => { barFinish(); clearClicking(); });

        // شمارش درخواست‌های فعال: نوار بالای صفحه + پاک‌سازی اسپینرها
        window.Livewire.hook('commit', ({ succeed, fail }) => {
            const isUserInitiated = (Date.now() - lastUserClickAt) < 2000;

            pending++;
            if (isUserInitiated) barStart();

            const done = () => {
                pending = Math.max(0, pending - 1);
                if (pending === 0) {
                    if (isUserInitiated) barFinish();
                    clearClicking();
                }
            };
            succeed(done);
            fail(done);
        });
    };

    if (window.Livewire) register();
    else document.addEventListener('livewire:init', register, { once: true });
})();

document.addEventListener('livewire:navigated', () => window.ApDnd.initAll());
document.addEventListener('DOMContentLoaded', () => window.ApDnd.initAll());

/* ------------------------------------------------------------------
 | novalidate خودکار برای فرم‌های دارای input ایمیل/URL — فاز ۱۴
 | اعتبارسنجی سمت Livewire انجام می‌شود؛ inputهای type=email/url با
 | داده‌های واقعی (مثلاً شماره در فیلد ایمیل) مانع submit بومی HTML5
 | می‌شدند و فرم هرگز ارسال نمی‌شد (باگ صفحه تنظیمات).
 ------------------------------------------------------------------ */
(function () {
    const applyNovalidate = () => {
        document.querySelectorAll('form').forEach((form) => {
            if (form.querySelector('input[type="email"], input[type="url"]')) {
                form.setAttribute('novalidate', '');
            }
        });
    };

    document.addEventListener('DOMContentLoaded', applyNovalidate);
    document.addEventListener('livewire:navigated', applyNovalidate);
})();
