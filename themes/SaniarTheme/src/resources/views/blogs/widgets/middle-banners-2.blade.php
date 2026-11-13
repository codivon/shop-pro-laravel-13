{{-- بنرهای میانی سانیار (۲ ستون بزرگ) — ویجت‌ساز وبلاگ --}}
@if($widget)
    @php
        $variables               = get_widget($widget);
        $index_middle_2_banners  = $variables['index_middle_2_banners'];
    @endphp

    @if ($index_middle_2_banners->count())
        <div class="banner py-20">
            <div class="container-fluid">
                <div class="row g-3">
                    @php $i=1; @endphp
                    @foreach ($index_middle_2_banners as $banner)
                        @if(in_array($widget->option('place'), $banner->places ?? []))
                            @if($i<=$widget->option('number'))
                                <div class="col-lg-6 col-12">
                                    <a href="{{ $banner->link }}">
                                        <img src="{{ $banner->image ? asset($banner->image) : asset('/no-image-product.svg') }}" alt="{{ $banner->title }}" class="img-fluid rounded-3 shadow-box w-100">
                                    </a>
                                </div>
                                @php $i++; @endphp
                            @endif
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endif
