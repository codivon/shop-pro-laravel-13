{{-- گرید دسته‌بندی محصولات سانیار (ویجت‌ساز وبلاگ) --}}
@php
    $variables  = get_widget($widget);
    $categories = $variables['categories'];
@endphp

@if ($categories->count())
    <div class="category py-20">
        <div class="container-fluid">
            <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                <div class="title d-flex align-items-center">
                    <img src="{{ theme_asset('image/square.png') }}" alt="" class="img-fluid">
                    <h5 class="font-16 ms-3">{{ $widget->option('title','دسته بندی محصولات') }}</h5>
                </div>
            </div>

            <div class="category-items">
                <div class="row gy-3 justify-content-center">
                    @foreach ($categories as $category)
                        <div class="col-lg-3 col-sm-6 col-12">
                            <div class="category-item p-3 rounded-3 bg-white shadow-box">
                                <a href="{{ $category->link }}">
                                    <div class="d-flex align-items-center justify-content-md-flex-start justify-content-between">
                                        <div class="image">
                                            <img src="{{ $category->image ? asset($category->image) : asset('/no-image-product.svg') }}" alt="{{ $category->title }}" class="img-fluid">
                                        </div>
                                        <div class="text ms-3">
                                            <p class="text-center font-14 text-overflow-1">{{ $category->title }}</p>
                                            <span class="btn text-muted-two btn-sm font-14 px-3 light-btn shadow-sm"><span>{{ $category->allPublishedProducts()->count() }} کالا</span>
                                                <i class="bi bi-chevron-double-left font-16 main-color-one-color ms-2"></i></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
