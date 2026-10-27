<?php

namespace App\Livewire\Admin\Finance;

use App\Models\Seller;
use App\Models\SellerDeposit;
use App\Support\MultiVendorSettings;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * پنل کمیسیون فروشندگان (فاز ۱۰۳).
 *
 * - جدول فروشندگان + کمیسیون اختصاصیِ هرکدام (override nullable روی sellers.commission)
 * - اولویت کمیسیون مؤثر: فروشنده > دسته‌بندی > پیش‌فرض سراسری (MultiVendorSettings::resolveCommission)
 * - ویرایش override با گیت کلید multi_vendor_seller_commission_override
 * - گیت صفحه: همان اجازهٔ «لیست واریزی فروشندگان» (payments.transactions.seller_deposit)
 *   — مخاطب همان است و کلید پرمیشن جدیدی به جدول permissions نیاز ندارد.
 * - گیت صفحهٔ کامل با کلید اصلی چندفروشندگی (multi_vendor_system_status).
 */
#[Title('کمیسیون فروشندگان')]
class VendorCommissionIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 15;

    /* ---------------- مودال ویرایش کمیسیون اختصاصی ---------------- */

    public bool $editing = false;

    public ?int $editingId = null;

    public string $editingValue = '';

    public string $editingLabel = '';

    public function mount(): void
    {
        // گیت کلید اصلی چندفروشندگی (هم‌تراز با SellerIndex فاز ۱۲) + پرمیشن مالی
        abort_unless(MultiVendorSettings::enabled(), 503, MultiVendorSettings::disabledMessage());

        auth('adminPanel')->user()?->can('payments.transactions.seller_deposit') ?: abort(403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function items()
    {
        $query = Seller::query()
            ->with('seller_info')
            ->orderByDesc('created_at');

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';

            $query->where(function ($q) use ($term) {
                $q->where('email', 'like', $term)
                    ->orWhere('mobile', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhereHas('seller_info', function ($sq) use ($term) {
                        $sq->where('business_name', 'like', $term)
                            ->orWhere('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term);
                    });
            });
        }

        return $query->paginate($this->perPage);
    }

    /** آمار کمیسیون‌های سایت (seller_deposits) */
    #[Computed(persist: true, seconds: 60)]
    public function statistics(): array
    {
        $base = SellerDeposit::query()->where('status', 'success');

        return [
            'total' => (clone $base)->sum('amount'),
            'month' => (clone $base)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'orders' => (clone $base)->distinct('order_id')->count('order_id'),
            'sellers' => Seller::isFullActive()->count(),
        ];
    }

    /** کمیسیون مؤثر نمایشی یک فروشنده (بدون دسته‌بندی — برای ستون جدول) */
    public function effectiveCommissionLabel(Seller $seller): string
    {
        if (MultiVendorSettings::sellerOverrideEnabled() && ! is_null($seller->commission)) {
            return (int) $seller->commission.'٪ (اختصاصی)';
        }

        $default = MultiVendorSettings::defaultCommission();

        if (! is_null($default)) {
            return $default.'٪ (پیش‌فرض)';
        }

        return 'بر اساس دسته‌بندی';
    }

    /* ---------------- ویرایش کمیسیون اختصاصی ---------------- */

    public function editCommission(int $sellerId): void
    {
        auth('adminPanel')->user()?->can('payments.transactions.seller_deposit') ?: abort(403);

        if (! MultiVendorSettings::sellerOverrideEnabled()) {
            throw ValidationException::withMessages([
                'editingValue' => 'کمیسیون اختصاصی فروشنده از تنظیمات چندفروشندگی غیرفعال است.',
            ]);
        }

        $seller = Seller::with('seller_info')->find($sellerId);

        if (! $seller) {
            return;
        }

        $this->editingId = $seller->id;
        $this->editingValue = is_null($seller->commission) ? '' : (string) (int) $seller->commission;

        $label = $seller->seller_info->business_name
            ?? trim(($seller->seller_info->first_name ?? '').' '.($seller->seller_info->last_name ?? ''));

        if (! is_string($label) || trim($label) === '') {
            $label = $seller->slug ?: ('فروشنده #'.$seller->id);
        }

        $this->editingLabel = $label;
        $this->editing = true;
    }

    public function saveCommission(): void
    {
        auth('adminPanel')->user()?->can('payments.transactions.seller_deposit') ?: abort(403);

        if (! MultiVendorSettings::sellerOverrideEnabled()) {
            abort(403, 'کمیسیون اختصاصی از تنظیمات چندفروشندگی غیرفعال است.');
        }

        $this->validate([
            'editingValue' => ['nullable', 'integer', 'between:0,100'],
        ], [
            'editingValue.integer' => 'کمیسیون باید عدد باشد.',
            'editingValue.between' => 'کمیسیون باید بین ۰ تا ۱۰۰ درصد باشد.',
        ], [
            'editingValue' => 'کمیسیون اختصاصی',
        ]);

        $seller = Seller::find($this->editingId);

        if ($seller) {
            // خالی = حذف override (برگشت به دسته‌بندی/پیش‌فرض)
            $seller->commission = trim((string) $this->editingValue) === '' ? null : (int) $this->editingValue;
            $seller->save();

            MultiVendorSettings::flushCache();

            activity()
                ->performedOn($seller)
                ->withProperties(['commission' => $seller->commission])
                ->log('تنظیم کمیسیون اختصاصی فروشنده');
        }

        $this->editing = false;
        $this->editingId = null;
        $this->editingValue = '';

        $this->dispatch('toast', message: 'کمیسیون فروشنده ذخیره شد.', type: 'success');
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.finance.vendor-commission')
            ->layout('admin-panel::layout');
    }
}
