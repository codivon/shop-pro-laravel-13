{{-- اسلایدر وبلاگ سانیار --}}
{{-- فاز ۱۶۴ — عنوان/لینک/عنوان لینک از تنظیمات خود ابزارک --}}
@php
    $variables = get_widget($widget);
    $posts = $variables['posts'];

    $widget_title   = $widget->option('title');
    $widget_link    = $widget->option('link') ?: route('front.blog.index');
    $widget_link_title = $widget->option('link_title', 'مشاهده همه');
@endphp

@if(count($posts))
    <div class="blog-slider site-slider site-slider-sm py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img loading="lazy" decoding="async" src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3">{{ $widget_title ?: 'آخرین مطالب وبلاگ' }}</h5>
                </div>
                <div class="link">
                    <a href="{{ $widget_link }}" class="border-animate fromCenter pb-1 fw-bold">
                        {{ $widget_link_title ?: 'مشاهده همه' }}
                        <i class="bi bi-chevron-double-left main-color-one-color"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="parent">
            <div class="container-fluid">
                <div class="swiper product-slider-swiper">
                    <div class="swiper-wrapper">
                        @foreach($posts as $post)
                            <div class="swiper-slide">
                                <div class="blog-item">
                                    <a href="{{ route('front.articles.show', $post->slug) }}">
                                        <div class="image">
                                            <img loading="lazy" decoding="async" src="{{ $post->image ? asset($post->image) : asset('/no-image-product.svg') }}" alt="{{ $post->title }}" class="img-fluid">
                                        </div>
                                        <div class="title">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="title-item">
                                                    <i class="bi bi-tag me-1"></i>
                                                    <span class="font-12">{{ $post->category->title ?? '' }}</span>
                                                </div>
                                                <div class="title-item">
                                                    <i class="bi bi-alarm me-1"></i>
                                                    <span class="font-12">{{ jdate($post->created_at)->format('%d %B %Y') }}</span>
                                                </div>
                                            </div>
                                            <h4 class="font-16 text-overflow-1 h4">{{ $post->title }}</h4>
                                        </div>
                                    </a>
                                </div>
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
