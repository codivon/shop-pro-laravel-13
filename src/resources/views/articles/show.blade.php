@extends('front::layouts.master', ['title' => $post->meta_title ?: $post->title])

@section('og_image')
    <meta property="og:image" content="{{ asset($post->image) }}">
@endsection

@push('meta')
    <meta property="og:type" content="article">
    <meta property="og:description" content="{{ $post->meta_description ?? $post->short_description }}">
    <meta name="description" content="{{ $post->meta_description ?? $post->short_description }}">
    <meta name="twitter:description" content="{{ $post->meta_description ?? $post->short_description }}">
    <meta name="twitter:image" content="{{ asset($post->image) }}">
    <link rel="canonical" href="{{ route('front.articles.show', $post) }}">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{theme_asset('js/plugins/plyr/plyr.css')}}" />
@endpush

@section('content')
    {{-- Breadcrumb — سانیار bread-crumb --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('front.articles.index') }}" class="font-14 text-muted">مجله {{ option('info_site_title') }}</a>
                            </li>
                            @if($post->categories->isNotEmpty())
                                <li class="breadcrumb-item">
                                    <a href="{{ route('front.articles.index', ['cat' => $post->categories->first()->slug]) }}" class="font-14 text-muted">
                                        {{ $post->categories->first()->title }}
                                    </a>
                                </li>
                            @endif
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">{{ $post->title }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="content article-page">
        <div class="container-fluid py-3">
            <div class="row gy-3">
                <div class="col-lg-9">
                    <div class="content-box h-100">
                        <div class="container-fluid">
                            <div class="blog-detail">
                                <div class="title-panel mb-2">
                                    <h1 class="font-18">{{ $post->title }}</h1>
                                </div>

                                <div class="blog-post-title-items">
                                    <div class="blog-post-title-item">
                                        @if($post->admin)
                                            <img src="{{ $post->admin->imageUrl }}" class="rounded-circle" width="32" height="32" alt="{{ $post->admin->full_name ?? 'ناشناس' }}" loading="lazy">
                                            <a class="text-decoration-none" href="{{ route('front.articles.index', ['profile' => $post->admin->username]) }}">
                                                <p class="text-muted">{{ $post->admin->full_name ?? 'ناشناس' }}</p>
                                            </a>
                                        @else
                                            <p class="text-muted">ناشناس</p>
                                        @endif
                                    </div>
                                    <div class="blog-post-title-item">
                                        <i class="bi bi-stopwatch"></i>
                                        <p class="text-muted">{{ jdate($post->created_at)->format('d F Y') }}</p>
                                    </div>
                                    <div class="blog-post-title-item">
                                        <i class="bi bi-chat-dots"></i>
                                        <p>{{ $post->acceptedComments()->count() }}</p>
                                    </div>
                                    <div class="blog-post-title-item">
                                        <i class="bi bi-eye-fill"></i>
                                        <p class="text-muted">{{ number_format($post->view) }} بازدید</p>
                                    </div>
                                </div>

                                {{-- نوار لایک و اشتراک گذاری — سانیار --}}
                                <div class="d-flex align-items-center flex-wrap gap-2 py-3">
                                    <button class="like-button btn light-btn shadow-sm rounded-3 border-0 d-flex align-items-center" data-action="{{ route("front.articles.like") }}" data-post-id="{{ $post->id }}">
                                        <i class="bi bi-heart main-color-one-color {{ $isLiked ? 'liked' : '' }}"></i>
                                        <span class="likes-count ms-1">{{ $post->likes_count ?? 0 }}</span>
                                    </button>
                                    <a href="#article-comments" class="btn light-btn shadow-sm rounded-3 border-0 d-flex align-items-center">
                                        <i class="bi bi-chat-dots main-color-one-color"></i>
                                    </a>
                                    <span class="text-muted">اشتراک گذاری:</span>
                                    <a href="https://telegram.me/share/url?url={{ urlencode(request()->url()) }}" target="_blank" class="btn light-btn shadow-sm rounded-circle border-0" aria-label="تلگرام"><i class="bi bi-telegram main-color-one-color"></i></a>
                                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" target="_blank" class="btn light-btn shadow-sm rounded-circle border-0" aria-label="فیسبوک"><i class="bi bi-facebook main-color-one-color"></i></a>
                                    <a href="https://wa.me/?text={{ urlencode(request()->url()) }}" target="_blank" class="btn light-btn shadow-sm rounded-circle border-0" aria-label="واتساپ"><i class="bi bi-whatsapp main-color-one-color"></i></a>
                                    <a href="https://twitter.com/intent/tweet?text={{ urlencode(request()->url()) }}" target="_blank" class="btn light-btn shadow-sm rounded-circle border-0" aria-label="توییتر"><i class="bi bi-twitter main-color-one-color"></i></a>
                                </div>

                                <div class="blog-post-content">
                                    {{-- تصویر یا ویدیوی اصلی --}}
                                    @php
                                        $isVideo = $post->post_type == 'video' && $post->video_url;
                                        $isPodcast = $post->post_type == 'podcast' && $post->podcast_url;
                                        $isMedia = $isVideo || $isPodcast;
                                        $mediaUrl = $isVideo ? $post->video_url : ($isPodcast ? $post->podcast_url : null);
                                        $readingTime = ceil(str_word_count(strip_tags($post->body)) / 200);
                                    @endphp

                                    @if($isMedia)
                                        <video class="articles-video-player shadow-box rounded-3 w-100 mb-4" controls poster="{{ $post->image ? asset($post->image) : theme_asset('image/blog-1.jpg') }}">
                                            <source src="{{ asset($mediaUrl) }}" type="video/mp4">
                                            مرورگر شما از ویدیو پشتیبانی نمی‌کند.
                                        </video>
                                    @else
                                        <img class="shadow-box rounded-3 w-100 mb-4" src="{{ $post->image ? asset($post->image) : theme_asset('image/blog-1.jpg') }}" alt="{{ $post->title }}" loading="lazy">
                                        <div class="d-flex align-items-center text-muted mb-3">
                                            <i class="bi bi-clock-history main-color-one-color me-1"></i>
                                            زمان مطالعه: <span class="fw-bold ms-1">{{ $readingTime }} دقیقه</span>
                                        </div>
                                    @endif

                                    {{-- متن مقاله --}}
                                    <div class="blog-post-content">
                                        {!! $post->content !!}
                                    </div>

                                    {{-- هشتگ‌ها --}}
                                    @if($post->tags->isNotEmpty())
                                        <ul class="hashtags mb-3">
                                            @foreach($post->tags as $tag)
                                                <li>
                                                    <a href="{{ route('front.articles.index', ['tag' => $tag->slug]) }}">
                                                        {{ $tag->name }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    {{-- منبع --}}
                                    @if($post->source)
                                        <div class="text-muted py-3">
                                            <span class="font-14"><i class="bi bi-link-45deg"></i> منبع: </span>{{ $post->source }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- اطلاعات نویسنده — سانیار content-box --}}
                    @if($post->admin)
                        <div class="content-box mt-3">
                            <div class="container-fluid">
                                <div class="title-panel mb-2">
                                    <h6 class="title"><i class="bi bi-person main-color-one-color me-2"></i> نویسنده</h6>
                                </div>
                                <div class="d-flex align-items-center flex-wrap">
                                    <img class="rounded-circle shadow-box" width="80" height="80"
                                         src="{{ $post->admin->imageUrl }}"
                                         alt="{{ $post->admin->full_name ?? 'ناشناس' }}" loading="lazy">
                                    <div class="ms-3">
                                        <h5 class="font-16 mb-1">
                                            <a href="{{ route('front.articles.index', ['profile' => $post->admin->username]) }}" class="text-decoration-none text-dark">
                                                {{ $post->admin->full_name ?? 'ناشناس' }}
                                            </a>
                                            <span class="badge rounded-pill main-color-one-bg text-white ms-1"><i class="bi bi-patch-check-fill"></i> مدیریت</span>
                                        </h5>
                                        <p class="text-muted font-14 mb-0">{{ $post->admin->bio ?? 'توضیحاتی برای نویسنده ثبت نشده است.' }}</p>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap ms-auto">
                                        <span class="text-muted font-14 ms-3"><i class="bi bi-newspaper main-color-one-color me-1"></i> منتشر شده: <span class="fw-bold">{{ $post->admin->published_posts_count }}</span></span>
                                        <span class="text-muted font-14 ms-3"><i class="bi bi-calendar3 main-color-one-color me-1"></i> تاریخ پیوستن: <span class="fw-bold">{{ jdate($post->admin->created_at)->format('d F Y') }}</span></span>
                                        <div class="d-flex align-items-center">
                                            @php
                                                $socials = [
                                                    'instagram' => ['icon' => '<i class="bi bi-instagram"></i>', 'name' => 'اینستاگرام'],
                                                    'whatsapp' => ['icon' => '<i class="bi bi-whatsapp"></i>', 'name' => 'واتساپ'],
                                                    'telegram' => ['icon' => '<i class="bi bi-telegram"></i>', 'name' => 'تلگرام'],
                                                    'twitter' => ['icon' => '<i class="bi bi-twitter"></i>', 'name' => 'توییتر'],
                                                    'facebook' => ['icon' => '<i class="bi bi-facebook"></i>', 'name' => 'فیسبوک'],
                                                    'rubika' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/rubika.png').'" class="me-1" width="17">', 'name' => 'روبیکا'],
                                                    'eitaa' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/eita.png').'" class="me-1" width="17">', 'name' => 'ایتا'],
                                                    'bale' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/bale.png').'" class="me-1" width="17">', 'name' => 'بله'],
                                                ];
                                            @endphp

                                            @foreach($socials as $platform => $info)
                                                @php $link = $post->admin->getSocialLink($platform); @endphp
                                                @if($link)
                                                    <a href="{{ $link }}" class="btn btn-sm light-btn shadow-sm rounded-circle ms-1" title="{{ $info['name'] }}" target="_blank" rel="noopener noreferrer">
                                                        {!! $info['icon'] !!}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- سایدبار --}}
                <div class="col-lg-3">
                    @include('front::articles.partials.sidebar', ['currentPost' => $post])
                </div>
            </div>
        </div>
    </div>

    {{-- نظرات --}}
    @include('front::articles.partials.comments', ['post' => $post])
@endsection

@push('scripts')
    <script src="{{theme_asset('js/plugins/plyr/plyr.js')}}"></script>
    <script src="{{ theme_asset('js/pages/comments.js') }}"></script>
    <script src="{{ theme_asset('js/pages/articles/show.js') }}"></script>
@endpush
