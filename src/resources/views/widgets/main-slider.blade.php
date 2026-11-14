{{-- اسلایدر اصلی سانیار: اسلایدر بزرگ + ستون کناری --}}
{{-- فاز ۱۶۴ — چیدمان از تنظیمات «همین نمونه» ابزارک خوانده می‌شود تا چند ابزارک
     main-slider بتوانند ظاهرهای متفاوت داشته باشند:
       show_moment  → ستون محصولات شگفت‌انگیز با تایمر (پیش‌فرض بله)
       show_banners → دو بنر کناری زیر هم (پیش‌فرض خیر)
       banner_position → جایگاه ستون کناری: راست/چپ --}}
@php
    $variables = get_widget($widget);
    $main_sliders    = $variables['main_sliders'];
    $mobile_sliders  = $variables['mobile_sliders'];

    /* ستون کناری: محصولات شگفت‌انگیز با تایمر (معادل suggetMoment تمپلیت)
       یا در حالت انتخاب بنر، دو بنر گروه index_slider_banners */
    $side_products = $variables['moment_products'] ?? collect();
    $side_banners  = $variables['index_slider_banners'] ?? collect();
    $has_side      = count($side_products) || count($side_banners);

    // جایگاه ستون کناری — پیش‌فرض راست (مطابق ترتیب تمپلیت سانیار)
    $side_position = $widget->option('banner_position', 'right') === 'left' ? 'left' : 'right';
    $side_order    = $side_position === 'left' ? 'order-lg-2' : 'order-lg-1';
    $slider_order  = $side_position === 'left' ? 'order-lg-1' : 'order-lg-2';
@endphp

@if(count($main_sliders) || $has_side)
    <div class="main-slider py-20">
        <div class="container-fluid">
            <div class="row gy-3">
                {{-- ستون کناری (شگفت‌انگیز یا بنرها) --}}
                @if($has_side)
                    <div class="col-lg-3 {{ $side_order }} order-2">
                        @if(count($side_products))
                            <div class="swiper js-moment-slider" id="suggetMoment{{ $widget->id }}">
                                <div class="swiper-wrapper position-relative">
                                    @foreach($side_products as $product)
                                        <div class="swiper-slide">
                                            @include('front::partials.product-card', ['product' => $product, 'withTimer' => true])
                                        </div>
                                    @endforeach
                                </div>
                                <div class="swiper-progress-bar">
                                    <span class="slide_progress-bar"></span>
                                </div>
                            </div>
                        @else
                            <div class="saniar-side-banners">
                                @foreach($side_banners as $banner)
                                    <a href="{{ $banner->link ?: '#' }}"
                                       aria-label="{{ $banner->title ?: 'بنر تبلیغاتی' }}">
                                        <img src="{{ asset($banner->image) }}"
                                             loading="lazy" decoding="async"
                                             alt="{{ $banner->title ?: 'بنر تبلیغاتی' }}">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- اسلایدر اصلی --}}
                @if(count($main_sliders))
                    <div class="col-lg-{{ $has_side ? '9' : '12' }} {{ $slider_order }} order-1">
                        <div class="slider shadow-box">
                            <div class="swiper js-home-slider" id="homeSlider{{ $widget->id }}">
                                <div class="swiper-wrapper">
                                    @foreach($main_sliders as $slider)
                                        <div class="swiper-slide">
                                            <a href="{{ $slider->link ?: '#' }}">
                                                {{-- فاز ۱۵۶ — اسلاید اول eager (LCP)، بقیه lazy --}}
                                                <img src="{{ asset($slider->image) }}"
                                                     @if(!$loop->first) loading="lazy" decoding="async" @endif
                                                     class="img-fluid"
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
