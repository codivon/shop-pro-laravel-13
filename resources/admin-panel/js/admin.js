/**
 * پنل مدیریت جدید — اسکریپت اصلی
 * Alpine (از Livewire) + تم روشن/تاریک + Toast + ابزارهای عمومی
 */

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
});

/* ------------------------------------------------------------------
 | آماده‌سازی Livewire (بدون هوک‌های سفارشی — امضای v4)
 ------------------------------------------------------------------ */
