<?php

namespace App\Http\Resources\Datatable\Product;

use App\Models\SellerVariant;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;

class Product extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {

        if (seller_info()) {
            // فاز ۱۱۸ — یکدست‌سازی: seller_variants/prices روی sellers.id هستند نه sellers_info.id
            $seller_info_id = seller_info()->seller_id;
        } else {
            $seller_info_id = null;
        }


        $seller = false;
        $SellerVariant = SellerVariant::where(['product_id' => $this->id, 'seller_id' => $seller_info_id])->first();
        if ($SellerVariant) {
            $seller = true;
        }

        $variant_count = 0;
        $seller_product_id = SellerVariant::where('product_id', $this->id)->get()->pluck('product_id')->toArray();
        if (count($seller_product_id)) {
            $variant_count = count($seller_product_id);
        }

        $variant_count_seller = 0;
        $variant_attribute = $this->prices()->withTrashed()->where('seller_id', $seller_info_id)->get();
        if (count($variant_attribute)) {
            $variant_count_seller = count($variant_attribute);
        }

        if (@$this->seller_id and @$this->seller_id == @seller_info()->seller_id) {
            $editRoute = route('seller.products.edit', ['product' => $this]);
        } else {
            $editRoute = "";
        }


        $seoAuditActive = module_is_active('SeoAudit');
        $detailsRoute = $seoAuditActive ? route('admin.seo-audit.products.details', ['product' => $this]) : '';


        return [
            'id' => $this->id,
            'image' => $this->image ? asset($this->image) : asset('/empty.svg'),
            'title' => $this->title,
            'created_at' => jdate($this->created_at)->format('%d %B %Y'),
            'addableToCart' => $this->addableToCart(),
            'published' => $this->isPublished(),
            'category' => $this->category,
            'brand' => $this->brand,
            'stock_count' => $this->prices()->sum('stock'),
            'price' => number_format(@$this->lowestPrice->price),
            'price_discount' => number_format(@$this->lowestPrice->discount_price),
            'seller_id' => $this->seller_id,
            'seller' => $seller,
            'status' => $this->status,
            'variant_count' => $variant_count,
            'variant_count_seller' => $variant_count_seller,
            'variant' => route('seller.products.variant', ['product_id' => $this->id]),


            'links' => [
                'show' => Gate::allows('products.details', $this) ? $detailsRoute : '',
                'edit' => Gate::allows('products.update', $this) ? route('admin.products.edit', ['product' => $this]) : '#',
                'edit_seller' => $editRoute,
                'destroy' => Gate::allows('products.delete', $this) ? route('admin.products.destroy', ['product' => $this]) : '#',
                'copy' => Gate::allows('products.create', $this) ? route('admin.products.create', ['product' => $this]) : '#',
                'front' => Route::has('front.products.show') ? route('front.products.show', ['product' => $this]) : '#',
            ]
        ];
    }
}
