@php
    $variables = get_widget($widget);
    $products = $variables['products'] ?? collect();
@endphp
@if($products->count())
    <div class="product-slider site-slider py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3">
                        {{ $widget->option('title', 'محصولات') }}
                        @if($widget->key == 'products-moment-block')
                            <span class="main-color-one-color d-inline-block ms-1">شگفت‌انگیز</span>
                        @endif
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
        </div>

        <div class="parent">
            <div class="container-fluid">
                <div class="swiper product-slider-swiper">
                    <div class="swiper-wrapper">
                        @foreach($products as $product)
                            <div class="swiper-slide">
                                @include('front::products.partials.product-card', ['product' => $product])
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-button-next d-lg-flex d-none"></div>
                    <div class="swiper-button-prev d-lg-flex d-none"></div>
                </div>
            </div>
        </div>
    </div>
@endif
