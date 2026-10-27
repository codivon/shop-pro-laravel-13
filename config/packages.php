<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API تنظیمات سرور مدیریت پکیج‌ها
    |--------------------------------------------------------------------------
    */
    'api' => [
        // فاز ۱۰۶ — سرور مارکت از update.weblak.ir (هاست آلمان) به update.opashop.ir (هاست ایران) منتقل شد.
        'base_url' => env('PACKAGES_API_URL', 'https://update.opashop.ir'),
        // ✅ حذف option() و استفاده مستقیم از env()
        'token' => env('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN', env('PACKAGES_API_TOKEN')),
        'project_key' => env('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN', env('PACKAGES_API_PROJECT_KEY')),
        'project_url' => env('PACKAGES_PROJECT_URL', env('APP_URL')),
        'timeout' => env('PACKAGES_API_TIMEOUT', 120),
        'connect_timeout' => env('PACKAGES_API_CONNECT_TIMEOUT', 30),
        'cache_ttl' => env('PACKAGES_CACHE_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | تنظیمات nwidart/laravel-modules
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'path' => base_path('Modules'),
        'namespace' => 'Modules\\',
    ],

    /*
    |--------------------------------------------------------------------------
    | امنیت - تأیید امضای فایل ZIP
    |--------------------------------------------------------------------------
    | اگر فعال باشد، هش SHA-256 فایل ZIP با هشی که API برمی‌گرداند
    | مقایسه می‌شود تا از دستکاری فایل جلوگیری شود.
    */
    'security' => [
        'verify_signature' => env('PACKAGES_VERIFY_SIGNATURE', true),
        'signature_field' => 'signature', // نام فیلد در response API
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue - نصب غیرهمزمان
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'connection' => env('PACKAGES_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
        'queue' => env('PACKAGES_QUEUE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | پرداخت
    |--------------------------------------------------------------------------
    */
    'payment' => [
        'callback_route' => 'admin.packages.payment.callback',
        'timeout' => 600, // ثانیه - مدت زمان معتبر ماندن تراکنش
    ],

    /*
    |--------------------------------------------------------------------------
    | دانلود و ذخیره‌سازی فایل‌های ZIP
    |--------------------------------------------------------------------------
    */
    'download' => [
        'disk' => 'local',
        'temp_path' => 'packages/temp',
        'timeout' => env('PACKAGES_DOWNLOAD_TIMEOUT', 300),
        'connect_timeout' => env('PACKAGES_DOWNLOAD_CONNECT_TIMEOUT', 30),
        'chunk_size' => 1024 * 1024, // 1MB
        'retry_times' => env('PACKAGES_DOWNLOAD_RETRY', 3),
        'retry_sleep' => env('PACKAGES_DOWNLOAD_RETRY_SLEEP', 1000), // میلی‌ثانیه
    ],

    /*
    |--------------------------------------------------------------------------
    | مسیرهای پنل ادمین
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => 'admin/packages',
        'middleware' => ['web', 'admin'], // 'admin' = middleware احراز هویت ادمین
        'as' => 'admin.packages.',
    ],

    /*
    |--------------------------------------------------------------------------
    | کش پیشرفته - TTL بر حسب دقیقه
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'list_ttl' => 5,    // کش لیست پکیج‌ها
        'detail_ttl' => 10,   // کش جزئیات پکیج
    ],

];
