{{-- =====================================================================
    فاز ۱۵۶ — باکس فروشنده / عملکرد / شناسه / ارسال
    موضع: ستون چپ صفحهٔ تک‌محصول (زیر «گارانتی اصالت»)، چیدمان عمودی
    هوک‌های JS (show.js): .sku-label (کپی شناسه) / #seller-count-row + .count_stores
    ===================================================================== --}}
@if(option('multi_vendor_system_status','false')=="true" && $product->addableToCart() && $selected_price)
    @php
        $stores_attribute_prices = [];
        if (is_array($get_stores) && !empty($get_stores['attribute_prices'])) {
            $stores_attribute_prices = $get_stores['attribute_prices'];
        }

        $uniqueSellers = [];
        foreach ($stores_attribute_prices as $attribute_prices) {
            $sellerKey = $attribute_prices->seller_id ?? 'site';
            $uniqueSellers[$sellerKey] = true;
        }
        $totalUniqueSellers = count($uniqueSellers);

        $currentSeller = $selected_price->seller_id ?? 'site';

        $otherSellersCount = 0;
        foreach ($uniqueSellers as $seller => $value) {
            if ($seller != $currentSeller) {
                $otherSellersCount++;
            }
        }
    @endphp

    <div class="product-seller-aside mt-3 w-100">
        <ul class="product-seller-aside-list">

            {{-- فروشنده --}}
            <li class="product-seller-aside-item">
                <span class="aside-label"><i class="bi bi-shop"></i> فروشنده:</span>
                @if($selected_price->seller)
                    <a class="aside-value table-name lts-05 fw-bold d-inline-flex align-items-center gap-1"
                       href="{{ route('front.showSellerStore', $selected_price->seller) }}">
                        @if($selected_price->seller->seller_info->logo)
                            <img src="{{ asset($selected_price->seller->seller_info->logo) }}"
                                 class="rounded-circle object-fit-cover" width="22" height="22" alt="seller">
                        @else
                            <i class="bi bi-patch-check-fill main-color-one-color"></i>
                        @endif
                        {{ $selected_price->seller->seller_info->business_name }}
                        <i class="bi bi-patch-check-fill main-color-one-color"></i>
                    </a>
                @else
                    <a class="aside-value table-name lts-05 fw-bold d-inline-flex align-items-center gap-1"
                       href="{{ route('front.index') }}">
                        <i class="bi bi-patch-check-fill main-color-one-color"></i>
                        {{ option('info_site_title', 'او پی شاپ') }}
                    </a>
                @endif
            </li>

            {{-- عملکرد فروشنده --}}
            <li class="product-seller-aside-item">
                <span class="aside-label"><i class="bi bi-graph-up-arrow"></i> عملکرد:</span>
                <span class="aside-value performance-label fw-bold text-success">عالی</span>
            </li>

            {{-- شناسه کالا (کلیک = کپی) --}}
            <li class="product-seller-aside-item">
                <span class="aside-label"><i class="bi bi-qr-code"></i> شناسه:</span>
                <span class="aside-value sku-label clickable text-muted" data-bs-toggle="tooltip"
                      data-bs-placement="top" data-bs-title="کپی شناسه محصول">
                    <span dir="ltr">{{ $selected_price->product->product_id ?: $product->id }}</span>
                </span>
            </li>

            {{-- نحوه ارسال --}}
            <li class="product-seller-aside-item">
                <span class="aside-label"><i class="bi bi-truck main-color-one-color"></i> ارسال:</span>
                <span class="aside-value font-13">{{ $selected_price->seller ? 'توسط فروشنده' : 'از انبار فروشگاه' }}</span>
            </li>

            {{-- سایر فروشندگان --}}
            @if ($totalUniqueSellers > 1)
                <li class="product-seller-aside-item">
                    <a href="#stores-tag" id="seller-count-row"
                       class="js-seller-count-row aside-value main-color-one-color text-decoration-none fw-bold">
                        <span class="js-seller-count u-text-bold count_stores"></span>
                        <span class="u-text-bold seller-count-text">{{ $otherSellersCount }} فروشنده دیگر</span>
                    </a>
                </li>
            @endif
        </ul>

        @if ($product->labels->count())
            <div class="product-seller-aside-labels d-flex flex-wrap gap-1 mt-2">
                @foreach ($product->labels as $label)
                    <span class="badge rounded-pill main-color-one-bg">{{ $label->title }}</span>
                @endforeach
            </div>
        @endif
    </div>
@endif
