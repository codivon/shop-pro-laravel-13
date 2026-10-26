<?php

namespace Modules\CustomerClub\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * تنظیمات پکیج باشگاه مشتریان (key-value).
 *
 * زنجیرهٔ خواندن: DB → config('customer-club.defaults.KEY') → مقدار پیش‌فرض
 * پارامتر. به این ترتیب حتی قبل از اجرای InstallPermissionsCommand، پکیج با
 * مقادیر منطقی کار می‌کند و ادمین بعداً از پنل بازنویسی می‌کند.
 */
class ClubSetting extends Model
{
    protected $guarded = ['id'];

    /** مقادیر از پیش محاسبه‌شدهٔ همین ریکوئست */
    protected static array $cache = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, static::$cache)) {
            return static::$cache[$key];
        }

        try {
            $row = static::query()->where('key', $key)->first();
        } catch (\Throwable $e) {
            // جدول هنوز migrate نشده — fallback به config
            return static::$cache[$key] = (string) config(
                "customer-club.defaults.{$key}",
                $default ?? ''
            );
        }

        if ($row !== null && $row->value !== null && $row->value !== '') {
            return static::$cache[$key] = (string) $row->value;
        }

        $configDefault = config("customer-club.defaults.{$key}");

        return static::$cache[$key] = (string) ($row?->value ?? $configDefault ?? $default ?? '');
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        static::$cache[$key] = (string) $value;
    }

    /** فعال‌بودن یک کلید بولی (قاعدهٔ سراسری: '1' = روشن) */
    public static function enabled(string $key): bool
    {
        return static::get($key, '0') === '1';
    }

    /** پاک‌کردن کش درون-ریکوئستی (برای تست) */
    public static function flushCache(): void
    {
        static::$cache = [];
    }
}
