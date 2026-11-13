{{-- سایدبار مقاله — سانیار side-blog-detail (آخرین مقالات / هشتگ‌های داغ) --}}

{{-- پیشنهادهایی برای شما — اسلایدر سانیار --}}
@if(!empty($suggestions) && count($suggestions))
    <div class="blog-slider site-slider site-slider-sm py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3 mb-0">پیشنهادهایی <span class="main-color-one-color d-inline-block">برای شما</span></h5>
                </div>
                <div class="link">
                    <a class="border-animate fromCenter pb-1 fw-bold" href="{{ route('front.articles.index',['sort=editor_pick']) }}">
                        بیشتر
                        <i class="bi bi-chevron-double-left main-color-one-color"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="parent">
            <div class="container-fluid">
                <div class="swiper product-slider-swiper">
                    <div class="swiper-wrapper">
                        @foreach($suggestions as $suggestion)
                            <div class="swiper-slide">
                                <div class="blog-item position-relative">
                                    <a href="{{ route('front.articles.show', $suggestion->slug) }}">
                                        <div class="image">
                                            <img src="{{ $suggestion->image ? asset($suggestion->image) : theme_asset('image/blog-1.jpg') }}"
                                                 alt="{{ $suggestion->title }}" class="img-fluid" loading="lazy">
                                        </div>
                                        <div class="title">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="title-item">
                                                    <i class="bi bi-tag me-1"></i>
                                                    <span class="font-12">{{ $suggestion->categories()->first()->title ?? '' }}</span>
                                                </div>
                                                <div class="title-item">
                                                    <i class="bi bi-alarm me-1"></i>
                                                    <span class="font-12">{{ jdate($suggestion->created_at)->format('d F Y') }}</span>
                                                </div>
                                            </div>
                                            <h4 class="font-16 text-overflow-1 h4">
                                                @if($suggestion->post_type=="video")
                                                    <i class="bi bi-play-circle-fill main-color-one-color me-1"></i>
                                                @endif
                                                {{ $suggestion->title }}
                                            </h4>
                                            <div class="d-flex align-items-center mt-2">
                                                <img class="rounded-circle me-2"
                                                     src="{{ $suggestion->admin?->imageUrl ?? theme_asset('image/logo.png') }}"
                                                     alt="{{ $suggestion->admin?->full_name ?? 'ناشناس' }} " width="24" height="24" loading="lazy">
                                                <span class="text-muted font-12">{{ $suggestion->admin?->full_name ?? 'ناشناس' }}</span>
                                            </div>
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

{{-- جدیدترین مقالات — mini-blog-item سانیار --}}
<div class="side-blog-detail position-sticky top-0">
    <div class="latest-posts">
        <div class="content-box">
            <div class="container-fluid">
                <div class="title-panel mb-2">
                    <h6 class="title"><i class="bi bi-clock-history main-color-one-color me-2"></i> جدیدترین مقالات</h6>
                </div>
                <nav class="navbar">
                    <ul class="navbar-nav">
                        @foreach($latestPosts ?? [] as $latestPost)
                            <li class="nav-item">
                                <a href="{{ route('front.articles.show', $latestPost->slug) }}" class="nav-link">
                                    <div class="mini-blog-item">
                                        <div class="image">
                                            <img src="{{ $latestPost->image ? asset($latestPost->image) : theme_asset('image/blog-1.jpg') }}" class="img-fluid" alt="{{ $latestPost->title }}" loading="lazy">
                                        </div>
                                        <div class="d-flex align-items-start desc flex-wrap flex-column justify-content-between">
                                            <h5 class="title">{{ $latestPost->title }}</h5>
                                            <div class="d-flex align-items-center">
                                                <p class="text-muted mb-0">{{ $latestPost->admin->full_name ?? 'ناشناس' }}</p>
                                                <p class="text-muted mb-0 ms-2">{{ jdate($latestPost->created_at)->format('d F Y') }}</p>
                                                <div class="text-end ms-2"><i class="bi bi-arrow-left main-color-one-color"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    {{-- هشتگ های داغ --}}
    <div class="hashtag-container mt-3">
        <div class="content-box">
            <div class="container-fluid">
                <div class="title-panel mb-2">
                    <h6 class="title"><i class="bi bi-hash main-color-one-color me-2"></i> هشتگ های داغ</h6>
                </div>
                <ul class="hashtags">
                    @foreach($hotTags ?? [] as $tag)
                        <li>
                            <a href="{{ route('front.articles.index', ['tag' => $tag->slug]) }}">
                                {{ $tag->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
