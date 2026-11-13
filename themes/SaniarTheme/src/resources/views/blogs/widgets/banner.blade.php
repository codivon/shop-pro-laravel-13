@php
    $variables      = get_widget($widget);
    $banners=$variables['banner'];
@endphp
<!--    Start Main Slider -------------------->
@if (count($banners))
    @foreach($banners as $banner)
    <div class="col-12">
        <aside class="adplacement-header">
            <a style="background: url('{{asset($widget->option('image'))}}');" href="{{$widget->option('link')}}" class="adplacement-item" title="{{$banner->title}}"></a>
        </aside>
    </div>
    @endforeach
@endif
