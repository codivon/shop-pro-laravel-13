{{-- فاز ۱۶۶ — اسلایدر مطالب وبلاگ سانیار (مطابق blog.html تمپلیت) --}}
{{-- کارت blog-item روی نوار رنگی site-slider + ناوبری swiper --}}
{{-- مقداردهی اسلایدر در js/app.js (سلکتور سراسری .product-slider-swiper) --}}
@php
    $variables        = get_widget($widget);
    $blogSliderPosts  = $variables['blog_slider_posts'] ?? collect();
    $blogSliderLink   = $widget->option('link') ?: route('front.articles.index');
@endphp

@if(count($blogSliderPosts))
    <div class="blog-slider site-slider site-slider-sm py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3">{{ $widget->option('title', 'پربازدید ترین مطالب') }} <span class="main-color-one-color d-inline-block">وبلاگ</span></h5>
                </div>
                @if($widget->option('link_title'))
                    <div class="link">
                        <a href="{{ $blogSliderLink }}" class="border-animate fromCenter pb-1 fw-bold">
                            {{ $widget->option('link_title') }}
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
                        @foreach($blogSliderPosts as $post)
                            <div class="swiper-slide">
                                @include('front::partials.blog-card', ['post' => $post])
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>
        </div>
    </div>
@endif
