{{-- فاز ۱۶۳ — اسلایدر و دو بنر کناری (یک اسلایدر + دوتا بنر زیر هم کنارش) --}}
{{-- اسلایدر از گروه main_sliders و بنرها از گروه index_slider_banners خوانده می‌شوند --}}
{{-- فاز ۱۶۴ — جایگاه بنرها (راست/چپ) و تعدادشان از تنظیمات «همین نمونه» ابزارک --}}
@php
    $variables = get_widget($widget);
    $main_sliders         = $variables['main_sliders'] ?? collect();
    $index_slider_banners = $variables['index_slider_banners'] ?? collect();

    // جایگاه بنرها — پیش‌فرض راست (مطابق ترتیب تمپلیت سانیار)
    $side_position = $widget->option('banner_position', 'right') === 'left' ? 'left' : 'right';
    $side_order    = $side_position === 'left' ? 'order-lg-2' : 'order-lg-1';
    $slider_order  = $side_position === 'left' ? 'order-lg-1' : 'order-lg-2';
@endphp

@if(count($main_sliders) || count($index_slider_banners))
    <div class="main-slider py-20">
        <div class="container-fluid">
            <div class="row gy-3">
                {{-- بنرها زیر هم --}}
                @if(count($index_slider_banners))
                    <div class="col-lg-3 {{ $side_order }} order-2">
                        <div class="saniar-side-banners">
                            @foreach($index_slider_banners as $banner)
                                <a href="{{ $banner->link ?: '#' }}"
                                   aria-label="{{ $banner->title ?: 'بنر تبلیغاتی' }}">
                                    <img src="{{ asset($banner->image) }}"
                                         loading="lazy" decoding="async"
                                         alt="{{ $banner->title ?: 'بنر تبلیغاتی' }}">
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- اسلایدر اصلی --}}
                @if(count($main_sliders))
                    <div class="col-lg-{{ count($index_slider_banners) ? '9' : '12' }} {{ $slider_order }} order-1">
                        <div class="slider shadow-box">
                            <div class="swiper saniar-side-slider">
                                <div class="swiper-wrapper">
                                    @foreach($main_sliders as $slider)
                                        <div class="swiper-slide">
                                            <a href="{{ $slider->link ?: '#' }}" aria-label="{{ $slider->title ?: 'اسلایدر' }}">
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
