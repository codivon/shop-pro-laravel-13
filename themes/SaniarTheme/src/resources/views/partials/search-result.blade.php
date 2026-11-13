<hr class="first-hr">
<div class="row search-result-product">

    {{-- ========== بخش محصولات ========== --}}
    <div class="large-12 columns w-100">
        <div class="product-carousel owl-carousel owl-carousel-search owl-theme owl-rtl owl-loaded owl-drag">
            @forelse($products as $product)
                <div class="item">
                    <a href="{{ route('front.products.show', ['product' => $product->id ?? $product]) }}" class="header-basket-list-item">
                        <div class="header-basket-list-item-image image-data-src">
                            <img class="img-fluid" data-src="{{ asset($product->image) ?? asset('/no-image-product.svg') }}" src="{{ theme_asset('images/600-600.png') }}" alt="{{ $product->title ?? '' }}">
                        </div>
                        <div class="header-basket-list-item-content">
                            <h2 class="header-basket-list-item-title">{{ $product->title ?? '' }}</h2>
                        </div>
                    </a>
                </div>
            @empty
                <div class="text-center p-3">هیچ محصولی یافت نشد</div>
            @endforelse
        </div>
    </div>
    {{-- ========== بخش برند ========== --}}
    @if(isset($brand) && $brand && is_object($brand))
        <a href="{{ route('front.brands.show', $brand->slug ?? ($brand->id ?? '')) }}" class="search-result-brands d-flex w-100 mb-2">
            <div class="image-data-src">
                <img class="img-fluid" width="40" data-src="{{ isset($brand->image) && $brand->image ? asset($brand->image) : asset('/no-image-product.svg') }}" src="{{ theme_asset('images/600-600.png') }}" alt="{{ $brand->name ?? 'برند' }}">
            </div>
            <div class="search-result-brands-title">
                همه کالاهای برند {{ $brand->name ?? '' }}
            </div>
        </a>
    @endif

    {{-- ========== بخش دسته‌بندی‌ها (آیکون) ========== --}}
    @if(isset($categories) && $categories->count() > 0)
        <div class="w-100 d-flex flex-wrap">
            @foreach($categories as $category)
                @if(is_object($category))
                    <a href="{{ route('front.products.category-products', $category->slug ?? ($category->id ?? '')) }}" class="search-result-brands d-flex mb-2 me-2">
                        <div class="image-data-src">
                            <img class="img-fluid" width="24" data-src="{{ theme_asset('img/search-category-icon.png') }}" src="{{ theme_asset('images/600-600.png') }}" alt="{{ $category->title ?? '' }}">
                        </div>
                        <div class="search-result-category-title">
                            همه کالاهای {{ $category->title ?? '' }}
                        </div>
                    </a>
                @endif
            @endforeach
        </div>
    @endif

    {{-- ========== بخش جستجو در دسته‌بندی‌ها ========== --}}
    @if(isset($categories) && $categories->count() > 0)
        <div class="w-100 mt-3">
            @foreach($categories as $category)
                @if(is_object($category))
                    <a href="{{ route('front.products.index') . '?q=' . urlencode($category->title ?? '') }}" class="search-result-brands d-flex mb-1">
                        <div class="image-data-src">
                            <img class="img-fluid" width="24" data-src="{{ theme_asset('img/search-search-icon.png') }}" src="{{ theme_asset('images/600-600.png') }}" alt="{{ $category->title ?? '' }}">
                        </div>
                        <div class="search-result-category-title">
                            {{ $category->title ?? '' }}
                            <div class="text-body1-strong" style="font-size: 13px">
                                <span class="color-500">در دسته</span>
                                <span class="color-secondary-500">{{ $category->title ?? '' }}</span>
                            </div>
                        </div>
                    </a>
                @endif
            @endforeach
        </div>
    @endif

    <hr style="width: 92%; margin: 1rem auto 0">

    {{-- ========== بخش جستجوی عمومی ========== --}}
    <div class="w-100 mt-3">
        <a href="{{ route('front.products.index') . '?q=' . urlencode($searchTerm ?? '') }}" class="search-result-brands d-flex mb-1">
            <div class="image-data-src">
                <img class="img-fluid" width="24" data-src="{{ theme_asset('img/search-search-icon.png') }}" src="{{ theme_asset('images/600-600.png') }}" alt="جستجو">
            </div>
            <div class="search-result-category-title">
                {{ $searchTerm ?? '' }}
            </div>
        </a>

        @if(isset($brand_categories) && is_array($brand_categories) && count($brand_categories) > 0)
            @foreach($brand_categories as $brandCategory)
                @if(is_object($brandCategory))
                    <a href="{{ route('front.products.index') . '?q=' . urlencode($brandCategory->title ?? '') }}" class="search-result-brands d-flex mb-1">
                        <div class="image-data-src">
                            <img class="img-fluid" width="24" data-src="{{ theme_asset('img/search-search-icon.png') }}" src="{{ theme_asset('images/600-600.png') }}" alt="{{ $brandCategory->title ?? '' }}">
                        </div>
                        <div class="search-result-category-title">
                            {{ $brandCategory->title ?? '' }}
                        </div>
                    </a>
                @endif
            @endforeach
        @endif
    </div>
</div>
