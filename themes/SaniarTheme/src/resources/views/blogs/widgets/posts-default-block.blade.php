{{-- گرید مطالب وبلاگ سانیار (gird-post-blog) — saniar blog-item --}}
@php
    $variables = get_widget($widget);
    $posts     = $variables['posts'];
@endphp

@if ($posts->count())
    <div class="gird-post-blog py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3">{{ $widget->option('title', 'آخرین مطالب') }} <span class="main-color-one-color d-inline-block">وبلاگ</span></h5>
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
        </div>

        <div class="container-fluid">
            <div class="row gy-3">
                @foreach($posts as $post)
                    <div class="col-lg-3 col-sm-6 col-12">
                        <div class="blog-item">
                            <a href="{{ route('front.articles.show', ['slug' => $post->slug]) }}">
                                <div class="image">
                                    <img src="{{ $post->image ? asset($post->image) : asset('/no-image-product.svg') }}" alt="{{ $post->title }}" class="img-fluid">
                                </div>
                                <div class="title">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="title-item">
                                            <i class="bi bi-tag me-1"></i>
                                            <span class="font-12">{{ $post->category ? $post->category->title : 'دسته بندی نشده' }}</span>
                                        </div>
                                        <div class="title-item">
                                            <i class="bi bi-alarm me-1"></i>
                                            <span class="font-12">{{ jdate($post->created_at)->format('d F Y') }}</span>
                                        </div>
                                    </div>
                                    <h4 class="font-16 text-overflow-1 h4">
                                        @if($post->post_type=="video")
                                            <i class="bi bi-play-circle-fill main-color-one-color me-1"></i>
                                        @elseif($post->post_type=="podcast")
                                            <i class="bi bi-soundwave main-color-one-color me-1"></i>
                                        @endif
                                        {{ $post->title }}
                                    </h4>
                                </div>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
