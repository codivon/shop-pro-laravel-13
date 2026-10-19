<?php

namespace App\Livewire\Admin\Imports\Concerns;

use App\Traits\ImportLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

/**
 * منطق مشترک سه صفحه درون‌ریزی اکسل — پورت Back\ImportsController
 * (lock صف با Cache + ذخیره فایل روی دیسک storage + dispatch Job قدیمی + حذف فایل خطا)
 *
 * کلاس‌های قدیمی App\Imports\* دست‌نخورده reuse می‌شوند؛ اینجا فقط مسیر
 * آپلود/صف‌گذاری کنترلر قدیمی بازسازی شده است (دیسپچ به همان Jobهای قدیمی).
 */
trait InteractsWithImports
{
    use ImportLogger;
    use WithFileUploads;

    /** فیلترهای ستون‌های اکسل (checkbox های filters[key] ویوی قدیمی) */
    public array $filters = [];

    /** بروزرسانی ردیف تکراری (پورت update_duplicate) */
    public bool $updateDuplicate = true;

    /** فایل اکسل آپلودشده */
    public $file = null;

    /* --------- متدهای abstract هر نوع ایمپورت --------- */

    abstract protected function importType(): string;            // posts | products | users

    abstract protected function importLabel(): string;           // مقالات | محصولات | کاربران

    abstract protected function errorFilePattern(): string;      // posts_import_errors_*.json

    abstract protected function lockKey(): string;               // laravel_unique_job_import_posts_1

    abstract protected function lockTtl(): int;                  // 60 | 240 | 0 (کاربران: قدیمی put نمی‌کرد)

    abstract protected function importDirectory(): string;       // imports/posts | imports/products

    abstract protected function baseFilename(): string;          // import_posts | import_products | import_users

    /** دیسپچ Job قدیمی همان نوع */
    abstract protected function dispatchImportJob(string $filePath, array $jobData): void;

    /* --------- آخرین فایل خطا (پورت بخش پایین ویوهای قدیمی) --------- */

    public function getErrorDataProperty(): ?array
    {
        return $this->loadLastErrorFile();
    }

    public function getErrorFileDateProperty(): ?string
    {
        $files = glob(storage_path('logs/' . $this->errorFilePattern()));
        $last = !empty($files) ? end($files) : null;

        return $last && file_exists($last) ? jdate(filemtime($last), 'Y-m-d H:i:s') : null;
    }

    public function getErrorFileNameProperty(): ?string
    {
        $files = glob(storage_path('logs/' . $this->errorFilePattern()));
        $last = !empty($files) ? end($files) : null;

        return $last ? basename($last) : null;
    }

    protected function loadLastErrorFile(): ?array
    {
        $errorFiles = glob(storage_path('logs/' . $this->errorFilePattern()));
        $lastErrorFile = !empty($errorFiles) ? end($errorFiles) : null;

        if ($lastErrorFile && file_exists($lastErrorFile)) {
            $data = json_decode(file_get_contents($lastErrorFile), true);

            return is_array($data) ? $data : null;
        }

        return null;
    }

    /* --------- حذف فایل خطا (پورت ۱:۱ deletePostErrorFile و ...) --------- */

    public function deleteErrorFile(?string $file = null): void
    {
        auth('adminPanel')->user()?->can('imports.' . $this->importType()) ?: abort(403);

        if (!$file) {
            $this->dispatch('toast', message: 'نام فایل ارسال نشده است', type: 'error');
            return;
        }

        // فقط نام ساده فایل (بدون مسیر) پذیرفته می‌شود — امن
        $file = basename($file);
        $filePath = storage_path('logs/' . $file);

        if (!file_exists($filePath)) {
            $this->dispatch('toast', message: 'فایل وجود ندارد', type: 'error');
            return;
        }

        if (unlink($filePath)) {
            $this->dispatch('toast', message: 'فایل خطا حذف شد.', type: 'success');
            return;
        }

        $this->dispatch('toast', message: 'خطا در حذف فایل', type: 'error');
    }

    /* --------- ذخیره مشترک (اسکلت store قدیمی) --------- */

    protected function storeExcel(array $jobDataExtra = []): bool
    {
        $userId = auth('adminPanel')->id();

        // بررسی وجود Job مشابه در صف یا پردازش (پورت دقیق)
        if (Cache::has($this->lockKey())) {
            $this->dispatch(
                'toast',
                message: 'درخواست قبلی برای واردات ' . $this->importLabel() . ' در حال پردازش است. لطفاً پس از اتمام آن، مجدداً تلاش کنید.',
                type: 'error'
            );
            return false;
        }

        try {
            $extension = $this->file->getClientOriginalExtension();
            $filename = $this->baseFilename() . '.' . $extension;

            // ذخیره در دیسک 'storage' (پورت دقیق قدیمی)
            $filePath = Storage::disk('storage')->putFileAs($this->importDirectory(), $this->file, $filename);

            if (!$filePath) {
                throw new \Exception('خطا در ذخیره فایل');
            }

            // لاگ ارسال به صف (trait قدیمی ImportLogger)
            $this->logQueueDispatch($this->importType(), $filePath, $userId);

            $jobData = array_merge([
                'filters'          => $this->filters,
                'update_duplicate' => $this->updateDuplicate,
                'user_id'          => $userId,
            ], $jobDataExtra);

            // ارسال Job به صف (همان Job قدیمی)
            $this->dispatchImportJob($filePath, $jobData);

            // قفل تکرار (پورت: مقالات ۶۰ ثانیه، محصولات ۲۴۰ ثانیه؛ کاربران قدیمی put نمی‌کرد)
            if ($this->lockTtl() > 0) {
                Cache::put($this->lockKey(), true, $this->lockTtl());
            }

            $this->reset('file');

            $this->dispatch(
                'toast',
                message: 'فایل با موفقیت آپلود شد و در صف پردازش قرار گرفت. نتیجه به اطلاع شما خواهد رسید.',
                type: 'success'
            );

            return true;
        } catch (\Exception $e) {
            \Log::error('خطا در آپلود فایل ' . $this->importLabel() . ': ' . $e->getMessage());
            $this->dispatch('toast', message: 'خطا در آپلود فایل: ' . $e->getMessage(), type: 'error');

            return false;
        }
    }
}
