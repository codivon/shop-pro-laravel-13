<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * شبکه‌های اجتماعی — پورت ۱:۱ Back\SettingController::showSocials/updateSocials
 * فهرست شبکه‌ها به‌صورت داینامیک از config('front.socials') خوانده می‌شود
 * (در تم WeblakShop: social_telegram / social_instagram / social_whatsapp).
 * کلیدهای options عیناً همان کلیدهای قدیمی‌اند (social_*).
 */
#[Title('شبکه های اجتماعی')]
class Socials extends Component
{
    /** مقادیر لینک هر شبکه: [key => url] */
    public array $values = [];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('settings.socials') ?: abort(403);

        foreach ($this->socialsList() as $social) {
            $this->values[$social['key']] = (string) option($social['key']);
        }
    }

    /**
     * فاز ۱۸: کاتالوگ کامل شبکه‌های اجتماعی + ادغام تعریف‌های تم (config('front.socials'))
     * کلیدهای option همان social_* هستند — سازگاری کامل با تم و داده‌های قبلی.
     */
    public function socialsList(): array
    {
        $catalog = [
            ['key' => 'social_telegram',   'name' => 'تلگرام',      'color' => '#229ED9', 'icon' => 'send',            'placeholder' => 'https://t.me/username'],
            ['key' => 'social_instagram',  'name' => 'اینستاگرام',  'color' => '#E1306C', 'icon' => 'instagram',       'placeholder' => 'https://instagram.com/username'],
            ['key' => 'social_whatsapp',   'name' => 'واتساپ',      'color' => '#25D366', 'icon' => 'message-circle',  'placeholder' => 'https://wa.me/989123456789'],
            ['key' => 'social_aparat',     'name' => 'آپارات',      'color' => '#ED145B', 'icon' => 'play',            'placeholder' => 'https://aparat.com/username'],
            ['key' => 'social_eitaa',      'name' => 'ایتا',        'color' => '#F57C00', 'icon' => 'send',            'placeholder' => 'https://eitaa.com/username'],
            ['key' => 'social_rubika',     'name' => 'روبیکا',      'color' => '#7C3AED', 'icon' => 'gem',             'placeholder' => 'https://rubika.ir/username'],
            ['key' => 'social_bale',       'name' => 'بله',         'color' => '#16A34A', 'icon' => 'message-square',  'placeholder' => 'https://ble.ir/username'],
            ['key' => 'social_x',          'name' => 'توییتر (ایکس)', 'color' => '#111827', 'icon' => 'twitter',       'placeholder' => 'https://x.com/username'],
            ['key' => 'social_facebook',   'name' => 'فیسبوک',      'color' => '#1877F2', 'icon' => 'facebook',        'placeholder' => 'https://facebook.com/username'],
            ['key' => 'social_youtube',    'name' => 'یوتیوب',      'color' => '#FF0000', 'icon' => 'youtube',         'placeholder' => 'https://youtube.com/@channel'],
            ['key' => 'social_linkedin',   'name' => 'لینکدین',     'color' => '#0A66C2', 'icon' => 'linkedin',        'placeholder' => 'https://linkedin.com/company/page'],
            ['key' => 'social_threads',    'name' => 'تردز',        'color' => '#000000', 'icon' => 'at-sign',         'placeholder' => 'https://threads.net/@username'],
            ['key' => 'social_twitch',     'name' => 'توییچ',       'color' => '#9146FF', 'icon' => 'twitch',          'placeholder' => 'https://twitch.tv/username'],
            ['key' => 'social_discord',    'name' => 'دیسکورد',     'color' => '#5865F2', 'icon' => 'headphones',      'placeholder' => 'https://discord.gg/invite'],
        ];

        // تعریف‌های تم (name/icon سفارشی) بر کاتالوگ ترجیح دارند + شبکه‌های سفارشی اضافه می‌شوند
        $themeSocials = collect(config('front.socials', []))
            ->filter(fn ($s) => ! empty($s['key']))
            ->keyBy(fn ($s) => $s['key']);

        foreach ($catalog as $i => $item) {
            if ($themeSocials->has($item['key'])) {
                $theme = $themeSocials->get($item['key']);
                $catalog[$i]['name'] = $theme['name'] ?? $item['name'];
                $catalog[$i]['from_theme'] = true;
                $themeSocials->forget($item['key']);
            }
        }

        foreach ($themeSocials->values() as $extra) {
            $catalog[] = [
                'key'        => $extra['key'],
                'name'       => $extra['name'] ?? $extra['key'],
                'color'      => '#64748B',
                'icon'       => 'link-2',
                'placeholder' => 'https://',
                'from_theme' => true,
            ];
        }

        return $catalog;
    }

    /** شمارش تکمیل‌شده‌ها برای نشان سربرگ */
    public function getFilledCountProperty(): int
    {
        return count(array_filter($this->socialsList(), fn ($s) => trim((string) ($this->values[$s['key']] ?? '')) !== ''));
    }

    /* ---------------- ذخیره (پورت updateSocials) ---------------- */

    public function save(): void
    {
        auth('adminPanel')->user()?->can('settings.socials') ?: abort(403);

        $allChanges = [];
        $adminName = auth('adminPanel')->user()->full_name ?? auth('adminPanel')->user()->name ?? 'مدیر';

        $socialNames = [];
        foreach ($this->socialsList() as $social) {
            $socialNames[$social['key']] = $social['name'];
        }

        foreach ($this->socialsList() as $social) {
            $key = $social['key'];
            $value = (string) ($this->values[$key] ?? '');
            $oldValue = option($key);

            if ($oldValue != $value) {
                $socialTitle = $socialNames[$key] ?? $key;

                $allChanges[$key] = [
                    'old' => $oldValue ?: 'خالی',
                    'new' => $value ?: 'خالی',
                    'title' => $socialTitle,
                ];
            }

            option_update($key, $value);
        }

        // ثبت لاگ در صورت وجود تغییرات (پورت دقیق با خلاصه‌سازی >۵۰۰ کاراکتر)
        if (! empty($allChanges)) {
            $changeTexts = [];
            foreach ($allChanges as $change) {
                $changeTexts[] = "{$change['title']} از «{$change['old']}» به «{$change['new']}»";
            }

            $logMessage = "مدیر {$adminName} تنظیمات شبکه‌های اجتماعی را به‌روزرسانی کرد: ".implode('، ', $changeTexts);

            if (mb_strlen($logMessage) > 500) {
                $logMessage = "مدیر {$adminName} تنظیمات شبکه‌های اجتماعی را در ".count($allChanges)." مورد به‌روزرسانی کرد";
            }

            activity()
                ->causedBy(auth('adminPanel')->user())
                ->withProperties([
                    'action' => 'تنظیمات شبکه‌های اجتماعی را به‌روزرسانی کرد',
                    'changes' => $allChanges,
                    'ip' => request()->ip(),
                ])
                ->log($logMessage);
        }

        $this->dispatch('toast', message: 'تنظیمات شبکه‌های اجتماعی ذخیره شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.settings.socials')
            ->layout('admin-panel::layout');
    }
}
