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

    /** فیلدهای اکسل محصولات — عیناً از ویوی قدیمی productsExcelImport */
    public array $fields = [
        'title'             => ['label' => 'عنوان',                    'required' => true],
        'title_en'          => ['label' => 'عنوان انگلیسی',            'required' => false],
        'slug'              => ['label' => 'Slug',                     'required' => false],
        'brand'             => ['label' => 'برند',                     'required' => false],
        'weight'            => ['label' => 'وزن',                      'required' => false],
        'unit'              => ['label' => 'واحد',                     'required' => false],
        'price'             => ['label' => 'قیمت',                     'required' => true],
        'stock'             => ['label' => 'موجودی انبار',             'required' => true],
        'short_description' => ['label' => 'توضیحات کوتاه',            'required' => false],
        'description'       => ['label' => 'توضیحات',                  'required' => false],
        'special'           => ['label' => 'محصول ویژه',               'required' => false],
        'published'         => ['label' => 'پیش‌نویس',                  'required' => false],
        'image'             => ['label' => 'تصویر شاخص',               'required' => false],
        'meta_title'        => ['label' => 'عنوان سئو',                'required' => false],
        'meta_description'  => ['label' => 'توضیحات سئو',              'required' => false],
        'tags'              => ['label' => 'کلمات کلیدی',              'required' => false],
        'publish_date'      => ['label' => 'تاریخ انتشار',             'required' => false],
        'category'          => ['label' => 'دسته بندی',                'required' => false],
        'type'              => ['label' => 'نوع محصول (فیزیکی،دانلودی)', 'required' => false],
    ];

    /** انبار مقصد (پورت warehouse_id ویوی قدیمی) */
    public $warehouseId = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('imports.products') ?: abort(403);

        $this->filters['title'] = '1';
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
