<?php

namespace App\Livewire\Admin\Packages;

use App\Jobs\UpdatePackageJob;
use App\Livewire\Admin\Packages\Concerns\InteractsWithPackages;
use App\Models\InstalledModule;
use App\Models\InstalledTheme;
use App\Services\ModuleRecoveryService;
use App\Services\PackageInstallerService;
use App\Support\MarketplaceSettings;
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

        /*
         * فاز ۹۹ — گیت نصب/به‌روزرسانی (گارد سروری): آپدیت ماژول هم دانلود از
         * سرور بازار است ⇒ با خاموشی کلید اصلی یا allow_install مسدود می‌شود.
         */
        if (! MarketplaceSettings::marketEnabled() || ! MarketplaceSettings::allowInstall()) {
            $this->dispatch('toast', message: MarketplaceSettings::installDisabledMessage(), type: 'error');
            return;
        }

        /*
         * فاز ۶۴ — قفل نصب همزمان: اگر نصب دیگری (پکیج/قالب) در جریان است، آپدیت شروع نشود.
         * پنجرهٔ تازگی ۱۰ دقیقه‌ای مثل PackageIndex::hasOtherInstallationInProgress.
         */
        $freshAfter = now()->subMinutes(10);
        $locked = InstalledModule::query()
                ->where('status', InstalledModule::STATUS_UPDATING)
                ->where('updated_at', '>', $freshAfter)
                ->exists()
            || InstalledTheme::query()
                ->where('status', InstalledTheme::STATUS_UPDATING)
                ->where('updated_at', '>', $freshAfter)
                ->exists();

        if ($locked) {
            $this->dispatch('toast', message: 'یک نصب دیگر در جریان است؛ تا پایان آن امکان شروع نصب پکیج یا قالب دیگری وجود ندارد.', type: 'error');
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

        /*
         * فاز ۹۹ — گیت حذف (گارد سروری، نه فقط UI): با خاموشی allow_uninstall
         * حذف ماژول از صفحهٔ نصب‌شده‌ها هم مسدود می‌شود (حذف تخریب‌کننده است؛
         * فعال/غیرفعال‌سازی محلی بلاک نمی‌شود — حیاتی برای پکیج خراب).
         */
        if (! MarketplaceSettings::allowUninstall()) {
            $this->dispatch('toast', message: MarketplaceSettings::uninstallDisabledMessage(), type: 'error');
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

    /* ---------------- بازیابی از پوشهٔ ماژول‌ها (فاز ۱۲۲ — گزارش کاربر) ---------------- */

    /**
     * بعد از ریست/بازیابی دیتابیس هاست، رکوردهای installed_modules خالی می‌شوند ولی
     * پوشه‌های Modules/ سر جایشان می‌مانند — نتیجه: بدون منوی پکیج‌ها و با لیست
     * نصب‌شدهٔ خالی. این اکشن ماژول‌های روی دیسک را اسکن و رکوردهای گم‌شده را
     * بازسازی می‌کند (نسخه از module.json + لایسنس از آخرین خرید پرداخت‌شده).
     */
    public function recoverFromDisk(): void
    {
        auth('adminPanel')->user()?->can('packages.installed') ?: abort(403);

        try {
            $report = app(ModuleRecoveryService::class)->recover(auth('adminPanel')->id());
        } catch (Throwable $e) {
            report($e);

            $this->dispatch('toast', message: 'بازیابی ناموفق بود: '.$e->getMessage(), type: 'error');

            return;
        }

        $restored = count($report['restored']);

        if ($restored === 0) {
            $this->dispatch(
                'toast',
                message: 'ماژولی برای بازیابی پیدا نشد — همهٔ ماژول‌های روی دیسک رکورد دیتابیس دارند.',
                type: 'info'
            );

            return;
        }

        $message = $restored.' ماژول از پوشهٔ ماژول‌ها بازیابی شد.';

        if ($report['statuses_rebuilt']) {
            $message .= ' فایل وضعیت ماژول‌ها بازسازی و کش‌ها پاک شد — منوی پکیج‌ها برمی‌گردد.';
        }

        $this->dispatch('toast', message: $message, type: 'success');

        // سایدبار (منوی پکیج‌ها) سمت سرور رندر می‌شود — رفرش کامل لازم است
        $this->js('setTimeout(() => window.location.reload(), 1500)');
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
