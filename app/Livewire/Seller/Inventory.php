<?php

namespace App\Livewire\Seller;

use App\Models\Price;
use App\Models\StockMovement;
use App\Support\MultiVendorSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * فاز ۱۰۸ — مرکز موجودی فروشنده (پورت SellerInventoryController@index).
 * فقط-خواندنی: تغییر موجودی از جریان ادمین/سرویس انبار است. گیت: seller_inventory_center.
 */
#[Title('مرکز موجودی')]
#[Layout('sellers.layouts.panel')]
class Inventory extends Component
{
    use WithPagination;

    public array $stats = [];

    public $movements;

    public function mount(): void
    {
        if (! MultiVendorSettings::inventoryCenterEnabled()) {
            session()->put('toast-error', 'مرکز موجودی از تنظیمات غیرفعال شده است.');
            $this->redirect(route('seller.dashboard'), navigate: true);

            return;
        }

        $sellerId = (int) sellerID();

        // مبنای واحد تنوع‌ها: قیمت‌هایی که seller_id مستقیم دارند یا محصولشان متعلق به این فروشنده است
        $priceBase = Price::query()
            ->where(function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId)
                    ->orWhereHas('product', fn ($p) => $p->where('seller_id', $sellerId));
            });

        // تاریخچهٔ حرکت انبار (اتحاد هر دو مبنا — عین کنترلر قدیمی)
        $movementBase = fn () => StockMovement::query()
            ->where(function ($q) use ($sellerId) {
                $q->whereHas('product', fn ($p) => $p->where('seller_id', $sellerId))
                    ->orWhereHas('price', fn ($p) => $p->where('seller_id', $sellerId));
            });

        $this->stats = [
            'total_variants' => (clone $priceBase)->count(),
            'out_of_stock' => (clone $priceBase)->where('stock', '<', 1)->count(),
            'low_stock' => (clone $priceBase)->whereBetween('stock', [1, 2])->count(),
            'recent_movements' => $movementBase()
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];

        $this->movements = $movementBase()
            ->with('product:id,title,slug')
            ->latest()
            ->limit(20)
            ->get();
    }

    public function render()
    {
        $sellerId = (int) sellerID();

        $prices = Price::query()
            ->where(function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId)
                    ->orWhereHas('product', fn ($p) => $p->where('seller_id', $sellerId));
            })
            ->with(['product:id,title,slug,type', 'get_attributes.group', 'file'])
            ->orderBy('stock')
            ->orderByDesc('id')
            ->paginate(20);

        return view('sellers.panel.inventory.index', ['prices' => $prices]);
    }
}
