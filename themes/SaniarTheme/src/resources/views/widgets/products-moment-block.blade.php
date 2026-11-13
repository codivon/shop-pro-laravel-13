{{-- اسلایدر محصولات شگفت‌انگیز سانیار (با تایمر) --}}
@php
    $variables = get_widget($widget);
    $products = $variables['products'];
@endphp

@if(count($products))
    <div class="product-slider site-slider py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/amazing.svg') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3"><span class="main-color-one-color d-inline-block ms-1">{{ $widget->title }}</span></h5>
                </div>
                <div class="link">
                    <a href="{{ route('front.products.discount') }}" class="border-animate fromCenter pb-1 fw-bold">
                        مشاهده همه
                        <i class="bi bi-chevron-double-left main-color-one-color"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="parent">
            <div class="container-fluid">
                <div class="swiper product-slider-swiper">
                    <div class="swiper-wrapper">
                        @foreach($products as $product)
                            <div class="swiper-slide">
                                @include('front::partials.product-card', ['product' => $product, 'withTimer' => true, 'hoverBtns' => true])
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-button-next d-lg-flex d-none"></div>
                    <div class="swiper-button-prev d-lg-flex d-none"></div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </div>
    </div>
@endif
