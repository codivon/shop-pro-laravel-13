<?php

namespace App\Livewire\Admin\Themes;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('لیست قالب‌ها')]
class ThemeIndex extends Component
{
    /** لیست تم‌ها (name + config) — پورت index قدیمی */
    public array $themes = [];

    /** نام قالب در حال حذف (مودال تایید) */
    public ?string $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('themes.index') ?: abort(403);

        $this->loadThemes();
    }

    /* ---------------- لیست (پورت ۱:۱ index قدیمی) ---------------- */

    public function loadThemes(): void
    {
        $dirs = Storage::disk('themes')->directories();

        $themes = [];
        foreach ($dirs as $dir) {
            $themes[] = [
                'name'   => $dir,
                'config' => customConfig(base_path() . '/themes/' . $dir . '/config/general.php'),
            ];
        }

        $this->themes = $themes;
    }

    /** نام قالب فعال (پورت get_current_theme قدیمی) */
    public function getCurrentThemeProperty(): ?array
    {
        return get_current_theme();
    }

    /* ---------------- فعال‌سازی (پورت ۱:۱ update قدیمی) ---------------- */

    public function activate(string $theme): void
    {
        auth('adminPanel')->user()?->can('themes.update') ?: abort(403);

        // امنیتی: جلوگیری از path-traversal در نام قالب
        if (!is_safe_extension_name($theme)) {
            $this->dispatch('toast', message: 'نام قالب معتبر نیست.', type: 'error');
            return;
        }

        if (!Storage::disk('themes')->exists($theme)) {
            $this->dispatch('toast', message: 'قالب پیدا نشد.', type: 'error');
            return;
        }

        $targetPath = base_path("themes/{$theme}/src/resources/assets"); // مسیر اصلی فایل‌ها
        $linkPath = public_path("themes/{$theme}");       // مسیر شورت‌کات

        // اگر لینک وجود ندارد، ایجاد کن (پورت دقیق قدیمی)
        if (!File::exists($linkPath)) {
            if (!File::exists(public_path('themes'))) {
                File::makeDirectory(public_path('themes'), 0755, true);
            }

            File::link($targetPath, $linkPath);
        }

        change_env('CURRENT_THEME', $theme);

        $this->loadThemes();

        $this->dispatch('toast', message: 'قالب «' . $theme . '» به عنوان قالب فعال تنظیم شد.', type: 'success');
    }

    /* ---------------- حذف (پورت ۱:۱ destroy قدیمی) ---------------- */

    public function confirmDelete(string $theme): void
    {
        auth('adminPanel')->user()?->can('themes.delete') ?: abort(403);

        // امنیتی: نام قالب باید معتبر باشد
        if (!is_safe_extension_name($theme)) {
            $this->dispatch('toast', message: 'نام قالب معتبر نیست.', type: 'error');
            return;
        }

        $this->confirmingDelete = $theme;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('themes.delete') ?: abort(403);

        $theme = (string) $this->confirmingDelete;
        $this->confirmingDelete = null;

        if ($theme === '') {
            return;
        }

        if (!Storage::disk('themes')->exists($theme)) {
            $this->dispatch('toast', message: 'قالب پیدا نشد.', type: 'error');
            return;
        }

        $config = customConfig(base_path() . '/themes/' . $theme . '/config/general.php');

        // گارد: قالب فعال حذف نشود (قدیمی اجازه می‌داد و پنل را می‌شکست)
        if ((get_current_theme()['name'] ?? null) === $theme) {
            $this->dispatch('toast', message: 'قالب فعال قابل حذف نیست؛ ابتدا قالب دیگری را فعال کنید.', type: 'error');
            return;
        }

        Storage::disk('themes')->deleteDirectory($theme);

        // امنیتی: جلوگیری از پاک‌شدن کل دیسک وقتی asset_path خالی/نامعتبر است
        $assetPath = is_array($config) ? ($config['asset_path'] ?? '') : '';
        if (is_string($assetPath) && $assetPath !== '' && !str_contains($assetPath, '..')) {
            Storage::disk('public')->deleteDirectory($assetPath);
        }

        $this->loadThemes();

        $this->dispatch('toast', message: 'قالب با موفقیت حذف شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.themes.index')
            ->layout('admin-panel::layout');
    }
}
