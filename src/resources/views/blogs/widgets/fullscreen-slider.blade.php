{{-- اسلایدر تمام‌عرض سانیار — ویجت‌ساز وبلاگ --}}
@php
    $variables      = get_widget($widget);
    $main_sliders   = $variables['fullscreen_slider'];
    $mobile_sliders = $variables['mobile_sliders'];
@endphp

<div class="full-width-slider-wrapper">
    <div id="mainCarousel" class="carousel slide full-width-carousel" data-ride="carousel" data-interval="5000" data-touch="true">
        @if(count($main_sliders) > 1)
            <ol class="carousel-indicators custom-indicators">
                @foreach($main_sliders as $key => $slider)
                    <li data-target="#mainCarousel" data-slide-to="{{ $key }}" class="@if($loop->first) active @endif"></li>
                @endforeach
            </ol>
        @endif

        <div class="carousel-inner full-width-carousel-inner">
            @foreach($main_sliders as $slider)
                <div class="carousel-item full-width-carousel-item @if($loop->first) active @endif">
                    <img class="d-block w-100 full-width-image"
                         src="{{ asset($slider->image) }}"
                         alt="{{ $slider->title ?: 'Slider Image' }}">

                    @if($slider->title || $slider->description)
                        <div class="carousel-caption d-none d-md-block custom-caption">
                            @if($slider->title)
                                <h2>{{ $slider->title }}</h2>
                            @endif
                            @if($slider->description)
                                <p>{{ $slider->description }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
