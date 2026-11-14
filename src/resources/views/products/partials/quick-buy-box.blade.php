{{-- =====================================================================
    فاز ۱۵۸ — کارت «خرید سریع» کنار تب‌های صفحهٔ تک‌محصول
    (مطابق قالب مرجع سانیار — ستون چپ تب‌ها، چسبان هنگام اسکرول)
    به‌روزرسانی لحظه‌ای قیمت/تنوع/موجودی: theme-product.js (js-mini-buy-box)
    هوک‌های JS (show.js): .btn-add-to-cart.add-to-cart / #cart-quantity
    ===================================================================== --}}
@if ($product->addableToCart() && $selected_price)
    @php
        $quickBuySeller = $selected_price->seller?->seller_info?->business_name;
        $quickBuyAttrs = collect();
        try {
            $quickBuyAttrs = $selected_price->get_attributes()->pluck('name')->filter()->values();
        } catch (\Throwable $quickBuyException) {
            $quickBuyAttrs = collect();
        }
    @endphp

    <div class="js-mini-buy-box quick-buy-box">
        <div class="quick-buy-head">
            <i class="bi bi-lightning-charge-fill"></i>
            <span>خرید سریع</span>
        </div>

        <div class="quick-buy-product">
            <img src="{{ $product->image ? asset($product->image) : asset('/no-image-product.svg') }}"
                 alt="{{ $product->title }}" loading="lazy" decoding="async">
            <div class="quick-buy-product-info">
                <a href="{{ route('front.products.show', ['product' => $product]) }}" class="quick-buy-title">
                    {{ $product->title }}
                </a>
                <span class="quick-buy-variant js-mini-variant-label" title="تنوع انتخاب‌شده">
                    {{ $quickBuyAttrs->implode(' ، ') ?: 'تنوع پیش‌فرض' }}
                </span>
            </div>
        </div>

        <ul class="quick-buy-meta">
            <li>
                <span class="qbm-label"><i class="bi bi-shop"></i> فروشنده:</span>
                <span class="qbm-value fw-bold">
                    {{ $quickBuySeller ?: option('info_site_title', 'او پی شاپ') }}
                </span>
            </li>
            <li>
                <span class="qbm-label"><i class="bi bi-graph-up-arrow"></i> عملکرد:</span>
                <span class="qbm-value fw-bold text-success">عالی</span>
            </li>
            <li>
                <span class="qbm-label"><i class="bi bi-box-seam"></i> موجودی:</span>
                <span class="qbm-value js-mini-stock {{ $selected_price->stock > 0 ? 'text-success' : 'text-danger' }}">
                    {{ $selected_price->stock > 0 ? 'موجود در انبار' : 'ناموجود' }}
                </span>
            </li>
        </ul>

        <div class="quick-buy-price">
            @if ($selected_price->hasDiscount())
                <div class="quick-buy-old js-mini-old-price">
                    <del>{{ number_format($selected_price->regularPrice()) }}</del>
                    <span class="quick-buy-discount js-mini-discount">{{ $selected_price->discount() }}٪</span>
                </div>
            @else
                <div class="quick-buy-old js-mini-old-price d-none">
                    <del></del>
                    <span class="quick-buy-discount js-mini-discount d-none"></span>
                </div>
            @endif
            <div class="quick-buy-new">
                <span class="js-mini-price">{{ number_format($selected_price->salePrice()) }}</span>
                <small>تومان</small>
            </div>
        </div>

        <div class="quick-buy-action">
            {{-- فاز ۱۵۹ — کنترل تعداد از خرید سریع حذف شد (درخواست کاربر)؛
                 تعداد همیشه ۱ در نظر گرفته می‌شود (show.js) --}}
            <button type="button"
                    class="btn-add-to-cart add-to-cart btn border-0 main-color-three-bg text-white"
                    data-price_id="{{ $selected_price->id }}"
                    data-action="{{ route('front.cart.store', ['product' => $product]) }}"
                    data-product="{{ $product->slug }}">
                <i class="bi bi-basket"></i>
                افزودن به سبد
            </button>
        </div>
    </div>
@endif
