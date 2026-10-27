<?php

namespace App\Exports;

use App\Models\Product;
use App\Models\SellerVariant;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * فاز ۱۰۶-۶ — خروجی اکسل محصولات فروشنده.
 *
 * الگوی SellerOrdersExport / SellerSettlementsExport (FromView) — همان مبنای
 * «مدیریت محصولات» پنل فروشنده: SellerVariant → product_id (نه products.seller_id
 * که مبنای داشبورد است) + قیمت مؤثر/موجودی کل/تعداد تنوع از جدول prices.
 */
class SellerProductsExport implements FromView, WithTitle
{
    public function __construct(public int $sellerId) {}

    public function view(): View
    {
        // همان مبنای apiIndex پنل فروشنده: تنوع‌های اختصاص‌داده‌شده به فروشنده
        $productIds = SellerVariant::where('seller_id', $this->sellerId)
            ->pluck('product_id')
            ->unique()
            ->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->with(['category:id,title', 'prices'])
            ->withSum('prices as total_stock', 'stock')
            ->latest()
            ->get();

        return view('back.exports.seller-products', [
            'products' => $products,
        ]);
    }

    public function title(): string
    {
        return 'محصولات فروشنده';
    }
}
