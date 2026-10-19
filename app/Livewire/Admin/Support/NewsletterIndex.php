<?php

namespace App\Livewire\Admin\Support;

use App\Models\Newsletter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * خبرنامه — پورت ۱:۱ Back\NewsletterController (index/show/destroy/multipleDestroy + اکسپورت)
 * دسترسی: newsletters.index / newsletters.show / newsletters.delete (پورت NewsletterPolicy قدیمی)
 */
#[Title('خبرنامه')]
class NewsletterIndex extends Component
{
    use WithPagination;

    public int $perPage = 15;

    /* ---------------- فیلترها (پورت index قدیمی — State در URL) ---------------- */

    #[Url(as: 'search', except: '')]
    public string $search = '';

    /** email | mobile (پورت regex قدیمی) */
    #[Url(as: 'contact_type', except: '')]
    public string $contactType = '';

    /** active | inactive */
    #[Url(as: 'status', except: '')]
    public string $status = '';

    /** desktop | mobile | tablet */
    #[Url(as: 'device_type', except: '')]
    public string $deviceType = '';

    /* ---------------- مودال جزئیات (پورت show قدیمی) ---------------- */

    public bool $showDetail = false;

    public ?int $viewingSubscriber = null;

    /* ---------------- حذف تکی/گروهی (پورت destroy/multipleDestroy قدیمی) ---------------- */

    public array $selected = [];

    public bool $selectAll = false;

    public ?int $confirmingDelete = null;

    public bool $confirmingBulkDelete = false;

    /* ---------------- مودال اکسپورت (پورت export قدیمی — روت قدیمی admin.newsletters.export) ---------------- */

    public bool $exportModal = false;

    public string $exportType = '';

    public string $exportFormat = 'xlsx';

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('newsletters.index') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['search', 'contactType', 'status', 'deviceType', 'perPage'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    public function applyFilters(): void
    {
        $this->search = '';
        $this->contactType = '';
        $this->status = '';
        $this->deviceType = '';
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    /* ---------------- لیست (پورت index قدیمی با فیلترها) ---------------- */

    #[Computed]
    public function subscribers()
    {
        $this->ensureSqliteRegexp();

        return Newsletter::query()
            ->when($this->search !== '', fn ($query) => $query->where('contact', 'like', "%{$this->search}%"))
            ->when($this->contactType !== '', function ($query) {
                if ($this->contactType === 'email') {
                    $query->emailSubscribers();
                } elseif ($this->contactType === 'mobile') {
                    $query->mobileSubscribers();
                }
            })
            ->when($this->status !== '', fn ($query) => $query->where('is_active', $this->status === 'active'))
            ->when($this->deviceType !== '', fn ($query) => $query->where('device_type', $this->deviceType))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    /* ---------------- آمار ۵ کارته (پورت index قدیمی) ---------------- */

    #[Computed]
    public function statistics(): array
    {
        $this->ensureSqliteRegexp();

        return [
            'total'    => Newsletter::count(),
            'active'   => Newsletter::where('is_active', true)->count(),
            'inactive' => Newsletter::where('is_active', false)->count(),
            'email'    => Newsletter::emailSubscribers()->count(),
            'mobile'   => Newsletter::mobileSubscribers()->count(),
        ];
    }

    /* ---------------- مودال جزئیات (پورت show قدیمی — gate newsletters.show) ---------------- */

    public function openDetail(int $id): void
    {
        auth('adminPanel')->user()?->can('newsletters.show') ?: abort(403);

        if (! Newsletter::find($id)) {
            $this->dispatch('toast', message: 'مشترک یافت نشد.', type: 'error');

            return;
        }

        $this->viewingSubscriber = $id;
        $this->showDetail = true;
    }

    #[Computed]
    public function detailSubscriber(): ?Newsletter
    {
        if (! $this->viewingSubscriber) {
            return null;
        }

        return Newsletter::find($this->viewingSubscriber);
    }

    /* ---------------- اکسپورت (پورت export قدیمی → روت قدیمی admin.newsletters.export) ---------------- */

    public function doExport(): void
    {
        auth('adminPanel')->user()?->can('newsletters.index') ?: abort(403);

        if (! Route::has('admin.newsletters.export')) {
            $this->dispatch('toast', message: 'روت خروجی اکسل (admin.newsletters.export) یافت نشد.', type: 'error');

            return;
        }

        if (! in_array($this->exportType, ['all', 'email', 'mobile', 'active', 'inactive'], true)) {
            $this->dispatch('toast', message: 'نوع خروجی را انتخاب کنید.', type: 'warning');

            return;
        }

        $format = $this->exportFormat === 'csv' ? 'csv' : 'xlsx';

        $query = http_build_query(['type' => $this->exportType, 'format' => $format]);

        $this->exportModal = false;

        // دانلود از مسیر قدیمی (الگوی اکسل فاز ۲/۴ با query[...])
        $this->dispatch('open-url', url: route('admin.newsletters.export') . '?' . $query);
    }

    /* ---------------- انتخاب گروهی ---------------- */

    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->subscribers->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    /* ---------------- حذف (پورت destroy/multipleDestroy قدیمی) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('newsletters.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('newsletters.delete') ?: abort(403);

        if ($newsletter = Newsletter::find($this->confirmingDelete)) {
            $newsletter->delete();
        }

        $this->confirmingDelete = null;

        if ($this->viewingSubscriber !== null && ! Newsletter::find($this->viewingSubscriber)) {
            $this->showDetail = false;
            $this->viewingSubscriber = null;
        }

        $this->refreshAfterMutation();

        $this->dispatch('toast', message: 'مشترک با موفقیت حذف شد.', type: 'success');
    }

    public function confirmBulkDelete(): void
    {
        auth('adminPanel')->user()?->can('newsletters.delete') ?: abort(403);

        if (count($this->selected) === 0) {
            $this->dispatch('toast', message: 'موردی انتخاب نشده است.', type: 'error');

            return;
        }

        $this->confirmingBulkDelete = true;
    }

    /** پورت multipleDestroy قدیمی (authorize newsletters.delete + حلقه حذف تکی) */
    public function bulkDelete(): void
    {
        auth('adminPanel')->user()?->can('newsletters.delete') ?: abort(403);

        foreach ($this->selected as $id) {
            if ($newsletter = Newsletter::find($id)) {
                $newsletter->delete();
            }
        }

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        $this->refreshAfterMutation();

        $this->dispatch('toast', message: 'مشترکین انتخاب‌شده با موفقیت حذف شدند.', type: 'success');
    }

    protected function refreshAfterMutation(): void
    {
        unset($this->subscribers);
        unset($this->statistics);
        unset($this->detailSubscriber);
    }

    /* ---------------- ابزار ---------------- */

    /**
     * SQLite (محیط تست) تابع REGEXP ندارد؛ برای هم‌ارزی دقیق عبارات منظم قدیمی
     * (scope emailSubscribers/mobileSubscribers) تابع regexp روی همان کانکشن ثبت می‌شود.
     * در MySQL (production) رفتار بومی REGEXP بدون تغییر استفاده می‌شود.
     */
    protected function ensureSqliteRegexp(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        $pdo = DB::connection()->getPdo();

        $pdo->sqliteCreateFunction('regexp', function ($pattern, $value) {
            if ($value === null) {
                return null;
            }

            return preg_match('#' . str_replace('#', '\#', (string) $pattern) . '#', (string) $value) ?: 0;
        }, 2);
    }

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.support.newsletters')
            ->layout('admin-panel::layout');
    }
}
