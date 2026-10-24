<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/*
 * ربات‌گیر (Bot Trap)
 * IP هایی که به مسیرهای طعمه ضربه زده‌اند — به‌صورت سراسری از کل سایت مسدود می‌شوند.
 */
class BotTrap extends Model
{
    protected $fillable = [
        'ip_address',
        'user_agent',
        'trap_path',
        'hit_count',
        'last_hit_at',
    ];

    protected $casts = [
        'last_hit_at' => 'datetime',
    ];

    /** کلید کش لیست IP های مسدودشده */
    public const CACHE_KEY = 'security:blocked_bot_ips';

    /**
     * فاز ۷۰ — سقف مسدودی خودکار (ساعت‌ها)
     * مسدودی پس از این مدت خودش می‌بَرد تا مدیرِ فروشگاه هرگز برای همیشه قفل نشود
     * (سناریوی واقعی: IP اشتراکی CGNAT isp یا پرشدن هانی‌پات توسط مرورگر خود کاربر).
     * ربات واقعی دوباره به تله می‌خورد و شمارنده تازه می‌شود — امنیت حفظ می‌شود.
     */
    public const BLOCK_TTL_HOURS = 24;

    /** حذف کش لیست IP ها (بعد از هر تغییر) */
    public static function flushCache(): void
    {
        cache()->forget(self::CACHE_KEY);
    }

    /**
     * ثبت یک ضربه ربات و مسدودکردن IP.
     * اگر IP قبلاً مسدود بوده فقط شمارنده و زمان به‌روزرسانی می‌شود.
     */
    public static function catch(string $ip, ?string $userAgent, string $trapPath): void
    {
        // فاز ۷۱ — کلید اصلی تلهٔ ربات (تنظیمات برنامه‌نویس): خاموش ⇒ هیچ IPای ثبت نمی‌شود
        if (! \App\Services\SecuritySettings::botTrapEnabled()) {
            return;
        }

        try {
            $existing = static::where('ip_address', $ip)->first();

            if ($existing) {
                $existing->increment('hit_count');
                $existing->update([
                    'user_agent'  => substr((string) $userAgent, 0, 500),
                    'trap_path'   => substr($trapPath, 0, 191),
                    'last_hit_at' => now(),
                ]);
            } else {
                static::create([
                    'ip_address'  => $ip,
                    'user_agent'  => substr((string) $userAgent, 0, 500),
                    'trap_path'   => substr($trapPath, 0, 191),
                    'hit_count'   => 1,
                    'last_hit_at' => now(),
                ]);
            }

            static::flushCache();
        } catch (\Throwable $e) {
            // اگر جدول هنوز ساخته نشده باشد (مثلاً قبل از نصب) مشکلی پیش نیاید
            report($e);
        }
    }

    /** آیا این IP مسدود است؟ (با کش — بدون کوئری اضافه در هر درخواست؛ سقف ۲۴ ساعت — فاز ۷۰) */
    public static function isBlocked(string $ip): bool
    {
        $blocked = cache()->remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            try {
                return static::query()
                    ->where('last_hit_at', '>=', now()->subHours(static::BLOCK_TTL_HOURS))
                    ->pluck('ip_address')
                    ->all();
            } catch (\Throwable $e) {
                return [];
            }
        });

        return in_array($ip, $blocked, true);
    }
}
