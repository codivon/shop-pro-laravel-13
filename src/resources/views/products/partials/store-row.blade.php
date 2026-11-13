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

{{-- ردیف فروشنده — مارکاپ سانیار multi-seller (td های جدول) --}}

<td>
    <div class="d-flex align-items-start">
        <div>
            <i class="bi bi-shop"></i>
        </div>
        <div class="text-start ms-2">
            <h6 class="text-muted-2 font-14">
                @if($sellerUrl)
                    <a href="{{ $sellerUrl }}" class="text-decoration-none">{{ $sellerName }}</a>
                @else
                    <a class="text-decoration-none">{{ $sellerName }}</a>
                @endif
                @if($isSiteStore)
                    <span class="success-label py-1 rounded-pill ms-1">منتخب</span>
                @elseif($isNewSeller)
                    <span class="danger-label py-1 rounded-pill ms-1">فروشنده جدید</span>
                @endif
            </h6>
            <div class="d-flex align-items-center mt-2">
                <p class="font-12 mb-0">
                    <span class="text-success ms-1">{{ $satisfaction }}٪</span>
                    <span class="text-muted">رضایت از کالا</span>
                </p>
                <p class="ps-1 ms-1 border-start font-12 mb-0">
                    <span class="text-muted">عملکرد</span>
                    <span class="text-success">{{ $rating }}</span>
                </p>
            </div>
        </div>
    </div>
</td>

<td>
    <i class="bi bi-box-seam me-1"></i>
    <p class="d-inline mb-0 font-14">آماده ارسال</p>
</td>

<td>
    @if(count($guarantees))
        <i class="bi bi-shield-check me-1"></i>
        <p class="d-inline font-14 mb-0">{{ implode('، ', $guarantees) }}</p>
    @else
        <span class="text-muted font-14">-</span>
    @endif
</td>

<td>
    @if($price->hasDiscount())
        <del class="text-danger font-12 d-block">{{ number_format($price->regularPrice()) }}</del>
    @endif
    <p class="fw-bold text-muted-2 mb-0 d-inline font-16">{{ number_format($price->salePrice()) }}</p>
    <span class="text-muted font-12">تومان</span>
</td>

<td class="font-14">
    <a class="js-btn-add-to-cart add-to-cart btn py-2 border-0 main-color-one-bg waves-effect waves-light"
       data-price_id="{{ $price->id }}"
       data-action="{{ route('front.cart.store', ['product' => $product]) }}"
       data-product="{{ $product->slug }}">
        <i class="bi bi-basket text-white font-16 me-1"></i> افزودن به سبد خرید
    </a>
</td>
