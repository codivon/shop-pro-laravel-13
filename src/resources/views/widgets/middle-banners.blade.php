{{-- بنرهای میانی سانیار (۴ ستون) --}}
@php
    $variables = get_widget($widget);
    $banners = $variables['index_middle_banners'];
@endphp

@if(count($banners))
    <div class="banner py-20">
        <div class="container-fluid">
            <div class="row g-3">
                @foreach($banners as $banner)
                    <div class="col-lg-3 col-6">
                        <a href="{{ $banner->link ?: '#' }}">
                            <img src="{{ asset($banner->image) }}" class="img-fluid rounded-3 shadow-box" alt="{{ $banner->title ?: 'بنر' }}">
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
