{{-- نوار ویژگی‌ها (feature) سانیار — ویجت‌ساز وبلاگ --}}
@php
    $variables       = get_widget($widget);
    $sevices_sliders = $variables['sevices_sliders'];
@endphp

@if ($sevices_sliders->count())
    <div class="feature">
        <div class="container-fluid">
            <div class="row justify-content-center g-2 row-cols-1 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-5">
                @foreach ($sevices_sliders as $slider)
                    <div class="col">
                        <a href="{{ $slider->link }}" class="feature-item d-flex align-items-center p-3 rounded-3 bg-white shadow-box">
                            <div class="image">
                                <img src="{{ asset($slider->image) }}" alt="{{ $slider->title }}" class="img-fluid">
                            </div>
                            <div class="desc ms-3">
                                <h6 class="mb-0">{{ $slider->title }}</h6>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
