<?php

namespace App\Http\Controllers\Install;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * فاز ۲۱ — اجرای مرحله‌به‌مرحله‌ی مایگریشن‌ها و سیدها (AJAX)
 *
 * هر درخواست فقط «یک» مایگریشن را اجرا می‌کند (یا بعد از اتمام، سید را یک‌بار).
 * چون هر درخواست کوتاه است، Timeout هاست رخ نمی‌دهد و اگر صفحه قطع شود،
 * با رفرش دقیقاً از همان‌جا ادامه می‌دهد (لیست pending از جدول migrations
 * دوباره محاسبه می‌شود — کاملاً idempotent).
 */
class MigrateController extends Controller
{
    /** حداکثر زمان هر درخواستِ مرحله */
    protected const STEP_TIME_LIMIT = 240;

    /**
     * آیا دیتابیس از قبل داده‌های اولیه دارد؟ (اجرای مجدد نصب را امن می‌کند — فاز ۲۱)
     */
    protected function databaseHasSeedData(): bool
    {
        foreach (['roles', 'admins', 'users', 'options', 'settings', 'currencies'] as $table) {
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

            // ---------- مایگریشن‌ها تمام شد → سید (یک‌بار و idempotent) ----------
            $alreadySeeded = Storage::disk('local')->exists('installer-seeded')
                || $this->databaseHasSeedData();

            if (! $alreadySeeded) {
                Artisan::call('db:seed', ['--force' => true], $output);
                Storage::disk('local')->put('installer-seeded', now()->toDateTimeString());

                return response()->json([
                    'phase' => 'seed',
                    'current' => 'DatabaseSeeder',
                    'total' => $files->count(),
                    'done' => $files->count(),
                    'remaining' => 0,
                    'log' => trim($output->fetch()),
                    'error' => null,
                ]);
            }

            // دیتابیس از قبل داده دارد (نصب ناقص قبلی یا اجرای مجدد) → سید رد می‌شود
            Storage::disk('local')->put('installer-seeded', now()->toDateTimeString());

            // ---------- همه‌چیز انجام شد ----------
            return response()->json([
                'phase' => 'done',
                'current' => null,
                'total' => $files->count(),
                'done' => $files->count(),
                'remaining' => 0,
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
