{{-- بنرهای میانی سانیار (۴ ستون) — ویجت‌ساز وبلاگ --}}
@php
    $variables            = get_widget($widget);
    $index_middle_banners = $variables['index_middle_banners'];
@endphp

@if ($index_middle_banners->count())
    <div class="banner py-20">
        <div class="container-fluid">
            <div class="row g-3">
                @foreach ($index_middle_banners as $banner)
                    @if(in_array($widget->option('place'), $banner->places ?? []))
                        <div class="col-12">
                            <a href="{{ $banner->link }}">
                                <img src="{{ $banner->image ? asset($banner->image) : asset('/no-image-product.svg') }}" alt="{{ $banner->title }}" class="img-fluid rounded-3 shadow-box w-100">
                            </a>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@endif
