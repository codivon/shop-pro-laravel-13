@php
    $variables      = get_widget($widget);
    $posts       = $variables['posts'];
    $widget_1=$widget->ordering;
    $css_col_lg_10=[];
@endphp

@if ($posts->count())
    <div class="col-lg-12 col-md-12 col-xs-12 pull-right">

        <div class="row">
            <div class="col-12">
                <div class="widget widget-product card blog-article-image-box-container">
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


                    <div class="product-carousel owl-carousel owl-theme owl-rtl owl-loaded owl-drag posts-carousel">
                        <div class="owl-stage-outer">
                            <div class="owl-stage"
                                 style="transform: translate3d(0px, 0px, 0px); transition: all 0s ease 0s; width: 2234px;">
                                @php $i=1; @endphp
                                @foreach($posts as $post)
                                    <div class="owl-item @if($i==1 or $i==2 or $i==3 or $i==4)active @endif"
                                         style="width: 309.083px; margin-left: 10px;">
                                        <div class=" card article-image shadow-1">
                                            <img class="article-image--center-crop"
                                                 src="{{ $post->image ? asset($post->image) : asset('/no-image-product.svg') }}"
                                                 alt="{{ $post->title }}"
                                                 loading="lazy">

                                            @if($post->post_type=="video")
                                                <div class="post-grid-thumbnail-video-type minimal">
                                                    <i class=" fas fa-play" style="color:red"></i>
                                                </div>
                                            @endif
                                            @if($post->post_type=="podcast")
                                                <div class="post-grid-thumbnail-video-type minimal">
                                                    <i class=" fa fa-headphones" style="color:red"></i>
                                                </div>
                                            @endif

                                            <a class="article-link"
                                               href="{{ route('front.articles.show', $post) }}"></a>
                                            <div class="article--footer"><h3 class="article-title mb-2">
                                                    <a class="link" href="{{ route('front.articles.show', $post) }}">
                                                        {{ $post->title }}
                                                    </a>
                                                </h3>
                                                <ul class="article-meta">
                                                    <li>
                                                        <img
                                                            src="{{$post->admin->imageUrl}}"
                                                            alt="{{ $post->admin->full_name ?? 'ناشناس' }} " class="article--meta-avatar shadow-1 me-2"
                                                            height="32" width="32" loading="lazy"><a
                                                            class="article--meta-username lts-05"
                                                            href="{{ route('front.articles.index', ['profile' => $post->admin->username]) }}">{{ $post->admin->full_name ?? 'ناشناس' }}</a>
                                                    </li>
                                                    <li class="divider"></li>
                                                    <li><span class="date">{{ jdate($post->created_at)->format('d F Y') }}</span></li>
                                                </ul>
                                            </div>
                                            @if($post->categories())
                                                <span class="category-badge">
                                                <a class="link text-white" href="">{{$post->categories()->first()->title}}</a>
                                            </span>
                                            @endif

                                        </div>
                                    </div>
                                @endforeach

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--   slider-product-------------------->
@endif


<!-- End products -->
