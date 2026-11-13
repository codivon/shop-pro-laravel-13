@php
    $displayedStores = [];
    $siteStoreCount = 0;
@endphp

@foreach($attribute_prices as $attribute_price)
    @php
        $product = $products[$attribute_price->product_id] ?? null;
        $variant = $sellers[$attribute_price->seller_id] ?? null;
        $price = $prices[$attribute_price->price_id] ?? null;

        if (!$price || $price->stock <= 0) continue;

        // Get attributes for this price/product combination
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
        <div class="table-suppliers-row table-suppliers-row-active">
            @include('front::products.partials.store-row', [
                'sellerName' => option('info_site_title', 'او پی شاپ'),
                'rating' => 5,
                'satisfaction' => 100,
                'isSiteStore' => true,
                'price' => $price,
                'product' => $product,
                'guarantees' => $guarantees,
                'isNewSeller' => false
            ])
        </div>
    @else
        @php $displayedStores[] = $attribute_price->id; @endphp
        <div class="table-suppliers-row {{ $loop->first ? 'table-suppliers-row-active' : '' }} {{ $loop->index > 2 ? 'in-filter in-list' : '' }}">
            @include('front::products.partials.store-row', [
                'sellerName' => $variant->business_name,
                'sellerUrl' => route('front.showSellerStore', ['seller' => $variant->seller]),
                'rating' => $variant->operation ?? null,
                'satisfaction' => $variant->satisfaction ?? null,
                'isSiteStore' => false,
                'price' => $price,
                'product' => $product,
                'guarantees' => $guarantees,
                'isNewSeller' => empty($variant->operation)
            ])
        </div>
    @endif
@endforeach

<input name="count_stores" type="hidden" value="{{ count($displayedStores) }}">
<input name="count_stores_zero" type="hidden" value="فروشنده {{ option('info_site_title', 'او پی شاپ') }}">
