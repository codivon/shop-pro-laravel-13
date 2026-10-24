<?php

namespace App\Livewire\Admin\Warehouses;

use App\Models\City;
use App\Models\Price;
use App\Models\Province;
use App\Models\Seller;
use App\Models\Warehouse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

/**
 * لیست انبارها — پورت ۱:۱ Back\WarehouseController (index / create / store / edit / update / destroy / toggleStatus)
 *
 * تفاوت گیت: toggleStatus قدیمی «warehouses.edit» را چک می‌کرد که در درخت دسترسی وجود ندارد
 * (عملاً فقط سازنده می‌توانست) — اینجا از کلید واقعی «warehouses.update» استفاده شد (مستند در گزارش).
 */
#[Title('مدیریت انبارها')]
class WarehouseIndex extends Component
{
    use WithPagination;

    /* ---------------- فیلترها (state در URL — پورت فیلترهای GET قدیمی) ---------------- */

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'type', except: 'all')]
    public string $type = 'all';

    #[Url(as: 'status', except: 'all')]
    public string $status = 'all';

    public int $perPage = 20;

    /* ---------------- مودال ایجاد (پورت create/store) ---------------- */

    public bool $creating = false;

    public string $createName = '';
    public string $createType = 'main';
    public $createSellerId = null;
    public string $createManagerName = '';
    public string $createPhone = '';
    public $createProvinceId = null;
    public $createCityId = null;
    public string $createAddress = '';
    public bool $createIsActive = true;

    /* ---------------- مودال ویرایش (پورت edit/update) ---------------- */

    public ?int $editingId = null;

    public string $editName = '';
    public string $editType = 'main';
    public $editSellerId = null;
    public string $editManagerName = '';
    public string $editPhone = '';
    public $editProvinceId = null;
    public $editCityId = null;
    public string $editAddress = '';
    public bool $editIsActive = true;

    /* ---------------- حذف ---------------- */

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('warehouses.index') ?: abort(403);
    }

    public function updating(string $name, $value): void
    {
        if (in_array($name, ['search', 'type', 'status', 'perPage'])) {
            $this->resetPage();
        }
    }

    /* ---------------- لیست (پورت index) ---------------- */

    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()
            ->withCount('prices')
            ->withCount('products')
            ->when($this->search !== '', function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            })
            ->when($this->type !== '' && $this->type !== 'all', fn ($query) => $query->where('type', $this->type))
            ->when($this->status !== '' && $this->status !== 'all', fn ($query) => $query->where('is_active', $this->status === 'active'))
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    /** آمار کلی — کوئری‌ها عیناً پورت index قدیمی */
    #[Computed]
    public function stats(): array
    {
        return [
            'total' => Warehouse::count(),
            'active' => Warehouse::where('is_active', true)->count(),
            'main' => Warehouse::where('type', 'main')->count(),
            'seller' => Warehouse::where('type', 'seller')->count(),
            'total_products' => Price::whereNotNull('warehouse_id')->count('product_id'),
            'total_stock' => Price::sum('stock'),
        ];
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->type = 'all';
        $this->status = 'all';
        $this->resetPage();
    }

    /* ---------------- فرم ایجاد (پورت create/store) ---------------- */

    public function openCreate(): void
    {
        auth('adminPanel')->user()?->can('warehouses.create') ?: abort(403);

        $this->reset([
            'createName', 'createType', 'createSellerId', 'createManagerName', 'createPhone',
            'createProvinceId', 'createCityId', 'createAddress',
        ]);
        $this->createType = 'main';
        $this->createIsActive = true;
        $this->creating = true;
    }

    /** آبشار استان → شهر (پورت data-action شهرهای قدیمی) */
    public function updatedCreateProvinceId($value): void
    {
        $this->createCityId = null;
    }

    public function updatedEditProvinceId($value): void
    {
        $this->editCityId = null;
    }

    public function createWarehouse(): void
    {
        auth('adminPanel')->user()?->can('warehouses.create') ?: abort(403);

        $this->validate([
            'createName' => 'required|string|max:255',
            'createType' => 'required|in:main,seller,temp',
            'createSellerId' => 'required_if:createType,seller|nullable|exists:sellers,id',
            'createManagerName' => 'nullable|string|max:255',
            'createPhone' => 'nullable|string|max:20',
            'createProvinceId' => 'nullable|string',
            'createCityId' => 'nullable|string',
            'createAddress' => 'nullable|string',
            'createIsActive' => 'nullable|boolean',
        ], [], [
            'createName' => 'نام انبار',
            'createType' => 'نوع انبار',
            'createSellerId' => 'فروشنده',
            'createManagerName' => 'مدیر انبار',
            'createPhone' => 'تلفن انبار',
            'createProvinceId' => 'استان',
            'createCityId' => 'شهر',
            'createAddress' => 'آدرس انبار',
            'createIsActive' => 'وضعیت انبار',
        ]);

        $warehouse = Warehouse::create([
            'name' => $this->createName,
            'type' => $this->createType,
            'seller_id' => $this->createType == 'seller' ? $this->createSellerId : null,
            'manager_name' => $this->createManagerName,
            'phone' => $this->createPhone,
            'province_id' => $this->createProvinceId,
            'city_id' => $this->createCityId,
            'address' => $this->createAddress,
            'is_active' => $this->createIsActive,
        ]);

        $this->creating = false;

        // پیام دقیق قدیمی (store پاسخ JSON با همین متن می‌داد)
        $this->dispatch('toast', message: "انبار {$warehouse->name} با موفقیت ایجاد شد. کد انبار: {$warehouse->code}", type: 'success');
    }

    /* ---------------- فرم ویرایش (پورت edit/update) ---------------- */

    public function editWarehouse(int $id): void
    {
        auth('adminPanel')->user()?->can('warehouses.update') ?: abort(403);

        $warehouse = Warehouse::findOrFail($id);

        $this->editingId = $warehouse->id;
        $this->editName = (string) $warehouse->name;
        $this->editType = (string) $warehouse->type;
        $this->editSellerId = $warehouse->seller_id;
        $this->editManagerName = (string) $warehouse->manager_name;
        $this->editPhone = (string) $warehouse->phone;
        $this->editProvinceId = $warehouse->province_id;
        $this->editCityId = $warehouse->city_id;
        $this->editAddress = (string) $warehouse->address;
        $this->editIsActive = (bool) $warehouse->is_active;
    }

    public function updateWarehouse(): void
    {
        auth('adminPanel')->user()?->can('warehouses.update') ?: abort(403);

        $warehouse = Warehouse::findOrFail((int) $this->editingId);

        $this->validate([
            'editName' => 'required|string|max:255',
            'editType' => 'required|in:main,seller,temp',
            'editSellerId' => 'required_if:editType,seller|nullable|exists:sellers,id',
            'editManagerName' => 'nullable|string|max:255',
            'editPhone' => 'nullable|string|max:20',
            'editProvinceId' => 'nullable|string',
            'editCityId' => 'nullable|string',
            'editAddress' => 'nullable|string',
            'editIsActive' => 'nullable|boolean',
        ], [], [
            'editName' => 'نام انبار',
            'editType' => 'نوع انبار',
            'editSellerId' => 'فروشنده',
            'editManagerName' => 'مدیر انبار',
            'editPhone' => 'تلفن انبار',
            'editProvinceId' => 'استان',
            'editCityId' => 'شهر',
            'editAddress' => 'آدرس انبار',
            'editIsActive' => 'وضعیت انبار',
        ]);

        $warehouse->update([
            'name' => $this->editName,
            'type' => $this->editType,
            'seller_id' => $this->editType == 'seller' ? $this->editSellerId : null,
            'manager_name' => $this->editManagerName,
            'phone' => $this->editPhone,
            'province_id' => $this->editProvinceId,
            'city_id' => $this->editCityId,
            'address' => $this->editAddress,
            'is_active' => $this->editIsActive,
        ]);

        $this->editingId = null;

        $this->dispatch('toast', message: "انبار {$warehouse->name} با موفقیت ویرایش شد", type: 'success');
    }

    /* ---------------- تغییر وضعیت (پورت toggleStatus) ---------------- */

    public function toggleStatus(int $id): void
    {
        auth('adminPanel')->user()?->can('warehouses.update') ?: abort(403);

        $warehouse = Warehouse::findOrFail($id);

        $adminName = auth('adminPanel')->user()->fullname ?? 'مدیر';
        $warehouseName = $warehouse->name ?? $warehouse->code ?? "#{$warehouse->id}";

        $oldStatus = $warehouse->is_active ? 'فعال' : 'غیرفعال';
        $warehouse->update(['is_active' => ! $warehouse->is_active]);
        $newStatus = $warehouse->is_active ? 'فعال' : 'غیرفعال';

        activity()
            ->performedOn($warehouse)
            ->event('updated')
            ->causedBy(auth('adminPanel')->user())
            ->withProperties([
                'action' => 'toggle_warehouse_status',
                'old' => ['وضعیت' => $oldStatus],
                'attributes' => ['وضعیت' => $newStatus],
                'ip' => request()->ip(),
            ])
            ->log("مدیر {$adminName} وضعیت انبار «{$warehouseName}» را تغییر داد");

        $this->dispatch('toast', message: "وضعیت انبار به {$newStatus} تغییر کرد", type: 'success');
    }

    /* ---------------- حذف (پورت destroy) ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('warehouses.delete') ?: abort(403);

        $this->confirmingDelete = $id;
    }

    public function delete(): void
    {
        auth('adminPanel')->user()?->can('warehouses.delete') ?: abort(403);

        $warehouse = Warehouse::find($this->confirmingDelete);

        if ($warehouse) {
            // بررسی وجود محصول در انبار — پیام دقیق قدیمی
            if ($warehouse->prices()->exists()) {
                $this->confirmingDelete = null;

                $this->dispatch('toast', message: 'این انبار دارای محصول است. ابتدا محصولات را جابجا یا حذف کنید.', type: 'error');

                return;
            }

            $name = $warehouse->name;
            $warehouse->delete();

            $this->dispatch('toast', message: "انبار {$name} با موفقیت حذف شد", type: 'success');
        }

        $this->confirmingDelete = null;
    }

    /* ---------------- ابزار ---------------- */

    public function provinces()
    {
        return Province::active()->orderBy('name')->get();
    }

    /** آبشار شهرهای استان برای فرم ایجاد — پورت data-action شهرهای قدیمی */
    public function cities($provinceId)
    {
        if (! $provinceId) {
            return collect();
        }

        return City::where('province_id', $provinceId)->orderBy('name')->get();
    }

    /** پورت کوئری create قدیمی: Seller::where(status ACTIVE)->whereHas(seller_info) — برچسب‌ها null-ایمن از sellers_info */
    public function sellers()
    {
        return Seller::where('status', 'ACTIVE')
            ->whereHas('seller_info')
            ->with('seller_info')
            ->get()
            ->map(function ($seller) {
                $info = $seller->seller_info;

                $label = $info->business_name ?? null;
                if (! $label) {
                    $label = trim(($info->first_name ?? '').' '.($info->last_name ?? '')) ?: ('فروشنده #'.$seller->id);
                }

                return (object) [
                    'id' => $seller->id,
                    'label' => $label.' ('.($seller->mobile ?? '-').')',
                ];
            });
    }

    public function editCities()
    {
        if (! $this->editProvinceId) {
            return collect();
        }

        return City::where('province_id', $this->editProvinceId)->orderBy('name')->get();
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
        return view('admin-panel::livewire.admin.warehouses.index')
            ->layout('admin-panel::layout');
    }
}
