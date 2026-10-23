@php
    $variables = get_widget($widget);
    $main_sliders = $variables['main_sliders'] ?? collect();
    $moment_products = $variables['moment_products'] ?? collect();
@endphp
<div class="main-slider py-20">
    <div class="container-fluid">
        <div class="row gy-3">

            @if($moment_products->count())
                <div class="col-lg-3 order-lg-1 order-2">
                    <div class="swiper" id="suggetMoment">
                        <div class="swiper-wrapper position-relative">
                            @foreach($moment_products as $product)
                                <div class="swiper-slide">
                                    @include('front::products.partials.product-card', ['product' => $product])
                                </div>
                            @endforeach
                        </div>
                        <div class="swiper-progress-bar">
                            <span class="slide_progress-bar"></span>
                        </div>
                    </div>
                </div>
            @endif

            <div class="{{ $moment_products->count() ? 'col-lg-9 order-lg-2 order-1' : 'col-lg-12' }}">
                <div class="slider shadow-box">
                    <div class="swiper" id="homeSlider">
                        <div class="swiper-wrapper">
                            @foreach($main_sliders as $slider)
                                <div class="swiper-slide">
                                    <a href="{{ $slider->link }}">
                                        <img src="{{ asset($slider->image) }}" loading="lazy" class="img-fluid" alt="{{ $slider->title ?? option('info_site_title') }}">
                                    </a>
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
    </div>
</div>
