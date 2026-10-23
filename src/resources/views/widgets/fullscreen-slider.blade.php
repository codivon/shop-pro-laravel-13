{{-- ویجت «اسلایدر تمام صفحه» سانیار — فاز B --}}
{{-- داده‌ها: fullscreen_slider (دسکتاپ) + mobile_sliders (موبایل) — دو سوایپر جدا برای پشتیبانی ریسپانسیو بدون اسلاید خالی --}}
@php
    $variables           = get_widget($widget);
    $fullscreen_sliders  = $variables['fullscreen_slider'] ?? collect();
    $mobile_sliders      = $variables['mobile_sliders'] ?? collect();
@endphp
@if($fullscreen_sliders->count() || $mobile_sliders->count())
    <div class="fullscreen-slider py-20">
        <div class="container-fluid">
            @if($fullscreen_sliders->count())
                {{-- اگر اسلایدر موبایل تعریف نشده باشد، همین اسلایدر در همه سایزها نمایش داده می‌شود --}}
                <div class="swiper fullscreen-slider-swiper {{ $mobile_sliders->count() ? 'd-none d-lg-block' : '' }}">
                    <div class="swiper-wrapper">
                        @foreach($fullscreen_sliders as $slider)
                            <div class="swiper-slide">
                                <a href="{{ $slider->link }}">
                                    <img src="{{ asset($slider->image) }}" loading="lazy" class="img-fluid w-100"
                                         alt="{{ $slider->title ?? option('info_site_title') }}">
                                    @if($slider->title || $slider->description)
                                        <div class="fullscreen-slider-caption">
                                            @if($slider->title)
                                                <h3>{{ $slider->title }}</h3>
                                            @endif
                                            @if($slider->description)
                                                <p>{{ $slider->description }}</p>
                                            @endif
                                        </div>
                                    @endif
                                </a>
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next d-lg-flex"></div>
                    <div class="swiper-button-prev d-lg-flex"></div>
                </div>
            @endif

            @if($mobile_sliders->count())
                <div class="swiper fullscreen-slider-swiper d-lg-none">
                    <div class="swiper-wrapper">
                        @foreach($mobile_sliders as $slider)
                            <div class="swiper-slide">
                                <a href="{{ $slider->link }}">
                                    <img src="{{ asset($slider->image) }}" loading="lazy" class="img-fluid w-100"
                                         alt="{{ $slider->title ?? option('info_site_title') }}">
                                </a>
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            @endif
        </div>
    </div>
@endif
