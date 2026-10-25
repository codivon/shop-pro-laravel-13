<?php

namespace Themes\WeblakShop\src\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Price;
use App\Models\PriceAlert;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * فاز ۷۸ — اشتراک هشدار کاهش قیمت (کاربر یا مهمان با موبایل).
 */
class PriceAlertController extends Controller
{
    public function store(Request $request)
    {
        $this->validate($request, [
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->where('status', 'Accept'),
            ],
        ]);

        $product = \App\Models\Product::query()
            ->where('id', $request->product_id)
            ->where('status', 'Accept')
            ->firstOrFail();

        if (auth()->check()) {
            $user   = auth()->user();
            $mobile = $user->mobile ?: $user->username;
            $name   = $user->fullname ?? $user->username;
            $userId = $user->id;
        } else {
            $this->validate($request, [
                'mobile' => 'required|string|regex:/(09)[0-9]{9}/|digits:11',
                'name'   => 'nullable|string|max:100',
            ]);

            $mobile = $request->mobile;
            $name   = $request->name;
            $userId = null;
        }

        PriceAlert::updateOrCreate(
            [
                'product_id' => $product->id,
                'mobile'     => $mobile,
            ],
            [
                'user_id'      => $userId,
                'name'         => $name,
                'target_price' => $this->currentBuyablePrice($product),
                'active'       => true,
                'notified_at'  => null,
            ]
        );

        return response('success');
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'product_id' => 'required|integer',
        ]);

        $mobile = auth()->check()
            ? (auth()->user()->mobile ?: auth()->user()->username)
            : $request->mobile;

        if (! $mobile) {
            return response('success');
        }

        PriceAlert::query()
            ->where('product_id', $request->product_id)
            ->where('mobile', $mobile)
            ->delete();

        return response('success');
    }

    /**
     * کمترین قیمت مؤثرِ قابل‌خریدِ فعلی محصول — هدف پیش‌فرضِ اشتراک.
     * با افت قیمت زیر همین عدد، کاربر پیامک می‌گیرد.
     */
    private function currentBuyablePrice($product)
    {
        return $product->prices()
            ->where('stock', '>', 0)
            ->where('published', 1)
            ->get()
            ->map(fn (Price $price) => $price->salePrice())
            ->filter(fn ($price) => $price > 0)
            ->min() ?: null;
    }
}
