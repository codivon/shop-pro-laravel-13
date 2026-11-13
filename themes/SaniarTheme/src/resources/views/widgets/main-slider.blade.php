@php
    $variables      = get_widget($widget);
    $main_sliders   = $variables['main_sliders'];
    $mobile_sliders = $variables['mobile_sliders'];
    $index_slider_banners = $variables['index_slider_banners'];
@endphp

<div class="d-block">
    <div class="col-lg-8 col-md-8 col-xs-12 pull-right mb-3">
        <div class="main-slider">
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
<!--    End Main Slider ---------------------->

<!--adplacement-------------------------------->
<div class="col-lg-4 col-md-4 col-xs-12 pull-left mb-3">
    <aside class="adplacement-container-column">
        @foreach($index_slider_banners as $banner)
        <a href="{{$banner->link}}" class="adplacement-item adplacement-item-column">
            <div class="adplacement-sponsored-box">
                <img src="{{asset($banner->image)}}" alt="{{$banner->title ?: $banner->image}}">
            </div>
        </a>
        @endforeach
    </aside>
</div>
<!--adplacement----------------
