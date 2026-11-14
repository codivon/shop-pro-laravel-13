{{-- کادر مقالات 3 باکس سانیار — فاز ۱۶۸: بازطراحی مطابق نمونهٔ «مقالات تماشائی» --}}
{{-- مقالهٔ بزرگ (تصویر + چیپ دسته + خلاصه + متای نویسنده) + فهرست عمودی مقالات کوچک --}}
@php
    $variables = get_widget($widget);
    $posts     = $variables['posts']->values();
    $threeBoxCat = $posts->count() ? $posts->first()->category : null;
    $threeBoxExcerpt = $posts->count()
        ? trim(strip_tags((string) ($posts->first()->summary ?: $posts->first()->short_description ?? '')))
        : '';
@endphp

@if ($posts->count())
    <div class="py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3">{{ $widget->option('title', 'منتخب') }} <span class="main-color-one-color d-inline-block">مقالات</span></h5>
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
                {{-- مقالهٔ اصلی (بزرگ) --}}
                <div class="{{ $posts->count() > 1 ? 'col-lg-7' : 'col-12' }} col-12">
                    @php $post = $posts->first(); @endphp
                    <article class="snr-post-hero">
                        <a href="{{ route('front.articles.show', ['slug' => $post->slug]) }}" class="image" aria-label="{{ $post->title }}">
                            @if($post->image)
                                <img src="{{ asset($post->image) }}" alt="{{ $post->title }}" class="img-fluid">
                            @else
                                <span class="snr-no-image" role="img" aria-label="تصویری برای این مقاله ثبت نشده است">
                                    <i class="bi bi-images"></i>
                                </span>
                            @endif
                            @if($post->category)
                                <span class="snr-chip">
                                    <i class="bi bi-tag"></i>
                                    {{ $post->category->title }}
                                </span>
                            @endif
                            @if($post->post_type === 'video')
                                <span class="snr-play"><i class="bi bi-play-fill"></i></span>
                            @elseif($post->post_type === 'podcast')
                                <span class="snr-play"><i class="bi bi-soundwave"></i></span>
                            @endif
                        </a>
                        <div class="body">
                            <h3 class="title">
                                <a href="{{ route('front.articles.show', ['slug' => $post->slug]) }}">{{ $post->title }}</a>
                            </h3>
                            @if($threeBoxExcerpt)
                                <p class="excerpt">{{ \Illuminate\Support\Str::limit($threeBoxExcerpt, 190) }}</p>
                            @endif
                            <div class="meta">
                                @if($post->admin)
                                    <img class="snr-avatar" src="{{ $post->admin->imageUrl }}" alt="{{ $post->admin->full_name ?? 'نویسنده' }}" loading="lazy">
                                    <span class="name">{{ $post->admin->full_name ?? 'نویسنده' }}</span>
                                @else
                                    <i class="bi bi-person-circle snr-avatar-icon"></i>
                                    <span class="name">نویسنده</span>
                                @endif
                                <span class="dot"></span>
                                <span><i class="bi bi-calendar3"></i>{{ jdate($post->created_at)->format('d F Y') }}</span>
                                <span class="dot"></span>
                                <span><i class="bi bi-eye"></i>{{ number_format((int) $post->view) }} بازدید</span>
                                <a class="more ms-auto" href="{{ route('front.articles.show', ['slug' => $post->slug]) }}">
                                    ادامه مطلب
                                    <i class="bi bi-arrow-left"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                </div>

                {{-- فهرست مقالات کوچک --}}
                @if($posts->count() > 1)
                    <div class="col-lg-5 col-12">
                        <div class="snr-post-list">
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
                                            <span><i class="bi bi-eye"></i>{{ number_format((int) $smallPost->view) }} بازدید</span>
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
