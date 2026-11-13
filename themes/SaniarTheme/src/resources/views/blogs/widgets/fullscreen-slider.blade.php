@php
    $variables      = get_widget($widget);
    $main_sliders   = $variables['fullscreen_slider'];
    $mobile_sliders = $variables['mobile_sliders'];
@endphp

<!-- Start Main-Slider -->
<div class="row index-main-slider mb-3">

    <div class="col-lg-12 col-md-12 order-1">
        <div class="main-slider full-scrin-slider">
            <div class="main-slider-container">
                <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
                    @if(count($main_sliders)>1)
                    <ol class="carousel-indicators">
                        @for($i=1;$i<= count($main_sliders);$i++)
                        <li data-target="#carouselExampleIndicators" data-slide-to="{{$i}}" class="@if($i==0)active @endif"></li>
                        @endfor
                    </ol>
                    @endif
                    <div class="carousel-inner">
                        @foreach($main_sliders as $slider)
                        <div class="carousel-item @if ($loop->first)active @endif">
                            <img class="d-block w-100" src="{{asset($slider->image)}}" alt="{{$slider->title ?: $slider->image}}">
                        </div>
                        @endforeach
                    </div>
                    @if(count($main_sliders)>1)
                    <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button"
                       data-slide="prev">
                        <span class="fa fa-angle-left" aria-hidden="true"></span>
                        <span class="sr-only">Previous</span>
                    </a>
                    <a class="carousel-control-next" href="#carouselExampleIndicators" role="button"
                       data-slide="next">
                        <span class="fa fa-angle-right" aria-hidden="true"></span>
                        <span class="sr-only">Next</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
