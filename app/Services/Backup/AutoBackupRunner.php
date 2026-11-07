<?php

namespace App\Services\Backup;

use App\Mail\AutoBackupMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * پشتیبان‌گیری خودکار دیتابیس — فاز ۱۴۵
 *
 * مغزِ قابلیت «فعال‌سازی پشتیبان‌گیری خودکار»: کرون جاب (schedule:run هر دقیقه)
 * این کلاس را صدا می‌زند و خودش تصمیم می‌گیرد آیا موعد بکاپ رسیده یا نه.
 *
 *  - فقط جداول و دیتای جداول (ZIP شامل manifest.json + database.sql — بدون فایل‌ها)
 *  - فایل‌ها با پیشوند «auto-backup-» ذخیره می‌شوند تا از بکاپ‌های دستی تفکیک شوند
 *  - حذف خودکار: فقط بکاپ‌های «خودکار» قدیمی‌تر از N روز (بکاپ‌های دستی هرگز دست‌نخورده)
 *  - ایمیل: اگر حجم ≤ حد پیوست، فایل ضمیمه می‌شود؛ در غیر این صورت فقط مشخصات ارسال می‌شود
 *  - در هاست اشتراکی (بدون Symfony Process) مستقیماً در همین پروسه‌ی PHP اجرا می‌شود
 *    چون موتور دامپ SiteBackupService کاملاً PHP-only است (بدون mysqldump)
 *
 * گارد هم‌پوشانی: با Cache::add (اتمیک) — اگر بکاپ قبلی هنوز در جریان باشد،
 * تیکِ بعدی کرون رد می‌شود تا دو دامپ هم‌زمان روی دیتابیس فشار نزنند.
 */
class AutoBackupRunner
{
    /* ------------------------------------------------ کلیدهای option (مستقل از زبان) */

    public const OPT_ENABLED = 'backup_auto_enabled';        // on/off
    public const OPT_PERIOD = 'backup_auto_period';          // daily|weekly|monthly|custom
    public const OPT_CUSTOM_DAYS = 'backup_auto_custom_days'; // عدد روز در حالت دلخواه
    public const OPT_TIME = 'backup_auto_time';              // 'HH:MM'
    public const OPT_DELETE_DAYS = 'backup_auto_delete_days'; // ۰ = حذف خودکار خاموش
    public const OPT_EMAIL_ENABLED = 'backup_auto_email_enabled'; // on/off
    public const OPT_EMAIL_TO = 'backup_auto_email_to';      // گیرنده

    /** دفترِ اجرا — داخلی */
    public const OPT_LAST_SLOT = 'backup_auto_last_slot';    // زمانِ اسلاتِ اجراشده (Y-m-d H:i:s)
    public const OPT_LAST_RUN = 'backup_auto_last_run';      // زمان واقعی آخرین اجرا
    public const OPT_LAST_RESULT = 'backup_auto_last_result'; // JSON نتیجه/خطا برای نمایش در پنل

    /** پیشوند نام فایل بکاپ‌های خودکار */
    public const FILE_PREFIX = 'auto-backup';

    /** حداکثر حجم پیوست ایمیل — بالاتر از این، فایل فقط روی سرور می‌ماند */
    public const EMAIL_ATTACH_MAX_MB = 10;

    /** قفل هم‌پوشانی (ثانیه) */
    private const LOCK_TTL = 3600;

    /** کلید قفل هم‌پوشانی */
    private const LOCK_KEY = 'backup_auto_running_lock';

    /* ------------------------------------------------ تنظیمات خوانده‌شده از option */

    public static function enabled(): bool
    {
        return option(self::OPT_ENABLED, 'off') === 'on';
    }

    public static function period(): string
    {
        $period = (string) option(self::OPT_PERIOD, 'daily');

        return in_array($period, ['daily', 'weekly', 'monthly', 'custom'], true) ? $period : 'daily';
    }

    public static function customDays(): int
    {
        return max(1, (int) option(self::OPT_CUSTOM_DAYS, 3));
    }

    /** ساعت اجرا 'HH:MM' */
    public static function time(): string
    {
        $time = (string) option(self::OPT_TIME, '03:00');

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) ? $time : '03:00';
    }

    public static function deleteDays(): int
    {
        return max(0, (int) option(self::OPT_DELETE_DAYS, 180));
    }

    public static function emailEnabled(): bool
    {
        return option(self::OPT_EMAIL_ENABLED, 'off') === 'on';
    }

    public static function emailTo(): string
    {
        $to = trim((string) option(self::OPT_EMAIL_TO, ''));

        return $to !== '' ? $to : (string) option('info_email', '');
    }

    /** فاصله‌ی دوره بر حسب روز — ماهانه ≈ ۳۰ روز (طبق اسپک «بر اساس روز») */
    public static function periodDays(?string $period = null, ?int $customDays = null): int
    {
        return match ($period ?? self::period()) {
            'weekly' => 7,
            'monthly' => 30,
            'custom' => $customDays ?? self::customDays(),
            default => 1, // daily
        };
    }

    /* ------------------------------------------------ محاسبه‌ی موعد */

    /** اسلاتِ امروز: امروز ساعتِ تنظیم‌شده */
    public static function todaySlot(): Carbon
    {
        return Carbon::today()->setTimeFromTimeString(self::time());
    }

    /**
     * موعد رسیده است؟
     *  - الان ≥ اسلاتِ امروز (ساعت تنظیم‌شده گذشته)
     *  - و از اسلاتِ قبلیِ اجراشده ≥ فاصله‌ی دوره گذشته
     *    (اسلاتِ قبلی ذخیره می‌شود نه زمانِ واقعی اجرا → تاخیر کرون باعث درِفت نمی‌شود)
     */
    public static function isDue(): bool
    {
        if (! self::enabled()) {
            return false;
        }

        $slot = self::todaySlot();

        if (now()->lessThan($slot)) {
            return false;
        }

        $lastSlot = (string) option(self::OPT_LAST_SLOT, '');

        if ($lastSlot === '') {
            return true;
        }

        try {
            $next = Carbon::parse($lastSlot)->addDays(self::periodDays());
        } catch (\Throwable) {
            return true;
        }

        return now()->greaterThanOrEqualTo($next);
    }

    /**
     * اجرای بعدی برای نمایش در پنل (به وقتِ اسلات) + آیا از موعد گذشته؟
     * @return array{at: Carbon, overdue: bool, jalali: string}
     */
    public static function nextRun(): array
    {
        $lastSlot = (string) option(self::OPT_LAST_SLOT, '');
        $todaySlot = self::todaySlot();

        try {
            $next = $lastSlot !== ''
                ? Carbon::parse($lastSlot)->addDays(self::periodDays())
                : $todaySlot;
        } catch (\Throwable) {
            $next = $todaySlot;
        }

        if ($next->lessThan($todaySlot)) {
            $next = $todaySlot;
        }

        $overdue = now()->greaterThanOrEqualTo($next);

        return [
            'at' => $next,
            'overdue' => $overdue && self::enabled(),
            'jalali' => \Morilog\Jalali\Jalalian::fromDateTime($next)->format('Y/m/d — H:i'),
        ];
    }

    /* ------------------------------------------------ اجرا */

    /**
     * اگر موعد رسیده باشد بکاپ می‌گیرد (نقطه‌ی ورود کرون).
     * @return array{ran: bool, reason: string, result?: array}
     */
    public function runIfDue(): array
    {
        if (! self::enabled()) {
            return ['ran' => false, 'reason' => 'disabled'];
        }

        if (! self::isDue()) {
            return ['ran' => false, 'reason' => 'not_due'];
        }

        return $this->run();
    }

    /**
     * اجرای کامل: بکاپ فقط-دیتابیس + پاکسازی قدیمی‌ها + ایمیل + دفترِ اجرا
     *
     * @param  bool  $force  بدون توجه به موعد (برای تست/اجرای دستی)
     * @return array{ran: bool, reason: string, result?: array}
     */
    public function run(bool $force = false): array
    {
        if (! $force && ! self::isDue()) {
            return ['ran' => false, 'reason' => 'not_due'];
        }

        $slot = self::todaySlot()->format('Y-m-d H:i:s');

        // گارد هم‌پوشانی — تیک کرونِ بعدی تا پایان کار رد می‌شود
        if (! Cache::add(self::LOCK_KEY, now()->timestamp, self::LOCK_TTL)) {
            return ['ran' => false, 'reason' => 'already_running'];
        }

        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        try {
            $service = app(SiteBackupService::class);
            $allTables = array_column($service->tables(), 'name');

            // فقط جداول و دیتای جداول — بدون هیچ گروه فایل (اسپک فاز ۱۴۵)
            $result = $service->create($allTables, [], self::FILE_PREFIX);

            // دفترِ اجرا — اسلات ذخیره می‌شود تا درِفت نداشته باشیم
            option_update(self::OPT_LAST_SLOT, $slot);
            option_update(self::OPT_LAST_RUN, now()->format('Y-m-d H:i:s'));
            option_update(self::OPT_LAST_RESULT, json_encode([
                'ok' => true,
                'filename' => $result['filename'],
                'size' => $result['size'],
                'tables' => $result['tables'],
                'rows' => $result['rows'],
                'at' => now()->toDateTimeString(),
            ], JSON_UNESCAPED_UNICODE));

            // پاکسازی بکاپ‌های خودکار قدیمی (فقط خودکارها — بکاپ دستی هرگز)
            $deleted = $this->cleanupOld();

            // ایمیل (اگر روشن باشد)
            $emailed = $this->sendEmail($result);

            return [
                'ran' => true,
                'reason' => 'backup_created',
                'result' => $result + ['deleted_old' => $deleted, 'emailed' => $emailed],
            ];
        } catch (\Throwable $e) {
            report($e);

            /*
             * اسلاتِ امروز بسته می‌شود حتی در خطا تا دامپِ خراب هر دقیقه تکرار نشود؛
             * شرح خطا در پنل نمایش داده می‌شود و دوره‌ی بعد دوباره تلاش می‌شود.
             */
            option_update(self::OPT_LAST_SLOT, $slot);
            option_update(self::OPT_LAST_RUN, now()->format('Y-m-d H:i:s'));
            option_update(self::OPT_LAST_RESULT, json_encode([
                'ok' => false,
                'error' => mb_substr($e->getMessage(), 0, 500),
                'at' => now()->toDateTimeString(),
            ], JSON_UNESCAPED_UNICODE));

            return ['ran' => false, 'reason' => 'failed', 'error' => $e->getMessage()];
        } finally {
            Cache::forget(self::LOCK_KEY);
        }
    }

    /* ------------------------------------------------ پاکسازی قدیمی‌ها */

    /**
     * حذف بکاپ‌های «خودکار» قدیمی‌تر از N روز — دست‌نخوردن کامل با بکاپ‌های دستی
     * (مطابق مثالِ خودِ رابط کاربری: «فقط بکاپ‌های خودکارِ روزهای ۶، ۱۲، ۱۸… حذف می‌شوند»)
     * @return int تعداد فایل‌های حذف‌شده
     */
    public function cleanupOld(?int $days = null): int
    {
        $days ??= self::deleteDays();

        // ۰ = حذف خودکار خاموش
        if ($days <= 0) {
            return 0;
        }

        $cutoff = now()->subDays($days)->timestamp;
        $deleted = 0;

        foreach (Storage::disk('backup')->allFiles() as $file) {
            $name = basename($file);

            if (! str_starts_with($name, self::FILE_PREFIX.'-') || ! str_ends_with(strtolower($name), '.zip')) {
                continue;
            }

            if (Storage::disk('backup')->lastModified($file) < $cutoff) {
                Storage::disk('backup')->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /* ------------------------------------------------ ایمیل */

    /**
     * ارسال ایمیل بکاپ — حجم ≤ ۱۰ مگابایت: فایل پیوست؛ بزرگ‌تر: فایل روی سرور
     * می‌ماند و فقط مشخصاتِ آن (نام/حجم/مسیر) ایمیل می‌شود.
     */
    public function sendEmail(array $result): bool
    {
        if (! self::emailEnabled()) {
            return false;
        }

        $to = self::emailTo();

        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        try {
            $attach = $result['size'] <= self::EMAIL_ATTACH_MAX_MB * 1048576;

            Mail::to($to)->send(new AutoBackupMail(
                siteTitle: (string) option('info_site_title', config('app.name')),
                filename: $result['filename'],
                sizeBytes: (int) $result['size'],
                tablesCount: (int) $result['tables'],
                rowsCount: (int) $result['rows'],
                jalaliDate: \Morilog\Jalali\Jalalian::fromDateTime(now())->format('Y/m/d — H:i'),
                attachmentPath: $attach ? $result['path'] : null,
            ));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
