{{-- نتایج جستجوی زنده هدر — استایل در assets/css/theme-header.css (کلاس‌های sr-*) --}}
@php
    $searchTerm = $searchTerm ?? '';
@endphp

@if(isset($products) && $products->count())
    <p class="sr-title">محصولات</p>
    <div class="sr-products">
        @foreach($products as $product)
            <a href="{{ route('front.products.show', ['product' => $product->id ?? $product]) }}" class="sr-product">
                <span class="sr-product-image">
                    <img src="{{ !empty($product->image) ? asset($product->image) : theme_asset('image/product/no-image.png') }}"
                         alt="{{ $product->title ?? '' }}" loading="lazy">
                </span>
                <span class="sr-product-title">{{ $product->title ?? '' }}</span>
            </a>
        @endforeach
    </div>
@endif

@if(isset($categories) && $categories->count())
    <p class="sr-title mt-3">دسته بندی ها</p>
    <div class="sr-links">
        @foreach($categories as $category)
            @if(is_object($category))
                <a href="{{ route('front.products.category-products', $category->slug ?? ($category->id ?? '')) }}"
                   class="sr-link">
                    <i class="bi bi-grid"></i> {{ $category->title ?? '' }}
                </a>
            @endif
        @endforeach
    </div>
@endif

@if(isset($brand) && $brand && is_object($brand))
    <p class="sr-title mt-3">برند</p>
    <div class="sr-links">
        <a href="{{ route('front.brands.show', $brand->slug ?? ($brand->id ?? '')) }}" class="sr-link">
            <i class="bi bi-award"></i> همه کالاهای برند {{ $brand->name ?? '' }}
        </a>
    </div>
@endif

@php
    $brandCategories = (isset($brand_categories) && is_array($brand_categories)) ? $brand_categories : [];
@endphp
@if(count($brandCategories))
    <div class="sr-links mt-2">
        @foreach($brandCategories as $brandCategory)
            @if(is_object($brandCategory))
                <a href="{{ route('front.products.index') }}?q={{ urlencode($brandCategory->title ?? '') }}"
                   class="sr-link">
                    <i class="bi bi-search"></i> {{ $brandCategory->title ?? '' }}
                </a>
            @endif
        @endforeach
    </div>
@endif

@if(trim($searchTerm) !== '')
    <a href="{{ route('front.products.index') }}?q={{ urlencode($searchTerm) }}" class="sr-link sr-link-all">
        <i class="bi bi-search"></i> مشاهده همه نتایج «{{ $searchTerm }}»
    </a>
@else
    <div class="sr-empty">نتیجه‌ای یافت نشد</div>
@endif
