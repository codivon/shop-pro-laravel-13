{{-- اسلایدر محصولات سانیار — ویجت‌ساز وبلاگ (حالت ویژه shortcode حفظ شده) --}}
@php
    $variables = get_widget($widget);
    $products  = $variables['products'];
    $widget_1  = $widget->ordering;
    $shortCode = false;
    if (isset($widget->shortcode)) {
        $shortCode = true;
    }
@endphp

@if($widget->key=="products-moment-block")
    {{-- حالت ویژه: اسلایدر شگفت‌انگیز + اسلایدر محصولات در ستون عریض --}}
    @if($widget_1==$widget->ordering && $shortCode==true)
        @php
            $css_col_lg_10 = 'col-lg-10';
        @endphp
        @include('front::widgets.products-moment-block')

        <div class="product-slider site-slider py-20">
            <div class="container-fluid">
                <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                    <div class="title d-flex align-items-center">
                        <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                        <h5 class="font-16 ms-3"><span class="main-color-one-color d-inline-block ms-1">{{ $widget->option('title') }}</span></h5>
                    </div>
                </div>
            </div>

            <div class="parent">
                <div class="container-fluid">
                    <div class="swiper product-slider-swiper">
                        <div class="swiper-wrapper">
                            @foreach($products as $product)
                                <div class="swiper-slide">
                                    @include('front::partials.product-card', ['product' => $product, 'withTimer' => false, 'hoverBtns' => true])
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
@else
    @if ($products->count())
        <div class="product-slider site-slider py-20">
            <div class="container-fluid">
                <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                    <div class="title d-flex align-items-center">
                        <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                        <h5 class="font-16 ms-3"><span class="main-color-one-color d-inline-block ms-1">{{ $widget->option('title') }}</span></h5>
                    </div>
                </div>
            </div>

            <div class="parent">
                <div class="container-fluid">
                    <div class="swiper product-slider-swiper">
                        <div class="swiper-wrapper">
                            @foreach($products as $product)
                                <div class="swiper-slide">
                                    @include('front::partials.product-card', ['product' => $product, 'withTimer' => false, 'hoverBtns' => true])
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
@endif
