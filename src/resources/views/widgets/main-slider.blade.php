{{-- اسلایدر اصلی سانیار: اسلایدر بزرگ + اسلایدر محصولات شگفت‌انگیز --}}
@php
    $variables = get_widget($widget);
    $main_sliders   = $variables['main_sliders'];
    $mobile_sliders = $variables['mobile_sliders'];

    // ستون سانیار: محصولات ویژه با تایمر (معادل suggetMoment تمپلیت)
    $moment_products = \App\Models\Product::query()
        ->with('lowestPrice', 'category:id,title,slug')
        ->select('id', 'title', 'type', 'category_id', 'slug', 'image', 'special', 'image_alt')
        ->published()
        ->available()
        ->special()
        ->orderByStock()
        ->latest()
        ->take(6)
        ->get();
@endphp

@if(count($main_sliders) || count($moment_products))
    <div class="main-slider py-20">
        <div class="container-fluid">
            <div class="row gy-3">
                @if(count($moment_products))
                    <div class="col-lg-3 order-lg-1 order-2">
                        <div class="swiper" id="suggetMoment">
                            <div class="swiper-wrapper position-relative">
                                @foreach($moment_products as $product)
                                    <div class="swiper-slide">
                                        @include('front::partials.product-card', ['product' => $product, 'withTimer' => true])
                                    </div>
                                @endforeach
                            </div>
                            <div class="swiper-progress-bar">
                                <span class="slide_progress-bar"></span>
                            </div>
                        </div>
                    </div>
                @endif

                @if(count($main_sliders))
                    <div class="col-lg-{{ count($moment_products) ? '9' : '12' }} order-lg-2 order-1">
                        <div class="slider shadow-box">
                            <div class="swiper" id="homeSlider">
                                <div class="swiper-wrapper">
                                    @foreach($main_sliders as $slider)
                                        <div class="swiper-slide">
                                            <a href="{{ $slider->link ?: '#' }}">
                                                <img src="{{ asset($slider->image) }}" loading="lazy" class="img-fluid"
                                                     alt="{{ $slider->title ?: 'اسلایدر' }}">
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
                @endif
            </div>
        </div>
    </div>
@endif
