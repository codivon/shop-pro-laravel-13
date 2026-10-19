<?php

namespace App\Livewire\Admin\System;

use App\Jobs\CreateBackup;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Morilog\Jalali\Jalalian;

#[Title('لیست بکاپ‌ها')]
class BackupIndex extends Component
{
    /** نام فایل بکاپ در حال حذف (مودال تایید — پورت delete-modal قدیمی) */
    public ?string $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('backups.index') ?: abort(403);
    }

    /* ---------------- لیست (پورت ۱:۱ index قدیمی) ---------------- */

    public function getBackupsProperty()
    {
        $files = Storage::disk('backup')->allFiles(config('backup.backup.name'));

        $backups = [];
        foreach ($files as $file) {
            $backups[] = [
                'path' => $file,
                'date' => Storage::disk('backup')->lastModified($file),
                'name' => substr($file, strrpos($file, '/') + 1),
                'size' => number_format(Storage::disk('backup')->size($file) / 1048576, 2) . ' mb',
            ];
        }

        return collect($backups)->sortByDesc('date')->values();
    }

    /* ---------------- ایجاد بکاپ (پورت ۱:۱ create قدیمی) ---------------- */

    public function create(): void
    {
        auth('adminPanel')->user()?->can('backups.create') ?: abort(403);

        CreateBackup::dispatchAfterResponse();

        $this->dispatch('toast', message: 'درخواست ایجاد بکاپ ثبت شد؛ پس از پردازش، فایل در همین لیست نمایش داده می‌شود.', type: 'success');
    }

    /* ---------------- دانلود (پورت ۱:۱ download قدیمی) ---------------- */

    public function download(string $backup): mixed
    {
        auth('adminPanel')->user()?->can('backups.download') ?: abort(403);

        $filename = config('backup.backup.name') . '/' . basename($backup);

        if (!Storage::disk('backup')->exists($filename)) {
            abort(404);
        }

        return Storage::disk('backup')->download($filename);
    }

    /* ---------------- حذف (پورت ۱:۱ destroy قدیمی) ---------------- */

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

        $filename = config('backup.backup.name') . '/' . basename($backup);

        if (!Storage::disk('backup')->exists($filename)) {
            abort(404);
        }

        Storage::disk('backup')->delete($filename);

        $this->dispatch('toast', message: 'بکاپ با موفقیت حذف شد.', type: 'success');
    }

    /* ---------------- ابزار ---------------- */

    public static function jdate($timestamp, $format = 'Y/m/d H:i:s'): string
    {
        if (!$timestamp) {
            return '—';
        }

        return Jalalian::fromDateTime($timestamp)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.system.backups')
            ->layout('admin-panel::layout');
    }
}
