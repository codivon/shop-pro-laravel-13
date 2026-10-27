<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/*
 * کلاینت سرویس فینوتک (فاز ۱۱۰) — احراز هویت موبایل فروشنده
 *
 * همان سرویس «شاهکار» که در پکیج InstallmentCredit برای خرید اقساطی
 * استفاده می‌شد: بررسی اینکه شمارهٔ موبایل و کد ملی متعلق به یک نفر است.
 * مستندات: api.finnotech.ir → شاهکار (mobileVerify)
 *
 * قرارداد خروجی mobileOwnershipMatches():
 *   MATCH       → موبایل و کد ملی متعلق به یک نفر است
 *   MISMATCH    → فینوتک صریحاً گفت متعلق به یک نفر نیست
 *   UNAVAILABLE → سرویس در دسترس/پیکربندی نبود (ادامهٔ ثبت‌نام آزاد می‌شود)
 *
 * کلیدهای تنظیمات (تب «چندفروشندگی» برنامه‌نویس):
 *   seller_kyc_mobile_verify → '1'/'0' (کلید اصلی روشن/خاموش)
 *   finnotech_client_id      → شناسهٔ کلاینت فینوتک
 *   finnotech_client_secret  → رمز کلاینت فینوتک
 */
class Finnotech
{
    /** نتیجهٔ بررسی مالکیت موبایل */
    public const MATCH = 'match';

    public const MISMATCH = 'mismatch';

    public const UNAVAILABLE = 'unavailable';

    /** آدرس پایهٔ API (قابل بازنویسی با option برای محیط آزمایشی) */
    public static function baseUrl(): string
    {
        return rtrim((string) option('finnotech_base_url', 'https://api.finnotech.ir'), '/');
    }

    public static function clientId(): string
    {
        return trim((string) option('finnotech_client_id', ''));
    }

    public static function clientSecret(): string
    {
        return trim((string) option('finnotech_client_secret', ''));
    }

    /** سوییچ اصلی + کلیدها هر دو پر باشند تا تماس بزنیم */
    public static function ready(): bool
    {
        return MultiVendorSettings::kycMobileVerifyEnabled()
            && self::clientId() !== ''
            && self::clientSecret() !== '';
    }

    /**
     * بررسی مالکیت موبایل با کد ملی (سرویس شاهکار).
     * هر خطا/عدم‌دسترسی = UNAVAILABLE (هرگز ثبت‌نام را به‌خاطر خرابیِ سرویسِ
     * خارجی نمی‌بندیم؛ تنها جواب صریحِ MISMATCH مسدودکننده است).
     */
    public static function mobileOwnershipMatches(string $mobile, string $nationalCode): string
    {
        if (! self::ready()) {
            return self::UNAVAILABLE;
        }

        $clientId = self::clientId();
        $cacheKey = 'finnotech:shahkar:token:'.$clientId.':'.$nationalCode.':'.$mobile;

        try {
            // توکن شاهکار به nid و موبایلِ همان درخواست گره می‌خورد (client_credentials)
            $token = Cache::remember($cacheKey, now()->addMinutes(25), function () use ($clientId, $nationalCode, $mobile) {
                $response = Http::asForm()
                    ->timeout(10)
                    ->retry(1, 300)
                    ->post(self::baseUrl().'/dev/v2/oauth2/token', [
                        'grant_type' => 'client_credentials',
                        'client_id' => $clientId,
                        'client_secret' => self::clientSecret(),
                        'nid' => $nationalCode,
                        'mobile' => $mobile,
                        'scopes' => 'kyc',
                    ]);

                $body = $response->json();

                $value = $body['value'] ?? $body['access_token'] ?? null;

                if (! $response->successful() || ! is_string($value) || $value === '') {
                    Log::warning('فینوتک: دریافت توکن شاهکار ناموفق', [
                        'status' => $response->status(),
                        'body' => $body,
                    ]);

                    return null;
                }

                return $value;
            });

            if (! is_string($token) || $token === '') {
                return self::UNAVAILABLE;
            }

            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(10)
                ->get(self::baseUrl().'/emiya/v2/clients/'.$clientId.'/shahkar/mobileVerify', [
                    'nationalCode' => $nationalCode,
                    'mobile' => $mobile,
                    'trackId' => 'seller-reg-'.substr((string) md5($mobile.$nationalCode), 0, 10),
                ]);

            $body = $response->json() ?? [];

            // پاسخ موفق: value.isValid صریح است
            if (array_key_exists('isValid', (array) ($body['value'] ?? []))) {
                return $body['value']['isValid'] === true ? self::MATCH : self::MISMATCH;
            }

            // برخی پاسخ‌ها فقط result.type = Successful دارند
            if (($body['result']['type'] ?? '') === 'Successful') {
                return self::MATCH;
            }

            // پاسخ ناشناخته/خطا → دست‌نیافتنی
            Log::warning('فینوتک: پاسخ نامفهوم شاهکار', [
                'status' => $response->status(),
                'body' => $body,
            ]);

            return self::UNAVAILABLE;
        } catch (\Throwable $e) {
            Log::warning('فینوتک: خطای ارتباط با سرویس شاهکار: '.$e->getMessage());

            return self::UNAVAILABLE;
        }
    }

    /** پیام استاندارد برای هر وضعیت — نمایش به کاربر در گام اطلاعات تکمیلی */
    public static function mismatchMessage(): string
    {
        return 'شمارهٔ موبایل و کد ملی واردشده متعلق به یک نفر نیستند؛ لطفاً اطلاعات را بررسی کنید.';
    }
}
