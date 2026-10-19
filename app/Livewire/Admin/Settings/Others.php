<?php

namespace App\Livewire\Admin\Settings;

use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * تنظیمات دیگر — پورت ۱:۱ Back\SettingController::showOthers/updateOthers
 * + ویوی back/settings/others.blade.php
 *
 * - envهای نوشتنی: PUSHER_APP_ID/KEY/SECRET/CLUSTER + MAIL_HOST/PORT/USERNAME/PASSWORD/ENCRYPTION
 *   (+ BROADCAST_DRIVER خودکار بر اساس کامل بودن pusher — پورت دقیق)
 * - ⚠️ quirk پورت‌شده: فیلد name="transport" در ویوی قدیمی جزو env_options نیست →
 *   به‌صورت option با کلید transport ذخیره می‌شود (عیناً مثل قدیمی).
 * - فایل‌ها: factor_logo / watermarkImage با uploadOptimizedImage (سایز ۱۰۰ + png + uploads/others/)
 * - انحراف کوچک: قدیمی در حلقه‌ی env هر کلید را بی‌قید change_env می‌کرد؛ اینجا هم همان است ولی
 *   فقط برای کلیدهای واقعاً موجود در فرم (MAIL_MAILER/MAIL_TRANSPORT در فرم قدیمی هم نبودند و
 *   $request->only آن‌ها را نمی‌آورد — رفتار یکسان ماند).
 */
#[Title('تنظیمات دیگر')]
class Others extends Component
{
    use WithFileUploads;

    /* ---------------- چند فروشندگی ---------------- */

    public string $multi_vendor_system_status = 'false';

    /* ---------------- فاکتور ---------------- */

    public string $factor_title = '';

    public string $factor_seller_name = '';

    public string $factor_national_code = '';

    public string $factor_registeration_id = '';

    public string $factor_economical_number = '';

    public $factor_logo;

    public string $current_factor_logo = '';

    /* ---------------- کاربران / معرفی ---------------- */

    public string $user_register_gift_credit = '0';

    public string $user_referrals_enable = 'false';

    public string $user_referrals_gift_type = 'discount_code';

    public string $user_referrals_gift_discount_type = 'percent';

    public string $owner_referrals_amount = '';

    public string $user_referrals_amount = '';

    public string $minimum_amount_gift = '0';

    public string $minimum_product_gift = '1';

    /* ---------------- تصاویر ---------------- */

    public string $optimizeImage = '10';

    public string $changePhotoFormat = 'webp';

    public string $watermarkStatus = 'false';

    public $watermarkImage;

    public string $current_watermarkImage = '';

    public string $watermarkImagePosition = 'bottom-right';

    /* ---------------- Pusher (env) ---------------- */

    public string $PUSHER_APP_ID = '';

    public string $PUSHER_APP_KEY = '';

    public string $PUSHER_APP_SECRET = '';

    public string $PUSHER_APP_CLUSTER = '';

    /* ---------------- Mail (env) ---------------- */

    public string $transport = '';

    public string $MAIL_HOST = '';

    public string $MAIL_PORT = '';

    public string $MAIL_USERNAME = '';

    public string $MAIL_PASSWORD = '';

    public string $MAIL_ENCRYPTION = '';

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('settings.others') ?: abort(403);

        // option ها — یک‌بار در mount خوانده می‌شوند
        $this->multi_vendor_system_status = (string) (option('multi_vendor_system_status') ?: 'false');

        $this->factor_title = (string) (option('factor_title') ?: option('info_site_title'));
        $this->factor_seller_name = (string) option('factor_seller_name');
        $this->factor_national_code = (string) option('factor_national_code');
        $this->factor_registeration_id = (string) option('factor_registeration_id');
        $this->factor_economical_number = (string) option('factor_economical_number');
        $this->current_factor_logo = (string) option('factor_logo');

        $this->user_register_gift_credit = (string) (option('user_register_gift_credit', 0) ?: 0);
        $this->user_referrals_enable = (string) (option('user_referrals_enable') ?: 'false');
        $this->user_referrals_gift_type = (string) (option('user_referrals_gift_type') ?: 'discount_code');
        $this->user_referrals_gift_discount_type = (string) (option('user_referrals_gift_discount_type') ?: 'percent');
        $this->owner_referrals_amount = (string) option('owner_referrals_amount');
        $this->user_referrals_amount = (string) option('user_referrals_amount');
        $this->minimum_amount_gift = (string) (option('minimum_amount_gift', 0) ?: 0);
        $this->minimum_product_gift = (string) (option('minimum_product_gift', 1) ?: 1);

        $this->optimizeImage = (string) (option('optimizeImage', '10') ?: '10');
        $this->changePhotoFormat = (string) (option('changePhotoFormat', 'webp') ?: 'webp');
        $this->watermarkStatus = (string) (option('watermarkStatus') ?: 'false');
        $this->current_watermarkImage = (string) option('watermarkImage');
        $this->watermarkImagePosition = (string) (option('watermarkImagePosition') ?: 'bottom-right');

        // envها از config خوانده می‌شوند (پورت دقیق ویوی قدیمی)
        $this->PUSHER_APP_ID = (string) config('broadcasting.connections.pusher.app_id');
        $this->PUSHER_APP_KEY = (string) config('broadcasting.connections.pusher.key');
        $this->PUSHER_APP_SECRET = (string) config('broadcasting.connections.pusher.secret');
        $this->PUSHER_APP_CLUSTER = (string) config('broadcasting.connections.pusher.options.cluster');

        $this->transport = (string) config('mail.mailers.smtp.transport');
        $this->MAIL_HOST = (string) config('mail.mailers.smtp.host');
        $this->MAIL_PORT = (string) config('mail.mailers.smtp.port');
        $this->MAIL_USERNAME = (string) config('mail.mailers.smtp.username');
        $this->MAIL_PASSWORD = (string) config('mail.mailers.smtp.password');
        $this->MAIL_ENCRYPTION = (string) config('mail.mailers.smtp.encryption');
    }

    /* ---------------- ذخیره (پورت updateOthers) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('settings.others') ?: abort(403);

        $env_options = [
            'PUSHER_APP_ID', 'PUSHER_APP_KEY', 'PUSHER_APP_SECRET', 'PUSHER_APP_CLUSTER',
            'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
        ];

        $allChanges = [];
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        // ========== 1. env ==========
        $envChanges = [];

        foreach ($env_options as $key) {
            $value = (string) $this->{$key};
            $oldValue = env($key);
            if ($oldValue != $value) {
                $envChanges[$key] = [
                    'old' => $oldValue ?: 'خالی',
                    'new' => $value ?: 'خالی',
                    'title' => $this->getOthersFieldTitle($key),
                ];
            }
            change_env($key, $value);
        }

        // بررسی BROADCAST_DRIVER (پورت دقیق)
        $oldBroadcastDriver = env('BROADCAST_DRIVER');
        if ($this->PUSHER_APP_ID && $this->PUSHER_APP_KEY && $this->PUSHER_APP_SECRET) {
            change_env('BROADCAST_DRIVER', 'pusher');
            if ($oldBroadcastDriver != 'pusher') {
                $envChanges['BROADCAST_DRIVER'] = [
                    'old' => $oldBroadcastDriver ?: 'خالی',
                    'new' => 'pusher',
                    'title' => 'درایور پخش',
                ];
            }
        } else {
            change_env('BROADCAST_DRIVER', 'log');
            if ($oldBroadcastDriver != 'log') {
                $envChanges['BROADCAST_DRIVER'] = [
                    'old' => $oldBroadcastDriver ?: 'خالی',
                    'new' => 'log',
                    'title' => 'درایور پخش',
                ];
            }
        }

        if (! empty($envChanges)) {
            $allChanges['env_settings'] = $envChanges;
        }

        // ========== 2. آپلود فایل‌ها ==========
        $file_inputs = [
            'factor_logo' => 'لوگو فاکتور',
            'watermarkImage' => 'تصویر واترمارک',
        ];

        $fileChanges = [];
        foreach ($file_inputs as $key => $title) {
            if ($this->{$key}) {
                $oldFile = option($key);

                $imageOptions = [
                    'size' => 100,
                    'watermark' => false,
                    'path' => 'uploads/others/',
                    'field' => 'option_value',
                    'format' => 'png',
                ];
                $filename = uploadOptimizedImage($this->{$key}, 'options', $key, $imageOptions);
                option_update($key, $filename);

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

        // ========== 3. options (پورت دقیق — شامل quirk کلید transport) ==========
        $optionKeys = [
            'multi_vendor_system_status',
            'factor_title', 'factor_seller_name', 'factor_national_code',
            'factor_registeration_id', 'factor_economical_number',
            'user_register_gift_credit', 'user_referrals_enable', 'user_referrals_gift_type',
            'user_referrals_gift_discount_type', 'owner_referrals_amount', 'user_referrals_amount',
            'minimum_amount_gift', 'minimum_product_gift',
            'optimizeImage', 'changePhotoFormat', 'watermarkStatus', 'watermarkImagePosition',
            'transport', // quirk قدیمی: این فیلد در env_options نبود → به‌صورت option ذخیره می‌شد
        ];

        $optionChanges = [];
        foreach ($optionKeys as $key) {
            $value = (string) $this->{$key};
            $oldValue = option($key);

            // پورت نرمال‌سازی multi_vendor_system_status برای لاگ
            $normalizedOld = $oldValue;
            $normalizedNew = $value;
            if ($key === 'multi_vendor_system_status') {
                $normalizedOld = $oldValue === 'true' ? 'فعال' : 'غیرفعال';
                $normalizedNew = $value === 'true' ? 'فعال' : 'غیرفعال';
            }

            if ($oldValue != $value) {
                $optionChanges[$key] = [
                    'old' => $normalizedOld ?: 'خالی',
                    'new' => $normalizedNew ?: 'خالی',
                    'title' => $this->getOthersFieldTitle($key),
                ];
            }

            option_update($key, $value);
        }

        if (! empty($optionChanges)) {
            $allChanges['option_settings'] = $optionChanges;
        }

        // ========== 4. لاگ نهایی (پورت دقیق) ==========
        if (! empty($allChanges)) {
            $logMessage = "مدیر {$adminName} تنظیمات عمومی سایت را به‌روزرسانی کرد: ";
            $changeGroups = [];

            if (isset($allChanges['env_settings']) && ! empty($allChanges['env_settings'])) {
                $envCount = count($allChanges['env_settings']);
                $changeGroups[] = "{$envCount} تنظیم محیطی";
            }

            if (isset($allChanges['file_uploads']) && ! empty($allChanges['file_uploads'])) {
                $fileCount = count($allChanges['file_uploads']);
                $changeGroups[] = "{$fileCount} فایل آپلودی";
            }

            if (isset($allChanges['option_settings']) && ! empty($allChanges['option_settings'])) {
                $optionCount = count($allChanges['option_settings']);
                $changeGroups[] = "{$optionCount} تنظیم دیگر";
            }

            $logMessage .= implode('، ', $changeGroups);

            if (isset($allChanges['option_settings']['multi_vendor_system_status'])) {
                $change = $allChanges['option_settings']['multi_vendor_system_status'];
                $logMessage .= " (سیستم چند فروشندگی: {$change['old']} → {$change['new']})";
            }

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'تنظیمات عمومی سایت را به‌روزرسانی کرد',
                    'changes' => $allChanges,
                    'ip' => request()->ip(),
                ])
                ->log($logMessage);
        }

        // ری‌خوانی envها از config تازه (change_env کانفیگ را cache می‌کند)
        $this->mount();

        $this->dispatch('toast', message: 'تنظیمات عمومی سایت ذخیره شد.', type: 'success');
    }

    /** پورت دقیق getOthersFieldTitle قدیمی */
    private function getOthersFieldTitle($key)
    {
        $titles = [
            'PUSHER_APP_ID' => 'شناسه اپلیکیشن Pusher',
            'PUSHER_APP_KEY' => 'کلید اپلیکیشن Pusher',
            'PUSHER_APP_SECRET' => 'رمز اپلیکیشن Pusher',
            'PUSHER_APP_CLUSTER' => 'خوشه Pusher',
            'BROADCAST_DRIVER' => 'درایور پخش',
            'AI_TOKEN_KEY' => 'توکن هوش مصنوعی',
            'multi_vendor_system_status' => 'سیستم چند فروشندگی',
            'default_currency_id' => 'ارز پیش فرض',
            'default_rounding_amount' => 'مقدار گرد کردن',
            'default_rounding_type' => 'نحوه گرد کردن',
            'factor_logo' => 'لوگو فاکتور',
            'factor_title' => 'عنوان فاکتور',
            'factor_seller_name' => 'نام فروشنده در فاکتور',
            'factor_national_code' => 'شناسه ملی',
            'factor_registeration_id' => 'شناسه ثبت',
            'factor_economical_number' => 'شماره اقتصادی',
            'user_register_gift_credit' => 'اعتبار هدیه ثبت نام کاربر',
            'user_referrals_enable' => 'امکان معرفی افراد',
            'owner_referrals_amount' => 'تخفیف معرفی کننده',
            'user_referrals_amount' => 'تخفیف معرفی شونده',
            'optimizeImage' => 'درصد بهینه سازی تصاویر',
            'changePhotoFormat' => 'فرمت تبدیل تصاویر',
            'watermarkStatus' => 'وضعیت واترمارک',
            'watermarkImage' => 'تصویر واترمارک',
            'watermarkImagePosition' => 'موقعیت واترمارک',
        ];

        return $titles[$key] ?? $key;
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.settings.others')
            ->layout('admin-panel::layout');
    }
}
