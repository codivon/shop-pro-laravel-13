<?php

namespace App\Services\Messenger;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * سرویس ربات بله (Bale Messenger Bot API)
 *
 * ⚠️ این کلاس در پروژه‌ی قدیمی وجود خارجی نداشت (dead dependency):
 * BaleSettingsController و BaleWebhookController به آن ارجاع می‌دادند ولی خود کلاس گم شده بود
 * (PROJECT_ANALYSIS.md — BaleSettings: dead — depends on missing BaleMessenger class).
 * این پیاده‌سازی بر اساس Bale Bot API (هم‌خانواده‌ی Telegram Bot API) نوشته شده است:
 *   https://tapi.bale.ai/bot{token}/{method}
 *
 * توکن ربات در options با کلید BALE_BOT_TOKEN ذخیره می‌شود (پورت BaleSettingsController قدیمی).
 */
class BaleMessenger
{
    /** آدرس پایه‌ی API بله */
    public const API_BASE = 'https://tapi.bale.ai/bot';

    /**
     * توکن فعال: مقدار پاس‌شده (اگر باشد) وگرنه مقدار ذخیره‌شده در options
     */
    public function token(?string $override = null): ?string
    {
        $token = $override ?: option('BALE_BOT_TOKEN');

        return ($token && is_string($token)) ? trim($token) : null;
    }

    /**
     * فراخوانی عمومی متدهای API
     */
    public function call(string $method, array $params = [], ?string $tokenOverride = null): array
    {
        $token = $this->token($tokenOverride);

        if (! $token) {
            return [
                'ok' => false,
                'description' => 'توکن ربات بله تنظیم نشده است.',
            ];
        }

        $response = Http::timeout(30)->post(self::API_BASE.$token.'/'.$method, $params);

        return $response->json() ?? [
            'ok' => false,
            'description' => 'پاسخ نامعتبر از سرور بله (کد '.$response->status().')',
        ];
    }

    /**
     * تست اتصال: متد getMe
     */
    public function testConnection(?string $tokenOverride = null): array
    {
        try {
            $result = $this->call('getMe', [], $tokenOverride);

            if ((($result['ok'] ?? false) === true)) {
                return [
                    'ok' => true,
                    'message' => 'اتصال موفق بود. ربات: '.($result['result']['username'] ?? $result['result']['first_name'] ?? 'نامشخص'),
                    'bot' => $result['result'] ?? null,
                ];
            }

            return [
                'ok' => false,
                'message' => $result['description'] ?? 'اتصال ناموفق بود',
            ];
        } catch (\Throwable $e) {
            Log::error('Bale testConnection error: '.$e->getMessage());

            return [
                'ok' => false,
                'message' => 'خطا در اتصال به سرور بله: '.$e->getMessage(),
            ];
        }
    }

    /**
     * تنظیم وب‌هوک — خروجی json خام (مثل قدیمی که json_decode روی آن انجام می‌شد)
     */
    public function setWebhook(string $url, ?string $tokenOverride = null): string
    {
        $result = $this->call('setWebhook', ['url' => $url], $tokenOverride);

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * حذف وب‌هوک — خروجی json خام
     */
    public function deleteWebhook(?string $tokenOverride = null): string
    {
        $result = $this->call('deleteWebhook', [], $tokenOverride);

        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    /**
     * اطلاعات وب‌هوک فعلی
     */
    public function getWebhookInfo(?string $tokenOverride = null): array
    {
        return $this->call('getWebhookInfo', [], $tokenOverride);
    }

    /**
     * ارسال پیام متنی به یک chat_id
     */
    public function sendMessage(int|string $chatId, string $text, ?string $tokenOverride = null): array
    {
        return $this->call('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
        ], $tokenOverride);
    }

    /**
     * دریافت آپدیت وب‌هوک — chat_id کاربران ذخیره می‌شود تا بتوان به آن‌ها پیام داد.
     * (کنترلر وب‌هوک قدیمی کامنت شده بود؛ این پیاده‌سازی حداقلی فقط شناسه‌ها را نگه می‌دارد)
     */
    public function handleWebhook(array $update): array
    {
        $chatId = $update['message']['chat']['id']
            ?? $update['edited_message']['chat']['id']
            ?? null;

        if ($chatId) {
            $ids = collect(json_decode(option('bale_chat_ids', '[]'), true) ?: [])
                ->push((int) $chatId)
                ->unique()
                ->values()
                ->take(1000)
                ->all();

            option_update('bale_chat_ids', json_encode($ids));
        }

        return ['ok' => true];
    }
}
