{{-- اسلایدر اصلی + بنرهای کنار سانیار — ویجت‌ساز وبلاگ --}}
@php
    $variables            = get_widget($widget);
    $main_sliders         = $variables['main_sliders'];
    $mobile_sliders       = $variables['mobile_sliders'];
    $index_slider_banners = $variables['index_slider_banners'];
@endphp

<div class="py-20">
    <div class="container-fluid">
        <div class="row gy-3 align-items-start">
            <div class="col-lg-8 col-12">
                <div id="blogMainCarousel" class="carousel slide full-width-carousel rounded-3 overflow-hidden shadow-box" data-bs-ride="carousel" data-bs-touch="true">
                    @if(count($main_sliders) > 1)
                        <ol class="carousel-indicators custom-indicators">
                            @foreach($main_sliders as $key => $slider)
                                <li data-bs-target="#blogMainCarousel" data-bs-slide-to="{{ $key }}" class="@if($loop->first) active @endif"></li>
                            @endforeach
                        </ol>
                    @endif

                    <div class="carousel-inner full-width-carousel-inner">
                        @foreach($main_sliders as $slider)
                            <div class="carousel-item full-width-carousel-item @if($loop->first) active @endif" data-bs-interval="5000">
                                {{-- فاز ۱۶۷ — اسلاید اول eager (LCP)، بقیه lazy --}}
                                <img class="d-block w-100 full-width-image"
                                     @if(!$loop->first) loading="lazy" decoding="async" @endif
                                     src="{{ asset($slider->image) }}"
                                     alt="{{ $slider->title ?: $slider->image }}">

                                @if($slider->title)
                                    <div class="carousel-caption d-none d-md-block custom-caption">
                                        <h2>{{ $slider->title }}</h2>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-12">
                <div class="row gy-3">
                    @foreach($index_slider_banners as $banner)
                        <div class="col-12">
                            <a href="{{ $banner->link }}" title="{{ $banner->title ?: $banner->image }}">
                                <img src="{{ asset($banner->image) }}" alt="{{ $banner->title ?: $banner->image }}" class="img-fluid rounded-3 shadow-box w-100">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
