@php
    $variables = get_widget($widget);
    $sliders = $variables['coworker_sliders'] ?? collect();
@endphp
@if(count($sliders))
    <div class="service py-20">
        <div class="container-fluid">
            <div class="row gy-3 justify-content-between align-items-center">
                <div class="col-lg-3">
                    <div class="service-title">
                        <h6 class="font-25 text-white mb-3">
                            بیش از {{ number_format(\App\Models\Product::published()->count()) }} محصول
                        </h6>
                        <p class="mb-0 text-white font-13">برترین برندهای اصلی و خارجی</p>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="row gy-2 gx-2 service-logo justify-content-md-end justify-content-center align-items-center">
                        @foreach($sliders as $slider)
                            <div class="col-md-2 col-4 item">
                                <a href="{{ $slider->link }}">
                                    <img src="{{ asset($slider->image) }}" alt="{{ $slider->title ?? '' }}" class="img-fluid" loading="lazy">
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
