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
| فاز ۴۶ — نصب‌کننده: قبل از نصب، هیچ ماژولی رجیستر/بوت نشود
|--------------------------------------------------------------------------
| ترتیب بوت لاراول: اول پرووایدرهای پکیج‌ها (از جمله nwidart) و بعد
| پرووایدرهای خود اپ (AppServiceProvider). یعنی «چکِ نصب» که در
| AppServiceProvider::boot() انجام می‌شود، بعد از بوت ماژول‌هاست.
|
| اگر ماژولی (مثل RackWidget روی سرور کاربر) در boot() خودش وضعیت نصبش را
| از دیتابیس بپرسد (select * from installed_modules where slug = ...)، با
| .env ناقص یا بدون اعتبارنامه‌ی درست، همان اولین درخواست ۵۰۰ می‌دهد و هرگز
| به /install ریدایرکت نمی‌شود — دقیقاً باگ گزارش‌شده‌ی کاربر.
|
| راه‌حل: وقتی نشان نصب (storage/installed) وجود ندارد، قبل از اینکه
| nwidart ماژول‌ها را بخواند، مسیرهای اسکن ModuleManifest خالی می‌شوند؛
| در نتیجه هیچ module.json خوانده نمی‌شود، هیچ پرووایدر ماژولی
| رجیستر/بوت نمی‌شود و هیچ فایل helperای از ماژول‌ها include نمی‌شود.
| کش پرووایدرها هم مشکلی ایجاد نمی‌کند: ProviderRepository با مقایسه‌ی
| لیست پرووایدرها (shouldRecompile) خودش manifest را از نو می‌سازد و بعد
| از نصب (بودن نشان) دوباره برمی‌گردد.
|
| نکته: این گیت هم برای HTTP و هم برای console است — در حالت نصب‌نشده
| مایگریشن/کش‌سازی ماژول‌ها هم نباید اتفاق بیفتد. hook به‌صورت resolving
| است چون singleton مذکور بعداً توسط خودِ nwidart تعریف می‌شود؛ callback
| ما در لحظه‌ی اولین resolve شدن (وسط register() پکیج) اجرا می‌شود.
*/
if (! is_file(__DIR__.'/../storage/installed')) {
    /*
     * فاز ۴۶ (لایه‌ی اول) — ریدایرکتِ پیش از هر پرووایدر
     *
     * این گیت «قبل از رجیستر شدن حتی یک پرووایدر» اجرا می‌شود؛ یعنی پاسخ
     * دقیق به این نیاز که «قبل از پکیج‌ها چک شود پروژه نصب است یا نه».
     * هر درخواست HTTP به‌جز مسیرهای /install* بلافاصله با ۳۰۲ به /install
     * می‌رود — بدون بوتِ هیچ ماژول/میدلور/سرویسی؛ پس هیچ کدِ وابسته به
     * دیتابیس (پرووایدر ماژول‌ها، پکیج‌های ثالث و ...) فرصت خطا دادن ندارد.
     *
     * کنسول (artisan/test) مستثناست — ابزارهای نصب باید کار کنند.
     * صفحات /install خودشان در AppServiceProvider گارد کامل دارند.
     */
    if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
        $__installPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');

        if (strtok($__installPath ?: '/', '/') !== 'install') {
            header('Location: /install', true, 302);
            exit;
        }
    }

    /*
     * فاز ۴۶ (لایه‌ی دوم) — ماژول‌ها در حالت نصب‌نشده لود نشوند
     * (حتی برای خود صفحات /install و دستورات artisan)
     */
    $app->resolving(\Nwidart\Modules\ModuleManifest::class, function ($manifest): void {
        $manifest->paths = \Illuminate\Support\Collection::empty();
    });

    /*
     * فاز ۴۶ (لایه‌ی سوم) — Pulse در حالت نصب‌نشده کاملاً خاموش
     * recoderهای Pulse در boot پکیج (قبل از AppServiceProvider) فعال می‌شوند
     * و در پایان درخواست داخل pulse_entries/pulse_aggregates درج می‌کنند؛
     * با .env ناقص همین درج‌ها خطای دیتابیس می‌دادند. با مقداردهی پیش از
     * لود کانفیگ، config/pulse.php مقدار enabled=false می‌گیرد.
     */
    $_ENV['PULSE_ENABLED'] = 'false';
    $_SERVER['PULSE_ENABLED'] = 'false';

    /*
     * فاز ۴۸ (لایه‌ی چهارم) — صف و زمان‌بند در حالت نصب‌نشده خاموش شوند
     *
     * ورکر صف (queue:work) یک دیمن است؛ کانفیگش را در لحظه‌ی استارت در حافظه
     * نگه می‌دارد و تا پایان عمرش همان را می‌خواند. اگر کاربر قبل از نصب
     * ورکر را روی سرور روشن گذاشته باشد (سناریوی واقعی سرور کاربر)، پیش از
     * نصب هر ۳~۴ ثانیه کوئریِ select از جدول jobs می‌زند و چون جدول وجود
     * ندارد، لاگ laravel.log پر از خطای «Table 'jobs' doesn't exist»
     * می‌شود (مورد مشاهده‌شده در لاگ سرور) و در بدترین حالت هم با دیتابیسِ
     * نصب‌نشده مدام اتصال برقرار می‌کند.
     *
     * راه‌حل: پیش از نصب، اگر دستور جاری ورکر یا زمان‌بند باشد، با پیامِ
     * شفاف و کد خروج ۰ خارج می‌شویم — بدون بوت هیچ پرووایدر و بدون لاگِ
     * خطای دیتابیس. بعد از نصب (ساخت نشان storage/installed) رفتار عادی.
     * دستورهای موردنیاز نصب (migrate، cache:clear، نصب‌کننده و ...) دست
     * نخورده‌اند.
     */
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        $__consoleCommand = $_SERVER['argv'][1] ?? '';

        if (in_array($__consoleCommand, ['queue:work', 'queue:listen', 'schedule:run'], true)) {
            fwrite(STDERR, '[نصب‌کننده] برنامه هنوز نصب نشده است (فایل storage/installed وجود ندارد).'."\n"
                .'[نصب‌کننده] اجرای صف/زمان‌بند پیش از نصب متوقف شد؛ پس از اتمام نصب دوباره اجرا کنید.'."\n");
            exit(0);
        }

        unset($__consoleCommand);
    }
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
