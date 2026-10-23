<?php

namespace App\Providers;

use Illuminate\Http\Request;
use RachidLaasri\LaravelInstaller\Helpers\EnvironmentManager;

/**
 * فاز ۲۳ — بازنویسی ذخیره‌ی .env در نصب‌کننده (Wizard)
 *
 * مشکل نسخه‌ی قبلی: کل فایل .env «از صفر بازنویسی» می‌شد و فقط ~۲۵ کلید قدیمی
 * (MAIL_DRIVER، QUEUE_DRIVER، ...) در آن می‌ماند؛ یعنی هر کلید دیگری که نصب
 * محصول لازم دارد (TRUST_PROXIES، CORS_ALLOWED_ORIGINS، FRONT_THEME،
 * ADMIN_ROUTE_PREFIX، LOG_CHANNEL، MAIL_MAILER و ...) حذف می‌شد.
 *
 * نسخه‌ی جدید:
 *   1) کلیدهای موجود .env را می‌خواند و «فقط» کلیدهای مربوط به ویزارد را
 *      به‌روزرسانی می‌کند (merge) — بقیه دست‌نخورده می‌مانند؛
 *   2) فاز ۴۵ — اگر .env از قبل هست، کلیدهای ویزارد (اعتبارنامه‌ی دیتابیس و ...)
 *      با اطلاعات جدید بروزرسانی می‌شوند و APP_KEY نیز «همیشه» به‌صورت تازه
 *      تولید و جایگزین می‌شود (نصب = محیط تازه؛ سشن/کوکی‌های نصب قبلی باید
 *      باطل شوند). نبود APP_KEY هم همین‌جا ساخته می‌شود؛
 *   3) کلیدهای تکراری حذف و فایل به‌صورت اتمیک (tmp+rename) نوشته می‌شود؛
 *   4) اگر کش پیکربندی از نصب قبلی مانده باشد حذف می‌شود تا .env همان لحظه
 *      اعمال شود (روی هاست اشتراکی artisan config:clear همیشه در دسترس نیست).
 */
class MyEnvironmentManager extends EnvironmentManager
{
    /**
     * @var string
     */
    private $envPath;

    /**
     * @var string
     */
    private $envExamplePath;

    /**
     * Set the .env and .env.example paths.
     */
    public function __construct()
    {
        parent::__construct();

        $this->envPath = base_path('.env');
        $this->envExamplePath = base_path('.env.example');
    }

    /**
     * Save the form content to the .env file (merge-based).
     *
     * @param Request $request
     * @return string
     */
    public function saveFileWizard(Request $request)
    {
        $results = trans('installer_messages.environment.success');

        try {
            // ---------- 1) مقادیر ویزارد ----------
            $wizardValues = [
                'APP_NAME' => 'WeblakShop',
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_URL' => rtrim(url('/'), '/'),
                'DEBUGBAR_ENABLED' => 'false',
                'LOG_CHANNEL' => 'stack',
                'LOG_LEVEL' => 'debug',

                'DB_CONNECTION' => $request->database_connection ?: 'mysql',
                'DB_HOST' => $request->database_hostname,
                'DB_PORT' => $request->database_port,
                'DB_DATABASE' => $request->database_name,
                'DB_USERNAME' => $request->database_username,
                'DB_PASSWORD' => $request->database_password,

                'CURRENT_THEME' => 'WeblakShop',

                'BROADCAST_DRIVER' => 'log',
                'CACHE_DRIVER' => 'file',
                'SESSION_DRIVER' => 'file',
                'QUEUE_CONNECTION' => 'sync',
                'SESSION_LIFETIME' => '120',

                'REDIS_HOST' => '127.0.0.1',
                'REDIS_PASSWORD' => 'null',
                'REDIS_PORT' => '6379',
                'REDIS_CLIENT' => 'predis',

                'MAIL_MAILER' => 'smtp',
                'MAIL_HOST' => 'smtp.mailtrap.io',
                'MAIL_PORT' => '2525',
                'MAIL_USERNAME' => '',
                'MAIL_PASSWORD' => '',
                'MAIL_ENCRYPTION' => 'tls',
                // فاز ۲۳: هرگز null/خالی نماند — spatie/laravel-backup روی boot 500 می‌دهد
                'MAIL_FROM_ADDRESS' => 'hello@example.com',
                'MAIL_FROM_NAME' => 'WeblakShop',
            ];

            // ---------- 2) خواندن .env فعلی ----------
            if (! is_file($this->envPath) && is_file($this->envExamplePath)) {
                @copy($this->envExamplePath, $this->envPath);
            }

            $existing = is_file($this->envPath) ? @file_get_contents($this->envPath) : '';
            $existing = $existing === false ? '' : $existing;

            // کلیدهای فعلی به ترتیب اولین وقوع (برای حفظ چیدمان) + تشخیص تکراری‌ها
            // ⚠ هشدار فاز ۲۳: هرگز از /\R/ برای split استفاده نکنید — در متن فارسیِ UTF-8
            // بایت 0x85 (نیمه‌ی دوم حرف «م») بدون مود /u به‌عنوان newline تفسیر می‌شود
            // و خط کامنت فارسی وسطش دو نیم می‌شود → dotenv با "invalid name" می‌میرد.
            $normalized = str_replace(["\r\n", "\r"], "\n", $existing);
            $seen = [];
            $outLines = [];

            foreach (explode("\n", $normalized) as $line) {
                $trimmed = ltrim($line);

                // کامنت یا خط خالی → عیناً حفظ کن
                if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                    $outLines[] = $line;

                    continue;
                }

                if (preg_match('/^([A-Z0-9_]+)\s*=\s*(.*)$/', $trimmed, $m)) {
                    $key = $m[1];

                    if (isset($seen[$key])) {
                        continue; // حذف کلید تکراری (مثل CURRENT_THEME دوبار در نسخه قبلی)
                    }

                    $seen[$key] = true;

                    if (array_key_exists($key, $wizardValues)) {
                        // کلید ویزارد → مقدار جدید
                        $outLines[] = $key.'='.$this->formatEnvValue($wizardValues[$key]);
                        unset($wizardValues[$key]);
                    } else {
                        $outLines[] = $key.'='.rtrim($m[2]);
                    }

                    continue;
                }

                $outLines[] = $line;
            }

            // ---------- 3) کلیدهای ویزاردی که در .env نبودند → اضافه ----------
            foreach ($wizardValues as $key => $value) {
                $outLines[] = $key.'='.$this->formatEnvValue($value);
            }

            // پیشوند پنل: فقط اگر از قبل تعیین نشده (سلیقه‌ی کاربر دست‌نخورده بماند)
            if (! isset($seen['ADMIN_ROUTE_PREFIX'])) {
                $outLines[] = 'ADMIN_ROUTE_PREFIX=admin2old';
            }

            // ---------- 4) فاز ۴۵ — APP_KEY همیشه تازه تولید می‌شود ----------
            // (نصب = محیط تازه؛ حتی وقتی .env از نصب قبلی مانده، کلید جدید نوشته
            // می‌شود تا کوکی/سشن‌های قبلی اعتبار نداشته باشند)
            $content = implode("\n", $outLines);

            $key = 'base64:'.base64_encode(random_bytes(32));

            if (preg_match('/^APP_KEY=.*$/m', $content)) {
                $content = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $content, 1);
            } else {
                $content = 'APP_KEY='.$key."\n".$content;
            }

            // ---------- 5) نوشتن اتمیک + حذف کش پیکربندی ----------
            $tmp = $this->envPath.'.tmp-'.getmypid();

            if (@file_put_contents($tmp, $content) === false || ! @rename($tmp, $this->envPath)) {
                @file_put_contents($this->envPath, $content);
                @unlink($tmp);
            }

            foreach (['config.php', 'routes-v7.php', 'routes.php', 'packages.php', 'services.php'] as $cacheFile) {
                $path = base_path('bootstrap/cache/'.$cacheFile);

                if (is_file($path)) {
                    @unlink($path);
                }
            }
        } catch (\Throwable $e) {
            // \Throwable نه فقط Exception — خطاهای Error هم باید 500 نشوند
            $results = trans('installer_messages.environment.errors');
        }

        return $results;
    }

    /**
     * مقدار حاوی فاصله/کاراکتر خاص → در گیومه
     */
    protected function formatEnvValue($value): string
    {
        $value = trim((string) $value);

        if ($value === '' || preg_match('/[\s#$"\']/', $value)) {
            return "'".str_replace(["\\", "'"], ["\\\\", "\\'"], $value)."'";
        }

        return $value;
    }
}
