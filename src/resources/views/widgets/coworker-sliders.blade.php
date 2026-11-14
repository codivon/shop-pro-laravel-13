{{-- نوار برندها (service) سانیار — از گروه coworker_sliders --}}
@php
    $variables = get_widget($widget);
    $coworker_sliders = $variables['coworker_sliders'];
@endphp

@if(count($coworker_sliders))
    <div class="service">
        <div class="container-fluid">
            <div class="row gy-3 justify-content-between align-items-center">
                <div class="col-lg-3">
                    <div class="service-title">
                        <h6 class="font-25 text-white mb-3">
                            {{ option('info_site_title', 'فروشگاه سانیار') }}
                        </h6>
                        <p class="mb-0 text-white font-13">برترین برندهای اصلی و خارجی</p>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="row gy-2 gx-2 service-logo justify-content-md-end justify-content-center align-items-center">
                        @foreach($coworker_sliders as $coworker_slider)
                            <div class="col-md-2 col-4 item">
                                <a href="{{ $coworker_slider->link ?: '#' }}">
                                    <img loading="lazy" decoding="async" src="{{ asset($coworker_slider->image) }}" alt="{{ $coworker_slider->title }}" class="img-fluid">
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
