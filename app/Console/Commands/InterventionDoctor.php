<?php

namespace App\Console\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Command;

/*
 * فاز ۷۵ — پزشک Intervention Image
 *
 * سناریو: سرور لایو هنگام ذخیرهٔ محصول این خطا را می‌داد:
 *   Class Intervention\Image\EncodedImage contains 5 abstract methods ...
 *   (FileInterface::save, toFilePointer, size, ...)
 * ریشه: فایل‌های vendor/intervention/image روی هاست از دو نسخهٔ مختلف v3
 * مخلوط شده‌اند (و یا OPcache کلاس‌های قدیمی را نگه داشته است).
 *
 * این فرمان «بدون بارگذاری هیچ کلاسی» وضعیت را تشخیص می‌دهد و درمان دقیق
 * را چاپ می‌کند — تا کاربر خروجی را بفرستد و ریشه در یک نگاه پیدا شود.
 *
 * استفاده:
 *   php artisan ops:intervention-doctor          گزارش خوانا
 *   php artisan ops:intervention-doctor --json   خروجی ساخت‌یافته برای پیوست در تیکت
 *   php artisan ops:intervention-doctor --write-opcache-reset   ساخت فایل یک‌بارمصرف ریست OPcache در public
 *
 * فاز ۷۷ — گزینهٔ --write-opcache-reset: چون validate_timestamps=0 روی هاست، بعد از
 * هر git pull باید حتماً OPcache ریست شود؛ در cPanel همیشه دکمهٔ آن در دسترس نیست؛
 * این گزینه یک فایل PHP یک‌بارمصرف با نام تصادفی در public می‌سازد که با یک بار
 * بازدید در مرورگر، کش OPcache همان FPM را پاک می‌کند — سپس باید حذفش کنید.
 *
 * کد خروج: 0 = سالم | 1 = معیوب (برای اسکریپت‌ها)
 */
class InterventionDoctor extends Command
{
    protected $signature = 'ops:intervention-doctor
                            {--json : خروجی JSON ساخت‌یافته (برای پیوست‌کردن در گزارش)}
                            {--write-opcache-reset : ساخت فایل یک‌بارمصرف ریست OPcache در public و چاپ آدرس بازدید}';

    protected $description = 'تشخیص یکپارچگی vendor Intervention Image + قابلیت‌های GD + وضعیت OPcache (بدون بارگذاری کلاس‌ها)';

    public function handle(): int
    {
        $report = $this->diagnose();

        if ($this->option('write-opcache-reset')) {
            $this->writeOpcacheResetFile();
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->renderHuman($report);
        }

        return $report['healthy'] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * فاز ۷۷ — فایل یک‌بارمصرف ریست OPcache در public می‌سازد؛ با یک بار بازدید
     * در مرورگر، کش همان FPM پاک می‌شود (معادل دکمهٔ ریست در cPanel).
     */
    private function writeOpcacheResetFile(): void
    {
        try {
            $name = 'opcache-reset-'.bin2hex(random_bytes(4)).'.php';
            $content = <<<'PHP'
            <?php
            // فایل یک‌بارمصرف ریست OPcache — فاز ۷۷ — بعد از بازدید حذف شود!
            header('Content-Type: text/plain; charset=utf-8');

            if (function_exists('opcache_reset')) {
                echo @opcache_reset() ? 'OPcache reset OK' : 'opcache_reset failed';
            } else {
                echo 'OPcache is not loaded';
            }
            PHP;

            file_put_contents(public_path($name), $content);

            $url = rtrim(url('/'), '/').'/'.$name;

            $this->warn('فایل ریست OPcache ساخته شد: '.public_path($name));
            $this->line('۱) یک‌بار در مرورگر باز کنید: '.$url);
            $this->line('۲) باید «OPcache reset OK» ببینید.');
            $this->line('۳) فوراً همین فایل را از public حذف کنید:   rm public/'.$name);
            $this->newLine();
        } catch (\Throwable $e) {
            $this->error('ساخت فایل ریست OPcache ممکن نشد: '.$e->getMessage());
            $this->line('به‌جای آن از پنل هاست: cPanel → Reset OPcache یا ری‌استارت PHP-FPM.');
        }
    }

    /**
     * کل تشخیص — فقط تحلیل فایل/ini؛ هیچ کلاسی از Intervention autoload نمی‌شود
     * (چون خودِ autoload نقطهٔ انفجار روی vendor معیوب است)
     */
    private function diagnose(): array
    {
        // ── نسخه‌ها ─────────────────────────────────────────────────────
        $lockVersion = null;

        if (is_file(base_path('composer.lock'))) {
            $lock = json_decode((string) file_get_contents(base_path('composer.lock')), true);

            foreach (($lock['packages'] ?? []) as $package) {
                if (($package['name'] ?? '') === 'intervention/image') {
                    $lockVersion = $package['version'] ?? null;

                    break;
                }
            }
        }

        $installedVersion = null;

        try {
            if (class_exists(InstalledVersions::class)
                && InstalledVersions::isInstalled('intervention/image')) {
                $installedVersion = InstalledVersions::getPrettyVersion('intervention/image');
            }
        } catch (\Throwable) {
            $installedVersion = null;
        }

        // ── همهٔ کپی‌های intervention/image زیر پروژه ────────────────────
        $copies = [];

        $patterns = [
            base_path('vendor/intervention/image'),
            base_path('modules/*/vendor/intervention/image'),
            base_path('modules/*/*/vendor/intervention/image'),
            base_path('themes/*/vendor/intervention/image'),
            base_path('packages/*/vendor/intervention/image'),
            base_path('packages/*/*/vendor/intervention/image'),
        ];

        foreach ($patterns as $pattern) {
            foreach ((glob($pattern, GLOB_ONLYDIR) ?: []) as $dir) {
                $copy = [
                    'path' => $dir,
                    'is_main_vendor' => realpath($dir) === realpath(base_path('vendor/intervention/image')),
                    'composer_version' => null,
                    'api_style' => null,   // v3 | v2 | unknown
                    'integrity_ok' => null,
                ];

                $composerJson = $dir.'/composer.json';

                if (is_file($composerJson)) {
                    $meta = json_decode((string) file_get_contents($composerJson), true);
                    $copy['composer_version'] = $meta['version'] ?? null;
                }

                $src = $dir.'/src';

                if (is_dir($src) && is_file($src.'/EncodedImage.php')) {
                    $copy['api_style'] = 'v3';
                    $copy['integrity_ok'] = function_exists('shop_intervention_vendor_ok')
                        ? shop_intervention_vendor_ok($src)
                        : null;
                } elseif (is_dir($src) && is_file($src.'/ImageManager.php')) {
                    $copy['api_style'] = 'v2';
                    $copy['integrity_ok'] = null; // v2 — لایهٔ سازگار فاز ۷۳ هندل می‌کند
                } else {
                    $copy['api_style'] = 'unknown';
                }

                $copies[] = $copy;
            }
        }

        // ── قابلیت‌های GD ────────────────────────────────────────────────
        $gdFunctions = [
            'imagecreatefromstring', 'imagescale', 'imagecopyresampled',
            'imagecreatetruecolor', 'imagealphablending', 'imagesavealpha',
            'imagewebp', 'imagejpeg', 'imagepng', 'imagegif', 'imageavif',
            'imagerotate', 'imagepalettetotruecolor', 'exif_read_data',
        ];

        $gd = [
            'extension_loaded' => extension_loaded('gd'),
            'functions' => [],
            'decode_probe_png' => false,
        ];

        foreach ($gdFunctions as $function) {
            $gd['functions'][$function] = function_exists($function);
        }

        try {
            $probePng = (string) base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
                true
            );
            $probe = function_exists('imagecreatefromstring') ? @imagecreatefromstring($probePng) : false;

            if ($probe !== false && is_object($probe)) {
                imagedestroy($probe);
                $gd['decode_probe_png'] = true;
            }
        } catch (\Throwable) {
            $gd['decode_probe_png'] = false;
        }

        // ── وضعیت OPcache ────────────────────────────────────────────────
        $opcache = [
            'loaded' => extension_loaded('Zend OPcache'),
            'enable' => ini_get('opcache.enable'),
            'enable_cli' => ini_get('opcache.enable_cli'),
            'validate_timestamps' => ini_get('opcache.validate_timestamps'),
            'revalidate_freq' => ini_get('opcache.revalidate_freq'),
        ];

        $opcache['stale_risk'] = $opcache['loaded']
            && (string) $opcache['validate_timestamps'] === '0';

        // ── یکپارچگی vendor اصلی (پروب فاز ۷۵) ──────────────────────────
        $mainIntegrity = function_exists('shop_intervention_vendor_ok')
            ? shop_intervention_vendor_ok()
            : null;

        $mainCopyIntegrity = null;

        foreach ($copies as $copy) {
            if ($copy['is_main_vendor']) {
                $mainCopyIntegrity = $copy['integrity_ok'];

                break;
            }
        }

        $healthy = $mainIntegrity === true
            && ($mainCopyIntegrity === null || $mainCopyIntegrity === true)
            && $gd['decode_probe_png'];

        $guidance = $healthy
            ? [
                'vendor سالم است — اگر باز هم کرش Intervention داشتید، لاگ خطا را بفرستید.',
                'توصیهٔ همیشگی بعد از هر git pull: php artisan optimize:clear',
            ]
            : [
                '۱) در ریشهٔ پروژه اجرا کنید:  composer install  (یا اگر قفل باز می‌شود:  composer update intervention/image --with-all-dependencies)',
                '۲) کش‌های لاراول:  php artisan optimize:clear',
                '۳) OPcache را از پنل هاست ریست کنید (cPanel: Reset OPcache / ری‌استارت PHP-FPM) — اگر opcache.validate_timestamps=0 باشد، بدون این مرحله کلاس‌های قدیمی در حافظه می‌مانند و خطای «abstract methods» ادامه پیدا می‌کند.',
                '۴) اگر composer روی هاست در دسترس نیست، خروجی --json همین فرمان را برای تیم ارسال کنید تا کپی‌های معیوب مشخص شوند.',
                'توضیح: تا زمانی که vendor معیوب است، آپلود تصویر محصولات خودکار از مسیر «GD خالص» عبور می‌کند (فاز ۷۵) و سایت از کار نمی‌افتد.',
            ];

        return [
            'healthy' => $healthy,
            'versions' => [
                'installed' => $installedVersion,
                'composer_lock' => $lockVersion,
                'php' => PHP_VERSION,
            ],
            'vendor_integrity_main' => $mainIntegrity,
            'copies' => $copies,
            'gd' => $gd,
            'opcache' => $opcache,
            'guidance' => $guidance,
        ];
    }

    private function renderHuman(array $report): void
    {
        $mark = fn ($value): string => $value === true ? '✔' : ($value === false ? '✘' : '؟');

        $this->info('═══ پزشک Intervention Image (فاز ۷۵) ═══');

        $this->line('نسخهٔ نصب‌شده: '.($report['versions']['installed'] ?? 'نامشخص')
            .'   |   composer.lock: '.($report['versions']['composer_lock'] ?? 'نامشخص')
            .'   |   PHP: '.$report['versions']['php']);

        $this->newLine();
        $this->line('── یکپارچگی vendor اصلی: '.($report['vendor_integrity_main'] === true ? '✔ سالم' : ($report['vendor_integrity_main'] === false ? '✘ معیوب/مخلوط' : 'نامشخص')));

        foreach ($report['copies'] as $copy) {
            $this->line(sprintf(
                '   %s %s  [%s]  نسخه: %s',
                $copy['integrity_ok'] === false ? '✘' : ($copy['integrity_ok'] === true ? '✔' : '—'),
                $copy['path'],
                $copy['api_style'],
                $copy['composer_version'] ?? ($copy['is_main_vendor'] ? 'از InstalledVersions' : 'نامشخص')
            ));
        }

        $this->newLine();
        $this->line('── قابلیت‌های GD:');
        $this->line('   اکستنشن GD: '.$mark($report['gd']['extension_loaded'])
            .'   decode نمونهٔ PNG: '.$mark($report['gd']['decode_probe_png']));

        $missing = array_filter($report['gd']['functions'], fn ($ok) => ! $ok);

        if ($missing === []) {
            $this->line('   همهٔ توابع موردنیاز موجودند ✔');
        } else {
            $this->line('   توابع غایب: '.implode(', ', array_keys($missing)));
            $this->line('   (توابع غایب یعنی آن فرمت‌ها به‌طور خودکار به جایگزین سقوط می‌کنند — خط تصویر نمی‌شکند)');
        }

        $this->newLine();
        $this->line('── OPcache:');
        $this->line('   بارگذاری‌شده: '.$mark($report['opcache']['loaded'])
            .'   enable: '.var_export($report['opcache']['enable'], true)
            .'   validate_timestamps: '.var_export($report['opcache']['validate_timestamps'], true)
            .'   revalidate_freq: '.var_export($report['opcache']['revalidate_freq'], true));

        if ($report['opcache']['stale_risk']) {
            $this->warn('   ⚠️  validate_timestamps=0 است — بعد از هر git pull کلاس‌های قدیمی در حافظه می‌مانند؛ همین عامل تکرار خطای «abstract methods» بعد از pull بود!');
            $this->line('   درمان فوری (یکی از دو راه):');
            $this->line('   a) php artisan ops:intervention-doctor --write-opcache-reset   ← فایل می‌سازد؛ آدرسش را در مرورگر باز کنید و فایل را حذف کنید');
            $this->line('   b) پنل هاست: cPanel → MultiPHP Manager / Select PHP Version → Reset OPcache یا ری‌استارت PHP-FPM');
        }

        $this->newLine();

        if ($report['healthy']) {
            $this->info('✅ وضعیت کلی: سالم');
        } else {
            $this->error('❌ وضعیت کلی: معیوب — درمان:');
        }

        foreach ($report['guidance'] as $line) {
            $this->line('   '.$line);
        }
    }
}
