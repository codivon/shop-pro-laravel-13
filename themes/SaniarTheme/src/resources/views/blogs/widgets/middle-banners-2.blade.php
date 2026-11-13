@if($widget)
    @php
        $variables              = get_widget($widget);
        $index_middle_2_banners   = $variables['index_middle_2_banners'];

    @endphp
<!-- Start Banner -->
@if ($index_middle_2_banners->count())

        <div class="adplacement">
            @php $i=1; @endphp
            @foreach ($index_middle_2_banners as $banner)
                @if(in_array($widget->option('place'), $banner->places ?? []))
                    @if($i<=$widget->option('number'))
            <div class="col-lg-6 col-xs-12 pull-right">
                <a href="{{ $banner->link }}" class="item-adplacement image-data-src">
                    <img data-src="{{ $banner->image ? asset($banner->image) : asset('/no-image-product.svg') }}" src="{{ theme_asset('images/600-600.png') }}" alt="{{ $banner->title }}">
                </a>
            </div>
                        @php $i++; @endphp
                    @endif
                @endif
            @endforeach
        </div>


@endif
<!-- End Banner -->
@endif
