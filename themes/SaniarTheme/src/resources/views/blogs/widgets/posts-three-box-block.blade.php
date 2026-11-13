@php
    $variables      = get_widget($widget);
    $posts          = $variables['posts'];
@endphp

    <!-- Start blogs -->
@if ($posts->count())
    <div class="col-lg-12 col-md-12 col-xs-12 pull-right mt-3 " style="margin-top: 20px; margin-bottom: 35px;">
        <div class="widget widget-product card blog-article-image-box-container p-1">
            @if($widget->option('title'))
                <div class="blog-article-image-box--header d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center">
                        <div class="title">{{$widget->option('title')}}</div>
                        @if($widget->option('link_title') and $widget->option('link'))
                            <a class="more text-white bg-secondary badge shadow-secondary el-center fs-9"
                               href="{{$widget->option('link')}}">{{$widget->option('link_title')}} <i
                                    class="fa fa-angle-left mr-2"></i>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
            <div class="grid-post-container post-three-box-container">
                <div class="row">
                    @forelse($posts as $index => $post)
                        @if($index == 0)
                            {{-- پست اول (بزرگ) --}}
                            <div class="col-lg-7 col-12  right-big">
                                <div class="post-grid shadow-1">
                                    <div class="post-grid-thumbnail">
                                        <div class="post-grid-thumbnail--level-one">
                                            <div class="post-grid-thumbnail--level-two">
                                                <div class="image"
                                                     style="background-image: url('{{ asset($post->image) }}');"></div>
                                            </div>
                                        </div>

                                        @if($post->post_type=="video")
                                            <div class="post-grid-thumbnail-video-type">
                                                <i class=" fas fa-play" style="color:red"></i>
                                            </div>
                                        @endif
                                        @if($post->post_type=="podcast")
                                            <div class="post-grid-thumbnail-video-type">
                                                <i class=" fa fa-headphones" style="color:red"></i>
                                            </div>
                                        @endif


                                    </div>
                                    <a class="post-grid-link" href="{{ route('front.articles.show', $post->slug) }}"></a>
                                    <div class="post-grid-details card shadow-1">
                                        @if($post->categories())
                                            <a class="post-grid-details--category link color-secondary"
                                               href="{{ route('front.articles.index'). '?cat='.$post->categories()->first()->slug }}">
                                                {{ $post->categories()->first()->title }}
                                            </a>
                                        @endif
                                        <h3 class="post-grid-details--title">
                                            <a href="{{ route('front.articles.show', $post->slug) }}">{{ $post->title }}</a>
                                        </h3>
                                        <div class="post-grid-details--footer">
                                            <div class="post-grid-details--author">
                                                    <img class="shadow-smooth-1" src="{{$post->admin->imageUrl}}"
                                                         alt="{{ $post->admin->full_name ?? 'ناشناس' }}" loading="lazy">
                                                توسط <a
                                                    href="{{ route('front.articles.index', ['profile' => $post->admin->username]) }}">{{ $post->admin->full_name ?? 'ناشناس' }}</a>
                                            </div>
                                            <div class="post-grid-details--stats">
                                                <span class="lts-05">{{ jdate($post->created_at)->format('d F Y') }}</span>
                                                <span class="divider"></span>
                                                <span class="lts-05">{{ number_format($post->view) }} بازدید</span>
                                            </div>


                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- پست‌های بعدی (کوچک) --}}
                            @if($posts->count() > 1)
                                <div class="col-lg-5 col-12 left-small">
                                    @foreach($posts->skip(1) as $smallPost)
                                        <div class="post-grid shadow-1">
                                            <div class="post-grid-thumbnail">
                                                <div class="post-grid-thumbnail--level-one">
                                                    <div class="post-grid-thumbnail--level-two">
                                                        <div class="image"
                                                             style="background-image: url('{{ asset($smallPost->image) }}');"></div>
                                                    </div>

                                                </div>
                                                @if($smallPost->post_type=="video")
                                                    <div class="post-grid-thumbnail-video-type minimal">
                                                        <i class=" fas fa-play" style="color:red"></i>
                                                    </div>
                                                @endif
                                                @if($smallPost->post_type=="podcast")
                                                    <div class="post-grid-thumbnail-video-type minimal">
                                                        <i class=" fa fa-headphones" style="color:red"></i>
                                                    </div>
                                                @endif

                                            </div>
                                            <a class="post-grid-link"
                                               href="{{ route('front.articles.show', $smallPost->slug) }}"></a>
                                            <div class="post-grid-details card shadow-1">
                                                @if($smallPost->categories())
                                                    <a class="post-grid-details--category link color-secondary"
                                                       href="{{ route('front.articles.index'). '?cat='.$smallPost->categories()->first()->slug }}">
                                                        {{ $smallPost->categories()->first()->title }}
                                                    </a>
                                                @endif
                                                <h3 class="post-grid-details--title">
                                                    <a href="{{ route('front.articles.show', $smallPost->slug) }}">{{ $smallPost->title }}</a>
                                                </h3>
                                            </div>
                                        </div>
                                    @endforeach
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
    </div>
@endif
<!-- End blogs -->
