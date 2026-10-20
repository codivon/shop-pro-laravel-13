<?php

/*
|--------------------------------------------------------------------------
| فاز ۲۳ — بوت ایمن برای نصبِ خام (کلون تازه از گیت)
|--------------------------------------------------------------------------
| روی یک کلون خام از گیت سه مشکل پشت‌سرهم 500 می‌دهد:
|   1) پوشه‌ی bootstrap/cache وجود ندارد (گیت پوشه‌ی خالی را نگه نمی‌دارد) →
|      ساخت PackageManifest در هر request با «failed to open stream» می‌میرد؛
|   2) فایل .env اصلا وجود ندارد → نه پیکربندی دیتابیس نصب‌کننده در دسترس است؛
|   3) اگر APP_KEY خالی باشد، همان اولین درخواستِ /install هنگام رمزنگاری
|      کوکی/نشست با «No application encryption key has been specified.» 500 می‌دهد.
|
| این بلوک هر سه حالت را «قبل از بوت شدن اپ» (هم HTTP و هم artisan) برطرف
| می‌کند؛ وقتی همه‌چیز سر جای خودش است هزینه‌اش فقط چند is_dir/is_file است.
|
| این فایل همیشه بعد از vendor/autoload.php بارگذاری می‌شود، پس autoloader
| در دسترس است؛ اما از کلاس‌های فریمورک استفاده نمی‌کنیم تا وابسته به بوت نباشد.
*/

$__installBasePath = $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__);

// 1) bootstrap/cache (خروجی کش manifest/config/routes) باید وجود داشته باشد
if (! is_dir($__installBasePath.'/bootstrap/cache')) {
    @mkdir($__installBasePath.'/bootstrap/cache', 0755, true);
}

// 2) اگر .env نیست، از نمونه بساز — پیش از LoadEnvironmentVariables همین request
if (! is_file($__installBasePath.'/.env') && is_file($__installBasePath.'/.env.example')) {
    @copy($__installBasePath.'/.env.example', $__installBasePath.'/.env');
}

// 3) کلید رمزنگاری خالی/ناموجود → تولید و ماندگاری در .env (پیش از EncryptCookies)
if (is_file($__installBasePath.'/.env')) {
    $__envContent = @file_get_contents($__installBasePath.'/.env');

    if ($__envContent !== false && ! preg_match('/^APP_KEY=base64:\S{20,}/m', $__envContent)) {
        $__envKey = 'base64:'.base64_encode(random_bytes(32));

        if (preg_match('/^APP_KEY=.*$/m', $__envContent)) {
            $__envContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$__envKey, $__envContent, 1);
        } else {
            $__envContent = 'APP_KEY='.$__envKey."\n".ltrim($__envContent);
        }

        @file_put_contents($__installBasePath.'/.env', $__envContent);
    }
}

unset($__installBasePath, $__envContent, $__envKey);

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Dynamic Module Autoloader
|--------------------------------------------------------------------------
| این autoloader در هر request اجرا می‌شه و خودش ماژول‌های نصب‌شده رو پیدا می‌کنه
| کاملاً مستقل از composer dump-autoload - مناسب سرورهای shared hosting
|--------------------------------------------------------------------------
*/
$modulesBasePath = dirname(__DIR__) . '/Modules';
if (is_dir($modulesBasePath)) {
    spl_autoload_register(function (string $class) use ($modulesBasePath): void {
        // فقط کلاس‌های Modules\* رو پردازش کن
        if (strpos($class, 'Modules\\') !== 0) {
            return;
        }

        // تجزیه namespace: Modules\Story\Providers\StoryServiceProvider
        $parts = explode('\\', $class);
        if (count($parts) < 3) {
            return;
        }

        $moduleName = $parts[1]; // Story
        $relativePath = implode('/', array_slice($parts, 2)); // Providers/StoryServiceProvider

        // مسیرهای ممکن برای پیدا کردن فایل
        $candidates = [
            // 1. استاندارد PSR-4 با app/ (پیش‌فرض nwidart)
            $modulesBasePath . '/' . $moduleName . '/app/' . $relativePath . '.php',
            // 2. بدون app/ (برای موارد خاص)
            $modulesBasePath . '/' . $moduleName . '/' . $relativePath . '.php',
            // 3. حالت PascalCase (برخی ماژول‌ها)
            $modulesBasePath . '/' . $moduleName . '/app/' . $relativePath . '.php',
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }
    });
}

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
