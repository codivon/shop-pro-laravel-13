@php
    $variables          = get_widget($widget);
    $coworker_sliders   = $variables['coworker_sliders'];
@endphp

<!-- Start Partners -->
@if ($coworker_sliders->count())
    <div class="col-lg-12 col-md-12 col-xs-12 pull-right">
        <div class="row">
            <div class="col-12">
                <div class="widget widget-product card">
                    @if($widget->option('title'))
                        <header class="card-header">
                            <span class="title-one">{{$widget->option('title')}}</span>
                        </header>
                    @endif
                    <div class="product-carousel owl-carousel owl-theme owl-rtl owl-loaded owl-drag">
                        <div class="owl-stage-outer">
                            <div class="owl-stage" style="transform: translate3d(0px, 0px, 0px); transition: all 0s ease 0s; width: 2234px;">
                                @foreach ($coworker_sliders as $slider)
                                <div class="owl-item " style="width: 309.083px; margin-left: 10px;">
                                    <div class="item">
                                        <a href="brands/{{$slider->link}}" class="image-data-src">
                                            <img class="img-fluid" style="width:100px !important; height:100px !important;" data-src="{{ $slider->image ? asset($slider->image) : asset('/no-image-product.svg') }}" src="{{ theme_asset('images/600-600.png') }}" alt="{{ $slider->title }}">
                                        </a>
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

  {{--  <section class="slider-section dt-sl mb-3">
    <div class="row">
        <div class="col-12">
            <div class="brand-slider carousel-sm owl-carousel owl-theme">
                @foreach ($coworker_sliders as $slider)
                    <div class="item">
                        <img data-src="{{ $slider->image }}" class="img-fluid" alt="{{ $slider->title }}">
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>--}}
@endif
<!-- End Partners -->
