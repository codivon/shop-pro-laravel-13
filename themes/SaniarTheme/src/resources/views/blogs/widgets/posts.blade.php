@php
    $variables      = get_widget($widget);
    $posts          = $variables['blogs'];
@endphp

<!-- Start blogs -->
@if ($posts->count())
    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <section class="slider-section dt-sl mb-3">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="section-title text-sm-title title-wide no-after-title-wide">
                            <h2>{{ $widget->option('title') }}</h2>
                            @if ($widget->option('link'))
                                <a href="{{ $widget->option('link') }}">{{ $widget->option('link_title', 'مشاهده همه') }}</a>
                            @endif
                        </div>
                    </div>

                    <div class="col-12 px-res-0">
                        <div class="product-carousel carousel-md owl-carousel owl-theme posts-widget-owl">
                            @foreach ($posts as $post)
                                <div class="item">
                                    <div class="post-card mb-0">
                                        <div class="post-thumbnail">
                                            <a href="{{ route('front.articles.show', ['slug' => $post->slug]) }}">
                                                <img src="{{ asset('/no-image-product.svg') }}" data-src="{{ $post->image ? asset($post->image) : theme_asset('/no-image-product.svg') }}" alt="{{ $post->title }}">
                                            </a>
                                            <span class="post-tag">{{ $post->category ? $post->category->title : 'دسته بندی نشده' }}</span>

                                        </div>
                                        <div class="post-title">
                                            <a href="{{ route('front.articles.show', ['slug' => $post->slug]) }}">{{ $post->title }}</a>
                                            <span class="post-date">{{ jdate($post->created_at)->format('%d %B %Y') }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endif
<!-- End blogs -->
