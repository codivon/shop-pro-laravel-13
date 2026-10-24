<?php

namespace App\Livewire\Admin\Sellers;

use App\Models\Seller;
use App\Services\AdminPanel\SellerPersistence;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('لیست فروشندگان')]
class SellerIndex extends Component
{
    use WithPagination;

    /* ---------------- فیلترها (State در URL) ---------------- */

    #[Url(as: 'name', except: '')]
    public string $name = '';

    #[Url(as: 'national_number', except: '')]
    public string $nationalNumber = '';

    #[Url(as: 'status_documents', except: '')]
    public string $statusDocuments = '';

    public int $perPage = 15;

    /* ---------------- انتخاب گروهی ---------------- */

    public array $selected = [];

    public bool $selectAll = false;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public bool $confirmingBulkDelete = false;

    /* ---------------- گیت چندفروشندگی (مثل قدیمی) ---------------- */

    public function mount(): void
    {
        abort_unless(option('multi_vendor_system_status', 'false') == 'true', 503, 'سیستم چند فروشندگی غیرفعال است');
    }

    public function updating($name, $value)
    {
        if (in_array($name, ['name', 'nationalNumber', 'statusDocuments', 'perPage'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    /** بازنشانی همه فیلترها */
    public function applyFilters(): void
    {
        $this->name = '';
        $this->nationalNumber = '';
        $this->statusDocuments = '';
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    /* ---------------- آمار سربرگ ---------------- */

    #[Computed(persist: true, seconds: 60)]
    public function statistics(): array
    {
        return [
            'total' => Seller::count(),
            'active' => Seller::isFullActive()->count(),
            'waiting' => Seller::where('status_documents', 'Waiting')->count(),
        ];
    }

    /* ---------------- کوئری لیست (پورت SellerInfo::datatableFilter قدیمی) ---------------- */

    protected function baseQuery()
    {
        $query = Seller::query()
            ->with(['seller_info:id,seller_id,first_name,last_name,business_name,mobile,logo,national_identity_number,status_documents'])
            ->withCount('products');

        // نام فروشگاه / کد فروشگاه — مطابق قدیمی: عددی → id، غیر عددی → نام تجاری
        if ($this->name !== '') {
            if (ctype_digit($this->name)) {
                $query->where(function ($q) {
                    $q->whereHas('seller_info', fn ($info) => $info->where('id', 'like', '%' . $this->name . '%'))
                        ->orWhere('id', 'like', '%' . $this->name . '%');
                });
            } else {
                $query->whereHas('seller_info', fn ($info) => $info->where('business_name', 'like', '%' . $this->name . '%'));
            }
        }

        // شماره همراه / کد ملی — عددی → موبایل، غیر عددی → کد ملی
        if ($this->nationalNumber !== '') {
            if (ctype_digit($this->nationalNumber)) {
                $query->whereHas('seller_info', fn ($info) => $info->where('mobile', 'like', '%' . $this->nationalNumber . '%'));
            } else {
                $query->whereHas('seller_info', fn ($info) => $info->where('national_identity_number', 'like', '%' . $this->nationalNumber . '%'));
            }
        }

        // وضعیت مدارک (روی جدول sellers_info مثل قدیمی)
        if (in_array($this->statusDocuments, ['Accept', 'Waiting', 'Reject'])) {
            $query->whereHas('seller_info', fn ($info) => $info->where('status_documents', $this->statusDocuments));
        }

        return $query->latest('sellers.id');
    }

    #[Computed]
    public function sellers()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /* ---------------- وضعیت ترکیبی (پورت template قدیمی) ---------------- */

    public static function statusBadge(?Seller $seller): array
    {
        if (! $seller) {
            return ['label' => 'نامشخص', 'class' => 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400'];
        }

        if ($seller->status_register === 'complete') {
            if ($seller->status_documents === 'Accept') {
                if ($seller->status === 'ACTIVE') {
                    if ($seller->status_work === 'ACTIVE') {
                        return ['label' => 'فعال', 'class' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400'];
                    }

                    return ['label' => 'توقف کار', 'class' => 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400'];
                }

                return ['label' => 'غیر فعال', 'class' => 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400'];
            }

            if ($seller->status_documents === 'Waiting') {
                return ['label' => 'در انتظار تایید مدارک', 'class' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400'];
            }

            return ['label' => 'رد مدارک', 'class' => 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400'];
        }

        return ['label' => 'در حال ثبت نام', 'class' => 'bg-slate-100 text-slate-600 dark:bg-night-3 dark:text-night-ink-2'];
    }

    /* ---------------- انتخاب گروهی ---------------- */

    public function updatedSelectAll($value): void
    {
        $this->selected = $value
            ? $this->sellers->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    /* ---------------- حذف ---------------- */

    public function confirmDelete(int $id): void
    {
        $this->authorizeSeller('sellers.delete');
        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        $this->authorizeSeller('sellers.delete');

        $seller = Seller::find($this->confirmingDelete);

        if ($seller) {
            app(SellerPersistence::class)->destroy($seller);
        }

        $this->confirmingDelete = null;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'فروشنده با موفقیت حذف شد.', type: 'success');
    }

    public function bulkDelete(): void
    {
        $this->authorizeSeller('sellers.delete');

        $service = app(SellerPersistence::class);

        foreach (Seller::whereIn('id', $this->selected)->get() as $seller) {
            $service->destroy($seller);
        }

        $this->confirmingBulkDelete = false;
        $this->selected = [];
        $this->selectAll = false;

        $this->dispatch('toast', message: 'فروشندگان انتخاب‌شده حذف شدند.', type: 'success');
    }

    protected function authorizeSeller(string $permission): void
    {
        if (! auth('adminPanel')->user()?->can($permission)) {
            abort(403);
        }
    }

    /* ---------------- نمایش ---------------- */

    public static function jdate($date, $format = 'Y/m/d'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.sellers.index')
            ->layout('admin-panel::layout');
    }
}
