{{-- باکس خرید سانیار — $product, $selected_price, $get_stores (nullable)
     داخل product-info رندر می‌شود؛ با تغییر واریانت ریلود می‌شود --}}
@if ($product->addableToCart() && $selected_price)
    @php
        $stores_attribute_prices = $get_stores['attribute_prices'] ?? [];
        $uniqueSellers = [];
        foreach ($stores_attribute_prices as $attribute_prices) {
            $sellerKey = $attribute_prices->seller_id ?? 'site';
            $uniqueSellers[$sellerKey] = true;
        }
        $currentSeller = $selected_price->seller_id ?? 'site';
        $otherSellersCount = 0;
        foreach (array_keys($uniqueSellers) as $seller) {
            if ($seller != $currentSeller) $otherSellersCount++;
        }
    @endphp

    <div class="row align-items-center gy-3 bg-light rounded-3 p-3" id="saniar-buy-box">
        {{-- قیمت‌ها (داینامیک) --}}
        <div class="col-lg-3 col-6 w-100-in-400">
            @if ($selected_price->hasDiscount())
                @php
                    $saniar_discount = $selected_price->discount();
                    if ((!$saniar_discount || $saniar_discount <= 0) && $selected_price->regularPrice() > 0) {
                        $saniar_discount = (int) round(100 - ($selected_price->salePrice() / $selected_price->regularPrice()) * 100);
                    }
                @endphp
                <p class="mb-0 old-price font-16 text-lg-end text-center text-decoration-line-through text-danger">
                    {{ number_format($selected_price->regularPrice()) }} تومان
                </p>
                @if ($saniar_discount > 0)
                    <p class="mb-0 text-lg-end text-center">
                        <span class="badge main-color-one-bg text-white">{{ $saniar_discount }}% تخفیف</span>
                    </p>
                @endif
            @endif
        </div>
        <div class="col-lg-3 col-6 w-100-in-400">
            <h6 class="font-16 new-price main-color-one-color mb-0 text-lg-start text-center">
                {{ number_format($selected_price->salePrice()) }}
                <span class="font-12 text-muted">تومان</span>
            </h6>
        </div>

        {{-- دکمه خرید (قرارداد cart.store) --}}
        <div class="col-lg-3 col-6 w-100-in-400">
            <button type="button"
                    class="btn w-100 border-0 main-color-three-bg js-btn-add-to-cart add-to-cart btn-add-to-cart rounded-3 py-2"
                    data-price_id="{{ $selected_price->id }}"
                    data-action="{{ route('front.cart.store', ['product' => $product]) }}"
                    data-product="{{ $product->slug }}">
                <i class="bi bi-basket text-white font-18 me-1"></i>
                <span class="text-white">خرید کالا</span>
            </button>
        </div>

        {{-- شمارنده تعداد (touchspin) --}}
        <div class="col-lg-3 col-6 w-100-in-400">
            <div class="counter d-flex justify-content-center">
                <input type="text" name="count" class="form-control js-cart-quantity counter-input text-center"
                       value="1" inputmode="numeric" aria-label="تعداد">
            </div>
        </div>

        {{-- شناسه محصول + فروشندگان دیگر (داینامیک) --}}
        <div class="col-12">
            <div class="d-flex align-items-center flex-wrap gap-3 font-12 text-muted">
                <span>
                    <i class="bi bi-upc-scan me-1"></i>شناسه محصول:
                    <strong class="text-dark">{{ $selected_price->product->product_id }}</strong>
                </span>
                @if (option('multi_vendor_system_status', 'false') == "true" && $otherSellersCount > 0)
                    <a href="#saniar-stores" class="text-decoration-none main-color-one-color fw-bold js-seller-count-row">
                        <i class="bi bi-shop me-1"></i>
                        <span class="js-seller-count count_stores">{{ $otherSellersCount }}</span> فروشنده دیگر
                    </a>
                @endif
                @if ($product->labels->count())
                    @foreach ($product->labels as $label)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $label->title }}</span>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@else
    {{-- ناموجود + اطلاع‌رسانی موجودی (داینامیک) --}}
    <div class="row gy-3 bg-light rounded-3 p-3">
        <div class="col-12 text-center">
            <p class="mb-1 fw-bold text-danger">
                <i class="bi bi-x-circle me-1"></i>ناموجود
            </p>
            <p class="text-muted font-13 mb-3">متاسفانه این کالا در حال حاضر موجود نیست. می‌توانید از محصولات مشابه این کالا دیدن نمایید.</p>
            <button type="button"
                    id="stock_notify_btn"
                    class="btn btn-secondary rounded-3 px-4 cart-not-available"
                    data-user="{{ auth('web')->check() ? auth('web')->user()->id : '' }}"
                    data-product="{{ $product->id }}">
                <i class="bi bi-bell me-1"></i>موجود شد به من اطلاع بده
            </button>
        </div>
    </div>
@endif
