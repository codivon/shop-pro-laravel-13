<?php

namespace App\Livewire\Admin\Sellers;

use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerVariant;
use App\Services\AdminPanel\ProductPersistence;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('محصولات فروشندگان')]
class SellerProducts extends Component
{
    use WithPagination;

    /** فروشنده (در صفحه‌ی اختصاصی یک فروشنده مقدار دارد) */
    public ?Seller $seller = null;

    /** حذف فقط در صفحه‌ی سراسری فعال است (مثل قدیمی) */
    public bool $globalMode = true;

    public int $perPage = 20;

    public ?int $confirmingDelete = null;

    public function mount(?Seller $seller = null): void
    {
        auth('adminPanel')->user()?->can('sellers.products') ?: abort(403);

        $this->seller = $seller;
        $this->globalMode = $seller === null;
    }

    /* ---------------- کوئری لیست ---------------- */

    protected function baseQuery()
    {
        if ($this->globalMode) {
            // محصولات دارای حداقل یک تنوع فروشنده‌ای (مثل productsApiIndex قدیمی)
            $productIds = SellerVariant::whereNotNull('product_id')->distinct()->pluck('product_id');

            $query = Product::whereIn('id', $productIds);
        } else {
            $query = Product::where('seller_id', $this->seller->id);
        }

        return $query
            ->with(['category:id,title'])
            ->withSum('prices', 'stock')
            ->latest('id');
    }

    #[Computed]
    public function products()
    {
        return $this->baseQuery()->paginate($this->perPage);
    }

    /* ---------------- حذف ---------------- */

    public function confirmDelete(int $id): void
    {
        auth('adminPanel')->user()?->can('products.delete') ?: abort(403);
        $this->confirmingDelete = $id;
    }

    public function delete(ProductPersistence $persistence): void
    {
        auth('adminPanel')->user()?->can('products.delete') ?: abort(403);

        $product = Product::find($this->confirmingDelete);

        if ($product) {
            $persistence->destroy($product);
        }

        $this->confirmingDelete = null;

        $this->dispatch('toast', message: 'محصول با موفقیت حذف شد.', type: 'success');
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
        return view('admin-panel::livewire.admin.sellers.products')
            ->layout('admin-panel::layout');
    }
}
