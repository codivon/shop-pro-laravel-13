<?php

namespace App\Http\Controllers\Install;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * فاز ۲۱ — اجرای مرحله‌به‌مرحله‌ی مایگریشن‌ها و سیدها (AJAX)
 *
 * هر درخواست فقط «یک» مایگریشن (یا بعد از اتمام مایگریشن‌ها، «یک» سیدر) را اجرا می‌کند.
 * چون هر درخواست کوتاه است، Timeout هاست رخ نمی‌دهد و اگر صفحه قطع شود،
 * با رفرش دقیقاً از همان‌جا ادامه می‌دهد.
 *
 * فاز ۲۷ — رفع دو باگ گزارش‌شده‌ی کاربر:
 *  ۱) «آخرِ ساخت جداول پیام خطا می‌آمد و با تلاش مجدد سبز می‌شد ولی سیدرها وارد نشده بودند»
 *     علت: db:seed کل سیدرها را در «یک درخواست» اجرا می‌کرد؛ روی سیستم‌های کند
 *     درخواست وسط کار می‌مرد (بعد از RoleSeeder که جدول roles را پر می‌کند) و
 *     databaseHasSeedData() در تلاش مجدد، «سید ناقص» را «سیدشده‌ی کامل» فرض می‌کرد
 *     و سید را کلاً رد می‌کرد → سبز شدن بدون داده‌ی اولیه.
 *     حالا هر سیدر جدا اجرا و در فایل ردیاب (installer-seeded.json) ثبت می‌شود؛
 *     تلاش مجدد دقیقاً از همان سیدر ناتمام ادامه می‌دهد — نه از صفر، نه رد شدن کامل.
 *  ۲) خطاهای تکراری (۱۰۶۲) ناشی از نیمه‌کاره‌ماندن یک سیدر، به‌عنوان «قبلاً ثبت شده»
 *     مدیریت می‌شوند تا نصب قابل ادامه باشد.
 */
class MigrateController extends Controller
{
    /** حداکثر زمان هر درخواستِ مرحله */
    protected const STEP_TIME_LIMIT = 240;

    /**
     * آیا دیتابیس از قبل «داده‌ی سیدشده» دارد؟ (اجرای مجدد نصب را امن می‌کند — فاز ۲۱/۲۳)
     *
     * فاز ۲۳: فقط جدول‌هایی که «فقط» توسط Seeder پر می‌شوند بررسی شوند.
     * (options/currencies/settings از نسخه‌ی قبلی حذف شدند — بعضی مایگریشن‌ها
     * خودشان ردیف پیش‌فرض درج می‌کنند و باعث می‌شد سید کلاً رد شود و
     * جدول settings اصلا ساخته نشود!)
     */
    protected function databaseHasSeedData(): bool
    {
        foreach (['roles', 'admins', 'users'] as $table) {
            try {
                if (\Schema::hasTable($table) && DB::table($table)->count() > 0) {
                    return true;
                }
            } catch (\Throwable $e) {
                // جدول هنوز موجود نیست — ادامه
            }
        }

        return false;
    }

    /**
     * مسیر نشانگر «سید انجام شد» — فاز ۲۳: مسیر قطعی storage/app
     *
     * قبلاً از Storage::disk('local') استفاده می‌شد که در این پروژه ریشه‌اش
     * public/uploads است — یعنی نشانگر داخل وب‌روت می‌افتاد (از وب قابل دسترس،
     * و با هر تغییر پیکربندی دیسک یا پاک‌کردن uploads، رفتار نصب عوض می‌شد).
     */
    protected function seedMarkerPath(): string
    {
        return storage_path('app/installer-seeded');
    }

    /** فاز ۲۷ — فایل ردیاب سیدرها (کدام سیدرها کامل شده‌اند) */
    protected function seedTrackerPath(): string
    {
        return storage_path('app/installer-seeded.json');
    }

    /** خواندن ردیاب — ['done' => [کلاس...], 'finished' => bool] */
    protected function readTracker(): array
    {
        try {
            if (is_file($this->seedTrackerPath())) {
                $json = json_decode((string) file_get_contents($this->seedTrackerPath()), true);
                if (is_array($json)) {
                    return [
                        'done'     => array_values((array) ($json['done'] ?? [])),
                        'finished' => (bool) ($json['finished'] ?? false),
                    ];
                }
            }
        } catch (\Throwable $e) {
            // فایل خراب است → از صفر شروع می‌شود
        }

        return ['done' => [], 'finished' => false];
    }

    /** نوشتن ردیاب */
    protected function writeTracker(array $done, bool $finished = false): void
    {
        try {
            @file_put_contents(
                $this->seedTrackerPath(),
                json_encode(['done' => array_values($done), 'finished' => $finished, 'at' => now()->toDateTimeString()], JSON_UNESCAPED_UNICODE),
            );
        } catch (\Throwable $e) {
            // غیرحیاتی
        }
    }

    /*
     * فاز ۲۵ — ساخت «مدیر پیش‌فرض» حذف شد.
     *
     * از این پس آخرین مرحله‌ی نصب، صفحه‌ی «ثبت‌نام مدیر» است (/setup):
     * مدیر خودش نام کاربری/گذرواژه/آدرس اختصاصی پنل را انتخاب می‌کند و
     * همه‌ی کارهای پس از نصب (شورت‌کات استایل فرنت در public، theme:config،
     * optimize و…) همان‌جا انجام می‌شود.
     * صفحه‌ی «نصب کامل شد» دکمه‌ای برای هدایت به این صفحه دارد.
     */

    public function index()
    {
        return view('vendor.installer.database');
    }

    public function step(): \Illuminate\Http\JsonResponse
    {
        @set_time_limit(self::STEP_TIME_LIMIT);
        @ignore_user_abort(true);

        $output = new BufferedOutput;

        try {
            // ---------- لیست مایگریشن‌های اجرا نشده ----------
            $files = collect(glob(database_path('migrations/*.php')))
                ->map(fn ($f) => basename($f, '.php'))
                ->sort()
                ->values();

            $ran = [];

            try {
                $ran = DB::table('migrations')->pluck('migration')->all();
            } catch (\Throwable $e) {
                // جدول migrations هنوز ساخته نشده — همه pending هستند
            }

            $pending = $files->reject(fn ($f) => in_array($f, $ran))->values();

            // ---------- اجرای یک مایگریشن در هر درخواست ----------
            if ($pending->isNotEmpty()) {
                $current = $pending->first();

                Artisan::call('migrate', [
                    '--path' => 'database/migrations/' . $current . '.php',
                    '--force' => true,
                ], $output);

                return response()->json([
                    'phase' => 'migrate',
                    'current' => $current,
                    'total' => $files->count(),
                    'done' => $files->count() - $pending->count(),
                    'remaining' => $pending->count() - 1,
                    'log' => trim($output->fetch()),
                    'error' => null,
                ]);
            }

            // ---------- فاز ۲۷ — مایگریشن‌ها تمام شد → سیدرها یکی‌یکی ----------
            $seeders = \Database\Seeders\DatabaseSeeder::seederClasses();
            $tracker = $this->readTracker();

            // نشانگر قدیمی (نصب قبلی کامل) یا داده‌ی موجود (نصب ناقص قبلی روی همین
            // دیتابیس) → سید کلاً رد می‌شود تا داده‌ی سایت زنده تکثیر نشود
            $legacyMarker = is_file($this->seedMarkerPath());

            if ($tracker['finished'] || $legacyMarker) {
                $alreadySeeded = true;
            } elseif (empty($tracker['done']) && $this->databaseHasSeedData()) {
                // بدون ردیاب ولی دیتابیس داده دارد = نصب قبلی با نسخه‌ی قدیمی نصاب
                $alreadySeeded = true;
            } else {
                $alreadySeeded = false;
            }

            if (! $alreadySeeded) {
                // اولین سیدرِ هنوز انجام‌نشده
                $current = null;
                foreach ($seeders as $seeder) {
                    if (! in_array($seeder, $tracker['done'], true)) {
                        $current = $seeder;
                        break;
                    }
                }

                if ($current !== null) {
                    Artisan::call('db:seed', [
                        '--class' => $current,
                        '--force' => true,
                    ], $output);
                    $log = trim($output->fetch());

                    // درج تکراری (نیمه‌کاره‌ی تلاش قبلی) = سیدر قبلاً بخشی از داده را
                    // گذاشته؛ ادامه‌ی نصب مهم‌تر از دوباره‌کاری است — مثل «done» رفتار می‌شود
                    $duplicate = preg_match('/(1062|Duplicate entry|duplicate key)/i', $log) === 1;

                    $tracker['done'][] = $current;
                    $this->writeTracker($tracker['done'], false);

                    return response()->json([
                        'phase' => 'seed',
                        'current' => $current,
                        'total' => $files->count(),
                        'done' => $files->count(),
                        'remaining' => 0,
                        'seed_done' => count($tracker['done']),
                        'seed_total' => count($seeders),
                        'duplicate' => $duplicate,
                        'log' => $log !== '' ? $log : ('✔ ' . class_basename($current)),
                        'error' => null,
                    ]);
                }
            }

            // ---------- همه‌ی سیدرها کامل شد (یا سید رد شد) ----------
            $this->writeTracker(count($tracker['done']) === count($seeders) ? $tracker['done'] : $seeders, true);
            @file_put_contents($this->seedMarkerPath(), now()->toDateTimeString());

            // فاز ۲۵ — مدیر پیش‌فرض ساخته نمی‌شود؛ مدیر در صفحه‌ی ثبت‌نام (/setup) ساخته می‌شود

            // فاز ۲۵ — درایور سشن/صف همین‌جا (بعد از ساخت جداول) دیتابیسی می‌شود
            // تا سشن ثبت‌نامِ مدیر در صفحه‌ی بعد، واقعاً در جدول sessions ذخیره شود
            // و ورود خودکار پس از ثبت‌نام از بین نرود.
            //
            // ★ فاز ۲۷ — پارامتر سوم «false»: هرگز config:cache داخل همین درخواست!
            //   قبلاً change_env با پیش‌فرض true وسط درخواستِ آخرِ نصب config:cache
            //   اجرا می‌کرد که یک Application دوم را بوت می‌کند؛ روی هاست‌های کند
            //   همین درخواست کشته می‌شد → پیام خطا در پایان کار → «تلاش مجدد» سبز
            //   می‌شد (چون همه‌چیز قبلاً انجام شده بود). درایور جدید در درخواستِ
            //   بعدی (همین حلقه AJAX) خودکار اعمال می‌شود؛ کش نهایی هم SetupFinalizer
            //   بعد از ثبت‌نام مدیر با register_shutdown_function می‌سازد.
            try {
                if (config('session.driver') !== 'database') {
                    change_env('SESSION_DRIVER', 'database', false);
                    change_env('QUEUE_CONNECTION', 'database', false);
                }
            } catch (\Throwable $e) {
                $output->writeln('env skip: ' . $e->getMessage());
            }

            return response()->json([
                'phase' => 'done',
                'current' => null,
                'total' => $files->count(),
                'done' => $files->count(),
                'remaining' => 0,
                'seed_done' => count($seeders),
                'seed_total' => count($seeders),
                'log' => 'نصب دیتابیس کامل شد.',
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'phase' => 'error',
                'current' => null,
                'total' => 0,
                'done' => 0,
                'remaining' => 0,
                'log' => trim($output->fetch()),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
