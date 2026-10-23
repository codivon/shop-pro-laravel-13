{{-- کارت محصول سانیار — $product (Product) --}}
@php
    $lowest_price = $product->lowestPrice;
    $discount_percent = 0;
    if ($lowest_price && $lowest_price->hasDiscount() && $lowest_price->regularPrice() > 0) {
        $discount_percent = (int) round(100 - ($lowest_price->salePrice() / $lowest_price->regularPrice()) * 100);
    }
@endphp
<div class="product-box">
    @if($discount_percent > 0)
        <div class="product-timer">
            <div class="timer-label">
                <span>{{ $discount_percent }}% تخفیف</span>
            </div>
        </div>
    @endif
    <div class="product-image">
        <a href="{{ route('front.products.show', ['product' => $product]) }}">
            <img src="{{ $product->image ? asset($product->image) : asset('no-image-product.svg') }}"
                 loading="lazy"
                 alt="{{ $product->title }}"
                 class="img-fluid">
        </a>
    </div>
    <div class="product-title">
        <div class="title">
            <a href="{{ route('front.products.show', ['product' => $product]) }}" class="d-block text-decoration-none text-dark">
                <p class="text-overflow-1">{{ $product->title }}</p>
                @if($product->title_en)
                    <span class="text-muted text-overflow-1">{{ $product->title_en }}</span>
                @endif
            </a>
        </div>
        <div class="rating">
            <div class="number"><span class="text-muted font-12">({{ $product->reviews_count ?? 0 }}+)</span></div>
            <div class="icon"><i class="bi bi-star-fill"></i></div>
        </div>
    </div>
    <div class="product-action">
        <div class="price">
            @if($product->getLowestDiscount())
                <p class="old-price">{{ $product->getLowestDiscount() }}</p>
            @endif
            <p class="new-price">{{ $product->getLowestPrice() }}</p>
        </div>
        <div class="link">
            <a href="{{ route('front.products.show', ['product' => $product]) }}" class="btn border-0 rounded-3 main-color-one-bg">
                <i class="bi bi-basket text-white"></i>
                <span class="text-white">خرید محصول</span>
            </a>
        </div>
    </div>
</div>
