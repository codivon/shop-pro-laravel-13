<?php

namespace App\Livewire\Admin\System;

use App\Services\Messenger\BaleMessenger;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * تنظیمات ربات بله — پورت ۱:۱ Back\BaleSettingsController
 * (testConnection / setWebhook / deleteWebhook)
 *
 * ⚠️ در پروژه‌ی قدیمی کلاس BaleMessenger وجود نداشت (dead dependency) و روت‌های این بخش
 * کامنت بودند؛ سرویس در این فاز ساخته شد: app/Services/Messenger/BaleMessenger.php
 *
 * گیت: کنترلر قدیمی هیچ گardi نداشت و روت‌هایش هم کامنت بودند؛ مطابق اسپک (fallback به
 * تنظیمات برنامه‌نویس که creator-only است) همین شرط creator اعمال شده است.
 */
#[Title('ربات بله')]
class BaleSettings extends Component
{
    /** توکن ربات بله (options: BALE_BOT_TOKEN) */
    public string $botToken = '';

    /** آدرس وب‌هوک فرم */
    public string $webhookUrl = '';

    /** نتیجه‌ی آخرین عملیات برای نمایش */
    public ?array $lastResult = null;

    public function mount(): void
    {
        $this->ensureCreator();

        $this->botToken = (string) option('BALE_BOT_TOKEN');
        $this->webhookUrl = '';
        $this->lastResult = null;
    }

    private function ensureCreator(): void
    {
        $user = auth('adminPanel')->user();

        abort_unless($user && $user->level === 'creator', 403);
    }

    /** ذخیره‌ی توکن در options (کلید قدیمی BALE_BOT_TOKEN) */
    public function saveToken(): void
    {
        $this->ensureCreator();

        $this->validate([
            'botToken' => 'nullable|string|max:255',
        ], [], [
            'botToken' => 'توکن ربات',
        ]);

        option_update('BALE_BOT_TOKEN', $this->botToken);

        $this->dispatch('toast', message: 'توکن ربات بله ذخیره شد.', type: 'success');
    }

    /** پورت testConnection: اگر توکن از فرم آمده باشد ابتدا ذخیره می‌شود */
    public function testConnection(): void
    {
        $this->ensureCreator();

        $this->validate([
            'botToken' => 'nullable|string|max:255',
        ], [], [
            'botToken' => 'توکن ربات',
        ]);

        if (filled($this->botToken)) {
            option_update('BALE_BOT_TOKEN', $this->botToken);
        }

        try {
            $bale = app(BaleMessenger::class);
            $result = $bale->testConnection();
        } catch (\Throwable $e) {
            $result = [
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }

        $this->lastResult = [
            'kind' => 'test',
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => (string) ($result['message'] ?? $result['description'] ?? 'نتیجه‌ای دریافت نشد'),
        ];

        // توکن ممکن است در همین لحظه ذخیره شده باشد
        $this->botToken = (string) option('BALE_BOT_TOKEN');
    }

    /** پورت setWebhook */
    public function setWebhook(): void
    {
        $this->ensureCreator();

        $this->validate([
            'webhookUrl' => 'required|url',
        ], [
            'webhookUrl.required' => 'آدرس وب‌هوک الزامی است.',
            'webhookUrl.url' => 'آدرس وب‌هوک معتبر نیست.',
        ], [
            'webhookUrl' => 'آدرس وب‌هوک',
        ]);

        try {
            $bale = app(BaleMessenger::class);
            $result = $bale->setWebhook($this->webhookUrl);
            $decoded = json_decode($result, true);

            if (isset($decoded['ok']) && $decoded['ok'] === true) {
                $this->lastResult = [
                    'kind' => 'webhook',
                    'ok' => true,
                    'message' => 'وب‌هوک با موفقیت تنظیم شد',
                ];
            } else {
                $this->lastResult = [
                    'kind' => 'webhook',
                    'ok' => false,
                    'message' => $decoded['description'] ?? 'خطا در تنظیم وب‌هوک',
                ];
            }
        } catch (\Throwable $e) {
            report($e);

            $this->lastResult = [
                'kind' => 'webhook',
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /** پورت deleteWebhook */
    public function deleteWebhook(): void
    {
        $this->ensureCreator();

        try {
            $bale = app(BaleMessenger::class);
            $result = $bale->deleteWebhook();
            $decoded = json_decode($result, true);

            if (isset($decoded['ok']) && $decoded['ok'] === true) {
                $this->lastResult = [
                    'kind' => 'webhook',
                    'ok' => true,
                    'message' => 'وب‌هوک با موفقیت حذف شد',
                ];
            } else {
                $this->lastResult = [
                    'kind' => 'webhook',
                    'ok' => false,
                    'message' => $decoded['description'] ?? 'خطا در حذف وب‌هوک',
                ];
            }
        } catch (\Throwable $e) {
            report($e);

            $this->lastResult = [
                'kind' => 'webhook',
                'ok' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.system.bale-settings')
            ->layout('admin-panel::layout');
    }
}
