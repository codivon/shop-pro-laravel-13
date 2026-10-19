<?php

namespace App\Livewire\Admin\Imports;

use App\Jobs\ImportPostsJob;
use App\Livewire\Admin\Imports\Concerns\InteractsWithImports;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('درون‌ریزی مقالات')]
class PostsImport extends Component
{
    use InteractsWithImports;

    /** فیلدهای اکسل مقالات — عیناً از ویوی قدیمی postsExcelImport */
    public array $fields = [
        'title'            => ['label' => 'عنوان',              'required' => true,  'sample' => 'آموزش لاراول از صفر'],
        'slug'             => ['label' => 'Slug',               'required' => false, 'sample' => 'laravel-tutorial'],
        'image'            => ['label' => 'تصویر شاخص',         'required' => false, 'sample' => 'https://site.com/img.jpg'],
        'content'          => ['label' => 'محتوا',              'required' => false, 'sample' => '<p>متن کامل نوشته...</p>'],
        'meta_title'       => ['label' => 'عنوان سئو',          'required' => false, 'sample' => 'آموزش جامع لاراول'],
        'meta_description' => ['label' => 'توضیحات سئو',        'required' => false, 'sample' => 'یادگیری لاراول به صورت پروژه محور'],
        'tags'             => ['label' => 'کلمات کلیدی',        'required' => false, 'sample' => 'لاراول, PHP, آموزش'],
        'publish_date'     => ['label' => 'تاریخ انتشار',       'required' => false, 'sample' => '1403/04/08'],
        'view'             => ['label' => 'تعداد بازدید',       'required' => false, 'sample' => '120'],
        'published'        => ['label' => 'پیش نویس یا انتشار', 'required' => false, 'sample' => '1'],
    ];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('imports.posts') ?: abort(403);

        // فیلد الزامی عنوان به‌صورت پیش‌فرض تیک دارد (مثل ویوی قدیمی)
        $this->filters['title'] = '1';
    }

    /* --------- پیاده‌سازی trait --------- */

    protected function importType(): string
    {
        return 'posts';
    }

    protected function importLabel(): string
    {
        return 'مقالات';
    }

    protected function errorFilePattern(): string
    {
        return 'posts_import_errors_*.json';
    }

    protected function lockKey(): string
    {
        $userId = auth('adminPanel')->id();

        return 'laravel_unique_job_' . 'import_posts_' . ($userId ?? 'guest');
    }

    protected function lockTtl(): int
    {
        return 60;
    }

    protected function importDirectory(): string
    {
        return 'imports/posts';
    }

    protected function baseFilename(): string
    {
        return 'import_posts';
    }

    protected function dispatchImportJob(string $filePath, array $jobData): void
    {
        ImportPostsJob::dispatch($filePath, $jobData);
    }

    /* --------- ذخیره (پورت ۱:۱ postsExcelImport_Store قدیمی) --------- */

    public function store(): void
    {
        auth('adminPanel')->user()?->can('imports.posts') ?: abort(403);

        $this->validate([
            'file'          => 'required|file|mimes:xlsx,csv',
            'filters.title' => 'required',
        ], [
            'filters.title.required' => 'فیلد عنوان الزامی است',
        ], [
            'file' => 'فایل اکسل',
        ]);

        $this->storeExcel();
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.imports.posts')
            ->layout('admin-panel::layout');
    }
}
