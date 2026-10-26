<?php

namespace Modules\CustomerClub\Permissions;

use App\Models\Permission;

/**
 * تعریف پرمیژن‌های پکیج باشگاه مشتریان.
 *
 * در جدول permissions هسته ثبت می‌شوند (گیت‌های @can پنل قدیم + منوی
 * دو-پنلی ماژول از همین نام‌ها استفاده می‌کنند).
 *
 * ensureInDatabase() — خودترمیم (الگوی ReferralTier::ensureSeeded): پکیج
 * بدون نیاز به اجرای دستی کامند، پرمیژن‌هایش را در بوت تضمین می‌کند
 * (idempotent — عنوان موجود بازنویسی نمی‌شود؛ فقط اگر نبود ساخته می‌شود).
 * کامند قراردادی InstallPermissionsCommand هم همین را صریح اجرا می‌کند.
 */
class ModulesPermissions
{
    public static function all(): array
    {
        return [
            'customerclub' => [
                'title' => 'باشگاه مشتریان',
                'values' => [
                    'rules'      => 'مدیریت قوانین امتیاز',
                    'points'     => 'مشاهده دفتر امتیازها',
                    'members'    => 'مدیریت اعضا و تنظیم دستی امتیاز',
                    'giftcards'  => 'مدیریت کارت‌های هدیه',
                    'referrals'  => 'گزارش معرفی دوستان',
                    'settings'   => 'تنظیمات باشگاه مشتریان',
                ],
            ],
        ];
    }

    public static function rootKeys(): array
    {
        return array_keys(static::all());
    }

    /** نصب خودکار پرمیژن‌ها (self-heal) — از بوت پروایدر صدا زده می‌شود */
    public static function ensureInDatabase(): int
    {
        // گارد سریع: اگر ریشه موجود است، فرزندان هم توسط نصب‌کننده ساخته شده‌اند
        // (هزینهٔ هر بوت = یک کوئری ایندکس‌دار)
        if (Permission::query()->where('name', 'customerclub')->exists()) {
            return 0;
        }

        $created = 0;
        $ordering = (int) Permission::query()->max('ordering');

        foreach (static::all() as $name => $value) {
            $ordering += self::ensureRecursive($name, $value, null, $ordering);
        }

        return $created;
    }

    private static function ensureRecursive(string $name, $value, ?int $parentId, int &$ordering): int
    {
        $created = 0;

        if (is_array($value) && isset($value['values'])) {
            $perm = Permission::query()->firstOrCreate(
                ['name' => $name],
                ['title' => $value['title'], 'ordering' => $ordering++, 'permission_id' => $parentId]
            );

            foreach ($value['values'] as $n => $val) {
                $created += self::ensureRecursive($name.'.'.$n, $val, (int) $perm->id, $ordering);
            }

            return $created;
        }

        Permission::query()->firstOrCreate(
            ['name' => $name],
            ['title' => (string) $value, 'ordering' => $ordering++, 'permission_id' => $parentId]
        );

        return 1;
    }
}
