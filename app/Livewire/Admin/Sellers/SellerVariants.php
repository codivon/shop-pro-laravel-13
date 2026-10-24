<?php

namespace App\Livewire\Admin\Sellers;

use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerVariant;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('تنوع‌های فروشنده')]
class SellerVariants extends Component
{
    use WithPagination;

    public Seller $seller;

    public int $perPage = 20;

    public function mount(Seller $seller): void
    {
        auth('adminPanel')->user()?->can('sellers.products') ?: abort(403);

        $this->seller = $seller;
    }

    protected function baseQuery()
    {
        return SellerVariant::where('seller_id', $this->seller->id)->latest('id');
    }

    #[Computed]
    public function variants()
    {
        return $this->baseQuery()->paginate($this->perPage);
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
        return view('admin-panel::livewire.admin.sellers.variants')
            ->layout('admin-panel::layout');
    }
}
