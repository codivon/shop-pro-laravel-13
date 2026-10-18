/**
 * پنل مدیریت جدید — اسکریپت اصلی
 * Alpine (از Livewire) + تم روشن/تاریک + Toast + CKEditor + Jalali DatePicker + ابزارها
 */

/* ---------- کتابخانه‌های شخص ثالث ---------- */
import 'ckeditor5/ckeditor5.css';
import '@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css';
import '@majidh1/jalalidatepicker';
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
 | CKEditor 5 — ویرایشگر متن غنی توضیحات
 | <div data-editor-value="..."> → جایگزین textarea قدیمی
 ------------------------------------------------------------------ */
window.ApEditor = {
    instance: null,

    async init() {
        const container = document.querySelector('#ap-ckeditor[data-editor-value]:not([data-ap-ready])');

        if (!container || !window.Livewire) return;

        container.setAttribute('data-ap-ready', '1');

        try {
            const { ClassicEditor, Essentials, Paragraph, Heading, Bold, Italic, Underline,
                Strikethrough, BlockQuote, Link, List, Alignment,
                Table, TableToolbar, MediaEmbed, PasteFromOffice, Undo, RemoveFormat } =
                await import('ckeditor5');

            const editor = await ClassicEditor.create(container, {
                licenseKey: 'GPL',
                plugins: [Essentials, Paragraph, Heading, Bold, Italic, Underline, Strikethrough,
                    BlockQuote, Link, List, Alignment, Table, TableToolbar,
                    MediaEmbed, PasteFromOffice, Undo, RemoveFormat],
                toolbar: ['heading', '|', 'bold', 'italic', 'underline', 'strikethrough', '|',
                    'link', 'bulletedList', 'numberedList', '|', 'alignment', 'blockQuote',
                    'insertTable', 'mediaEmbed', '|', 'undo', 'redo', 'removeFormat'],
                language: { ui: 'en', content: 'fa' },
                heading: {
                    options: [
                        { model: 'paragraph', title: 'متن', class: 'ck-heading_paragraph' },
                        { model: 'heading2', view: 'h2', title: 'تیتر ۲', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: 'h3', title: 'تیتر ۳', class: 'ck-heading_heading3' },
                        { model: 'heading4', view: 'h4', title: 'تیتر ۴', class: 'ck-heading_heading4' },
                    ],
                },
                table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] },
            });

            editor.setData(container.dataset.editorValue || '');
            this.instance = editor;

            // همگام‌سازی محتوا با Livewire
            const componentEl = container.closest('[wire\\:id]');
            const component = componentEl ? window.Livewire.find(componentEl.getAttribute('wire:id')) : null;

            editor.model.document.on('change:data', () => {
                const data = editor.getData();
                if (component) component.$wire.description = data;
                const input = document.querySelector('#ap-ckeditor-input');
                if (input) input.value = data;
            });
        } catch (error) {
            console.error('CKEditor init failed:', error);
        }
    },
};

document.addEventListener('DOMContentLoaded', () => ApEditor.init());
document.addEventListener('livewire:navigated', () => ApEditor.init());

/* ------------------------------------------------------------------
 | ECharts — راه‌انداز نمودارها (فارسی/RTL)
 | <div data-chart="..." data-options="JSON"> — مقدار «fn:name» به فرمت‌کننده تبدیل می‌شود
 ------------------------------------------------------------------ */
window.ApChart = {
    instances: {},

    formatters: {
        faNum: (v) => (typeof v === 'number' ? ApFormat.number(v) : ApFormat.faDigits(v ?? '')),
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
                const t = { ...e.detail, visible: false };
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

    /* سایدبار موبایل */
    Alpine.data('apSidebar', () => ({
        open: false,
        search: '',
        init() {
            window.addEventListener('ap-close-sidebar', () => { this.open = false; });
        },
    }));

    /* آکاردئون گروه منو */
    Alpine.data('apMenuGroup', (openByDefault = false) => ({
        open: openByDefault,
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
});

/* ------------------------------------------------------------------
 | آماده‌سازی Livewire (بدون هوک‌های سفارشی — امضای v4)
 ------------------------------------------------------------------ */
