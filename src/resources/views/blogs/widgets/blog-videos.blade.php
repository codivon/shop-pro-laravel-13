{{-- فاز ۱۶۶ — آخرین ویدیوهای وبلاگ سانیار (مطابق blog.html تمپلیت) --}}
{{-- گرید tv-blog-item (col-lg-9) + پلی‌لیست video-blog-item (سایدبار col-lg-3) --}}
{{-- استایلها در style.css تمپلیت (بخش .tv-blog-item / .video-blog-item / .navbar-video) --}}
@php
    $variables         = get_widget($widget);
    $videoGridPosts    = $variables['video_grid_posts'] ?? collect();
    $videoSidebarPosts = $variables['video_sidebar_posts'] ?? collect();
    $videosLink        = $widget->option('link') ?: route('front.articles.index');
@endphp

@if(count($videoGridPosts) || count($videoSidebarPosts))
    <div class="py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3">{{ $widget->option('title', 'آخرین ویدیوهای') }} <span class="main-color-one-color d-inline-block">وبلاگ</span></h5>
                </div>
                @if($widget->option('link_title'))
                    <div class="link">
                        <a href="{{ $videosLink }}" class="border-animate fromCenter pb-1 fw-bold">
                            {{ $widget->option('link_title') }}
                            <i class="bi bi-chevron-double-left main-color-one-color"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="container-fluid">
            <div class="row gy-3">
                {{-- پلی‌لیست ویدیوها (سایدبار) --}}
                @if(count($videoSidebarPosts))
                    <div class="col-lg-3 {{ count($videoGridPosts) ? '' : 'd-none' }}">
                        <div class="content-box h-100">
                            <div class="container-fluid">
                                <div class="side-right-blog">
                                    <div class="navbar-video">
                                        <nav class="navbar" aria-label="پلی لیست ویدیوها">
                                            <ul class="navbar-nav">
                                                @foreach($videoSidebarPosts as $sidebarVideo)
                                                    <li class="nav-item">
                                                        <a href="{{ route('front.articles.show', ['slug' => $sidebarVideo->slug]) }}" class="nav-link" aria-label="{{ $sidebarVideo->title }}">
                                                            <div class="video-blog-item @if($sidebarVideo->post_type !== 'video' && $sidebarVideo->post_type !== 'podcast') video-item-text @endif">
                                                                <div class="image">
                                                                    <img src="{{ $sidebarVideo->image ? asset($sidebarVideo->image) : asset('/no-image-product.svg') }}"
                                                                         loading="lazy" decoding="async" class="img-fluid" alt="{{ $sidebarVideo->title }}">
                                                                </div>
                                                                <div class="d-flex align-items-start desc flex-wrap flex-column justify-content-between">
                                                                    <h5 class="title">{{ $sidebarVideo->title }}</h5>
                                                                    <div class="d-flex align-items-center">
                                                                        <p class="text-muted mb-0">{{ jdate($sidebarVideo->created_at)->format('d F Y') }}</p>
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
                        </div>
                    </div>
                @endif

                {{-- گرید ویدیوها --}}
                @if(count($videoGridPosts))
                    <div class="{{ count($videoSidebarPosts) ? 'col-lg-9' : 'col-12' }}">
                        <div class="side-left-blog">
                            <div class="row gy-3">
                                @foreach($videoGridPosts as $gridVideo)
                                    <div class="{{ $loop->index < 3 ? ($loop->index === 1 ? 'col-md-6' : 'col-md-3') : 'col-md-4' }} col-12">
                                        <div class="tv-blog-item @if($gridVideo->post_type === 'podcast') tv-item-podcast @elseif($gridVideo->post_type !== 'video') tv-item-text @endif">
                                            <a href="{{ route('front.articles.show', ['slug' => $gridVideo->slug]) }}" aria-label="{{ $gridVideo->title }}">
                                                <img src="{{ $gridVideo->image ? asset($gridVideo->image) : asset('/no-image-product.svg') }}"
                                                     @if(! $loop->first) loading="lazy" decoding="async" @endif
                                                     alt="{{ $gridVideo->title }}" class="img-fluid">
                                                <div class="desc">
                                                    <h3 class="title">{{ $gridVideo->title }}</h3>
                                                    <div class="date">
                                                        <span class="text-date">{{ jdate($gridVideo->created_at)->format('d F Y') }}</span>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
