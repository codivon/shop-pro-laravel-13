@php
    $variables = get_widget($widget);
    $posts = $variables['posts'] ?? collect();
@endphp
@if(count($posts))
    <div class="blog-slider site-slider site-slider-sm py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3">آخرین مطالب <span class="main-color-one-color d-inline-block">وبلاگ</span></h5>
                </div>
                <div class="link">
                    <a href="{{ route('front.articles.index') }}" class="border-animate fromCenter pb-1 fw-bold">
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
                        @foreach($posts as $post)
                            <div class="swiper-slide">
                                <div class="blog-item">
                                    <a href="{{ route('front.articles.show', ['slug' => $post->slug]) }}">
                                        <div class="image">
                                            <img src="{{ $post->image ? asset($post->image) : asset('no-image-product.svg') }}"
                                                 alt="{{ $post->title }}" class="img-fluid" loading="lazy">
                                        </div>
                                        <div class="title">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="title-item">
                                                    <i class="bi bi-tag me-1"></i>
                                                    <span class="font-12">{{ $post->category?->title ?? 'وبلاگ' }}</span>
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
                </div>
            </div>
        </div>
    </div>
@endif
