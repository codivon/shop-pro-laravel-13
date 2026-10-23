@php
    $variables = get_widget($widget);
    $banners = $variables['index_middle_4_banners'] ?? collect();
@endphp
@if(count($banners))
    <div class="index-banner py-20">
        <div class="container-fluid">
            <div class="row gy-3">
                @foreach($banners->take((int) $widget->option('number', 4)) as $banner)
                    <div class="col-lg-3 col-md-3 col-6">
                        <a href="{{ $banner->link }}">
                            <img src="{{ $banner->image ? asset($banner->image) : theme_asset('image/banner-four-1.jpg') }}"
                                 alt="{{ $banner->title }}"
                                 loading="lazy"
                                 class="img-fluid w-100 rounded-3">
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
