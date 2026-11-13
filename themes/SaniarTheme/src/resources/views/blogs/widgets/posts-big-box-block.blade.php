{{-- باکس بزرگ مطالب وبلاگ سانیار — پست بزرگ + لیست mini-blog-item --}}
@php
    $variables = get_widget($widget);
    $posts     = $variables['posts'];
@endphp

@if ($posts->count())
    <div class="py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3">{{ $widget->option('title', 'مطالب ویژه') }} <span class="main-color-one-color d-inline-block">وبلاگ</span></h5>
                </div>
                @if($widget->option('link_title') and $widget->option('link'))
                    <div class="link">
                        <a href="{{ $widget->option('link') }}" class="border-animate fromCenter pb-1 fw-bold">
                            {{ $widget->option('link_title') }}
                            <i class="bi bi-chevron-double-left main-color-one-color"></i>
                        </a>
                    </div>
                @endif
            </div>

            <div class="content-box">
                <div class="container-fluid">
                    <div class="row gy-3">
                        @php
                            $postsArray = $posts->values(); // تبدیل به آرایه با ایندکس 0 شروع
                        @endphp

                        @foreach($postsArray as $index => $post)
                            @if($index == 0)
                                {{-- مقاله اول (بزرگ) --}}
                                <div class="col-lg-7 col-12">
                                    <div class="blog-news-item h-100">
                                        <a href="{{ route('front.articles.show', ['slug' => $post->slug]) }}">
                                            <img src="{{ $post->image ? asset($post->image) : asset('/no-image-product.svg') }}" alt="{{ $post->title }}" class="img-fluid">
                                            <div class="desc">
                                                <h3 class="title">
                                                    @if($post->post_type=="video")
                                                        <i class="bi bi-play-circle-fill main-color-one-color me-1"></i>
                                                    @elseif($post->post_type=="podcast")
                                                        <i class="bi bi-soundwave main-color-one-color me-1"></i>
                                                    @endif
                                                    {{ $post->title }}
                                                </h3>
                                                <div class="date">
                                                    <span class="text-date">{{ jdate($post->created_at)->format('d F Y') }}</span>
                                                    <i class="bi bi-arrow-left"></i>
                                                </div>
                                            </div>
                                        </a>
                                        {{-- متا (خارج از لینک کارت) --}}
                                        <div class="d-flex align-items-center justify-content-between px-3 pb-3 flex-wrap gap-1">
                                            @if($post->categories())
                                                <a class="font-12 main-color-one-color text-decoration-none" href="{{ route('front.articles.index').'?cat='.$post->categories()->first()->slug }}">
                                                    {{ $post->categories()->first()->title }}
                                                </a>
                                            @endif
                                            <div class="d-flex align-items-center">
                                                <img class="rounded-circle me-2" src="{{ $post->admin->imageUrl }}" alt="{{ $post->admin->full_name ?? '' }}" width="28" height="28" loading="lazy">
                                                <a class="text-muted font-12" href="{{ route('front.articles.index', ['profile' => $post->admin->username]) }}">{{ $post->admin->full_name ?? 'ناشناس' }}</a>
                                            </div>
                                            <span class="text-muted font-12"><i class="bi bi-eye me-1"></i>{{ $post->view }} بازدید</span>
                                        </div>
                                        @if($post->summary)
                                            <p class="text-muted mt-1 mb-0 px-3 pb-3 text-overflow-3">{!! Str::limit($post->summary, 200) !!}</p>
                                        @endif
                                    </div>
                                </div>

                                {{-- شروع مقالات کوچک --}}
                                @if($postsArray->count() > 1)
                                    <div class="col-lg-5 col-12">
                                        <nav class="navbar">
                                            <ul class="navbar-nav">
                                                @foreach($postsArray as $smallIndex => $smallPost)
                                                    @if($smallIndex > 0)
                                                        <li class="nav-item">
                                                            <a href="{{ route('front.articles.show', ['slug' => $smallPost->slug]) }}" class="nav-link">
                                                                <div class="mini-blog-item">
                                                                    <div class="image">
                                                                        <img src="{{ $smallPost->image ? asset($smallPost->image) : asset('/no-image-product.svg') }}" class="img-fluid" alt="{{ $smallPost->title }}" loading="lazy">
                                                                    </div>
                                                                    <div class="d-flex align-items-start desc flex-wrap flex-column justify-content-between">
                                                                        <h5 class="title">
                                                                            @if($smallPost->post_type=="video")
                                                                                <i class="bi bi-play-circle-fill main-color-one-color me-1"></i>
                                                                            @elseif($smallPost->post_type=="podcast")
                                                                                <i class="bi bi-soundwave main-color-one-color me-1"></i>
                                                                            @endif
                                                                            {{ $smallPost->title }}
                                                                        </h5>
                                                                        <div class="d-flex align-items-center">
                                                                            <p class="text-muted mb-0">{{ $smallPost->admin->full_name ?? 'ناشناس' }}</p>
                                                                            <p class="text-muted mb-0 ms-2">{{ jdate($smallPost->created_at)->format('d F Y') }}</p>
                                                                            <div class="text-end ms-2"><i class="bi bi-arrow-left main-color-one-color"></i></div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </a>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </nav>
                                    </div>
                                @endif
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
