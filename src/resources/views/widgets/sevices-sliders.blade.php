@php
    $variables = get_widget($widget);
    $sliders = $variables['sevices_sliders'] ?? collect();
@endphp
@if(count($sliders))
    <div class="feature py-20">
        <div class="container-fluid">
            <div class="row justify-content-center g-2 row-cols-1 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 row-cols-xl-5">
                @foreach($sliders as $slider)
                    <div class="col">
                        <div class="feature-item d-flex align-items-center p-3 rounded-3 bg-white shadow-box">
                            <div class="image">
                                <img src="{{ asset($slider->image) }}" alt="{{ $slider->title ?? '' }}" class="img-fluid" loading="lazy">
                            </div>
                            <div class="desc ms-3">
                                <h6 class="mb-2">{{ $slider->title ?? '' }}</h6>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
