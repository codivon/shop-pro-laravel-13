<?php

namespace App\Livewire\Seller;

use App\Models\Review;
use App\Support\MultiVendorSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * فاز ۱۰۸ — نظرات محصولات از نگاه فروشنده (پورت SellerReviewController@index).
 * فقط-خواندنی: تأیید/رد نظر کارِ جریان ادمین است. گیت: seller_reviews_access.
 */
#[Title('نظرات محصولات')]
#[Layout('sellers.layouts.panel')]
class Reviews extends Component
{
    use WithPagination;

    public array $stats = [];

    public function mount(): void
    {
        if (! MultiVendorSettings::reviewsAccessEnabled()) {
            session()->put('toast-error', 'دسترسی به نظرات محصولات از تنظیمات غیرفعال شده است.');
            $this->redirect(route('seller.dashboard'), navigate: true);

            return;
        }

        $sellerId = (int) sellerID();

        // مبنای واحد: نظراتِ محصولاتِ این فروشنده (products.seller_id)
        $base = fn () => Review::query()
            ->whereHas('product', fn ($q) => $q->where('seller_id', $sellerId));

        $accepted = (clone $base)()->where('status', 'accepted');

        $suggestYes = (clone $accepted)->where('suggest', 'yes')->count();
        $suggestTotal = (clone $accepted)->whereIn('suggest', ['yes', 'no', 'not_sure'])->count();

        $this->stats = [
            'total' => (clone $base)()->count(),
            'pending' => (clone $base)()->where('status', 'pending')->count(),
            'avg_rating' => (clone $accepted)->avg('rating'),
            'recommend_percent' => $suggestTotal > 0 ? round($suggestYes * 100 / $suggestTotal, 1) : null,
        ];
    }

    public function render()
    {
        $sellerId = (int) sellerID();

        $reviews = Review::query()
            ->whereHas('product', fn ($q) => $q->where('seller_id', $sellerId))
            ->with(['product:id,title,slug', 'user:id,first_name,last_name,username'])
            ->latest()
            ->paginate(20);

        return view('sellers.panel.reviews.index', ['reviews' => $reviews]);
    }
}
