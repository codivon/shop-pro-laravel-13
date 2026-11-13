@php
    $variables      = get_widget($widget);
    $posts          = $variables['posts'];
@endphp

    <!-- Start blogs -->
@if ($posts->count())
    <div class="   col-lg-12 col-md-12 col-xs-12 pull-right mt-3" style="margin-top: 20px; margin-bottom: 35px;">
        <div class="widget widget-product card blog-article-image-box-container p-1">
            @if($widget->option('title'))
                <div
                    class="blog-article-image-box--header d-flex align-items-center justify-content-between mb-3">
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
            <div class="promotion-categories-container grid-type-one-container card shadow-1">
                <div class="card-body">
                    <div class="row">
                        @php
                            $postsArray = $posts->values(); // تبدیل به آرایه با ایندکس 0 شروع
                        @endphp

                        @foreach($postsArray as $index => $post)
                            @if($index == 0)
                                {{-- مقاله اول (بزرگ) --}}
                                <div class="col-lg-7 col-12 big-article-container">
                                    <div class="article">
                                        <div class="grid-thumbnail">
                                            <div class="grid-thumbnail--level-one">
                                                <div class="grid-thumbnail--level-two">
                                                    <div class="image" style="background-image: url('{{ $post->image ? asset($post->image) : theme_asset('/no-image-product.svg') }}');"></div>
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
                                            <a class="article-link" href="{{ route('front.articles.show', $post->slug) }}"></a>
                                            @if($post->categories())
                                                <a class="category-badge shadow-2" href="{{ route('front.articles.index'). '?cat='.$post->categories()->first()->slug }}">
                                                    {{ $post->categories()->first()->title }}
                                                </a>
                                            @endif
                                        </div>
                                        <ul class="article--meta mt-3 mb-0">
                                            <li>
                                                <img src="{{$post->admin->imageUrl}}"
                                                     alt="{{ $post->admin->full_name ?? '' }}"
                                                     class="article--meta-avatar shadow-1 ml-2" height="32" width="32" loading="lazy">
                                                <a class="article--meta-username" href="{{ route('front.articles.index', ['profile' => $post->admin->username]) }}">
                                                    {{ $post->admin->full_name ?? 'ناشناس' }}
                                                </a>
                                            </li>
                                            <li class="divider"></li>
                                            <li><span class="date">{{ jdate($post->created_at)->format('d F Y') }}</span></li>
                                            <li class="divider"></li>
                                            <li><span class="date">{{ $post->view }} بازدید</span></li>
                                        </ul>
                                        <h3 class="article--title mb-1 mt-2">
                                            <a href="{{ route('front.articles.show', $post->slug) }}">{{ $post->title }}</a>
                                        </h3>
                                        @if($post->summary)
                                                <p  class="article--excerpt mb-0">{!! Str::limit($post->summary, 200) !!}</p>
                                        @endif


                                    </div>
                                </div>

                                {{-- شروع مقالات کوچک --}}
                                @if($postsArray->count() > 1)
                                    <div class="col-lg-5 col-12 small-article-container">
                                        @foreach($postsArray as $smallIndex => $smallPost)
                                            @if($smallIndex > 0)
                                                <div class="small-article">
                                                    <div class="grid-thumbnail">
                                                        <div class="grid-thumbnail--level-one">
                                                            <div class="grid-thumbnail--level-two">
                                                                <div class="image" style="background-image: url('{{ $smallPost->image ? asset($smallPost->image) : theme_asset('/no-image-product.svg') }}');"></div>
                                                            </div>
                                                            @if($smallPost->post_type=="video")
                                                                <div class="post-grid-thumbnail-video-type small">
                                                                    <i class=" fas fa-play" style="color:red"></i>
                                                                </div>
                                                            @endif
                                                            @if($smallPost->post_type=="podcast")
                                                                <div class="post-grid-thumbnail-video-type small">
                                                                    <i class=" fa fa-headphones" style="color:red"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <a class="article-link" href="{{ route('front.articles.show', $smallPost->slug) }}"></a>
                                                    </div>
                                                    <div class="small-article--details">
                                                        <h3>
                                                            <a href="{{ route('front.articles.show', $smallPost->slug) }}">{{ $smallPost->title }}</a>
                                                        </h3>
                                                        <ul class="article--meta">
                                                            <li>
                                                                <span class="by"></span>
                                                                <a class="article--meta-username" href="{{ route('front.articles.index', ['profile' => $smallPost->admin->username]) }}">
                                                                    {{ $smallPost->admin->full_name ?? 'ناشناس' }}
                                                                </a>
                                                            </li>
                                                            <li class="divider"></li>
                                                            <li><span class="date">{{ jdate($smallPost->created_at)->format('d F Y') }}</span></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
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
<!-- End blogs -->
