@php
    $variables      = get_widget($widget);
    $products       = $variables['products'];
@endphp

<!-- Start products -->
@if ($products->count())
    <!--    Slider-sidebar------------------->
    <div class="col-lg-2 col-md-12 col-xs-12 pull-left">
        <div class="slider-sidebar">
            <div class="widget-suggestion widget card">
                @if($widget->option('title'))
                    <header class="card-header promo-single-headline">
                        <h3 class="card-title">{{ $widget->option('title') }}</h3>
                    </header>
                @endif

                <div id="progressBar">
                    <div class="slide-progress" style="width: 100%; transition: width 5000ms ease 0s;"></div>
                </div>
                <div id="suggestion-slider" class="owl-carousel owl-theme owl-rtl owl-loaded owl-drag owl-nav-disabled">
                    <div class="owl-stage-outer">
                        <div class="owl-stage"
                             style="transform: translate3d(1369px, 0px, 0px); transition: all 0.25s ease 0s; width: 2190px;">
                            @php $i=1; @endphp
                            @foreach($products as $product)
                            <div class="owl-item @if($i==1 or $i==2)cloned @endif" style="width: 273.75px;">
                                <div class="item">
                                    <a href="{{ route('front.products.show', ['product' => $product]) }}" class="image-data-src">
                                        <img class="w-100" data-src="{{ $product->image ? asset($product->image) : asset('/no-image-product.svg') }}" src="{{ theme_asset('images/600-600.png') }}" alt="{{ $product->title }}">
                                    </a>
                                    <h3 class="product-title">
                                        <a href="{{ route('front.products.show', ['product' => $product]) }}">{{ $product->title }}</a>
                                    </h3>
                                    <div class="price">
                                        @if($product->getLowestDiscount())
                                        <del><span class="amount">{{ $product->getLowestDiscount() }}</span></del>
                                        @endif
                                        <span class="amount">{{ $product->getLowestPrice() }}{{--<span>تومان</span>--}}</span>
                                    </div>
                                </div>
                            </div>
                                @php $i++; @endphp
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--    Slider-sidebar------------------->

@endif
<!-- End products -->
