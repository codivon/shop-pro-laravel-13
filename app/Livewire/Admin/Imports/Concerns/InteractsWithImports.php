<?php

namespace App\Livewire\Admin\Imports\Concerns;

use App\Traits\ImportLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
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

    /* ------------------------------------------------------------------
     | فاز ۱۹ — پیش‌فرض همه‌ی فیلدها تیک‌خورده + ابزارهای پیش‌نمایش زنده اکسل
     ------------------------------------------------------------------ */

    /** همه‌ی فیلدها پیش‌فرض تیک می‌خورند (درخواست کاربر) */
    protected function defaultAllFiltersChecked(): void
    {
        $filters = [];
        foreach (array_keys($this->fields) as $key) {
            $filters[$key] = '1';
        }
        $this->filters = $filters;
    }

    /** تیک همه (الزامی‌ها همیشه روشن می‌مانند) */
    public function checkAllFilters(): void
    {
        $this->defaultAllFiltersChecked();
    }

    /** برداشتن تیک همه (الزامی‌ها روشن می‌مانند) */
    public function uncheckAllFilters(): void
    {
        $filters = [];
        foreach ($this->fields as $key => $field) {
            $filters[$key] = ! empty($field['required']) ? '1' : '0';
        }
        $this->filters = $filters;
    }

    /** تعداد فیلدهای فعال (برای بج زنده) */
    #[Computed]
    public function activeFieldsCount(): int
    {
        return collect($this->filters)->filter(fn ($v) => $v === '1' || $v === true)->count();
    }

    /** حرف ستون اکسل (A، B، C، …) برای سربرگ شبیه‌سازی */
    public static function excelColumn(int $index): string
    {
        $letters = '';
        $index += 1;
        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26);
        }
        return $letters;
    }

    /* ------------------------------------------------------------------
     | فاز ۱۹ — فایل‌های آپلودشده‌ی درون‌ریزی با قابلیت حذف
     | (فایل‌ها روی دیسک storage در imports/{type} ذخیره می‌شوند)
     ------------------------------------------------------------------ */

    #[Computed]
    public function uploadedFiles(): array
    {
        $disk = Storage::disk('storage');
        $dir = $this->importDirectory();

        if (! $disk->exists($dir)) {
            return [];
        }

        return collect($disk->files($dir))
            ->map(fn ($f) => [
                'name' => basename($f),
                'size' => $disk->size($f),
                'date' => $disk->lastModified($f),
            ])
            ->filter(fn ($f) => str_starts_with($f['name'], $this->baseFilename()))
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    /** حذف فایل آپلودشده (فقط نام ساده — امن) */
    public function deleteUploadedFile(string $name): void
    {
        auth('adminPanel')->user()?->can('imports.' . $this->importType()) ?: abort(403);

        $name = basename($name);
        $disk = Storage::disk('storage');
        $path = $this->importDirectory() . '/' . $name;

        if (! str_starts_with($name, $this->baseFilename()) || ! $disk->exists($path)) {
            $this->dispatch('toast', message: 'فایل وجود ندارد', type: 'error');
            return;
        }

        $disk->delete($path);
        unset($this->uploadedFiles);

        $this->dispatch('toast', message: 'فایل آپلودشده حذف شد.', type: 'success');
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
