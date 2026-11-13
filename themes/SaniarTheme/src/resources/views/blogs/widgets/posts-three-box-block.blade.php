{{-- سه‌بلاک مطالب وبلاگ سانیار (blog-news-item) — یک پست بزرگ + پست‌های کوچک --}}
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
                    <h5 class="font-16 ms-3">{{ $widget->option('title', 'منتخب') }} <span class="main-color-one-color d-inline-block">وبلاگ</span></h5>
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

            <div class="row gy-3">
                @forelse($posts as $index => $post)
                    @if($index == 0)
                        {{-- پست اول (بزرگ) --}}
                        <div class="col-lg-6 col-12">
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
                                        <img class="rounded-circle me-2" src="{{ $post->admin->imageUrl }}" alt="{{ $post->admin->full_name ?? 'ناشناس' }}" width="28" height="28" loading="lazy">
                                        <a class="text-muted font-12" href="{{ route('front.articles.index', ['profile' => $post->admin->username]) }}">{{ $post->admin->full_name ?? 'ناشناس' }}</a>
                                    </div>
                                    <span class="text-muted font-12"><i class="bi bi-eye me-1"></i>{{ number_format($post->view) }} بازدید</span>
                                </div>
                            </div>
                        </div>

                        {{-- پست‌های بعدی (کوچک) --}}
                        @if($posts->count() > 1)
                            <div class="col-lg-6 col-12">
                                <div class="row gy-3">
                                    @foreach($posts->skip(1) as $smallPost)
                                        <div class="col-lg-6 col-12">
                                            <div class="blog-news-item h-100">
                                                <a href="{{ route('front.articles.show', ['slug' => $smallPost->slug]) }}">
                                                    <img src="{{ $smallPost->image ? asset($smallPost->image) : asset('/no-image-product.svg') }}" alt="{{ $smallPost->title }}" class="img-fluid">
                                                    <div class="desc">
                                                        <h3 class="title">
                                                            @if($smallPost->post_type=="video")
                                                                <i class="bi bi-play-circle-fill main-color-one-color me-1"></i>
                                                            @elseif($smallPost->post_type=="podcast")
                                                                <i class="bi bi-soundwave main-color-one-color me-1"></i>
                                                            @endif
                                                            {{ $smallPost->title }}
                                                        </h3>
                                                        <div class="date">
                                                            <span class="text-date">{{ jdate($smallPost->created_at)->format('d F Y') }}</span>
                                                            <i class="bi bi-arrow-left"></i>
                                                        </div>
                                                    </div>
                                                </a>
                                                @if($smallPost->categories())
                                                    <a class="d-block px-3 pb-2 font-12 main-color-one-color text-decoration-none" href="{{ route('front.articles.index').'?cat='.$smallPost->categories()->first()->slug }}">
                                                        {{ $smallPost->categories()->first()->title }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif
                @empty
                    <div class="col-12 text-center">
                        <p>مقاله‌ای برای نمایش وجود ندارد.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endif
