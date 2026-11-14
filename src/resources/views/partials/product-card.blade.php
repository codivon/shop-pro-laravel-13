{{-- کارت محصول سانیار (product-box) — قابل استفاده در اسلایدرها و گریدها --}}
{{-- پارامترها: $product، $withTimer (شمارنده ویژه)، $hoverBtns --}}
@php
    $withTimer = $withTimer ?? false;
    $hoverBtns = $hoverBtns ?? false;
@endphp
<div class="product-box">
{{-- فاز ۱۷۰ — کانتینر .product-timer همیشه رندر می‌شود (حتی خالی) تا
     ارتفاع ردیف نشان تخفیف/دکمه‌ها در همهٔ کارت‌ها یکسان بماند و کارتِ
     تخفیف‌دار از بقیه بزرگ‌تر نشود (استایل min-height در style.css) --}}
<div class="product-timer">
    @if($product->getLowestDiscount())
        <div class="timer-label">
            <span>{{ $product->discount }}٪ تخفیف</span>
        </div>
    @endif

    @if($withTimer && $product->isSpecial() && $product->special_end_date)
        <div class="timer">
            <div class='countdown' data-date="{{ $product->special_end_date->format('Y-m-d') }}" data-time="{{ $product->special_end_date->format('H:i') }}"></div>
        </div>
    @endif

    @if($hoverBtns)
        <div class="product-header-btn">
            <a href="{{ route('front.favorites.index') }}" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="افزودن به علاقه مندی ها"><i class="bi bi-heart"></i></a>
            <a href="{{ route('front.products.show', ['product' => $product]) }}" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="مشاهده"><i class="bi bi-eye"></i></a>
        </div>
    @endif
</div>

    <div class="product-image">
        {{-- فاز ۱۵۸ — تصویر و عنوان کارت به صفحهٔ محصول لینک شدند --}}
        <a href="{{ route('front.products.show', ['product' => $product]) }}" class="product-image-link d-block" aria-label="{{ $product->title }}">
            <img loading="lazy" decoding="async" src="{{ $product->image ? asset($product->image) : asset('/no-image-product.svg') }}"
                 alt="{{ $product->image_alt ?: $product->title }}" class="img-fluid">
        </a>
    </div>

    <div class="product-title">
        <a href="{{ route('front.products.show', ['product' => $product]) }}" class="product-title-link d-block text-decoration-none">
            <div class="title">
                <p class="text-overflow-1">{{ $product->title }}</p>
                <span class="text-muted text-overflow-1">{{ $product->category->title ?? '' }}</span>
            </div>
        </a>
    </div>

    <div class="product-action">
        <div class="price">
            @if($product->isPreOrder())
                <span class="badge rounded-pill mb-1 pre-order-badge">{{ option('dt_pre_order_badge_text', 'پیش‌فروش') }}</span>
            @endif
            <p class="new-price">{{ $product->getLowestPrice() }}</p>
            @if($product->getLowestDiscount())
                <p class="old-price">{{ $product->getLowestDiscount() }}</p>
            @endif
        </div>
        <div class="link">
            <a href="{{ route('front.products.show', ['product' => $product]) }}" class="btn border-0 rounded-3 main-color-one-bg">
                <i class="bi bi-basket text-white"></i>
                <span class="text-white">خرید محصول</span>
            </a>
        </div>
    </div>
</div>
