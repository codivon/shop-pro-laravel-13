<?php

namespace App\Livewire\Admin\System;

use App\Services\Backup\AutoBackupRunner;
use App\Services\Backup\SiteBackupService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Morilog\Jalali\Jalalian;

/**
 * پشتیبان‌گیری و بازیابی — فاز ۱۸ (بازطراحی کامل مثل نمونه‌ی قدیمی بهبودیافته)
 *
 *  - ساخت بکاپ انتخابی: مودالِ تمام موارد (جداول + گروه‌های فایل) — انتخاب در سمت کلاینت
 *    (الپاین) و ارسال آرایه‌ها به create() — بدون رفت‌وبرگشت برای هر تیک (درس فاز ۱۷-د)
 *  - ساخت همگام و بدون صف → رفع باگ «پیغام موفق ولی بکاپ ساخته نمی‌شود»
 *  - بازیابی ZIP با منطق skip-if-exists + گزارش کامل
 *  - فاز ۱۴۵ — تنظیمات پشتیبان‌گیری خودکار فقط-دیتابیس (کرون جاب) در همین صفحه:
 *    دوره/ساعت/حذف خودکار/ایمیل — منطق موعد و اجرا در App\Services\Backup\AutoBackupRunner
 */
#[Title('پشتیبان‌گیری')]
class BackupIndex extends Component
{
    use WithFileUploads;

    /* ---------------- بازیابی ---------------- */

    /** فایل ZIP آپلودی برای بازیابی */
    public $restoreFile;

    /* ---------------- حذف (پورت delete-modal قدیمی) ---------------- */

    public ?string $confirmingDelete = null;

    /* ---------------- پشتیبان‌گیری خودکار (فاز ۱۴۵) ---------------- */

    public bool $autoEnabled = false;

    /** daily | weekly | monthly | custom */
    public string $autoPeriod = 'daily';

    /** فاصله‌ی اجرا در حالت «بازه‌ی دلخواه» (روز) */
    public int $autoCustomDays = 3;

    /** ساعت اجرا 'HH:MM' */
    public string $autoTime = '03:00';

    /** حذف خودکار بکاپ‌های خودکار قدیمی‌تر از N روز — ۰ = خاموش */
    public int $autoDeleteDays = 180;

    public bool $autoEmailEnabled = false;

    /** گیرنده‌ی ایمیل بکاپ — خالی = info_email سایت */
    public string $autoEmailTo = '';

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('backups.index') ?: abort(403);

        // فاز ۱۴۵ — بارگذاری تنظیمات پشتیبان‌گیری خودکار
        $this->autoEnabled = AutoBackupRunner::enabled();
        $this->autoPeriod = AutoBackupRunner::period();
        $this->autoCustomDays = AutoBackupRunner::customDays();
        $this->autoTime = AutoBackupRunner::time();
        $this->autoDeleteDays = AutoBackupRunner::deleteDays();
        $this->autoEmailEnabled = AutoBackupRunner::emailEnabled();
        $this->autoEmailTo = (string) option(AutoBackupRunner::OPT_EMAIL_TO, '');
    }

    /* ---------------- داده‌ها ---------------- */

    public function getServiceProperty(): SiteBackupService
    {
        return app(SiteBackupService::class);
    }

    public function getTablesProperty(): array
    {
        return $this->service->tables();
    }

    public function getFileGroupsProperty(): array
    {
        return $this->service->fileGroups();
    }

    public function getReportProperty(): ?array
    {
        return $this->service->lastRestoreReport();
    }

    public function getIncomingProperty(): array
    {
        return $this->service->incomingFiles();
    }

    /**
     * لیست بکاپ‌ها (نام فایل، تاریخ، اندازه)
     *
     * فاز ۲۰ — رفع باگ 404 دانلود/حذف: برای فایل‌های ریشه‌ی دیسک، strrpos('/') مقدار
     * false برمی‌گرداند و false+1=1 می‌شود؛ یعنی substr اولین حرف نام فایل را حذف می‌کرد
     * («backup-….zip» → «ackup-….zip») و چون چنین فایلی وجود نداشت، exists() شکست می‌خورد
     * و abort(404) رخ می‌داد. حالا از basename() استفاده می‌شود و فایل‌های پوشه‌ی
     * incoming (ورودی‌های بازیابی — نه بکاپ ساخته‌شده) از فهرست حذف شده‌اند.
     */
    public function getBackupsProperty()
    {
        $files = Storage::disk('backup')->allFiles();

        $backups = [];

        foreach ($files as $file) {
            if (! str_ends_with(strtolower($file), '.zip')) {
                continue;
            }

            // پوشه‌ی incoming فقط ورودی بازیابی است — در فهرست «بکاپ‌های ساخته‌شده» نمی‌آید
            if (str_starts_with($file, 'incoming/')) {
                continue;
            }

            $backups[] = [
                'path' => $file,
                'date' => Storage::disk('backup')->lastModified($file),
                'name' => basename($file),
                'size' => number_format(Storage::disk('backup')->size($file) / 1048576, 2).' mb',
                // فاز ۱۴۵ — تفکیک بکاپ خودکارِ کرون از دستی (پیشوند auto-backup-)
                'auto' => str_starts_with(basename($file), AutoBackupRunner::FILE_PREFIX.'-'),
            ];
        }

        return collect($backups)->sortByDesc('date')->values();
    }

    /** آمار سربرگ (جداول / رکورد / فایل / حجم) */
    public function getStatsProperty(): array
    {
        $tables = $this->tables;
        $files = $this->fileGroups;

        return [
            'driver' => (string) config('database.connections.'.config('database.default').'.driver'),
            'table_count' => count($tables),
            'row_count' => (int) array_sum(array_column($tables, 'rows')),
            'file_count' => $files['total_files'],
            'file_size' => (int) $files['total_size'],
        ];
    }

    /* ---------------- ساخت بکاپ انتخابی ---------------- */

    /**
     * آرایه‌ها از مودال Alpine می‌آیند — با فهرست واقعی سفیدلیست می‌شوند
     * (هیچ نام جدول/مسیر دلخواهی قابل تزریق نیست)
     */
    public function create(array $tables = [], array $groups = []): void
    {
        auth('adminPanel')->user()?->can('backups.create') ?: abort(403);

        $validTables = array_column($this->service->tables(), 'name');
        $tables = array_values(array_intersect(array_map('strval', $tables), $validTables));

        if (empty($tables)) {
            $this->dispatch('toast', message: 'حداقل یک جدول باید انتخاب شود.', type: 'error');

            return;
        }

        $validGroups = array_column($this->service->fileGroups()['groups'], 'id');
        $groups = array_values(array_intersect(array_map('strval', $groups), $validGroups));

        try {
            $result = $this->service->create($tables, $groups);

            $this->dispatch('toast', message: 'نسخه‌ی پشتیبان «'.$result['filename'].'» ساخته شد ('.number_format($result['size'] / 1048576, 2).' مگابایت).', type: 'success');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'ساخت پشتیبان ناموفق بود: '.$e->getMessage(), type: 'error');
        }
    }

    /* ---------------- بازیابی ---------------- */

    public function updatedRestoreFile(): void
    {
        $this->validate([
            'restoreFile' => 'required|file|mimes:zip|max:512000',
        ], [
            'restoreFile.required' => 'فایل ZIP پشتیبان را انتخاب کنید.',
            'restoreFile.mimes' => 'فقط فایل ZIP پشتیبان پذیرفته می‌شود.',
            'restoreFile.max' => 'حجم فایل حجیم است؛ آن را در مسیر storage/app/backups/incoming قرار دهید و از همان فهرست بازیابی کنید.',
        ], [
            'restoreFile' => 'فایل پشتیبان',
        ]);
    }

    public function restoreRun(): void
    {
        auth('adminPanel')->user()?->can('backups.create') ?: abort(403);

        $this->validate([
            'restoreFile' => 'required|file|mimes:zip|max:512000',
        ]);

        $incomingDir = storage_path('app/backups/incoming');

        if (! is_dir($incomingDir)) {
            mkdir($incomingDir, 0775, true);
        }

        $original = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', (string) $this->restoreFile->getClientOriginalName());
        $path = $this->restoreFile->storeAs('incoming', now()->format('Ymd-His').'-'.$original, 'backup');
        $absolute = storage_path('app/backups/'.$path);

        $this->reset('restoreFile');

        $this->runRestore($absolute);
    }

    /** بازیابی فایلی که دستی در storage/app/backups/incoming گذاشته شده (فایل‌های حجیم) */
    public function restoreIncoming(string $name): void
    {
        auth('adminPanel')->user()?->can('backups.create') ?: abort(403);

        $name = basename($name);

        if (! preg_match('/^[A-Za-z0-9_\-\.]+\.zip$/i', $name)) {
            $this->dispatch('toast', message: 'نام فایل نامعتبر است.', type: 'error');

            return;
        }

        $absolute = storage_path('app/backups/incoming/'.$name);

        if (! is_file($absolute)) {
            $this->dispatch('toast', message: 'فایل یافت نشد.', type: 'error');

            return;
        }

        $this->runRestore($absolute);
    }

    private function runRestore(string $absolute): void
    {
        try {
            $report = $this->service->restore($absolute);
            $summary = $report['summary'];

            $message = 'بازیابی انجام شد: '.number_format($summary['rows_added']).' رکورد افزوده، '
                .number_format($summary['rows_skipped']).' رکورد موجود (رد شد)، '
                .number_format($summary['files_added']).' فایل افزوده، '
                .number_format($summary['files_skipped']).' فایل موجود (رد شد).';

            $this->dispatch('toast', message: $message, type: 'success');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', message: 'بازیابی ناموفق بود: '.$e->getMessage(), type: 'error');
        }
    }

    /* ---------------- پشتیبان‌گیری خودکار (فاز ۱۴۵) ---------------- */

    /**
     * ضربان زمان‌بند: کرون جاب در ۲ دقیقه‌ی اخیر اجرا شده؟
     * (همان منطق صفحه‌ی تنظیمات توسعه‌دهنده — بدون کرون، بکاپ خودکار هرگز اجرا نمی‌شود)
     */
    public function getScheduleAliveProperty(): bool
    {
        $last = option('schedule_run');

        if (! $last) {
            return false;
        }

        try {
            $last = is_object($last) ? $last : \Carbon\Carbon::parse($last);
        } catch (\Throwable) {
            return false;
        }

        return $last->diffInMinutes(now()) <= 2;
    }

    /** اجرای بعدی موعد برای نمایش در پنل */
    public function getAutoNextRunProperty(): array
    {
        return AutoBackupRunner::nextRun();
    }

    /** نتیجه‌ی آخرین اجرا (موفق/خطا) برای خط وضعیت زیر تنظیمات */
    public function getAutoLastResultProperty(): ?array
    {
        $raw = (string) option(AutoBackupRunner::OPT_LAST_RESULT, '');

        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /** آخرین اسلاتِ اجراشده (جلالی) برای نمایش */
    public function getAutoLastSlotJalaliProperty(): string
    {
        $last = (string) option(AutoBackupRunner::OPT_LAST_SLOT, '');

        if ($last === '') {
            return '';
        }

        try {
            return Jalalian::fromDateTime(\Carbon\Carbon::parse($last))->format('Y/m/d — H:i');
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * ذخیره‌ی تنظیمات پشتیبان‌گیری خودکار — سوییچ‌ها/دوره/ساعت/حذف/ایمیل
     * گیت: ساخت بکاپ (backups.create) — تغییر این تنظیمات عملاً «ساختن بکاپ آینده» است.
     */
    public function saveAutoBackup(): void
    {
        auth('adminPanel')->user()?->can('backups.create') ?: abort(403);

        $this->validate([
            'autoPeriod' => ['required', 'in:daily,weekly,monthly,custom'],
            'autoCustomDays' => ['required_if:autoPeriod,custom', 'nullable', 'integer', 'min:1', 'max:365'],
            'autoTime' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'autoDeleteDays' => ['required', 'integer', 'min:0', 'max:3650'],
            'autoEmailTo' => ['nullable', 'email', 'max:190'],
        ], [
            'autoPeriod.required' => 'دوره‌ی پشتیبان‌گیری را انتخاب کنید.',
            'autoPeriod.in' => 'دوره‌ی پشتیبان‌گیری نامعتبر است.',
            'autoCustomDays.required_if' => 'برای بازه‌ی دلخواه، فاصله‌ی اجرا (روز) را وارد کنید.',
            'autoCustomDays.integer' => 'فاصله‌ی اجرا باید عدد باشد.',
            'autoCustomDays.min' => 'فاصله‌ی اجرا باید حداقل ۱ روز باشد.',
            'autoCustomDays.max' => 'فاصله‌ی اجرا حداکثر ۳۶۵ روز است.',
            'autoTime.required' => 'ساعت اجرا را مشخص کنید.',
            'autoTime.regex' => 'فرمت ساعت اجرا باید HH:MM باشد.',
            'autoDeleteDays.integer' => 'حذف خودکار باید عدد روز باشد.',
            'autoDeleteDays.min' => 'برای خاموش‌کردن حذف خودکار، عدد را صفر کنید.',
            'autoDeleteDays.max' => 'حداکثر ۳۶۵۰ روز.',
            'autoEmailTo.email' => 'ایمیل گیرنده معتبر نیست.',
        ], [
            'autoPeriod' => 'دوره‌ی پشتیبان‌گیری',
            'autoCustomDays' => 'فاصله‌ی اجرا',
            'autoTime' => 'ساعت اجرا',
            'autoDeleteDays' => 'حذف خودکار',
            'autoEmailTo' => 'ایمیل گیرنده',
        ]);

        option_update(AutoBackupRunner::OPT_ENABLED, $this->autoEnabled ? 'on' : 'off');
        option_update(AutoBackupRunner::OPT_PERIOD, $this->autoPeriod);
        option_update(AutoBackupRunner::OPT_CUSTOM_DAYS, (string) max(1, $this->autoCustomDays));
        option_update(AutoBackupRunner::OPT_TIME, $this->autoTime);
        option_update(AutoBackupRunner::OPT_DELETE_DAYS, (string) max(0, $this->autoDeleteDays));
        option_update(AutoBackupRunner::OPT_EMAIL_ENABLED, $this->autoEmailEnabled ? 'on' : 'off');
        option_update(AutoBackupRunner::OPT_EMAIL_TO, trim($this->autoEmailTo));

        $this->dispatch('toast', message: $this->autoEnabled
            ? 'پشتیبان‌گیری خودکار فعال شد — اجرای بعدی: '.$this->autoNextRun['jalali']
            : 'تنظیمات پشتیبان‌گیری خودکار ذخیره شد (فعال نیست).', type: 'success');
    }

    /** ساخت فوریِ یک بکاپ خودکار (بدون توجه به موعد — برای تست قابلیت) */
    public function runAutoBackupNow(): void
    {
        auth('adminPanel')->user()?->can('backups.create') ?: abort(403);

        $outcome = app(AutoBackupRunner::class)->run(force: true);

        if (! $outcome['ran']) {
            $this->dispatch('toast', message: 'اجرای بکاپ خودکار انجام نشد: '.($outcome['reason'] ?? ''), type: 'error');

            return;
        }

        $r = $outcome['result'];

        $this->dispatch('toast', message: 'بکاپ خودکار «'.$r['filename'].'» ساخته شد — '
            .$r['tables'].' جدول، '.number_format($r['rows']).' رکورد.', type: 'success');
    }

    /* ---------------- دانلود / حذف (پورت ۱:۱ قدیمی) ---------------- */

    /**
     * فاز ۲۰ — رفع 404: مسیر واقعی فایل بر اساس نام (basename) در کل دیسک resolve
     * می‌شود (بکاپ‌ها ممکن است در ریشه یا زیرپوشه باشند)؛ سپس BinaryFileResponse
     * (پشتیبانی داخلی Livewire 4 برای دانلود از اکشن) برگردانده می‌شود.
     */
    public function download(string $backup): mixed
    {
        auth('adminPanel')->user()?->can('backups.download') ?: abort(403);

        $path = $this->resolveBackupPath($backup);

        if ($path === null) {
            abort(404);
        }

        return Storage::disk('backup')->download($path, basename($backup));
    }

    /** یافتن مسیر واقعی یک بکاپ فقط با نام فایل (ضد مسیردهی — basename) */
    private function resolveBackupPath(string $backup): ?string
    {
        $filename = basename($backup);

        if ($filename === '' || $filename === '.' || $filename === '..') {
            return null;
        }

        // ۱) ریشه‌ی دیسک (محل پیش‌فرض SiteBackupService)
        if (Storage::disk('backup')->exists($filename)) {
            return $filename;
        }

        // ۲) جستجو در کل دیسک بر اساس basename
        foreach (Storage::disk('backup')->allFiles() as $file) {
            if (basename($file) === $filename) {
                return $file;
            }
        }

        return null;
    }

    public function confirmDelete(string $backup): void
    {
        auth('adminPanel')->user()?->can('backups.delete') ?: abort(403);

        $this->confirmingDelete = $backup;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('backups.delete') ?: abort(403);

        $backup = (string) $this->confirmingDelete;
        $this->confirmingDelete = null;

        if ($backup === '') {
            return;
        }

        // فاز ۲۰ — رفع 404 حذف: resolve مسیر واقعی با basename (مثل download)
        $path = $this->resolveBackupPath($backup);

        if ($path === null) {
            $this->dispatch('toast', message: 'فایل پشتیبان یافت نشد؛ ممکن است قبلاً حذف شده باشد.', type: 'error');

            return;
        }

        Storage::disk('backup')->delete($path);

        $this->dispatch('toast', message: 'فایل پشتیبان با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public static function jdate($timestamp, $format = 'Y/m/d H:i'): string
    {
        if (!$timestamp) {
            return '—';
        }

        return Jalalian::fromDateTime(\Carbon\Carbon::createFromTimestamp((int) $timestamp))->format($format);
    }

    public static function faSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' مگابایت';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0).' کیلوبایت';
        }

        return number_format($bytes).' بایت';
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.system.backups')
            ->layout('admin-panel::layout');
    }
}
