<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;

/*
 * محتوای صفحهٔ فرود فروشندگان (/seller — فاز ۱۰۹)
 *
 * ساختار کامل صفحه (الهام از لندینگ «فروشنده شوید» دیجی‌کالا) در یک کلید
 * options به‌صورت JSON نگهداری می‌شود تا از پنل مدیریت (فروشندگان ←
 * تنظیمات فروشندگان ← «صفحهٔ فرود فروشندگان») قابل شخصی‌سازی باشد.
 *
 * بخش‌های «چرا ما» و «سوالات متداول» و «مزایا/کمیسیون» از مدل‌های
 * SellerHero / SellerQuestion / SellerCommission می‌آیند که صفحات ادمین
 * خودشان را دارند (فاز ۳) — اینجا تکرار نمی‌شوند.
 *
 * آمار نوار تیره: اگر value خالی باشد، عدد واقعی از دیتابیس محاسبه
 * می‌شود (صادقانه‌تر از عدد جعلی)؛ ادمین می‌تواند مقدار دلخواه بنویسد.
 *
 * قرارداد: کلید seller_landing_content — مقدار خالی/ناموجود یعنی
 * «پیش‌فرض»؛ toggle روشن/خاموش خود لندینگ پیشرفته کلید جداگانهٔ
 * seller_landing_page است (MultiVendorSettings::landingPageEnabled()).
 */
class SellerLandingContent
{
    /** کلید options نگهداری JSON محتوا */
    public const KEY = 'seller_landing_content';

    /**
     * محتوای نهایی (ادغام JSON ذخیره‌شده روی پیش‌فرض‌ها — هر بخش که
     * در JSON نباشد یا خراب باشد، پیش‌فرضش سرو می‌شود).
     */
    public static function get(): array
    {
        $stored = json_decode((string) option(self::KEY, ''), true);

        if (! is_array($stored)) {
            return self::defaults();
        }

        $defaults = self::defaults();

        foreach ($defaults as $section => $value) {
            if (! array_key_exists($section, $stored) || ! is_array($stored[$section])) {
                $stored[$section] = $value;
            }
        }

        return $stored;
    }

    /** پیش‌فرض‌های کامل صفحه (هم‌ساختار با ادیتور ادمین) */
    public static function defaults(): array
    {
        return [
            'hero' => [
                'title' => 'امروز فروشنده شوید و فروشت را شروع کن!',
                'subtitle' => 'ثبت‌نام تنها چند دقیقه زمان می‌برد؛ فروشگاهت را بساز و اولین محصولت را بفروش.',
                'shop_label' => 'نام فروشگاه',
                'mobile_label' => 'شماره موبایل',
                'button' => 'ادامه',
                'response_time' => 'میانگین زمان پاسخ‌گویی: کمتر از ۲ روز کاری',
                'image' => 'images/seller-landing/hero.png',
            ],

            // آمار نوار تیره — value خالی ⇒ عدد واقعی از دیتابیس
            'stats' => [
                ['value' => '', 'label' => 'فروشنده'],
                ['value' => '', 'label' => 'فروشندهٔ فعال'],
                ['value' => '', 'label' => 'محصول'],
                ['value' => '', 'label' => 'سفارش پرداخت‌شده'],
            ],

            // کارت‌های پلن همکاری (۳ کارت مثل لندینگ مرجع)
            'plans' => [
                [
                    'title' => 'فروشندهٔ پایه',
                    'features' => [
                        'فعال‌سازی فروشگاه در چند دقیقه',
                        'کیف پول و تسویهٔ شفاف',
                        'مدیریت موجودی و قیمت‌گذاری',
                        'پشتیبانی پیام‌رسان پنل',
                    ],
                ],
                [
                    'title' => 'فروشندهٔ حرفه‌ای',
                    'features' => [
                        'تمام امکانات پلن پایه',
                        'آمار فروش و بازدید پیشرفته',
                        'سیگنال‌های تقاضا (ناموجود / کاهش قیمت)',
                        'صورت‌حساب تسویهٔ ردیف‌به‌ردیف',
                        'خروجی اکسل محصولات و سفارش‌ها',
                    ],
                ],
                [
                    'title' => 'فروشندهٔ سازمانی',
                    'features' => [
                        'تمام امکانات پلن حرفه‌ای',
                        'کمیسیون اختصاصی فروشنده',
                        'پشتیبانی اختصاصی',
                        'حضور در کمپین‌ها و پیشنهاد شگفت‌انگیز',
                    ],
                ],
            ],

            // مراحل شروع فروش
            'steps' => [
                [
                    'title' => 'ثبت‌نام',
                    'description' => 'شمارهٔ موبایل و ایمیل خود را وارد کنید و کد تأیید را تأیید کنید.',
                ],
                [
                    'title' => 'اطلاعات کسب‌وکار و مدارک',
                    'description' => 'اطلاعات هویتی و فروشگاه را تکمیل و مدارک را بارگذاری کنید.',
                ],
                [
                    'title' => 'شروع فروش',
                    'description' => 'محصولات را فعال کنید و اولین سفارش را دریافت کنید.',
                ],
            ],

            // تجربهٔ فروشندگان (نظرات)
            'testimonials' => [
                [
                    'name' => 'آقای محمودی',
                    'business' => 'فروشگاه اکسسوری',
                    'quote' => 'پنل فروشنده ساده و سریع است؛ سفارش‌ها و تسویه‌ها را در یک نگاه می‌بینم.',
                ],
                [
                    'name' => 'خانم راد',
                    'business' => 'بوتیک پوشاک',
                    'quote' => 'بدون هزینهٔ راه‌اندازی شروع کردم؛ صورت‌حساب تسویه دقیقاً ردیف‌به‌ردیف شفاف است.',
                ],
                [
                    'name' => 'آقای کاظمی',
                    'business' => 'فروشگاه دیجیتال',
                    'quote' => 'آمار بازدید و سیگنال تقاضا کمک کرد موجودی را هوشمندانه بچینم.',
                ],
            ],

            // فروشندگان برتر — پیش‌فرض خالی ⇒ بخش مخفی تا ادمین واقعی‌ها را وارد کند
            'brands' => [
                ['name' => '', 'tag' => '', 'metric' => ''],
            ],

            // CTA پایین صفحه
            'app' => [
                'title' => 'همه چیز را از یک پنل مدیریت کنید',
                'button' => 'شروع کنید',
                'link' => '',
                'pills' => ['مدیریت سفارش‌ها', 'مدیریت موجودی', 'قیمت‌گذاری', 'تخفیف‌ها'],
            ],
        ];
    }

    /**
     * آمار نوار تیره — value خالی ⇒ محاسبهٔ واقعی از دیتابیس.
     * خروجی: آرایهٔ [['value' => نمایشی, 'label' => ...], ...]
     */
    public static function resolvedStats(array $content): array
    {
        $live = [
            'فروشنده' => Seller::where('status_register', 'complete')->count(),
            'فروشندهٔ فعال' => Seller::where('status_register', 'complete')->where('status_work', 'ACTIVE')->count(),
            'محصول' => Product::count(),
            'سفارش پرداخت‌شده' => Order::where('status', 'paid')->count(),
        ];

        $resolved = [];

        foreach ($content['stats'] ?? [] as $stat) {
            $label = trim((string) ($stat['label'] ?? ''));
            $value = trim((string) ($stat['value'] ?? ''));

            if ($label === '') {
                continue;
            }

            if ($value === '') {
                $count = $live[$label] ?? 0;

                if ($count === 0) {
                    // بدون دیتای واقعی، ردیف بی‌عدد رندر نمی‌شود
                    continue;
                }

                $value = '+'.self::formatFa($count);
            }

            $resolved[] = ['value' => $value, 'label' => $label];
        }

        return $resolved;
    }

    /** نمایش فارسی عدد با جداکنندهٔ هزارگان */
    private static function formatFa(int $number): string
    {
        return number_format($number);
    }
}
