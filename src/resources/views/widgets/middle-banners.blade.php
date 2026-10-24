@php
    $variables = get_widget($widget);
    $banners = $variables['index_middle_banners'] ?? collect();
@endphp
@if(count($banners))
    <div class="index-banner py-20">
        <div class="container-fluid">
            <div class="row gy-3">
                @foreach($banners->take((int) $widget->option('number', 1)) as $banner)
                    <div class="col-12">
                        <a href="{{ $banner->link }}">
                            <img src="{{ $banner->image ? asset($banner->image) : theme_asset('image/banner-4.webp') }}"
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
