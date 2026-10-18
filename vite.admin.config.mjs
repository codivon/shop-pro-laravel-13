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
        emptyOutDir: true,
    },
});
