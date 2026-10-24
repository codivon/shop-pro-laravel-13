{{-- قالب لیست فروشندگان سانیار (خروجی AJAX get_stores) — attribute_prices, attribute_prices_site, products, sellers, prices, attributes --}}
@php
    $displayedStores = [];
    $siteStoreCount = 0;
@endphp

<div class="responsive-table p-0">
    <table class="table main-table rounded-0 mb-0 saniar-stores-table">
        <thead class="text-center">
            <tr>
                <th class="font-14">فروشنده</th>
                <th class="font-14">ارسال</th>
                <th class="font-14">گارانتی</th>
                <th class="font-14">قیمت</th>
                <th class="font-14"></th>
            </tr>
        </thead>
        <tbody class="text-center">
        @foreach ($attribute_prices as $attribute_price)
            @php
                $store_product = $products[$attribute_price->product_id] ?? null;
                $variant = $sellers[$attribute_price->seller_id] ?? null;
                $price = $prices[$attribute_price->price_id] ?? null;

                if (!$price || $price->stock <= 0) continue;

                $priceAttributes = \Illuminate\Support\Facades\DB::table('attribute_price')
                    ->where('product_id', $attribute_price->product_id)
                    ->where('price_id', $attribute_price->price_id)
                    ->get();

                $guarantees = [];
                foreach ($priceAttributes as $attr) {
                    $attribute = $attributes[$attr->attribute_id] ?? null;
                    if ($attribute) {
                        $guarantees[] = $attribute->name;
                    }
                }

                $isSiteStore = !$variant;
                if ($isSiteStore) $siteStoreCount++;
            @endphp

            @if($isSiteStore)
                @include('front::products.partials.store-row', [
                    'sellerName' => option('info_site_title', 'سانیار'),
                    'rating' => 5,
                    'satisfaction' => 100,
                    'isSiteStore' => true,
                    'price' => $price,
                    'product' => $store_product,
                    'guarantees' => $guarantees,
                    'isNewSeller' => false,
                ])
            @else
                @php $displayedStores[] = $attribute_price->id; @endphp
                @include('front::products.partials.store-row', [
                    'sellerName' => $variant->business_name,
                    'sellerUrl' => route('front.showSellerStore', ['seller' => $variant->seller]),
                    'rating' => $variant->operation ?? null,
                    'satisfaction' => $variant->satisfaction ?? null,
                    'isSiteStore' => false,
                    'price' => $price,
                    'product' => $store_product,
                    'guarantees' => $guarantees,
                    'isNewSeller' => empty($variant->operation),
                ])
            @endif
        @endforeach
        </tbody>
    </table>
</div>

<input name="count_stores" type="hidden" value="{{ count($displayedStores) }}">
<input name="count_stores_zero" type="hidden" value="فروشنده {{ option('info_site_title', 'سانیار') }}">
