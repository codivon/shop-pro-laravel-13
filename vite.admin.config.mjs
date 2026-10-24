import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

/**
 * Build استقلالی برای پنل مدیریت جدید (Livewire 4)
 * خروجی: public/admin-panel  (CSS و JS کامپایل‌شده و جدا از Blade)
 * پنل قدیمی با laravel-mix بدون تغییر کار می‌کند.
 */
export default defineConfig({
    plugins: [
        laravel({
            hotFile: 'admin-panel.hot',
            buildDirectory: 'admin-panel',
            input: [
                'resources/admin-panel/css/admin.css',
                'resources/admin-panel/js/admin.js',
            ],
            refresh: false,
        }),
        tailwindcss(),
    ],
    build: {
        // خروجی = public/admin-panel (manifest هم همین‌جا ساخته می‌شود)
        outDir: 'public/admin-panel',

        // فاز ۳۵ — خالی‌نکردن پوشه در هر بیلد:
        // فایل‌های هش‌شده‌ی بیلدهای قبلی «بمانند» تا HTML کش‌شده (مرورگر/CDN)
        // که هنوز به هش قدیمی اشاره می‌کند، خطای 404 + MIME text/html نگیرد.
        // (گزارش کاربر: admin-COa1hQtO.css بعد از بیلد جدید پاک شده بود)
        emptyOutDir: false,

        // مرورگرهای مدرن — Tailwind CSS v4 و CSS nesting به همین حداقل نیاز دارند.
        // با این تارگت، esbuild دیگر ۷۲ اخطار unsupported-css-nesting نمی‌دهد.
        target: ['chrome112', 'edge112', 'firefox117', 'safari16.5'],

        // چانک ckeditor5 به‌صورت lazy و جداگانه لود می‌شود (~2.2MB مخصوص CKEditor 5 است)
        chunkSizeWarningLimit: 2600,

        // بدون sourcemap در پروداکشن + حجم gzip محاسبه نشود (بیلد سریع‌تر و لاگ تمیزتر)
        sourcemap: false,
        reportCompressedSize: false,
    },
});
