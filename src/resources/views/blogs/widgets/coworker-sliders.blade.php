{{-- نوار برندها (service) سانیار — ویجت‌ساز وبلاگ --}}
@php
    $variables        = get_widget($widget);
    $coworker_sliders = $variables['coworker_sliders'];
@endphp

@if ($coworker_sliders->count())
    <div class="service">
        <div class="container-fluid">
            <div class="row gy-3 justify-content-between align-items-center">
                <div class="col-lg-3">
                    <div class="service-title">
                        <h6 class="font-25 text-white mb-3">
                            {{ $widget->option('title', option('info_site_title', 'فروشگاه سانیار')) }}
                        </h6>
                        <p class="mb-0 text-white font-13">برترین برندهای اصلی و خارجی</p>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="row gy-2 gx-2 service-logo justify-content-md-end justify-content-center align-items-center">
                        @foreach ($coworker_sliders as $slider)
                            <div class="col-md-2 col-4 item">
                                <a href="brands/{{ $slider->link }}">
                                    <img src="{{ $slider->image ? asset($slider->image) : asset('/no-image-product.svg') }}" alt="{{ $slider->title }}" class="img-fluid">
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
