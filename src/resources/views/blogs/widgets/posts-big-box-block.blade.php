{{-- کادر مقالات باکس اول بزرگ سانیار — فاز ۱۶۸: بازطراحی مطابق نمونهٔ «جدیدترین مقالات» --}}
{{-- مقالهٔ بزرگ (تصویر + چیپ دسته + خلاصه + متا) + فهرست فشردهٔ مقالات کوچک --}}
@php
    $variables = get_widget($widget);
    $posts     = $variables['posts']->values();

    $bigBoxPost    = $posts->first();
    $bigBoxCat     = $bigBoxPost ? $bigBoxPost->category : null;
    $bigBoxExcerpt = $bigBoxPost
        ? trim(strip_tags((string) ($bigBoxPost->summary ?: $bigBoxPost->short_description ?? '')))
        : '';
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

            <div class="row gy-4">
                {{-- مقالهٔ اول (بزرگ) --}}
                <div class="{{ $posts->count() > 1 ? 'col-lg-8' : 'col-12' }} col-12">
                    <article class="snr-post-hero snr-post-hero-wide">
                        <a href="{{ route('front.articles.show', ['slug' => $bigBoxPost->slug]) }}" class="image" aria-label="{{ $bigBoxPost->title }}">
                            @if($bigBoxPost->image)
                                <img src="{{ asset($bigBoxPost->image) }}" alt="{{ $bigBoxPost->title }}" class="img-fluid">
                            @else
                                <span class="snr-no-image" role="img" aria-label="تصویری برای این مقاله ثبت نشده است">
                                    <i class="bi bi-images"></i>
                                </span>
                            @endif
                            @if($bigBoxCat)
                                <span class="snr-chip">
                                    <i class="bi bi-tag"></i>
                                    {{ $bigBoxCat->title }}
                                </span>
                            @endif
                            @if($bigBoxPost->post_type === 'video')
                                <span class="snr-play"><i class="bi bi-play-fill"></i></span>
                            @elseif($bigBoxPost->post_type === 'podcast')
                                <span class="snr-play"><i class="bi bi-soundwave"></i></span>
                            @endif
                        </a>
                        <div class="body">
                            <h3 class="title">
                                <a href="{{ route('front.articles.show', ['slug' => $bigBoxPost->slug]) }}">{{ $bigBoxPost->title }}</a>
                            </h3>
                            @if($bigBoxExcerpt)
                                <p class="excerpt">{{ \Illuminate\Support\Str::limit($bigBoxExcerpt, 220) }}</p>
                            @endif
                            <div class="meta">
                                @if($bigBoxPost->admin)
                                    <img class="snr-avatar" src="{{ $bigBoxPost->admin->imageUrl }}" alt="{{ $bigBoxPost->admin->full_name ?? 'نویسنده' }}" loading="lazy">
                                    <span class="name">{{ $bigBoxPost->admin->full_name ?? 'نویسنده' }}</span>
                                @else
                                    <i class="bi bi-person-circle snr-avatar-icon"></i>
                                    <span class="name">نویسنده</span>
                                @endif
                                <span class="dot"></span>
                                <span><i class="bi bi-calendar3"></i>{{ jdate($bigBoxPost->created_at)->format('d F Y') }}</span>
                                <span class="dot"></span>
                                <span><i class="bi bi-eye"></i>{{ number_format((int) $bigBoxPost->view) }} بازدید</span>
                                <a class="more ms-auto" href="{{ route('front.articles.show', ['slug' => $bigBoxPost->slug]) }}">
                                    ادامه مطلب
                                    <i class="bi bi-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                </div>

                {{-- فهرست فشردهٔ مقالات کوچک --}}
                @if($posts->count() > 1)
                    <div class="col-lg-4 col-12">
                        <div class="snr-post-list snr-post-list-compact">
                            @foreach($posts->skip(1) as $smallPost)
                                <article class="snr-post-item">
                                    <a href="{{ route('front.articles.show', ['slug' => $smallPost->slug]) }}" class="image" aria-label="{{ $smallPost->title }}">
                                        @if($smallPost->image)
                                            <img src="{{ asset($smallPost->image) }}" alt="{{ $smallPost->title }}" loading="lazy" decoding="async">
                                        @else
                                            <span class="snr-no-image snr-no-image-sm" role="img" aria-label="بدون تصویر"><i class="bi bi-images"></i></span>
                                        @endif
                                    </a>
                                    <div class="body">
                                        @if($smallPost->category)
                                            <a class="cat" href="{{ route('front.articles.index').'?cat='.$smallPost->category->slug }}">{{ $smallPost->category->title }}</a>
                                        @endif
                                        <h5 class="title">
                                            <a href="{{ route('front.articles.show', ['slug' => $smallPost->slug]) }}">{{ $smallPost->title }}</a>
                                        </h5>
                                        <div class="meta">
                                            <span><i class="bi bi-calendar3"></i>{{ jdate($smallPost->created_at)->format('d F Y') }}</span>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
