<?php

namespace App\Http\Controllers\Install;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

    /**
     * فاز ۲۳ — اگر هیچ مدیری وجود نداشت، مدیر پیش‌فرض بساز
     * (admin / admin1234 — صفحه‌ی «پایان نصب» کاربر را به تغییر آن مکلف می‌کند)
     */
    protected function createDefaultAdmin(BufferedOutput $output): void
    {
        try {
            if (! \Schema::hasTable('admins') || DB::table('admins')->count() > 0) {
                return;
            }

            $adminId = DB::table('admins')->insertGetId([
                'first_name' => 'مدیر',
                'last_name' => 'سایت',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => \Illuminate\Support\Facades\Hash::make('admin1234'),
                'level' => 'admin',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $role = DB::table('roles')->orderBy('id')->first();

            if ($role && \Schema::hasTable('admin_role')) {
                DB::table('admin_role')->insertOrIgnore([
                    'admin_id' => $adminId,
                    'role_id' => $role->id,
                ]);
            }

            $output->writeln('مدیر پیش‌فرض ایجاد شد (admin / admin1234) — بعد از ورود رمز را عوض کنید.');
        } catch (\Throwable $e) {
            // ساخت مدیر اختیاری است — خطا نباید نصب را متوقف کند
            $output->writeln('admin skip: '.$e->getMessage());
        }
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
            $alreadySeeded = is_file($this->seedMarkerPath())
                || $this->databaseHasSeedData();

            if (! $alreadySeeded) {
                Artisan::call('db:seed', ['--force' => true], $output);
                @file_put_contents($this->seedMarkerPath(), now()->toDateTimeString());

                // فاز ۲۳ — مدیر پیش‌فرض: بدون آن ورود به پنل پس از نصب غیرممکن بود
                $this->createDefaultAdmin($output);

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
            @file_put_contents($this->seedMarkerPath(), now()->toDateTimeString());

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
