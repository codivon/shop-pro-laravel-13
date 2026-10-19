<?php

namespace App\Livewire\Admin\Themes;

use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('تنظیمات قالب')]
class ThemeSettings extends Component
{
    use WithFileUploads;

    /** مقادیر تنظیمات (input/select/editor/inline-editor) — کلیدها عیناً کلیدهای options قالب */
    public array $settings = [];

    /** فایل‌های تنظیمات (file) — کلید => TemporaryUploadedFile */
    public array $settingFiles = [];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('themes.settings') ?: abort(403);

        // پیش‌بارگذاری مقادیر فعلی از options
        foreach ($this->settingsFields() as $setting) {
            if (in_array($setting['input-type'] ?? '', ['input', 'editor', 'inline-editor', 'select'])) {
                $this->settings[$setting['key']] = option($setting['key']);
            }
        }
    }

    /** فیلدهای تنظیمات قالب فعال (config('front.settings.fields')) */
    public function settingsFields(): array
    {
        return config('front.settings.fields', []);
    }

    /* ---------------- ذخیره (پورت ۱:۱ updateSettings قدیمی) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('themes.settings') ?: abort(403);

        $allChanges = [];
        $admin = auth('adminPanel')->user();
        $adminName = $admin->full_name ?? $admin->name ?? 'مدیر';

        $settingsFields = $this->settingsFields();

        // فیلدهای فایل
        $fileFields = [];
        foreach ($settingsFields as $setting) {
            if (isset($setting['input-type']) && $setting['input-type'] === 'file') {
                $fileFields[$setting['key']] = $setting['title'] ?? $setting['key'];
            }
        }

        // ========== 1. بررسی تغییرات فایل‌ها ==========
        $fileChanges = [];
        foreach ($fileFields as $key => $title) {
            $file = $this->settingFiles[$key] ?? null;

            if ($file) {
                // امنیتی: اعتبارسنجی نوع/حجم فایل آپلودی (تصویر) — جلوگیری از آپلود فایل اجرایی
                $this->validate(
                    ['settingFiles.'.$key => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'extensions:jpg,jpeg,png,webp,gif,svg', 'max:8192']],
                    [],
                    ['settingFiles.'.$key => $title]
                );

                $oldFile = option($key);

                $imageName = time() . '_' . $key . '.' . strtolower($file->getClientOriginalExtension());
                $file->move(public_path('uploads/themes/'), $imageName);

                if ($oldFile && file_exists(public_path($oldFile))) {
                    unlink(public_path($oldFile));
                }

                $newFile = '/uploads/themes/' . $imageName;
                option_update($key, $newFile);

                $fileChanges[$key] = [
                    'old'   => $oldFile ?: 'بدون فایل',
                    'new'   => $newFile,
                    'title' => $title,
                ];
            }
        }

        if (!empty($fileChanges)) {
            $allChanges['file_uploads'] = $fileChanges;
        }

        // ========== 2. بررسی تغییرات تنظیمات ==========
        $optionChanges = [];

        foreach ($this->settings as $key => $value) {
            // اگر فیلد فایل است، قبلاً بررسی شده
            if (isset($fileFields[$key])) {
                continue;
            }

            $oldValue = option($key);

            if ($oldValue != $value) {
                $optionChanges[$key] = [
                    'old'   => $oldValue ?: 'خالی',
                    'new'   => $value ?: 'خالی',
                    'title' => $this->getThemeFieldTitle($key, $settingsFields),
                ];
            }

            option_update($key, $value);
        }

        if (!empty($optionChanges)) {
            $allChanges['option_settings'] = $optionChanges;
        }

        // ========== 3. ثبت لاگ (پورت دقیق) ==========
        $changeDetails = [];

        if (isset($allChanges['file_uploads'])) {
            foreach ($allChanges['file_uploads'] as $change) {
                $changeDetails[] = "{$change['title']} آپلود شد";
            }
        }

        if (isset($allChanges['option_settings'])) {
            foreach ($allChanges['option_settings'] as $change) {
                $changeDetails[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
            }
        }

        if (!empty($changeDetails)) {
            $logMessage = "مدیر {$adminName} تنظیمات قالب را به‌روزرسانی کرد: " . implode('، ', $changeDetails);

            activity()
                ->causedBy($admin)
                ->withProperties([
                    'action'  => 'تنظیمات قالب را به‌روزرسانی کرد',
                    'changes' => $allChanges,
                    'ip'      => request()->ip(),
                ])
                ->log($logMessage);
        }

        // پیش‌بارگذاری مجدد (فایل‌ها پس از آپلود خالی شوند)
        $this->reset('settingFiles');
        foreach ($this->settingsFields() as $setting) {
            if (in_array($setting['input-type'] ?? '', ['input', 'editor', 'inline-editor', 'select'])) {
                $this->settings[$setting['key']] = option($setting['key']);
            }
        }

        $this->dispatch('toast', message: 'تنظیمات قالب با موفقیت ذخیره شد.', type: 'success');
    }

    /**
     * دریافت عنوان فارسی فیلدهای تنظیمات قالب (پورت دقیق getThemeFieldTitle قدیمی)
     */
    private function getThemeFieldTitle($key, $settingsFields)
    {
        foreach ($settingsFields as $setting) {
            if (isset($setting['key']) && $setting['key'] === $key) {
                return $setting['title'] ?? $key;
            }
        }

        // ترجمه فیلدهای رایج
        $titles = [
            // تنظیمات نمایش
            'dt_show_price_change_chart'     => 'نمایش نمودار تغییرات قیمت محصول',
            'show_product_share_links'       => 'نمایش لینک‌های اشتراک‌گذاری محصول',
            'dt_product_reviews_description' => 'متن توضیحات بخش نظرات محصول',

            // تنظیمات پاپ آپ
            'dt_index_popup_image'        => 'تصویر پاپ آپ',
            'dt_index_popup_image_mobile' => 'تصویر پاپ آپ (نسخه موبایل)',
            'dt_index_popup_link'         => 'لینک تصویر پاپ آپ',
            'dt_index_popup_text'         => 'متن پاپ آپ',

            // تنظیمات صفحه تماس
            'dt_show_form_in_contact'            => 'نمایش فرم تماس',
            'dt_show_map_in_contact'             => 'نمایش نقشه در صفحه تماس',
            'dt_show_contact_top_description'    => 'نمایش توضیحات بالای صفحه تماس',
            'dt_show_contact_bottom_description' => 'نمایش توضیحات پایین صفحه تماس',
            'dt_contact_bottom_description'      => 'متن توضیحات پایین صفحه تماس',

            // تنظیمات هدر و فوتر
            'dt_show_top_banner'  => 'نمایش بنر بالای صفحه',
            'dt_show_bottom_banner' => 'نمایش بنر پایین صفحه',
            'dt_header_style'     => 'استایل هدر',
            'dt_footer_style'     => 'استایل فوتر',

            // تنظیمات رنگ
            'dt_primary_color'   => 'رنگ اصلی قالب',
            'dt_secondary_color' => 'رنگ ثانویه قالب',
            'dt_header_color'    => 'رنگ هدر',
            'dt_footer_color'    => 'رنگ فوتر',
        ];

        return $titles[$key] ?? str_replace('_', ' ', $key);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.themes.settings')
            ->layout('admin-panel::layout');
    }
}
