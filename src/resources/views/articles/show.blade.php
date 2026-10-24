@php
    $readingTime = max(1, (int) ceil(count(preg_split('/\s+/u', strip_tags($post->content))) / 200));
    $mainComments = $post->mainComments()
        ->with(['user', 'admin', 'replies' => fn($q) => $q->where('status', 'accepted')
            ->with(['user', 'admin'])->orderBy('created_at', 'asc')])
        ->orderBy('created_at', 'desc')
        ->paginate(10);
    $likedComments = json_decode(request()->cookie('comment_likes', '[]'), true) ?: [];
    $postUrl = route('front.articles.show', ['slug' => $post->slug]);
    // گارد saniar_seo_value: پیش‌فرض رشته‌ای 'NULL' ستون‌های متا (باگ مهاجرت میزبان)
    $postTitle = saniar_seo_value($post->meta_title, $post->title);
    $postDescription = saniar_seo_value($post->meta_description, \Illuminate\Support\Str::limit(strip_tags($post->summary ?: $post->content), 160));
    $postDescription = trim(saniar_seo_value($postDescription, ''));
@endphp
@extends('front::layouts.master', [
    'title' => $postTitle,
    'description' => $postDescription,
    'og_image' => $post->image ? asset($post->image) : null,
])

@push('meta')
    <link rel="canonical" href="{{ $postUrl }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $post->title }}">
    <meta property="og:description" content="{{ $postDescription }}">
    <meta property="article:published_time" content="{{ $post->created_at->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
    <meta property="article:author" content="{{ $post->admin?->full_name ?? option('info_site_title') }}">
    <meta property="article:section" content="{{ $post->category?->title ?? 'وبلاگ' }}">
    @foreach($post->tags->take(10) as $tag)
        <meta property="article:tag" content="{{ $tag->name }}">
    @endforeach

    {{-- سئو: JSON-LD مقاله + بردکرامب --}}
    <script type="application/ld+json">{!! json_encode([
        '@@context' => 'https://schema.org',
        '@@type' => 'Article',
        'headline' => $post->title,
        'description' => $postDescription,
        'image' => $post->image ? [asset($post->image)] : null,
        'datePublished' => $post->created_at->toIso8601String(),
        'dateModified' => $post->updated_at->toIso8601String(),
        'inLanguage' => 'fa-IR',
        'mainEntityOfPage' => ['@@type' => 'WebPage', '@@id' => $postUrl],
        'author' => ['@@type' => 'Person', 'name' => $post->admin?->full_name ?? 'تحریریه'],
        'publisher' => [
            '@@type' => 'Organization',
            'name' => option('info_site_title', config('front.demo.name')),
            'logo' => ['@@type' => 'ImageObject', 'url' => option('info_logo', theme_asset('image/logo.png'))],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <script type="application/ld+json">{!! json_encode([
        '@@context' => 'https://schema.org',
        '@@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => route('front.index')],
            ['@@type' => 'ListItem', 'position' => 2, 'name' => 'وبلاگ', 'item' => route('front.blog.index')],
            ['@@type' => 'ListItem', 'position' => 3, 'name' => $post->category?->title ?? 'مقالات', 'item' => $post->category ? route('front.articles.index', ['cat' => $post->category->slug]) : route('front.articles.index')],
            ['@@type' => 'ListItem', 'position' => 4, 'name' => $post->title, 'item' => $postUrl],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/blog.css') }}">
@endpush

@section('content')

    {{-- bredcrumb --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('front.index') }}" wire:navigate class="font-14 text-muted">خانه</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('front.blog.index') }}" wire:navigate class="font-14 text-muted">وبلاگ</a>
                            </li>
                            @if($post->category)
                                <li class="breadcrumb-item">
                                    <a href="{{ route('front.articles.index', ['cat' => $post->category->slug]) }}" wire:navigate
                                       class="font-14 text-muted">{{ $post->category->title }}</a>
                                </li>
                            @endif
                            <li class="breadcrumb-item active main-color-one-color font-14 text-overflow-1" aria-current="page">{{ $post->title }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content py-3" data-saniar-article
         data-post-id="{{ $post->id }}"
         data-like-url="{{ route('front.articles.like') }}"
         data-comment-store-url="{{ route('front.articles.comments.store', ['post' => $post->slug]) }}"
         data-comment-reply-url="{{ route('front.articles.comments.reply') }}"
         data-comments-list-url="{{ route('front.articles.comments.list', ['post' => $post->id]) }}"
         data-is-liked="{{ $isLiked ? 'true' : 'false' }}"
         data-likes-count="{{ $post->likes_count }}">
        <div class="container-fluid">
            <div class="row gy-3">
                <div class="col-lg-9">
                    <div class="content-box">
                        <div class="container-fluid">
                            <article class="blog-detail">
                                <div class="title-panel mb-2">
                                    <h1 class="font-20 fw-bold">{{ $post->title }}</h1>
                                </div>
                                <div class="blog-post-title-items">
                                    <div class="blog-post-title-item">
                                        <img src="{{ $post->admin?->imageUrl ?? theme_asset('image/logo.png') }}"
                                             alt="{{ $post->admin?->full_name ?? 'تحریریه' }}" class="rounded-circle" width="32" height="32" loading="lazy">
                                        <p class="text-muted mb-0">{{ $post->admin?->full_name ?? 'تحریریه' }}</p>
                                    </div>
                                    <div class="blog-post-title-item">
                                        <i class="bi bi-stopwatch"></i>
                                        <p class="text-muted mb-0">{{ jdate($post->created_at)->format('d F Y') }}</p>
                                    </div>
                                    <div class="blog-post-title-item">
                                        <i class="bi bi-chat-dots"></i>
                                        <p class="mb-0">{{ number_format($post->comments_count) }}</p>
                                    </div>
                                    <div class="blog-post-title-item">
                                        <i class="bi bi-eye-fill"></i>
                                        <p class="text-muted mb-0">{{ number_format($post->view) }} بازدید</p>
                                    </div>
                                    <div class="blog-post-title-item">
                                        <i class="bi bi-book"></i>
                                        <p class="text-muted mb-0">{{ $readingTime }} دقیقه مطالعه</p>
                                    </div>
                                </div>

                                @if($post->image)
                                    <figure class="blog-post-featured my-3">
                                        <img src="{{ asset($post->image) }}" alt="{{ $post->title }}" class="img-fluid rounded-3 shadow-box">
                                    </figure>
                                @endif

                                @if($post->summary)
                                    <p class="blog-post-summary font-15">{{ $post->summary }}</p>
                                @endif

                                <div class="blog-post-content dynamic-content">
                                    {!! $post->content !!}
                                </div>

                                {{-- تگ‌ها + لایک + اشتراک‌گذاری --}}
                                <div class="blog-post-footer d-flex align-items-center justify-content-between flex-wrap gap-2 py-3 mt-2 border-top">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        @if($post->tags->isNotEmpty())
                                            @foreach($post->tags as $tag)
                                                <a href="{{ route('front.articles.index', ['tag' => $tag->slug]) }}" wire:navigate
                                                   class="badge post-tag-badge rounded-pill bg-light text-dark border">
                                                    <i class="bi bi-hash"></i>{{ $tag->name }}
                                                </a>
                                            @endforeach
                                        @endif
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-outline-danger rounded-pill btn-sm px-3 like-btn {{ $isLiked ? 'active' : '' }}"
                                                aria-label="لایک مقاله" aria-pressed="{{ $isLiked ? 'true' : 'false' }}">
                                            <i class="bi bi-heart{{ $isLiked ? '-fill' : '' }}"></i>
                                            <span class="likes-count">{{ number_format($post->likes_count) }}</span>
                                        </button>
                                        <a class="btn btn-outline-secondary rounded-pill btn-sm px-3 share-btn" target="_blank" rel="noopener noreferrer"
                                           href="https://t.me/share/url?url={{ urlencode($postUrl) }}&text={{ urlencode($post->title) }}" aria-label="اشتراک در تلگرام">
                                            <i class="bi bi-telegram"></i>
                                        </a>
                                        <a class="btn btn-outline-secondary rounded-pill btn-sm px-3 share-btn" target="_blank" rel="noopener noreferrer"
                                           href="https://wa.me/?text={{ urlencode($post->title . ' — ' . $postUrl) }}" aria-label="اشتراک در واتساپ">
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-secondary rounded-pill btn-sm px-3 share-btn"
                                                data-copy-url="{{ $postUrl }}" aria-label="کپی لینک مقاله">
                                            <i class="bi bi-link-45deg"></i>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>

                    {{-- نظرات --}}
                    <div class="content-box mt-3" id="article-comments" data-liked-comments='@json(array_keys($likedComments ?? []))'>
                        <div class="container-fluid">
                            <div class="title-panel mb-3">
                                <h6 class="font-16 mb-0">
                                    دیدگاه‌ها
                                    <span class="badge bg-secondary rounded-pill ms-1">{{ number_format($post->comments_count) }}</span>
                                </h6>
                            </div>

                            {{-- نظرات در انتظار تایید کاربر --}}
                            @if($userPendingComments->isNotEmpty() || $userPendingReplies->isNotEmpty())
                                <div class="alert alert-warning font-13">
                                    <i class="bi bi-clock-history me-1"></i>
                                    {{ ($userPendingComments->count() + $userPendingReplies->count()) }} دیدگاه شما در انتظار تایید مدیر است و پس از بررسی نمایش داده می‌شود.
                                </div>
                            @endif

                            {{-- لیست نظرات تاییدشده (هدف AJAX) --}}
                            <div id="comments-list-container">
                                @include('front::articles.partials.comments-list', ['comments' => $mainComments])
                            </div>
                            <div id="comments-pagination-container">
                                @include('front::articles.partials.comments-pagination', ['comments' => $mainComments])
                            </div>

                            {{-- فرم ثبت دیدگاه جدید --}}
                            @if($post->allow_comments)
                                <div class="comment-form mt-4">
                                    <div class="title-panel mb-3">
                                        <h6 class="font-18 mb-0">دیدگاه خود را بنویسید</h6>
                                    </div>
                                    <form data-saniar-comment-form class="mb-2">
                                        <div class="form-group mb-2">
                                            <label for="commentBody" class="form-label my-2">دیدگاه شما <span class="text-danger">*</span></label>
                                            <textarea id="commentBody" name="content" rows="5" class="form-control"
                                                      placeholder="دیدگاه شما ..." required maxlength="1000"></textarea>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <p class="text-muted font-12 mb-0">
                                                @auth('web')
                                                    <i class="bi bi-person-check me-1"></i>به‌عنوان {{ auth('web')->user()->full_name ?? auth('web')->user()->mobile }}
                                                @else
                                                    <i class="bi bi-person me-1"></i>به‌عنوان مهمان — برای تجربهٔ بهتر <a href="{{ route('login') }}" class="main-color-one-color">وارد شوید</a>
                                                @endauth
                                            </p>
                                            <button type="submit" class="btn main-color-one-bg text-white border-0 rounded-pill px-4 comment-submit-btn">
                                                <i class="bi bi-send me-1"></i>ثبت دیدگاه
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @else
                                <div class="alert alert-light border font-13">
                                    <i class="bi bi-slash-circle me-1"></i>امکان ثبت دیدگاه برای این مقاله بسته شده است.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- سایدبار مقاله (sticky) --}}
                <div class="col-lg-3">
                    <div class="side-blog-detail position-sticky top-0">
                        <div class="content-box mb-3">
                            <div class="container-fluid">
                                <div class="title-panel mb-2">
                                    <h6 class="title font-14 mb-0">آخرین مطالب وبلاگ</h6>
                                </div>
                                <nav class="navbar p-0">
                                    <ul class="navbar-nav w-100">
                                        @foreach($latestPosts as $latest)
                                            <li class="nav-item">
                                                <a href="{{ route('front.articles.show', ['slug' => $latest->slug]) }}" wire:navigate class="nav-link">
                                                    <div class="mini-blog-item">
                                                        <div class="image">
                                                            <img src="{{ $latest->image ? asset($latest->image) : asset('no-image-product.svg') }}"
                                                                 alt="{{ $latest->title }}" class="img-fluid" loading="lazy">
                                                        </div>
                                                        <div class="desc">
                                                            <h5 class="title font-14 mb-1 text-overflow-2">{{ $latest->title }}</h5>
                                                            <p class="text-muted font-12 mb-0">
                                                                <i class="bi bi-alarm me-1"></i>{{ jdate($latest->created_at)->format('d F Y') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </nav>
                            </div>
                        </div>

                        @if($hotTags->isNotEmpty())
                            <div class="content-box mb-3">
                                <div class="container-fluid">
                                    <div class="title-panel mb-2">
                                        <h6 class="title font-14 mb-0">هشتگ‌های داغ</h6>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 py-2">
                                        @foreach($hotTags as $tag)
                                            <a href="{{ route('front.articles.index', ['tag' => $tag->slug]) }}" wire:navigate
                                               class="badge post-tag-badge rounded-pill bg-light text-dark border font-12">
                                                #{{ $tag->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="content-box">
                            <div class="container-fluid">
                                <div class="title-panel mb-2">
                                    <h6 class="title font-14 mb-0">دسته‌بندی مقالات</h6>
                                </div>
                                <nav class="navbar p-0">
                                    <ul class="navbar-nav w-100 blog-cats-list">
                                        @foreach(\App\Models\Category::published()->where('type', 'postcat')->whereNull('category_id')->orderBy('ordering')->get() as $category)
                                            <li class="nav-item">
                                                <a href="{{ route('front.articles.index', ['cat' => $category->slug]) }}" wire:navigate
                                                   class="nav-link d-flex align-items-center justify-content-between">
                                                    <span>{{ $category->title }}</span>
                                                    <i class="bi bi-chevron-left font-12"></i>
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

            {{-- پیشنهادها (اسلایدر انتخاب سردبیر) --}}
            @if($suggestions->isNotEmpty())
                <div class="blog-slider site-slider site-slider-sm py-20">
                    <div class="container-fluid">
                        <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                            <div class="title d-flex align-items-center">
                                <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                                <h5 class="font-16 ms-3">پیشنهادهایی <span class="main-color-one-color d-inline-block">برای شما</span></h5>
                            </div>
                            <div class="link">
                                <a href="{{ route('front.articles.index', ['sort' => 'editor_pick']) }}" wire:navigate class="border-animate fromCenter pb-1 fw-bold">
                                    مشاهده همه
                                    <i class="bi bi-chevron-double-left main-color-one-color"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="parent">
                        <div class="container-fluid">
                            <div class="swiper product-slider-swiper" data-saniar-blog-slider>
                                <div class="swiper-wrapper">
                                    @foreach($suggestions as $suggestion)
                                        <div class="swiper-slide">
                                            <div class="blog-item">
                                                <a href="{{ route('front.articles.show', ['slug' => $suggestion->slug]) }}" wire:navigate>
                                                    <div class="image">
                                                        <img src="{{ $suggestion->image ? asset($suggestion->image) : asset('no-image-product.svg') }}"
                                                             alt="{{ $suggestion->title }}" class="img-fluid" loading="lazy">
                                                    </div>
                                                    <div class="title">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <div class="title-item">
                                                                <i class="bi bi-tag me-1"></i>
                                                                <span class="font-12">{{ $suggestion->category?->title ?? 'وبلاگ' }}</span>
                                                            </div>
                                                            <div class="title-item">
                                                                <i class="bi bi-alarm me-1"></i>
                                                                <span class="font-12">{{ jdate($suggestion->created_at)->format('d F Y') }}</span>
                                                            </div>
                                                        </div>
                                                        <h4 class="font-16 text-overflow-1 h4">{{ $suggestion->title }}</h4>
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
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/blog/blog-slider.js') }}"></script>
    <script src="{{ theme_asset('js/pages/blog/articles-show.js') }}"></script>
@endpush
