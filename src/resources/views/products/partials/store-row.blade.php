{{-- ردیف فروشنده سانیار — sellerName, sellerUrl, rating, satisfaction, isSiteStore, price, product, guarantees, isNewSeller --}}
@php
    $row_discount = 0;
    if ($price->hasDiscount()) {
        $row_discount = $price->discount();
        if ((!$row_discount || $row_discount <= 0) && $price->regularPrice() > 0) {
            $row_discount = (int) round(100 - ($price->salePrice() / $price->regularPrice()) * 100);
        }
    }
@endphp
<tr class="saniar-store-row">
    <td class="font-14">
        <div class="d-flex align-items-start">
            <div class="flex-shrink-0">
                <i class="bi bi-shop font-20 text-muted"></i>
            </div>
            <div class="text-start ms-2">
                <h6 class="text-muted-2 font-14 mb-1">
                    @if(isset($sellerUrl) && $sellerUrl)
                        <a href="{{ $sellerUrl }}" class="text-decoration-none text-dark">{{ $sellerName }}</a>
                    @else
                        {{ $sellerName }}
                    @endif
                    @if($isSiteStore)
                        <span class="success-label py-1 rounded-pill ms-1 font-12">منتخب</span>
                    @elseif($isNewSeller)
                        <span class="warning-label py-1 rounded-pill ms-1 font-12">فروشنده جدید</span>
                    @endif
                </h6>
                <div class="d-flex align-items-center mt-1 flex-wrap gap-2">
                    <p class="font-12 mb-0">
                        @if($rating !== null && $rating !== '')
                            <span class="text-success ms-1">{{ $satisfaction ?? 100 }}%</span>
                            <span class="text-muted">رضایت از کالا</span>
                        @else
                            <span class="text-muted">عملکرد: </span><span class="text-success">در حال ارزیابی</span>
                        @endif
                    </p>
                    @if($rating !== null && $rating !== '')
                        <p class="ps-1 ms-1 border-start font-12 mb-0">
                            <span class="text-muted">عملکرد</span>
                            <span class="text-success">{{ $rating >= 4 ? 'عالی' : ($rating >= 3 ? 'خوب' : 'متوسط') }}</span>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </td>
    <td class="font-14">
        <i class="bi bi-box-seam me-1 text-muted"></i>
        <p class="d-inline mb-0 font-14">آماده ارسال</p>
    </td>
    <td class="font-14">
        <i class="bi bi-shield-check me-1 text-muted"></i>
        @if(count($guarantees))
            <p class="d-inline font-14 mb-0">{{ implode('، ', $guarantees) }}</p>
        @else
            <p class="d-inline font-14 mb-0 text-muted">گارانتی اصالت و سلامت فیزیکی کالا</p>
        @endif
    </td>
    <td class="font-14">
        @if ($price->hasDiscount())
            <del class="text-danger font-12 d-block">{{ number_format($price->regularPrice()) }}</del>
        @endif
        <p class="fw-bold text-muted-2 mb-0 d-inline font-16">{{ number_format($price->salePrice()) }}</p>
        <span class="text-muted font-12">تومان</span>
        @if($row_discount > 0)
            <span class="badge main-color-one-bg text-white font-11 ms-1">{{ $row_discount }}%</span>
        @endif
    </td>
    <td class="font-14">
        <a href="javascript:void(0)"
           class="btn py-2 px-3 border-0 main-color-one-bg waves-effect waves-light rounded-3 js-btn-add-to-cart add-to-cart"
           data-price_id="{{ $price->id }}"
           data-action="{{ route('front.cart.store', ['product' => $product]) }}"
           data-product="{{ $product->slug }}">
            <i class="bi bi-basket text-white font-14 me-1"></i>
            <span class="text-white font-14">افزودن به سبد</span>
        </a>
    </td>
</tr>
