<?php

namespace App\Livewire\Seller\Products;

use App\Models\AttributeGroup;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۱۰۸ — مدیریت تنوع‌های قیمت محصول (پورت ProductSellerController@variant).
 * فرم کلاسیک POST به seller.products.variant.store (variant_store قدیمی تمام
 * منطق یکتایی ویژگی‌ها/رنگ/فروشنده را خودش انجام می‌دهد) با فیلدهای عین قدیمی:
 * prices[i][attributes][] / [price] / [discount] / [cart_max] / [cart_min] /
 * [stock] / [published] / [discount_expire] / [attributes_seller][]
 */
#[Title('تنوع‌های محصول')]
#[Layout('sellers.layouts.panel')]
class Variants extends Component
{
    public ?object $product = null;

    public $attributeGroups;

    /** ساختار سطرهای قیمت برای پیش‌فرض‌سازی فرم (از تنوع‌های فعلی فروشنده) */
    public array $rows = [];

    public function mount(int $product_id): void
    {
        if (seller()->status_documents != 'Accept') {
            session()->put('toast-warning', 'مدارک شما در حال برسی می باشد.');
            $this->redirect(route('seller.dashboard'), navigate: true);

            return;
        }
        if (seller()->status_work != 'ACTIVE') {
            session()->put('toast-warning', 'اظلاعات شما در حال برسی می باشد.');
            $this->redirect(route('seller.dashboard'), navigate: true);

            return;
        }

        $product = Product::find($product_id);
        if (! $product) {
            abort(404);
        }

        $this->product = $product;
        $this->attributeGroups = AttributeGroup::detectLang()->orderBy('ordering')->get();

        // پیش‌فرض: یک سطر خالی + تنوع‌های موجود فروشنده (مثل فرم قدیمی که با JS سطر می‌سازد)
        $currentSellerId = seller_info()->id;
        $currentSellerIdForAttr = seller_info()->seller_id;

        $sellerPrices = $product->prices()
            ->where('seller_id', $currentSellerId)
            ->with(['get_attributes', 'file'])
            ->get();

        foreach ($sellerPrices as $price) {
            $this->rows[] = [
                'id' => $price->id,
                'attributes' => $price->get_attributes->pluck('id')->all(),
                'price' => $price->price(),
                'discount' => $price->discount,
                'cart_max' => $price->cart_max,
                'cart_min' => $price->cart_min,
                'stock' => $price->stock,
                'published' => $price->published,
                'discount_expire' => $price->discount_expire,
            ];
        }

        if (count($this->rows) === 0) {
            $this->rows[] = [
                'id' => null,
                'attributes' => [],
                'price' => '',
                'discount' => '',
                'cart_max' => '',
                'cart_min' => '',
                'stock' => '',
                'published' => 1,
                'discount_expire' => '',
            ];
        }

        // برای پنهان‌سازی فیلد attributes_seller مثل فرم قدیمی (سرویس سمت فروشنده)
        unset($currentSellerIdForAttr);
    }

    public function render()
    {
        return view('sellers.panel.products.variant');
    }
}
