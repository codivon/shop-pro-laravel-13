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

    /** پورت config('front.socials', []) قدیمی — [name, key, icon] */
    public function socialsList(): array
    {
        return collect(config('front.socials', []))
            ->map(fn ($social) => [
                'name' => $social['name'] ?? $social['key'],
                'key' => $social['key'],
                'icon' => $social['icon'] ?? '',
            ])
            ->values()
            ->all();
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
