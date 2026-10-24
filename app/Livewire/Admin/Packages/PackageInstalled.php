<?php

namespace App\Livewire\Admin\Packages;

use App\Jobs\UpdatePackageJob;
use App\Livewire\Admin\Packages\Concerns\InteractsWithPackages;
use App\Models\InstalledModule;
use App\Services\PackageInstallerService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Title('ماژول‌های نصب‌شده')]
class PackageInstalled extends Component
{
    use InteractsWithPackages;
    use WithPagination;

    public int $perPage = 20;

    /** حذف ماژول (پورت uninstall قدیمی) */
    public ?string $confirmingUninstall = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('packages.installed') ?: abort(403);
    }

    /* ---------------- لیست (پورت installed قدیمی) ---------------- */

    public function getModulesProperty()
    {
        return InstalledModule::with('cache')
            ->latest('installed_at')
            ->paginate($this->perPage);
    }

    /* ---------------- آپدیت ماژول (پورت ۱:۱ update قدیمی) ---------------- */

    public function updateModule(string $slug): void
    {
        auth('adminPanel')->user()?->can('packages.installed') ?: abort(403);

        $installed = InstalledModule::where('slug', $slug)->first();

        if (!$installed) {
            $this->dispatch('toast', message: 'ماژول نصب نشده است.', type: 'error');
            return;
        }

        if ($installed->status === InstalledModule::STATUS_UPDATING) {
            $this->dispatch('toast', message: 'آپدیت قبلی همچنان در حال اجراست.', type: 'error');
            return;
        }

        if ($installed->isExpired()) {
            $this->dispatch('toast', message: 'لایسنس ماژول منقضی شده است. ابتدا آن را تمدید کنید.', type: 'error');
            return;
        }

        UpdatePackageJob::dispatch($slug, auth('adminPanel')->id());

        $this->dispatch('toast', message: 'آپدیت در صف پردازش قرار گرفت.', type: 'success');
    }

    /* ---------------- حذف ماژول (پورت ۱:۱ uninstall قدیمی) ---------------- */

    public function confirmUninstall(string $slug): void
    {
        auth('adminPanel')->user()?->can('packages.installed') ?: abort(403);

        $this->confirmingUninstall = $slug;
    }

    public function uninstall(): void
    {
        auth('adminPanel')->user()?->can('packages.installed') ?: abort(403);

        $slug = (string) $this->confirmingUninstall;
        $this->confirmingUninstall = null;

        if ($slug === '') {
            return;
        }

        try {
            app(PackageInstallerService::class)->uninstall($slug, auth('adminPanel')->id());

            $this->dispatch('toast', message: 'ماژول با موفقیت حذف شد.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'حذف ناموفق بود: ' . $e->getMessage(), type: 'error');
        }
    }

    /* ---------------- فعال/غیرفعال‌سازی (پورت ۱:۱ toggleActivation قدیمی) ---------------- */

    public function toggleActivation(string $slug): void
    {
        auth('adminPanel')->user()?->can('packages.installed') ?: abort(403);

        try {
            $installed = app(PackageInstallerService::class)->toggleActivation($slug);

            $this->dispatch(
                'toast',
                message: $installed->is_active ? 'ماژول فعال شد.' : 'ماژول غیرفعال شد.',
                type: 'success'
            );
        } catch (Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    /* ---------------- ابزار ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return \Morilog\Jalali\Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.packages.installed')
            ->layout('admin-panel::layout');
    }
}
