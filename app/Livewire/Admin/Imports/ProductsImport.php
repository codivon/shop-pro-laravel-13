<?php

namespace App\Livewire\Admin\Imports;

use App\Jobs\ImportProductsJob;
use App\Livewire\Admin\Imports\Concerns\InteractsWithImports;
use App\Models\Warehouse;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('درون‌ریزی محصولات')]
class ProductsImport extends Component
{
    use InteractsWithImports;

    /** فیلدهای اکسل محصولات — عیناً از ویوی قدیمی productsExcelImport (+ نمونه برای پیش‌نمایش زنده فاز ۱۹) */
    public array $fields = [
        'title'             => ['label' => 'عنوان',                    'required' => true,  'sample' => 'گوشی موبایل سامسونگ'],
        'title_en'          => ['label' => 'عنوان انگلیسی',            'required' => false, 'sample' => 'Samsung Galaxy A54'],
        'slug'              => ['label' => 'Slug',                     'required' => false, 'sample' => 'samsung-galaxy-a54'],
        'brand'             => ['label' => 'برند',                     'required' => false, 'sample' => 'سامسونگ'],
        'weight'            => ['label' => 'وزن',                      'required' => false, 'sample' => '204'],
        'unit'              => ['label' => 'واحد',                     'required' => false, 'sample' => 'عدد'],
        'price'             => ['label' => 'قیمت',                     'required' => true,  'sample' => '19500000'],
        'stock'             => ['label' => 'موجودی انبار',             'required' => true,  'sample' => '15'],
        'short_description' => ['label' => 'توضیحات کوتاه',            'required' => false, 'sample' => 'گوشی میان‌رده با دوربین ۵۰ مگاپیکسل'],
        'description'       => ['label' => 'توضیحات',                  'required' => false, 'sample' => '<p>توضیحات کامل محصول…</p>'],
        'special'           => ['label' => 'محصول ویژه',               'required' => false, 'sample' => '1'],
        'published'         => ['label' => 'پیش‌نویس',                  'required' => false, 'sample' => '0'],
        'image'             => ['label' => 'تصویر شاخص',               'required' => false, 'sample' => 'https://site.com/img.jpg'],
        'meta_title'        => ['label' => 'عنوان سئو',                'required' => false, 'sample' => 'خرید گوشی سامسونگ A54'],
        'meta_description'  => ['label' => 'توضیحات سئو',              'required' => false, 'sample' => 'خرید با بهترین قیمت'],
        'tags'              => ['label' => 'کلمات کلیدی',              'required' => false, 'sample' => 'گوشی, سامسونگ, موبایل'],
        'publish_date'      => ['label' => 'تاریخ انتشار',             'required' => false, 'sample' => '1403/04/08'],
        'category'          => ['label' => 'دسته بندی',                'required' => false, 'sample' => 'موبایل و تبلت'],
        'type'              => ['label' => 'نوع محصول (فیزیکی،دانلودی)', 'required' => false, 'sample' => 'physical'],
    ];

    /** انبار مقصد (پورت warehouse_id ویوی قدیمی) */
    public $warehouseId = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('imports.products') ?: abort(403);

        // فاز ۱۹: همه‌ی فیلدها به‌صورت پیش‌فرض تیک می‌خورند
        $this->defaultAllFiltersChecked();
    }

    /** انبارهای فعال (پورت productsExcelImport قدیمی) */
    public function getWarehousesProperty()
    {
        return Warehouse::active()->get();
    }

    /* --------- پیاده‌سازی trait --------- */

    protected function importType(): string
    {
        return 'products';
    }

    protected function importLabel(): string
    {
        return 'محصولات';
    }

    protected function errorFilePattern(): string
    {
        return 'products_import_errors_*.json';
    }

    protected function lockKey(): string
    {
        $userId = auth('adminPanel')->id();

        return 'laravel_unique_job_' . 'import_products_' . ($userId ?? 'guest');
    }

    protected function lockTtl(): int
    {
        return 240;
    }

    protected function importDirectory(): string
    {
        return 'imports/products';
    }

    protected function baseFilename(): string
    {
        return 'import_products';
    }

    protected function dispatchImportJob(string $filePath, array $jobData): void
    {
        ImportProductsJob::dispatch($filePath, $jobData);
    }

    /* --------- ذخیره (پورت ۱:۱ productsExcelImport_Store قدیمی) --------- */

    public function store(): void
    {
        auth('adminPanel')->user()?->can('imports.products') ?: abort(403);

        $this->validate([
            'file'          => 'required|file|mimes:xlsx,csv',
            'filters.title' => 'required',
            'warehouseId'   => 'required|exists:warehouses,id',
        ], [], [
            'file'        => 'فایل اکسل',
            'warehouseId' => 'انبار',
        ]);

        $this->storeExcel([
            'warehouse_id' => $this->warehouseId,
        ]);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.imports.products')
            ->layout('admin-panel::layout');
    }
}
