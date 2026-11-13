{{-- بنر تک‌عرضی سانیار (ویجت‌ساز وبلاگ) --}}
@php
    $variables = get_widget($widget);
    $banners   = $variables['banner'];
@endphp

@if (count($banners))
    <div class="banner py-20">
        <div class="container-fluid">
            <div class="row g-3">
                @foreach($banners as $banner)
                    <div class="col-12">
                        <a href="{{ $widget->option('link', '#') }}" title="{{ $banner->title }}">
                            <img src="{{ asset($widget->option('image')) }}" class="img-fluid rounded-3 shadow-box w-100" alt="{{ $banner->title ?: 'بنر' }}">
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
