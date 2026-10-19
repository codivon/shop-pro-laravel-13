<?php

namespace App\Livewire\Admin\Imports;

use App\Jobs\ImportUsersJob;
use App\Livewire\Admin\Imports\Concerns\InteractsWithImports;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('درون‌ریزی کاربران')]
class UsersImport extends Component
{
    use InteractsWithImports;

    /** فیلدهای اکسل کاربران — عیناً از ویوی قدیمی usersExcelImport */
    public array $fields = [
        'first_name'    => ['label' => 'نام',            'required' => true,  'sample' => 'علی'],
        'last_name'     => ['label' => 'نام خانوادگی',   'required' => false, 'sample' => 'محمدی'],
        'email'         => ['label' => 'ایمیل',          'required' => true,  'sample' => 'ali@example.com'],
        'mobile'        => ['label' => 'موبایل',         'required' => true,  'sample' => '09123456789'],
        'password'      => ['label' => 'رمز ورود',       'required' => true,  'sample' => '123456789'],
        'image'         => ['label' => 'تصویر پروفایل',  'required' => false, 'sample' => 'https://site.com/avatar.jpg'],
        'national_code' => ['label' => 'کد ملی',         'required' => false, 'sample' => '1234567890'],
        'birth_date'    => ['label' => 'تاریخ تولد',     'required' => false, 'sample' => '1370/01/01'],
        'card_number'   => ['label' => 'شماره کارت',     'required' => false, 'sample' => '5022291012345678'],
    ];

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('imports.users') ?: abort(403);
    }

    /* --------- پیاده‌سازی trait --------- */

    protected function importType(): string
    {
        return 'users';
    }

    protected function importLabel(): string
    {
        return 'کاربران';
    }

    protected function errorFilePattern(): string
    {
        return 'users_import_errors_*.json';
    }

    protected function lockKey(): string
    {
        $userId = auth('adminPanel')->id();

        return 'laravel_unique_job_' . 'import_users_' . ($userId ?? 'guest');
    }

    protected function lockTtl(): int
    {
        // قدیمی برای کاربران Cache::put نمی‌کرد (فقط چک می‌کرد) — عیناً حفظ شد
        return 0;
    }

    protected function importDirectory(): string
    {
        // ⚠️ quirk تاریخی قدیمی: فایل کاربران هم در imports/products ذخیره می‌شد — عیناً حفظ شد
        return 'imports/products';
    }

    protected function baseFilename(): string
    {
        return 'import_users';
    }

    protected function dispatchImportJob(string $filePath, array $jobData): void
    {
        ImportUsersJob::dispatch($filePath, $jobData);
    }

    /* --------- ذخیره (پورت ۱:۱ usersExcelImport_Store قدیمی) --------- */

    public function store(): void
    {
        auth('adminPanel')->user()?->can('imports.users') ?: abort(403);

        $this->validate([
            'file'           => 'required|file|mimes:xlsx,csv',
            'filters.email'  => 'required_without_all:filters.mobile',
            'filters.mobile' => 'required_without_all:filters.email',
            'filters.password' => 'required',
        ], [
            'filters.email.required_without_all'  => 'یکی از فیلد های ایمیل یا موبایل الزامی است',
            'filters.mobile.required_without_all' => 'یکی از فیلد های ایمیل یا موبایل الزامی است',
            'filters.password.required'           => 'فیلد رمز ورود الزامی است',
        ], [
            'file' => 'فایل اکسل',
        ]);

        $this->storeExcel();
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.imports.users')
            ->layout('admin-panel::layout');
    }
}
