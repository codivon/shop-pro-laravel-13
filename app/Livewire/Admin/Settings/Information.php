<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Province;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * تنظیمات کلی سایت — پورت ۱:۱ Back\SettingController::showInformation/updateInformation
 * (فاز ۷-ج۳) + ویوی back/settings/information.blade.php
 *
 * کلیدهای options دقیقاً همان قدیمی‌ها هستند (info_* / map_api / multi_language_enabled).
 *
 * تفاوت‌های مستندشده با قدیمی:
 *  1. آپلود تصاویر (info_icon/info_logo/info_logo_seller/info_logo_panel_seller/info_og_image)
 *     در قدیمی کامنت شده بود و هرگز ذخیره نمی‌شد — طبق اسپک فاز ۷ با WithFileUploads +
 *     uploadOptimizedImage (الگوی updateOthers برای factor_logo) فعال شد.
 *  2. پس از تغییر admin_route_prefix، قدیمی Artisan::call('route:cache') اجرا می‌کرد؛
 *     route:cache با روت‌های closure و Livewire ناسازگار است (اسپک فاز ۷) → فقط change_env.
 */
#[Title('تنظیمات کلی')]
class Information extends Component
{
    use WithFileUploads;

    /* ---------------- اطلاعات پایه ---------------- */

    public string $info_site_title = '';

    public string $info_email = '';

    public string $info_tel = '';

    public string $info_fax = '';

    public string $info_postal_code = '';

    public string $info_support_phone = '';

    public string $info_working_hours = '';

    public string $info_primary_color = '#6366f1';

    public string $info_primary_color_text = '#6366f1';

    public string $info_province_id = '';

    public string $info_city_id = '';

    public string $info_address = '';

    /* ---------------- تصاویر و لوگو ---------------- */

    /** فایل‌های جدید (Livewire TemporaryUploadedFile) */
    public $info_icon;

    public $info_logo;

    public $info_logo_seller;

    public $info_logo_panel_seller;

    public $info_og_image;

    /** مسیرهای فعلی (برای پیش‌نمایش) — یک‌بار در mount خوانده می‌شود */
    public string $current_info_icon = '';

    public string $current_info_logo = '';

    public string $current_info_logo_seller = '';

    public string $current_info_logo_panel_seller = '';

    public string $current_info_og_image = '';

    /* ---------------- پیکربندی پیشرفته ---------------- */

    public string $admin_route_prefix = '';

    public string $multi_language_enabled = '0';

    /* ---------------- سئو و متا ---------------- */

    public string $info_tags = '';

    public string $info_canonical = '';

    public string $info_short_description = '';

    public string $info_footer_text = '';

    public string $info_robots_txt = '';

    public string $info_enamad = '';

    public string $info_samandehi = '';

    /* ---------------- اسکریپت‌ها ---------------- */

    public string $info_gtm_id = '';

    public string $info_meta_pixel = '';

    public string $info_header_codes = '';

    public string $info_scripts = '';

    /* ---------------- نقشه ---------------- */

    public string $info_latitude = '38.07709880960678';

    public string $info_Longitude = '46.28582686185837';

    public string $info_map_zoom = '13';

    public string $map_api = '';

    public string $info_map_type = 'mapir';

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('settings.information') ?: abort(403);

        // مقادیر یک‌بار در mount از option() خوانده می‌شوند و در پراپرتی‌های public می‌مانند؛
        // ذخیره با option_update کش option را invalidه می‌کند (Cache::forever با مقدار جدید) — حباب کش نمی‌سازد.
        $this->info_site_title = (string) option('info_site_title');
        $this->info_email = (string) option('info_email');
        $this->info_tel = (string) option('info_tel');
        $this->info_fax = (string) option('info_fax');
        $this->info_postal_code = (string) option('info_postal_code');
        $this->info_support_phone = (string) option('info_support_phone');
        $this->info_working_hours = (string) option('info_working_hours');
        $this->info_primary_color = (string) (option('info_primary_color') ?: '#6366f1');
        $this->info_primary_color_text = (string) (option('info_primary_color_text') ?: $this->info_primary_color);
        $this->info_province_id = (string) option('info_province_id');
        $this->info_city_id = (string) option('info_city_id');
        $this->info_address = (string) option('info_address');

        $this->current_info_icon = (string) option('info_icon');
        $this->current_info_logo = (string) option('info_logo');
        $this->current_info_logo_seller = (string) option('info_logo_seller');
        $this->current_info_logo_panel_seller = (string) option('info_logo_panel_seller');
        $this->current_info_og_image = (string) option('info_og_image');

        $this->admin_route_prefix = (string) config('general.admin_route_prefix');
        $this->multi_language_enabled = (string) option('multi_language_enabled', '0');

        $this->info_tags = (string) option('info_tags');
        $this->info_canonical = (string) option('info_canonical');
        $this->info_short_description = (string) option('info_short_description');
        $this->info_footer_text = (string) option('info_footer_text');
        $this->info_robots_txt = (string) option('info_robots_txt', "User-agent: *\nAllow: /");
        $this->info_enamad = (string) option('info_enamad');
        $this->info_samandehi = (string) option('info_samandehi');

        $this->info_gtm_id = (string) option('info_gtm_id');
        $this->info_meta_pixel = (string) option('info_meta_pixel');
        $this->info_header_codes = (string) option('info_header_codes');
        $this->info_scripts = (string) option('info_scripts');

        $this->info_latitude = (string) (option('info_latitude') ?: '38.07709880960678');
        $this->info_Longitude = (string) (option('info_Longitude') ?: '46.28582686185837');
        $this->info_map_zoom = (string) (option('info_map_zoom') ?: '13');
        $this->map_api = (string) option('map_api');
        $this->info_map_type = (string) (option('info_map_type') ?: 'mapir');
    }

    /** شهرهای استان انتخاب‌شده (پورت showInformation) */
    public function getCitiesProperty(): array
    {
        if (! $this->info_province_id) {
            return [];
        }

        $province = Province::find($this->info_province_id);

        return $province ? $province->cities->all() : [];
    }

    public function provinces()
    {
        return Province::detectLang()->get();
    }

    public function updatedInfoProvinceId(): void
    {
        // تغییر استان → شهر ریست شود (آبشار قدیمی با ajax provinces.get-cities)
        $this->info_city_id = '';
    }

    /* ---------------- ذخیره (پورت updateInformation) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('settings.information') ?: abort(403);

        $this->validate([
            'info_site_title' => 'required',
            'info_icon' => 'nullable|max:2048',
            'info_logo' => 'nullable|max:2048',
            'info_logo_seller' => 'nullable|max:2048',
            'info_logo_panel_seller' => 'nullable|max:2048',
            'info_og_image' => 'nullable|max:2048',
            'info_city_id' => 'nullable|exists:cities,id',
            'info_province_id' => 'nullable|exists:provinces,id',
        ], [
            'info_site_title.required' => 'عنوان وبسایت الزامی است.',
            'info_icon.max' => 'حجم آیکون سایت نباید بیشتر از ۲ مگابایت باشد.',
            'info_logo.max' => 'حجم لوگو نباید بیشتر از ۲ مگابایت باشد.',
            'info_logo_seller.max' => 'حجم لوگوی فروشندگان نباید بیشتر از ۲ مگابایت باشد.',
            'info_logo_panel_seller.max' => 'حجم لوگوی پنل فروشندگان نباید بیشتر از ۲ مگابایت باشد.',
            'info_og_image.max' => 'حجم تصویر OG نباید بیشتر از ۲ مگابایت باشد.',
            'info_city_id.exists' => 'شهر انتخاب‌شده معتبر نیست.',
            'info_province_id.exists' => 'استان انتخاب‌شده معتبر نیست.',
        ], [
            'info_site_title' => 'عنوان وبسایت',
        ]);

        $allChanges = [];
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        // ========== 1. آپلود فایل‌ها (در قدیمی کامنت بود — طبق اسپک فعال شد) ==========
        $fileInputs = [
            'info_icon' => 'آیکون سایت',
            'info_logo' => 'لوگوی شرکت',
            'info_logo_seller' => 'لوگوی شرکت فروشندگان',
            'info_logo_panel_seller' => 'لوگوی پنل فروشندگان',
            'info_og_image' => 'تصویر OG (اشتراک‌گذاری)',
        ];

        $fileChanges = [];
        foreach ($fileInputs as $key => $title) {
            if ($this->{$key}) {
                $oldFile = option($key);

                $filename = uploadOptimizedImage($this->{$key}, 'options', $key, [
                    'watermark' => false,
                    'path' => 'uploads/others/',
                    'field' => 'option_value',
                    'format' => 'png',
                ]);
                option_update($key, $filename);

                // به‌روزرسانی مسیر فعلی برای پیش‌نمایش
                $this->{'current_'.$key} = $filename;
                $this->{$key} = null;

                $fileChanges[$key] = [
                    'old' => $oldFile ?: 'بدون فایل',
                    'new' => $filename,
                    'title' => $title,
                ];
            }
        }

        if (! empty($fileChanges)) {
            $allChanges['file_uploads'] = $fileChanges;
        }

        // ========== 2. تغییرات env (پیشوند ادمین) ==========
        $admin_route_prefix_changed = false;
        // نکته: مقایسه با config (نه env) — وقتی کانفیگ کش شده است env() مقدار نمی‌دهد
        // و باعث change_env→config:cache در هر ذخیره می‌شد.
        $oldAdminRoutePrefix = config('general.admin_route_prefix');
        $newAdminRoutePrefix = $this->admin_route_prefix;

        if ($oldAdminRoutePrefix != $newAdminRoutePrefix) {
            $admin_route_prefix_changed = true;
            change_env('ADMIN_ROUTE_PREFIX', $newAdminRoutePrefix);
            // قدیمی: Artisan::call('route:cache') — با Livewire/روت‌های closure ناسازگار است؛ اجرا نمی‌شود (اسپک فاز ۷)

            $allChanges['env_settings'] = [
                'admin_route_prefix' => [
                    'old' => $oldAdminRoutePrefix ?: 'admin',
                    'new' => $newAdminRoutePrefix,
                    'title' => 'پیشوند آدرس مدیریت',
                ],
            ];

            $this->admin_route_prefix = (string) config('general.admin_route_prefix');
        }

        // ========== 3. ذخیره‌ی options (پورت حلقه‌ی request->all قدیمی) ==========
        $optionKeys = [
            'info_site_title', 'info_email', 'info_tel', 'info_fax', 'info_postal_code',
            'info_support_phone', 'info_working_hours', 'info_primary_color',
            'info_primary_color_text', 'info_province_id', 'info_city_id', 'info_address',
            'multi_language_enabled',
            'info_tags', 'info_canonical', 'info_short_description', 'info_footer_text',
            'info_robots_txt', 'info_enamad', 'info_samandehi',
            'info_gtm_id', 'info_meta_pixel', 'info_header_codes', 'info_scripts',
            'info_latitude', 'info_Longitude', 'info_map_zoom', 'map_api', 'info_map_type',
        ];

        $optionChanges = [];
        foreach ($optionKeys as $key) {
            $value = (string) ($this->{$key} ?? '');
            $oldValue = option($key);

            if ($oldValue != $value) {
                $optionChanges[$key] = [
                    'old' => $oldValue ?: 'خالی',
                    'new' => $value ?: 'خالی',
                    'title' => $this->getInformationFieldTitle($key),
                ];
            }

            option_update($key, $value);
        }

        if (! empty($optionChanges)) {
            $allChanges['option_settings'] = $optionChanges;
        }

        // ========== 4. لاگ نهایی (پورت دقیق) ==========
        if (! empty($allChanges)) {
            $logMessage = "مدیر {$adminName} تنظیمات اطلاعات کلی سایت را به‌روزرسانی کرد: ";
            $changeDetails = [];

            if (isset($allChanges['file_uploads'])) {
                foreach ($allChanges['file_uploads'] as $change) {
                    $changeDetails[] = "{$change['title']} آپلود شد";
                }
            }

            if (isset($allChanges['env_settings'])) {
                foreach ($allChanges['env_settings'] as $change) {
                    $changeDetails[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
                }
            }

            if (isset($allChanges['option_settings']) && ! empty($allChanges['option_settings'])) {
                foreach ($allChanges['option_settings'] as $change) {
                    $changeDetails[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
                }
            }

            $logMessage .= implode('، ', $changeDetails);

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'تنظیمات اطلاعات کلی سایت را به‌روزرسانی کرد',
                    'changes' => $allChanges,
                    'ip' => request()->ip(),
                ])
                ->log($logMessage);
        }

        if ($admin_route_prefix_changed) {
            $this->dispatch('toast', message: 'پیشوند آدرس مدیریت تغییر کرد. برای اعمال کامل، کش مسیرها/کانفیگ سرور باید تازه شود.', type: 'warning');
        }

        $this->dispatch('toast', message: 'تنظیمات کلی سایت ذخیره شد.', type: 'success');
    }

    /** پورت دقیق getInformationFieldTitle قدیمی */
    private function getInformationFieldTitle($key)
    {
        $titles = [
            'info_site_title' => 'عنوان وبسایت',
            'info_email' => 'ایمیل',
            'info_tel' => 'تلفن',
            'info_fax' => 'فکس',
            'info_postal_code' => 'کد پستی',
            'info_support_phone' => 'شماره پشتیبانی',
            'info_working_hours' => 'ساعات کاری',
            'info_primary_color' => 'رنگ اصلی سایت',
            'info_primary_color_text' => 'رنگ اصلی سایت (متن)',
            'info_address' => 'آدرس',
            'info_province_id' => 'استان',
            'info_city_id' => 'شهر',
            'info_icon' => 'آیکون سایت',
            'info_logo' => 'لوگوی شرکت',
            'info_logo_seller' => 'لوگوی شرکت فروشندگان',
            'info_logo_panel_seller' => 'لوگوی پنل فروشندگان',
            'info_og_image' => 'تصویر OG (اشتراک‌گذاری)',
            'info_tags' => 'کلمات کلیدی',
            'info_canonical' => 'آدرس کانونیکال',
            'info_short_description' => 'توضیحات کوتاه (Meta Description)',
            'info_footer_text' => 'متن فوتر',
            'info_robots_txt' => 'Robots.txt',
            'info_gtm_id' => 'شناسه Google Tag Manager',
            'info_meta_pixel' => 'شناسه Meta Pixel',
            'info_header_codes' => 'کدهای هدر',
            'info_scripts' => 'اسکریپت‌های اضافه',
            'info_enamad' => 'اسکریپت نماد اعتماد',
            'info_samandehi' => 'کد ساماندهی',
            'info_latitude' => 'عرض جغرافیایی (Latitude)',
            'info_Longitude' => 'طول جغرافیایی (Longitude)',
            'info_map_zoom' => 'سطح زوم نقشه',
            'map_api' => 'کلید API نقشه (map.ir)',
            'info_map_type' => 'نوع نقشه',
            'admin_route_prefix' => 'پیشوند آدرس ورود به بخش مدیریت',
            'multi_language_enabled' => 'فعال کردن سایت چند زبانه',
            'info_copyright_text' => 'متن کپی‌رایت',
            'info_terms_url' => 'لینک قوانین و مقررات',
            'info_privacy_url' => 'لینک حریم خصوصی',
            'info_about_us' => 'درباره ما',
            'info_contact_us' => 'تماس با ما',
        ];

        return $titles[$key] ?? $this->humanReadableKey($key);
    }

    /** پورت دقیق humanReadableKey قدیمی */
    private function humanReadableKey($key)
    {
        $cleanKey = str_replace('info_', '', $key);
        $cleanKey = str_replace('_', ' ', $cleanKey);

        return ucwords($cleanKey);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.settings.information')
            ->layout('admin-panel::layout');
    }
}
