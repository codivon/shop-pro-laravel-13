{{-- فاز ۱۶۳ — اسلایدر شگفت انگیز سانیار (مطابق index.html تمپلیت) --}}
{{-- اسلاید بزرگ جزئیات محصول + فهرست کارت‌های کوچک کنارش با تایمر --}}
{{-- مارکاپ اسلایدرها در js/app.js (amazing-slider-slider / amazing-slider-link) --}}
{{-- استایلها در style.css تمپلیت (بخش .amazing) + دارک مود در theme-dark.css --}}
@php
    $variables = get_widget($widget);
    $amazing_products = $variables['amazing_products'] ?? collect();
@endphp

@if(count($amazing_products))
    <div class="amazing py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img loading="lazy" decoding="async" src="{{ theme_asset('image/amazing.svg') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3">اسلایدر<span class="main-color-one-color d-inline-block ms-1">{{ $widget->title ?: 'شگفت انگیز' }}</span></h5>
                </div>
                <div class="link">
                    <a href="{{ route('front.products.specials') }}" class="border-animate fromCenter pb-1 fw-bold">
                        مشاهده همه
                        <i class="bi bi-chevron-double-left main-color-one-color"></i>
                    </a>
                </div>
            </div>

            <div class="amazing-slider">
                <div class="row">
                    {{-- اسلاید بزرگ جزئیات محصول --}}
                    <div class="col-lg-8">
                        <div class="amazing-pro-parent">
                            <div class="swiper amazing-slider-slider">
                                <div class="swiper-wrapper">
                                    @foreach($amazing_products as $product)
                                        <div class="swiper-slide">
                                            <div class="slider-item rounded-3 shadow-box bg-white">
                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        <div class="image">
                                                            <a href="{{ route('front.products.show', ['product' => $product]) }}"
                                                               aria-label="{{ $product->title }}">
                                                                <img src="{{ $product->image ? asset($product->image) : asset('/no-image-product.svg') }}"
                                                                     @if(!$loop->first) loading="lazy" decoding="async" @endif
                                                                     class="img-fluid"
                                                                     alt="{{ $product->image_alt ?: $product->title }}">
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-8">
                                                        <div class="timer">
                                                            @if($product->isSpecial() && $product->special_end_date)
                                                                <div class='countdown' data-date="{{ $product->special_end_date->format('Y-m-d') }}" data-time="{{ $product->special_end_date->format('H:i') }}"></div>
                                                            @endif
                                                        </div>
                                                        <div class="desc mt-3">
                                                            <div class="title">
                                                                <div class="d-flex align-items-center flex-wrap">
                                                                    <h3 class="text-overflow-1 font-18 me-2">
                                                                        <a href="{{ route('front.products.show', ['product' => $product]) }}" class="text-decoration-none">
                                                                            {{ $product->title }}
                                                                        </a>
                                                                    </h3>
                                                                    @if($product->discount)
                                                                        <span class="discount danger-label mt-sm-0 mt-3">{{ $product->discount }}٪ تخفیف</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="short-desc mt-3">
                                                                <p class="mb-2 text-overflow-3 text-muted">
                                                                    {{ \Illuminate\Support\Str::limit(strip_tags((string) $product->short_description), 260) }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div class="meta">
                                                            <ul class="navbar-nav flex-lg-row justify-content-between">
                                                                @forelse($product->labels->take(4) as $label)
                                                                    <li class="nav-item">
                                                                        <span class="nav-link text-overflow-1 font-14 fw-bold">{{ $label->title }}</span>
                                                                    </li>
                                                                @empty
                                                                    <li class="nav-item">
                                                                        <span class="nav-link text-overflow-1 font-14 fw-bold">{{ $product->category->title ?? '' }}</span>
                                                                    </li>
                                                                @endforelse
                                                            </ul>
                                                        </div>
                                                        <div class="foot mt-3 d-flex justify-content-between align-items-center flex-wrap">
                                                            <div class="price d-flex align-items-center">
                                                                @if($product->isPreOrder())
                                                                    <span class="badge rounded-pill me-2 pre-order-badge">{{ option('dt_pre_order_badge_text', 'پیش‌فروش') }}</span>
                                                                @endif
                                                                <h6 class="font-25 main-color-one-color">{{ $product->getLowestPrice() }}</h6>
                                                                @if($product->getLowestDiscount())
                                                                    <h6 class="font-13 old-price ms-2">{{ $product->getLowestDiscount() }}</h6>
                                                                @endif
                                                            </div>
                                                            <div class="link">
                                                                <a href="{{ route('front.products.show', ['product' => $product]) }}"
                                                                   class="btn border-0 rounded-3 main-color-one-bg">
                                                                    <i class="bi bi-basket text-white"></i>
                                                                    <span class="text-white">خرید محصول</span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="swiper-pagination d-lg-none d-block"></div>
                            </div>
                        </div>
                    </div>

                    {{-- فهرست کارت‌های کوچک کنار اسلایدر (thumbs) --}}
                    <div class="col-lg-4 d-lg-block d-none">
                        <div class="parent">
                            <div class="swiper amazing-slider-link">
                                <div class="swiper-wrapper">
                                    @foreach($amazing_products as $product)
                                        <div class="swiper-slide">
                                            <div class="item">
                                                <div class="row gy-2">
                                                    <div class="col-4">
                                                        <div class="image">
                                                            <img src="{{ $product->image ? asset($product->image) : asset('/no-image-product.svg') }}"
                                                                 loading="lazy" alt="{{ $product->image_alt ?: $product->title }}" class="img-fluid">
                                                        </div>
                                                    </div>
                                                    <div class="col-8">
                                                        <div class="title">
                                                            <h6 class="font-14 text-overflow-2">{{ $product->title }}</h6>
                                                            <p class="mb-0 text-muted font-12 mt-2 text-overflow-2">{{ $product->title_en }}</p>
                                                        </div>
                                                        <div class="price">
                                                            <p class="text-end new-price mb-2 price-off fw-bold">
                                                                <span class="main-color-one-color">{{ $product->getLowestPrice() }}</span>
                                                            </p>
                                                            @if($product->getLowestDiscount())
                                                                <p class="text-end old-price price-discount">{{ $product->getLowestDiscount() }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
