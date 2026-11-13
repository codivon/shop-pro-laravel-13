@php
    $variables    = get_widget($widget);
    $categories   = $variables['categories'];
    // FIX مقیاس: شمارش محصولات هر دسته در «یک کوئری» کش‌شده
    // (قبلاً به‌ازای هر دسته ۲ کوئری بازگشتی + COUNT اجرا می‌شد = N+1)
    $category_counts = front_category_published_counts($categories);
@endphp

<!-- Start Category-Section -->
@if ($categories->count())
    <div class="col-12">
        <div class="promotion-categories-container mt-4 mb-4">
            <span class="promotion-categories-title">{{ $widget->option('title','دسته بندی محصولات') }}</span>
            <div class="category-container">
                <div class="promotion-categories">
                    @foreach ($categories as $category)
                    <a href="{{ $category->link }}" class="image-data-src promotion-category">
                        <img data-src="{{ $category->image ? asset($category->image) : asset('/no-image-product.svg') }}" src="{{ theme_asset('images/600-600.png') }}" alt="{{ $category->title }}">
                        <div class="promotion-category-name">{{ $category->title }}</div>
                        <div class="promotion-category-quantity">{{ $category_counts[$category->id] ?? 0 }} کالا</div>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>


@endif
<!-- End Category-Section -->
