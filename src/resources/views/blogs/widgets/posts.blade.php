{{-- اسلایدر مطالب وبلاگ سانیار (ویجت‌ساز وبلاگ) — فاز ۱۶۸: کارت مدرن مشترک (blog-card) --}}
@php
    $variables = get_widget($widget);
    /* فاز ۱۶۷ — FIX: کلید خروجی helpers برای ویجت «posts» همان «posts» است
       (نه «blogs») — مقدار قبلی همیشه null بود و count(null) خطا می‌داد. */
    $posts     = $variables['posts'];
@endphp

@if(count($posts))
    <div class="blog-slider site-slider site-slider-sm py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3">{{ $widget->option('title', 'آخرین مطالب') }} <span class="main-color-one-color d-inline-block">وبلاگ</span></h5>
                </div>
                @if($widget->option('link'))
                    <div class="link">
                        <a href="{{ $widget->option('link') }}" class="border-animate fromCenter pb-1 fw-bold">
                            {{ $widget->option('link_title', 'مشاهده همه') }}
                            <i class="bi bi-chevron-double-left main-color-one-color"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="parent">
            <div class="container-fluid">
                <div class="swiper product-slider-swiper">
                    <div class="swiper-wrapper">
                        @foreach($posts as $post)
                            <div class="swiper-slide">
                                @include('front::partials.blog-card', ['post' => $post])
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
