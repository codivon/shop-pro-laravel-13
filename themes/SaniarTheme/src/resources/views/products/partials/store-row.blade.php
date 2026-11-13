@props([
    'sellerName',
    'sellerUrl' => null,
    'rating' => null,
    'satisfaction' => null,
    'isSiteStore' => false,
    'price',
    'product',
    'guarantees' => [],
    'isNewSeller' => false
])

<div class="table-suppliers-cell table-suppliers-cell-title">
    <div class="seller-wrapper">
        <p class="table-suppliers-seller-name">
            <span>
                @if($sellerUrl)
                    <a href="{{ $sellerUrl }}">{{ $sellerName }}</a>
                @else
                    <a>{{ $sellerName }}</a>
                @endif
            </span>
        </p>
        <div class="table-suppliers-rating">
            @if($isSiteStore)
                <div class="product-seller-second-line">
                    عملکرد:
                    <span class="u-text-bold">{{ $rating }}</span>
                    از ۵
                    <span class="u-divider"></span>
                    <span class="u-text-bold">{{ $satisfaction }}٪</span>
                    رضایت از کالا
                </div>
            @else
                @if($isNewSeller)
                    <div class="product-seller-second-line">فروشنده جدید</div>
                @else
                    <div class="product-seller-second-line">
                        عملکرد:
                        <span class="u-text-bold">{{ $rating }}</span>
                        از ۵
                        <span class="u-divider"></span>
                        <span class="u-text-bold">{{ $satisfaction }}٪</span>
                        رضایت از کالا
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<div class="table-suppliers-cell table-suppliers-cell-no-lead-time">
    <div class="seller-wrapper">
        <p>آماده ارسال</p>
    </div>
</div>

<div class="table-suppliers-cell table-suppliers-cell-guarantee">
    <div class="seller-wrapper">
        @foreach($guarantees as $guarantee)
            <span>{{ $guarantee }}</span>
        @endforeach
    </div>
</div>

<div class="table-suppliers-cell table-suppliers-cell-price">
    <div class="seller-wrapper">
        <div class="price-secondary">
            <div class="price-secondary">
                <div class="price-secondary">
                    <div class="product-seller-row-price mb-3">
                        @if($price->hasDiscount())
                            <div class="product-seller-price-real text-danger">
                                <del class="product-seller-price-prev text-danger font-size-16">
                                    {{ number_format($price->regularPrice()) }}
                                </del>
                                تومان
                                <div class="discount show-discount mr-3 t-25 r--10">
                                    <span>{{ $price->discount() }}%</span>
                                </div>
                            </div>
                        @endif
                        <div class="product-seller-price-real">
                            <div class="product-seller-price-prev font-size-20">
                                {{ number_format($price->salePrice()) }}
                            </div>
                            تومان
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="table-suppliers-cell table-suppliers-cell-action">
    <div class="seller-wrapper">
        <a class="js-btn-add-to-cart add-to-cart"
           data-price_id="{{ $price->id }}"
           data-action="{{ route('front.cart.store', ['product' => $product]) }}"
           data-product="{{ $product->slug }}">
            افزودن به سبد
        </a>
    </div>
</div>
