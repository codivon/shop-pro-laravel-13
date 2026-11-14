{{-- گرید دسته‌بندی محصولات سانیار (ویجت‌ساز وبلاگ) --}}
{{-- فاز ۱۶۸ — فالبک آیکونی برای دسته‌های بدون تصویر (به‌جای no-image محصول) --}}
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
                            <div class="category-item snr-cat-card p-3 rounded-3 bg-white shadow-box">
                                <a href="{{ $category->link }}" aria-label="{{ $category->title }}">
                                    <div class="d-flex align-items-center justify-content-md-flex-start justify-content-between">
                                        <div class="image">
                                            @if($category->image)
                                                <img src="{{ asset($category->image) }}" alt="{{ $category->title }}" class="img-fluid" loading="lazy">
                                            @else
                                                <span class="snr-cat-fallback" role="img" aria-label="{{ $category->title }}">
                                                    <i class="bi bi-grid"></i>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text ms-3">
                                            <p class="font-14 fw-bold text-overflow-1 mb-2">{{ $category->title }}</p>
                                            <span class="snr-cat-more">{{ $category->allPublishedProducts()->count() }} کالا
                                                <i class="bi bi-chevron-double-left"></i>
                                            </span>
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
