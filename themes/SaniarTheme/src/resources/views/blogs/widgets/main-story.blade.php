@push('styles')
    <link rel="stylesheet" href="{{theme_asset('css/main-story.css')}}" />
    <link rel="stylesheet" href="{{theme_asset('js/plugins/plyr/plyr.css')}}" />
@endpush


@php
    $variables          = get_widget($widget);
    $stories   = $variables['main_story'];
       $storySeen = Illuminate\Support\Facades\Cookie::get('story_seen');
    $storySeen = json_decode($storySeen, true);
    if(empty($storySeen)){
        $storySeen = [];
    }
@endphp
    <!-- Start Partners -->
@if ($stories->count())

    <div class="col-lg-12 col-md-12 col-xs-12 pull-right mt-3">
        <div class="row">
            <div class="col-12">
                <div class="widget widget-product card">
                    @if($widget->option('title'))
                        <header class="card-header">
                            <span class="title-one">{{$widget->option('title')}}</span>
                        </header>
                    @endif

                    <div class="product-carousel-story owl-carousel owl-theme owl-rtl owl-loaded owl-drag allStoryIndex"
                         data-action='{{route('front.setStorySeen')}}' data-action-interaction="{{route('front.setStoryInteraction')}}">
                        <div class="owl-stage-outer">
                            <div class="owl-stage d-flex">
                                @foreach ($stories as $story)
                                    @if($story->expiry_date > now())
                                        <div class="owl-item {{$loop->index<=10 ? 'active' : ''}}">
                                            <div data-toggle="modal" data-target="#story-modal" id='{{$story->id}}'
                                                 class="item storyItem @if(in_array($story->id, $storySeen)) unActive @endif">
                                                <a class="image-data-src">
                                                    <img class="img-fluid"
                                                         data-src="{{ $story->cover_image ? asset($story->cover_image) : asset('/no-image-product.svg') }}"
                                                         src="{{ theme_asset('images/600-600.png') }}"
                                                         alt="{{ $story->title }}">
                                                </a>

                                            </div>
                                            <span
                                                class="font-size-12">{{ \Illuminate\Support\Str::limit($story->title, 35) }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endif
<!-- End Partners -->

@push('scripts')

    <script src="{{theme_asset('js/main-story.js')}}"></script>

    <div class="modal fade" id="story-modal" tabindex="-1" aria-labelledby="price-changes-modal-label"
         aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-body chart-area">
                    <div class="main-slider-container">
                        <div id="carouselExampleIndicatorsStory" class="carousel " data-ride="carousel"
                             data-interval="false">
                            <div class="stories-progress-bars" id="storyProgressBars"></div>
                            <div class="carousel-inner">
                                @foreach($stories as $index => $story)
                                    @if($story->expiry_date > now())
                                        @php
                                            $storyDuration = 10000;
                                            if($story->type != 'video' && !empty($story->duration)) {
                                               $storyDuration = $story->duration * 1000;
                                            } elseif($story->type == 'video') {
                                                 $storyDuration = 0;
                                            }
                                        @endphp
                                        <div class="carousel-item @if ($loop->first)active @endif" id='story-{{$story->id}}'
                                             data-story-id="{{$story->id}}" data-duration="{{$storyDuration}}"
                                             data-type="{{$story->type}}">

                                                <?php
                                                $pathInfo = pathinfo($story->image);
                                                ?>


                                            <div class="story-header">
                                                <div class="story-progress-bars" data-story-id="{{$story->id}}"></div>
                                                <div class="story-counter">
                                                    {{$index + 1}} / {{count($stories)}}
                                                </div>

                                                <div class="user-info">
                                                    <div class="user-avatar">
                                                        @if($story->cover_image)
                                                            <img src="{{asset($story->cover_image)}}" alt="avatar">
                                                        @else
                                                            <i class="fa fa-store"></i>
                                                        @endif
                                                    </div>
                                                    <div class="user-details">
                                                        <div class="user-name">{{$story->title}}</div>
                                                        <div
                                                            class="story-time">{{$story->updated_at ? $story->updated_at->diffForHumans() : 'چند لحظه پیش'}}</div>
                                                    </div>
                                                </div>
                                                <button class="close-story" data-dismiss="modal">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>


                                            <div class="story-content {{$story->product_id ? 'story-content-with-video':''}}">
                                                @if($story->type=="video")
                                                    <video id='video-{{$story->id}}' autoplay controls class='w-100 story-video-player'>
                                                        <source src="{{$story->video}}" type="video/mp4">
                                                    </video>
                                                    <div class="story-video-sound" data-story-id="{{$story->id}}"><i class="fa-solid fa-volume-up"></i></div>
                                                @else
                                                    <img class="d-block w-100" onloadeddata='seenStory({{$story->id}})'
                                                         src="{{asset($story->image)}}"
                                                         alt="{{$story->title ?: $story->image}}">
                                                @endif

                                            </div>


                                            <div class="story-footer">
                                                <ul class="story-likes-comments"
                                                    style="{{$story->product_id ? 'bottom: 90%' :'bottom:0'}}">
                                                    @if($story->active_likes)
                                                        <li class="story-likes {{$story->isLikedByUser() ? 'liked' : ''}}" data-action="{{route('front.setStoryLike')}}"><i
                                                                class="fa fa-heart"></i><span>{{$story->likes_count}}</span>
                                                        </li>
                                                    @endif
                                                    @if($story->active_comments)
                                                        <li class="story-comments"><i
                                                                class="fa fa-comment"></i><span>0</span></li>
                                                    @endif




                                                </ul>

                                                @if($story->widget_title and $story->widget_link)
                                                    <a href="{{$story->widget_link}}" target="_blank" id="liveWidget"
                                                       class="story-widget-area" style="">
                                                        <span id="liveWidgetTitle">{{$story->widget_title}}</span>
                                                        <i class="fa-solid fa-link"></i>
                                                    </a>
                                                @endif

                                                @if($story->product_id)

                                                    @php
                                                        $product = $story->product;
                    $color = $product->getPrices()->first()->get_attributes()->first();
                    $product = [
                        'link' => route('front.products.show', ['product' => $story->product]),
                        'title' => $product->title,
                        'price' => number_format($product->getPrices()->first()->discount_price) . ' تومان ',
                        'discount' => $product->getPrices()->first()->discount ? $product->getPrices()->first()->discount . '%' : '',
                        'image' => asset($product->image),
                        'color' => [
                            'name' => $color->name,
                            'value' => $color->value,
                        ]
                    ];
                                                    @endphp
                                                    <div class="story-product card">
                                                        <a href="{{$product['link']}}" target="_blank"
                                                           class="card-body d-flex align-items-center">
                                                            <div class="image"><img
                                                                    src="{{$product['image']}}"
                                                                    alt="{{$product['title']}}">
                                                            </div>
                                                            <div class="meta d-flex align-items-center flex-column">
                                                                <div class="title clickable lts-05"
                                                                     title="{{$product['title']}}">
                                                                    {{$product['title']}}
                                                                </div>
                                                                @if($product['discount']!="")
                                                                    <div
                                                                        class="discount-percent shadow-secondary shadow-1 me-2">
                                                                        {{$product['discount']}}
                                                                    </div>
                                                                @endif

                                                                <div
                                                                    class="w-100 d-flex align-items-center justify-content-between">
                                                                    <ul class="product-colors">
                                                                        <li class="" data-pd-tooltip="true"
                                                                            title="{{$product['color']['name']}}"
                                                                            style="background-color: {{$product['color']['value']}};"></li>
                                                                    </ul>
                                                                    <div class="d-inline-flex align-items-center">


                                                                        <div
                                                                            class="d-flex flex-column justify-content-end">
                                                                            <span
                                                                                class="product-price-now fw-bold lts-05">{{$product['price']}}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </a>
                                                    </div>
                                                @endif


                                            </div>


                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            @if(count($stories)>1)
                                <a class="carousel-control-prev" role="button"
                                   data-slide="prev">
                                    <span class="fa fa-angle-left" aria-hidden="true"></span>
                                    <span class="sr-only">Previous</span>
                                </a>
                                <a class="carousel-control-next" role="button"
                                   data-slide="next">
                                    <span class="fa fa-angle-right" aria-hidden="true"></span>
                                    <span class="sr-only">Next</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{theme_asset('js/plugins/plyr/plyr.js')}}"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const player = new Plyr('.story-video-player', {
            controls: ['current-time'],
            settings: ['captions', 'quality', 'speed']
        });
    });
</script>
@endpush
