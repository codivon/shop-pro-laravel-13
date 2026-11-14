{{-- فاز ۱۶۶ — هدر وبلاگ سانیار (مطابق blog.html تمپلیت) --}}
{{-- جستجوی مقالات + فهرست دسته‌بندی‌ها (سایدبار) + بنر + خبرهای داغ --}}
{{-- استایلها در style.css تمپلیت (بخش .side-right-blog / .blog-news-item / .navbar-blog) --}}
@php
    $variables            = get_widget($widget);
    $blogHeaderCategories = $variables['blog_header_categories'] ?? collect();
    $blogHeaderBanner     = $variables['blog_header_banner'] ?? null;
    $blogHeaderNews       = $variables['blog_header_news'] ?? collect();

    /* آیکون‌های چرخشی فهرست دسته‌بندی‌ها (مطابق تمپلیت) */
    $blogCatIcons = ['bi-newspaper', 'bi-bounding-box-circles', 'bi-controller', 'bi-journal-richtext', 'bi-film', 'bi-cup-hot', 'bi-tag', 'bi-book'];
@endphp

@if($widget->option('show_search', 'yes') === 'yes' || $widget->option('show_categories', 'yes') === 'yes' || $blogHeaderBanner || count($blogHeaderNews))
    <div class="container-fluid py-20">
        <div class="row gy-3">
            {{-- سایدبار: جستجو + دسته‌بندی‌ها --}}
            @if($widget->option('show_search', 'yes') === 'yes' || ($widget->option('show_categories', 'yes') === 'yes' && count($blogHeaderCategories)))
                <div class="col-lg-3">
                    <div class="content-box h-100">
                        <div class="container-fluid">
                            <div class="side-right-blog">
                                @if($widget->option('show_search', 'yes') === 'yes')
                                    <div class="search-form">
                                        <form action="{{ route('front.articles.index') }}" method="get" role="search">
                                            <div class="search-filed">
                                                <label class="sr-only" for="blog-header-search">جستجوی مقالات</label>
                                                <input type="text" id="blog-header-search" name="search" value="{{ request('search') }}"
                                                       placeholder="جستجوی مقالات ..." class="form-control search-input">
                                                <button type="submit" class="btn search-btn main-color-one-bg rounded-pill" aria-label="جستجو">
                                                    <i class="bi bi-search"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                @endif

                                @if($widget->option('show_categories', 'yes') === 'yes' && count($blogHeaderCategories))
                                    <div class="navbar-blog">
                                        <nav class="navbar" aria-label="دسته بندی مقالات">
                                            <ul class="navbar-nav">
                                                <li class="nav-item mco-hover">
                                                    <a href="{{ route('front.articles.index') }}" class="nav-link">
                                                        <i class="bi bi-newspaper font-25 me-3"></i>جدیدترین مطالب
                                                    </a>
                                                </li>
                                                @foreach($blogHeaderCategories as $blogHeaderCategory)
                                                    <li class="nav-item">
                                                        <a href="{{ route('front.articles.index').'?cat='.$blogHeaderCategory->slug }}" class="nav-link">
                                                            <i class="bi {{ $blogCatIcons[$loop->index % count($blogCatIcons)] }} font-25 me-3"></i>{{ $blogHeaderCategory->title }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </nav>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- بنر + خبرهای داغ --}}
            @if($blogHeaderBanner || count($blogHeaderNews))
                <div class="{{ ($widget->option('show_search', 'yes') === 'yes' || ($widget->option('show_categories', 'yes') === 'yes' && count($blogHeaderCategories))) ? 'col-lg-9' : 'col-12' }}">
                    <div class="side-left-blog">
                        <div class="row gy-3">
                            @if($blogHeaderBanner)
                                <div class="col-12">
                                    @if($blogHeaderBanner->link)
                                        <a href="{{ $blogHeaderBanner->link }}" aria-label="{{ $blogHeaderBanner->title ?: 'بنر' }}">
                                            <img src="{{ $blogHeaderBanner->image ? asset($blogHeaderBanner->image) : asset('/no-image-product.svg') }}"
                                                 class="img-fluid shadow-box rounded-4" loading="lazy" decoding="async"
                                                 alt="{{ $blogHeaderBanner->title ?: 'بنر' }}">
                                        </a>
                                    @else
                                        <img src="{{ $blogHeaderBanner->image ? asset($blogHeaderBanner->image) : asset('/no-image-product.svg') }}"
                                             class="img-fluid shadow-box rounded-4" loading="lazy" decoding="async"
                                             alt="{{ $blogHeaderBanner->title ?: 'بنر' }}">
                                    @endif
                                </div>
                            @endif

                            @foreach($blogHeaderNews as $blogHeaderNewsItem)
                                <div class="{{ $loop->index === 1 ? 'col-md-6' : 'col-md-3' }} col-12">
                                    <div class="blog-news-item">
                                        <a href="{{ route('front.articles.show', ['slug' => $blogHeaderNewsItem->slug]) }}" aria-label="{{ $blogHeaderNewsItem->title }}">
                                            <img src="{{ $blogHeaderNewsItem->image ? asset($blogHeaderNewsItem->image) : asset('/no-image-product.svg') }}"
                                                 @if(! $loop->first) loading="lazy" decoding="async" @endif
                                                 alt="{{ $blogHeaderNewsItem->title }}" class="img-fluid">
                                            <div class="desc">
                                                <h3 class="title">
                                                    @if($blogHeaderNewsItem->post_type === 'video')
                                                        <i class="bi bi-play-circle-fill main-color-one-color me-1"></i>
                                                    @elseif($blogHeaderNewsItem->post_type === 'podcast')
                                                        <i class="bi bi-soundwave main-color-one-color me-1"></i>
                                                    @endif
                                                    {{ $blogHeaderNewsItem->title }}
                                                </h3>
                                                <div class="date">
                                                    <span class="text-date">{{ jdate($blogHeaderNewsItem->created_at)->format('d F Y') }}</span>
                                                    <i class="bi bi-arrow-left"></i>
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
@endif
