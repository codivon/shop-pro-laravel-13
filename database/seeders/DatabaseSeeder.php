<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * فاز ۲۷ — لیست مرجع سیدرها به ترتیب اجرا
     *
     * نصاب جدید هر سیدر را «در یک درخواست جدا» اجرا می‌کند (مثل مایگریشن‌ها)
     * تا روی هاست‌های کند Timeout رخ ندهد؛ اگر وسط کار قطع شد، از همین لیست
     * و فایل ردیاب (storage/app/installer-seeded.json) از همان‌جا ادامه می‌دهد.
     * این متد «تک منبع حقیقت» است — هم نصاب و هم run() از همین لیست می‌خوانند.
     */
    public static function seederClasses(): array
    {
        return [
            ProvinceSeeder::class,
            CitySeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            MenuSeeder::class,
            CategorySeeder::class,
            SliderSeeder::class,
            BannerSeeder::class,
            ProductSeeder::class,
            AttributeSeeder::class,
            StaticFilterSeeder::class,
            LinkSeeder::class,
            WarehouseSeeder::class,
            ReturnReasonSeeder::class,
        ];
    }

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(self::seederClasses());
    }
}
