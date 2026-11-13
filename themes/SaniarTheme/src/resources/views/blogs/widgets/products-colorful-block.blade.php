@php
    $variables      = get_widget($widget);
    $products       = $variables['products'];
@endphp

@if ($products->count())
    <!--slider-amazing----------------------------->
    <section class="section-slider amazing-section mb-3 mt-4" style="background: {{$widget->option('block_color')?:'rgb(239, 57, 78)'}};">
        <div class="container-amazing">
            <div class="container-main">
                <div>
                    <div class="col-lg-3 display-md-none pull-right">
                        <div class="amazing-product text-center mt-5">
                            <a href="{{$widget->option('link')}}">
                                <img src="{{$widget->option('image') ?: theme_asset('images/amazing/amazing.png')}}" alt="{{$widget->option('title')}}">
                            </a>
                            <a href="{{$widget->option('link')}}" class="view-all-amazing-btn">
                                {{$widget->option('link_title', 'مشاهده همه')}}
                                <i class="uil uil-angle-left"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-9 col-md-12 pull-left">
                        <div class="section-slider-content">
                            <div class="section-slider-product slider-amazing mt-3">
                                <div class="widget widget-product card" style="margin:0;">
                                    <header class="card-header card-header-amazing">
                                        <span class="title-one">{{ $widget->option('title','پیشنهاد شگفت انگیز') }}</span>
                                        <a class="card-title">{{ $widget->option('link_title', 'مشاهده همه') }}</a>
                                    </header>
                                    <div class="product-carousel owl-carousel owl-theme owl-rtl owl-loaded owl-drag">
                                        <div class="owl-stage-outer">
                                            <div class="owl-stage" style="transform: translate3d(0px, 0px, 0px); transition: all 0s ease 0s; width: 2234px;">
                                                @php $i=1; @endphp
                                                @foreach ($products as $product)
                                                    @include('front::partials.product-block-2')
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--slider-amazing----------------------------->
@endif

