<?php

namespace App\Livewire\Admin\Sellers;

use App\Models\Seller;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Morilog\Jalali\Jalalian;

#[Title('بازدیدهای فروشنده')]
class SellerViews extends Component
{
    use WithPagination;

    public Seller $seller;

    public int $perPage = 20;

    public function mount(Seller $seller): void
    {
        auth('adminPanel')->user()?->can('sellers.view') ?: abort(403);

        $this->seller = $seller;
    }

    #[Computed]
    public function views()
    {
        return $this->seller->views()->latest()->paginate($this->perPage);
    }

    public static function jdate($date, $format = 'Y/m/d H:i'): string
    {
        if (! $date) {
            return '—';
        }

        return Jalalian::fromDateTime($date)->format($format);
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.sellers.views')
            ->layout('admin-panel::layout');
    }
}
