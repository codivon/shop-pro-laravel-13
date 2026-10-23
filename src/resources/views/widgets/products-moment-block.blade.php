{{-- ویجت «پیشنهاد شگفت‌انگیز» سانیار — بازسازی بخش amazing در index.html مرجع --}}
{{-- داده‌ها: products (با lowestPrice + specifications ویژه + description) — فاز B --}}
@php
    $variables = get_widget($widget);
    $products  = $variables['products'] ?? collect();

    // تاریخ پایان کمپین (داینامیک از ویجت‌ساز پنل) — خالی = بدون تایمر
    $timer_date = null;
    if ($widget->option('timer_date')) {
        try {
            $timer_date = \Carbon\Carbon::parse($widget->option('timer_date'))->format('Y-m-d');
        } catch (\Throwable $e) {
            $timer_date = null;
        }
    }
@endphp
@if($products->count())
    <div class="amazing py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3">
                        {{ $widget->option('title', 'پیشنهاد') }}
                        <span class="main-color-one-color d-inline-block ms-1">شگفت‌انگیز</span>
                    </h5>
                </div>
                <div class="link">
                    @if($widget->option('link'))
                        <a href="{{ $widget->option('link') }}" class="border-animate fromCenter pb-1 fw-bold">
                            {{ $widget->option('link_title', 'مشاهده همه') }}
                            <i class="bi bi-chevron-double-left main-color-one-color"></i>
                        </a>
                    @endif
                </div>
            </div>

            <div class="amazing-slider">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="amazing-pro-parent">
                            <div class="swiper amazing-slider-slider">
                                <div class="swiper-wrapper">
                                    @foreach($products as $product)
                                        @php
                                            $lowest_price = $product->lowestPrice;
                                            $discount_percent = 0;
                                            if ($lowest_price && $lowest_price->hasDiscount()) {
                                                if ($lowest_price->regularPrice() > 0 && $lowest_price->salePrice() > 0) {
                                                    $discount_percent = (int) round(100 - ($lowest_price->salePrice() / $lowest_price->regularPrice()) * 100);
                                                }
                                                // fallback: درصد ذخیره‌شده روی خود قیمت (ستون discount)
                                                if ($discount_percent <= 0 && is_numeric($lowest_price->discount) && $lowest_price->discount > 0 && $lowest_price->discount <= 100) {
                                                    $discount_percent = (int) round($lowest_price->discount);
                                                }
                                            }
                                        @endphp
                                        <div class="swiper-slide">
                                            <div class="slider-item rounded-3 shadow-box bg-white">
                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        <div class="image">
                                                            <a href="{{ route('front.products.show', ['product' => $product]) }}">
                                                                <img src="{{ $product->image ? asset($product->image) : asset('no-image-product.svg') }}"
                                                                     loading="lazy" class="img-fluid"
                                                                     alt="{{ $product->image_alt ?: $product->title }}">
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-8">
                                                        @if($timer_date)
                                                            <div class="timer">
                                                                <div class="countdown" data-date="{{ $timer_date }}"></div>
                                                            </div>
                                                        @endif
                                                        <div class="desc mt-3">
                                                            <div class="title">
                                                                <div class="d-flex align-items-center flex-wrap">
                                                                    <h3 class="text-overflow-1 font-18 me-2">
                                                                        <a href="{{ route('front.products.show', ['product' => $product]) }}"
                                                                           class="text-dark text-decoration-none">{{ $product->title }}</a>
                                                                    </h3>
                                                                    @if($discount_percent > 0)
                                                                        <span class="discount danger-label mt-sm-0 mt-3">{{ $discount_percent }}% تخفیف</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @if($product->description)
                                                            <div class="short-desc mt-3">
                                                                <p class="mb-2 text-overflow-3 text-muted">
                                                                    {{ \Illuminate\Support\Str::limit(strip_tags($product->description), 170) }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                        @if($product->specifications->count())
                                                            <div class="meta">
                                                                <ul class="navbar-nav flex-lg-row justify-content-between">
                                                                    @foreach($product->specifications->take(4) as $specification)
                                                                        <li class="nav-item">
                                                                            <span class="nav-link text-overflow-1 font-14 fw-bold">
                                                                                {{ $specification->name }} {{ $specification->pivot->value }}
                                                                            </span>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        @endif
                                                        <div class="foot mt-3 d-flex justify-content-between align-items-center flex-wrap">
                                                            <div class="price d-flex align-items-center">
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

                    @if($products->count() > 1)
                        <div class="col-lg-4 d-lg-block d-none">
                            <div class="parent">
                                <div class="swiper amazing-slider-link">
                                    <div class="swiper-wrapper">
                                        @foreach($products as $product)
                                            @php
                                                $numeric_price = $product->getLowestPrice(true);
                                                $numeric_old_price = $product->getLowestDiscount(true);
                                            @endphp
                                            <div class="swiper-slide">
                                                <div class="item">
                                                    <div class="row gy-2">
                                                        <div class="col-4">
                                                            <div class="image">
                                                                <img src="{{ $product->image ? asset($product->image) : asset('no-image-product.svg') }}"
                                                                     loading="lazy" class="img-fluid"
                                                                     alt="{{ $product->image_alt ?: $product->title }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-8">
                                                            <div class="title">
                                                                <h6 class="font-14 text-overflow-2">{{ $product->title }}</h6>
                                                                @if($product->title_en)
                                                                    <p class="mb-0 text-muted font-12 mt-2 text-overflow-2">{{ $product->title_en }}</p>
                                                                @endif
                                                            </div>
                                                            <div class="price">
                                                                @if($numeric_price)
                                                                    <p class="text-end new-price mb-2 price-off fw-bold">
                                                                        <span class="main-color-one-color">{{ number_format($numeric_price) }}</span>
                                                                        <small class="font-11">تومان</small>
                                                                    </p>
                                                                @endif
                                                                @if($numeric_old_price)
                                                                    <p class="text-end old-price price-discount">
                                                                        {{ number_format($numeric_old_price) }} <small class="font-11">تومان</small>
                                                                    </p>
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
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
