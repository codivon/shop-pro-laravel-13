{{-- بنرهای میانی سانیار (۴ بنر عرضی) — ویجت‌ساز وبلاگ --}}
@if($widget)
    @php
        $variables               = get_widget($widget);
        $index_middle_4_banners  = $variables['index_middle_4_banners'];
    @endphp

    @if ($index_middle_4_banners->count())
        <div class="banner py-20">
            <div class="container-fluid">
                <div class="row g-3">
                    @php $i=1; @endphp
                    @foreach ($index_middle_4_banners as $banner)
                        @if(in_array($widget->option('place'), $banner->places ?? []))
                            @if($i<=$widget->option('number'))
                                <div class="col-12 col-md-6">
                                    <a href="{{ $banner->link }}" title="{{ $banner->title }}">
                                        <img src="{{ asset($banner->image) }}" alt="{{ $banner->title }}" class="img-fluid rounded-3 shadow-box w-100">
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
