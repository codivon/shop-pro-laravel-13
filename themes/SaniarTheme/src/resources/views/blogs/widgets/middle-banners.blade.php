@php
    $variables              = get_widget($widget);
    $index_middle_banners   = $variables['index_middle_banners'];
@endphp
<!-- Start Banner -->
@if ($index_middle_banners->count())
    @foreach ($index_middle_banners as $banner)
        @if(in_array($widget->option('place'), $banner->places ?? []))
            <div class="col-12">
                <div class="banner" style=" height: auto; @if($widget->ordering==1)margin-bottom:0 @endif">
                    <a href="{{$banner->link}}" class="image-data-src"><img data-src="{{ $banner->image ? asset($banner->image) : asset('/no-image-product.svg') }}" src="{{ theme_asset('images/600-600.png') }}" alt="{{ $banner->title }}"></a>
                </div>
            </div>
        @endif
    @endforeach
@endif
<!-- End Banner -->
