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
    <link rel="stylesheet" href="{{theme_asset('css/single-articles.css')}}">
    <style>
        .article-container .article--right-meta > ul.meta-list{
            top: 120px;
        }
        .plyr audio, .plyr iframe, .plyr video{
            max-height: 500px;
        }
    </style>
@endpush


    @section('content')
        <div class="col-12 article-page">
            <div class="row">
                {{-- Breadcrumb --}}
                <nav class="product-page mb-3 simplebar-container" aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" style="min-width: 800px;">
                        <li class="breadcrumb-item">
                            <a href="{{ route('front.articles.index') }}">مجله {{option('info_site_title')}}</a>
                        </li>
                        @if($post->categories->isNotEmpty())
                            <li class="breadcrumb-item">
                                <a href="{{ route('front.articles.index', ['cat' => $post->categories->first()->slug]) }}">
                                    {{ $post->categories->first()->title }}
                                </a>
                            </li>
                        @endif
                        <li class="breadcrumb-item active" aria-current="page">{{ $post->title }}</li>
                    </ol>
                </nav>

                <div class="article-container col-lg-9 col-md-9">
                    {{-- عنوان مقاله --}}
                    <div class="article--title mb-4">
                        <h1>{{ $post->title }}</h1>
                        <ul class="article--title-details mt-3 mb-0">
                            <li>
                                {{-- فاز ۹۵ — گارد نال: مقاله‌های بدون نویسنده (admin_id خالی) نباید ۵۰۰ بدهند --}}
                                @if($post->admin)
                                    <img src="{{$post->admin->imageUrl}}"
                                         class="article--meta-avatar shadow-1 ml-2" height="32" width="32" loading="lazy">
                                    <a class="article--meta-username" href="{{ route('front.articles.index', ['profile' => $post->admin->username]) }}">
                                        {{ $post->admin->full_name ?? 'ناشناس' }}
                                    </a>
                                @else
                                    <span class="article--meta-username">ناشناس</span>
                                @endif
                            </li>
                            <li class="divider"></li>
                            <li><span class="date">{{ jdate($post->created_at)->format('d F Y') }}</span></li>
                            <li class="divider"></li>
                            <li><span class="date">{{ number_format($post->view) }} بازدید</span></li>
                        </ul>
                    </div>

                   <div class="article-main-container">
                        {{-- نوار کناری راست (لایک و اشتراک گذاری) --}}
                        <div class="article--right-meta pl-3">
                            <ul class="meta-list">
                                <li>
                                    <button class="like-button" data-action="{{ route("front.articles.like") }}" data-post-id="{{ $post->id }}">
                                        <i class="fa-regular fa-heart {{$isLiked ? 'fa-heart-like' : ''}}"></i>
                                        <span class="likes-count">{{ $post->likes_count ?? 0 }}</span>
                                    </button>
                                </li>
                                <li>
                                    <a href="#article-comments">
                                        <i class="fa-regular fa-message mr-0"></i>
                                    </a>
                                    <span>{{ $post->acceptedComments()->count() }}</span>
                                </li>
                                <li class="divider"></li>
                                <li>
                                    <a href="https://telegram.me/share/url?url={{ urlencode(request()->url()) }}" target="_blank">
                                        <i class="fa-regular fa-paper-plane"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" target="_blank">
                                        <i class="fa-brands fa-facebook"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://wa.me/?text={{ urlencode(request()->url()) }}" target="_blank">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://twitter.com/intent/tweet?text={{ urlencode(request()->url()) }}" target="_blank">
                                        <i class="fa-brands fa-twitter"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {{-- محتوای اصلی مقاله --}}
                        <div class="article--details">
                            {{-- تصویر یا ویدیوی اصلی --}}
                            <div class="article--image mb-3">
                                @php
                                    $isVideo = $post->post_type == 'video' && $post->video_url;
                                    $isPodcast = $post->post_type == 'podcast' && $post->podcast_url;
                                    $isMedia = $isVideo || $isPodcast;
                                    $mediaUrl = $isVideo ? $post->video_url : ($isPodcast ? $post->podcast_url : null);
                                    $readingTime = ceil(str_word_count(strip_tags($post->body)) / 200);
                                @endphp

                                @if($isMedia)
                                    <video class="articles-video-player shadow-1 w-100" controls style="border-radius: 12px;" poster="{{ asset($post->image) }}">
                                        <source src="{{ asset($mediaUrl) }}" type="video/mp4">
                                        مرورگر شما از ویدیو پشتیبانی نمی‌کند.
                                    </video>
                                @else
                                    <img class="shadow-1 w-100" src="{{ asset($post->image) }}" alt="{{ $post->title }}" loading="lazy" style="border-radius: 12px;">
                                    <div class="read-time-badge shadow-1">
                                        <i class="fa-regular fa-clock"></i> زمان مطالعه: <span>{{ $readingTime }} دقیقه</span>
                                    </div>
                                @endif

                            </div>

                            {{-- متن مقاله --}}
                            <div class="article--content main-content card shadow-1 mb-4">
                                <div class="card-body">
                                    {!! $post->content !!}
                                </div>

                                {{-- هشتگ‌ها --}}
                                @if($post->tags->isNotEmpty())
                                    <div class="hashtag-container">
                                        <ul class="hashtags mb-2">
                                            @foreach($post->tags as $tag)
                                                <li>
                                                    <a href="{{ route('front.articles.index', ['tag' => $tag->slug]) }}">
                                                         {{ $tag->name }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                {{-- منبع --}}
                                @if($post->source)
                                    <div class="text-dark pb-3 px-4">
                                        <span class="text-gray fs-8"><i class="fa-regular fa-link"></i> منبع: </span>{{ $post->source }}
                                    </div>
                                @endif
                            </div>

                            {{-- اطلاعات نویسنده --}}
                            <div class="section-title mb-2">
                                <div class="title no-after"><i class="fa-regular fa-user"></i> نویسنده</div>
                            </div>
                            <div class="author card shadow-1 mb-4">
                                <div class="card-body flex-grow-1">
                                    <div class="author--avatar">
                                        <img class="shadow-1 mb-2" width="100" height="100"
                                             src="{{$post->admin->imageUrl}}"
                                             alt="{{ $post->admin->full_name ?? 'ناشناس' }}" loading="lazy">
                                        <div class="author--badge"><i class="fa-solid fa-crown"></i> مدیریت</div>
                                    </div>
                                    <div class="author--meta">
                                        <div class="author--name">
                                            <a href="{{ route('front.articles.index', ['profile' => $post->admin->username]) }}" style="text-decoration: none;">
                                                <span>{{ $post->admin->full_name ?? 'ناشناس' }}</span>
                                            </a>
                                        </div>
                                        <p class="author--description mb-3">{{ $post->admin->bio ?? 'توضیحاتی برای نویسنده ثبت نشده است.' }}</p>
                                        <div class="autor--meta-details">
                                            <ul>
                                                <li><i class="fa-regular fa-newspaper ml-2"></i> منتشر شده: <span>{{ $post->admin->published_posts_count }}</span></li>
                                                <li class="divider"></li>
                                                <li><i class="fa-regular fa-calendar ml-2"></i> تاریخ پیوستن: <span>{{ jdate($post->admin->created_at)->format('d F Y') }}</span></li>
                                            </ul>
                                            <div class="social">
                                                @php
                                                    $socials = [
                                                        'instagram' => ['icon' => '<i class="fa-brands fa-instagram"></i>', 'name' => 'اینستاگرام'],
                                                        'whatsapp' => ['icon' => '<i class="fa-brands fa-whatsapp"></i>', 'name' => 'واتساپ'],
                                                        'telegram' => ['icon' => '<i class="fa-brands fa-telegram"></i>', 'name' => 'تلگرام'],
                                                        'twitter' => ['icon' => '<i class="fa-brands fa-twitter"></i>', 'name' => 'توییتر'],
                                                        'facebook' => ['icon' => '<i class="fa-brands fa-facebook"></i>', 'name' => 'فیسبوک'],
                                                        'rubika' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/rubika.png').'" style="margin-top: -7px;width:17px">', 'name' => 'روبیکا'],
                                                        'eitaa' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/eita.png').'" style="margin-top: -7px;width:17px">', 'name' => 'ایتا'],
                                                        'bale' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/bale.png').'" style="margin-top: -7px;width:17px">', 'name' => 'بله'],
                                                    ];
                                                @endphp

                                                @foreach($socials as $platform => $info)
                                                    @php $link = $post->admin->getSocialLink($platform); @endphp
                                                    @if($link)
                                                        <a href="{{ $link }}" class="" title="{{$info['name']}}" target="_blank" rel="noopener noreferrer">
                                                             {!! $info['icon'] !!}
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- نظرات --}}
                            @include('front::articles.partials.comments', ['post' => $post])
                        </div>
                    </div>
                </div>

                {{-- سایدبار --}}
                <div class="col-lg-3 col-md-3 blog-sidebar">
                    <div class="blog-sidebar--inner">
                        @include('front::articles.partials.sidebar', ['currentPost' => $post])
                    </div>
                </div>
            </div>
        </div>
    @endsection



@push('scripts')
    <script src="{{theme_asset('js/plugins/plyr/plyr.js')}}"></script>
    <script src="{{ theme_asset('js/pages/comments.js') }}"></script>
    <script src="{{ theme_asset('js/pages/articles/show.js') }}"></script>
    <script>

    </script>
@endpush
