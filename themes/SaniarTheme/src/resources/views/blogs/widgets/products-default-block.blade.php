@php
    $variables      = get_widget($widget);
    $products       = $variables['products'];
    $widget_1=$widget->ordering;
    $css_col_lg_10=[];
      $shortCode=false;
    if (isset($widget->shortcode)){
        $shortCode=true;
    }

@endphp

    <!-- Start products -->
@if($widget->key=="products-moment-block")

    @if($widget_1==$widget->ordering && $shortCode==true)

        @php
            $variables      = get_widget($widget);
            $products       = $variables['products'];
            $css_col_lg_10='col-lg-10'
        @endphp
        @include('front::widgets.products-moment-block')
        <div class="col-lg-10 col-md-12 col-xs-12 pull-right mt-2">
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
                                <div class="owl-stage"
                                     style="transform: translate3d(0px, 0px, 0px); transition: all 0s ease 0s; width: 2234px;">
                                    @php $i=1; @endphp
                                    @foreach($products as $product)
                                        @include('front::partials.product-block')
                                    @endforeach

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

@else
    @if ($products->count())
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
                                <div class="owl-stage"
                                     style="transform: translate3d(0px, 0px, 0px); transition: all 0s ease 0s; width: 2234px;">
                                    @php $i=1; @endphp
                                    @foreach($products as $product)
                                        @include('front::partials.product-block')
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
@endif

<!-- End products -->
