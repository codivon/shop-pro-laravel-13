@php
    // سانیار فاز K — بلوک سه‌کاره مقالات (blog-news-item مرجع):
    // کارت بزرگ وسط + دو کارت کوچک؛ داده‌ها از get_widget یا آرگومان پیش‌فرض
    if ($widget ?? null) {
        $variables = get_widget($widget);
        $posts = $variables['posts'] ?? collect();
    }
    $blockPosts = $posts ?? collect();
    $blockTitle = $fallbackTitle ?? ($widget?->option('title', 'مطالب وبلاگ') ?: 'مطالب وبلاگ');
@endphp
@if($blockPosts->isNotEmpty())
    <div class="blog-top-posts py-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <div class="header-content mb-4 d-flex align-items-center justify-content-between flex-wrap">
                        <div class="title-panel mb-0">
                            <h5 class="font-16 mb-0">{{ $blockTitle }}</h5>
                        </div>
                        <a href="{{ route('front.articles.index') }}" wire:navigate class="border-animate fromCenter pb-1 fw-bold font-14">
                            مشاهده همه
                            <i class="bi bi-chevron-double-left main-color-one-color"></i>
                        </a>
                    </div>

                    <div class="row gy-3">
                        @foreach($blockPosts->take(3) as $index => $post)
                            <div class="{{ $index == 1 ? 'col-md-6' : 'col-md-3' }}">
                                <div class="blog-news-item">
                                    <a href="{{ route('front.articles.show', ['slug' => $post->slug]) }}" wire:navigate>
                                        <img src="{{ $post->image ? asset($post->image) : asset('no-image-product.svg') }}"
                                             alt="{{ $post->title }}" class="img-fluid" loading="lazy">
                                        <div class="desc">
                                            @if($post->category)
                                                <span class="badge-category main-color-one-bg">{{ $post->category->title }}</span>
                                            @endif
                                            <h3 class="title">{{ $post->title }}</h3>
                                            <div class="date">
                                                <span class="text-date">{{ jdate($post->created_at)->format('d F Y') }}</span>
                                                <i class="bi bi-arrow-left"></i>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($blockPosts->count() > 3)
                        <div class="row gy-3 mt-1">
                            @foreach($blockPosts->skip(3)->take(6) as $post)
                                <div class="col-md-2">
                                    <div class="blog-mini-news-item">
                                        <a href="{{ route('front.articles.show', ['slug' => $post->slug]) }}" wire:navigate>
                                            <div class="image">
                                                <img src="{{ $post->image ? asset($post->image) : asset('no-image-product.svg') }}"
                                                     alt="{{ $post->title }}" class="img-fluid" loading="lazy">
                                            </div>
                                            <h4 class="title font-14 mt-2 mb-1 text-overflow-2">{{ $post->title }}</h4>
                                            <p class="text-muted font-12 mb-0">
                                                <i class="bi bi-alarm me-1"></i>{{ jdate($post->created_at)->format('d F Y') }}
                                            </p>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
