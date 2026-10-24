<?php

namespace App\Livewire\Admin\Themes;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('تنظیمات قالب')]
class ThemeSettings extends Component
{
    /**
     * مقادیر تنظیمات (input/select/editor/inline-editor/file) — کلیدها عیناً کلیدهای options قالب.
     * فاز ۱۶: فیلدهای file هم مثل بقیه یک «مسیر رشته‌ای» هستند که با انتخابگر رسانه
     * (File Manager سایت) پر می‌شود — مطابق درخواست «تصاویر از فایل‌های خود سایت».
     */
    public array $settings = [];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('themes.settings') ?: abort(403);

        // پیش‌بارگذاری مقادیر فعلی از options
        foreach ($this->settingsFields() as $setting) {
            if (in_array($setting['input-type'] ?? '', ['input', 'editor', 'inline-editor', 'select', 'file'])) {
                $this->settings[$setting['key']] = (string) option($setting['key']);
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

        // عنوان فارسی فیلدهای فایل (برای لاگ)
        $fileFields = [];
        foreach ($settingsFields as $setting) {
            if (isset($setting['input-type']) && $setting['input-type'] === 'file') {
                $fileFields[$setting['key']] = $setting['title'] ?? $setting['key'];
            }
        }

        // ========== بررسی تغییرات همه تنظیمات (شامل مسیر فایل‌های انتخابی از رسانه‌ها) ==========
        $optionChanges = [];
        $fileChanges = [];

        foreach ($this->settings as $key => $value) {
            $oldValue = (string) option($key);

            if ($oldValue == $value) {
                continue;
            }

            $title = $this->getThemeFieldTitle($key, $settingsFields);

            $entry = [
                'old'   => $oldValue ?: 'خالی',
                'new'   => $value ?: 'خالی',
                'title' => $title,
            ];

            if (isset($fileFields[$key])) {
                // فایل جدید: فایل قبلی اگر داخل uploads/themes و متعلق به همین کلید بود حذف می‌شود
                if ($oldValue && str_starts_with($oldValue, '/uploads/themes/') && file_exists(public_path($oldValue))) {
                    @unlink(public_path($oldValue));
                }
                $fileChanges[$key] = $entry;
            } else {
                $optionChanges[$key] = $entry;
            }

            option_update($key, $value);
        }

        if (! empty($fileChanges)) {
            $allChanges['file_selected'] = $fileChanges;
        }

        if (! empty($optionChanges)) {
            $allChanges['option_settings'] = $optionChanges;
        }

        // ========== ثبت لاگ (پورت دقیق) ==========
        $changeDetails = [];

        if (isset($allChanges['file_selected'])) {
            foreach ($allChanges['file_selected'] as $change) {
                $changeDetails[] = "{$change['title']} تغییر کرد";
            }
        }

        if (isset($allChanges['option_settings'])) {
            foreach ($allChanges['option_settings'] as $change) {
                $changeDetails[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
            }
        }

        if (! empty($changeDetails)) {
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

        // پیش‌بارگذاری مجدد مقادیر
        foreach ($this->settingsFields() as $setting) {
            if (in_array($setting['input-type'] ?? '', ['input', 'editor', 'inline-editor', 'select', 'file'])) {
                $this->settings[$setting['key']] = (string) option($setting['key']);
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
