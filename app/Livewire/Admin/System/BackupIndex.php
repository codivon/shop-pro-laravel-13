<?php

namespace App\Livewire\Admin\System;

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

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('backups.index') ?: abort(403);
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

    /** لیست بکاپ‌ها (نام فایل، تاریخ، اندازه) */
    public function getBackupsProperty()
    {
        $files = Storage::disk('backup')->allFiles();

        $backups = [];

        foreach ($files as $file) {
            if (! str_ends_with(strtolower($file), '.zip')) {
                continue;
            }

            $backups[] = [
                'path' => $file,
                'date' => Storage::disk('backup')->lastModified($file),
                'name' => substr($file, strrpos($file, '/') + 1),
                'size' => number_format(Storage::disk('backup')->size($file) / 1048576, 2).' mb',
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

    /* ---------------- دانلود / حذف (پورت ۱:۱ قدیمی) ---------------- */

    public function download(string $backup): mixed
    {
        auth('adminPanel')->user()?->can('backups.download') ?: abort(403);

        $filename = basename($backup);

        if (!Storage::disk('backup')->exists($filename)) {
            abort(404);
        }

        return Storage::disk('backup')->download($filename);
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

        $filename = basename($backup);

        if (!Storage::disk('backup')->exists($filename)) {
            abort(404);
        }

        Storage::disk('backup')->delete($filename);

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
