{{-- نتیجه جستجوی زنده سانیار — $searchTerm, $products, $categories, $brand, $brand_categories (از ajax_search) --}}
<div class="search-live-dropdown" role="listbox" aria-label="نتایج جستجو">
    @if($products->count())
        <div class="sld-section">
            <h6 class="sld-title"><i class="bi bi-box-seam me-1"></i>محصولات</h6>
            <ul class="sld-list list-unstyled mb-0">
                @foreach($products as $product)
                    @php
                        $lowest_price = $product->lowestPrice;
                        $discount_percent = 0;
                        if ($lowest_price && $lowest_price->hasDiscount()) {
                            if ($lowest_price->regularPrice() > 0 && $lowest_price->salePrice() > 0) {
                                $discount_percent = (int) round(100 - ($lowest_price->salePrice() / $lowest_price->regularPrice()) * 100);
                            }
                            if ($discount_percent <= 0 && is_numeric($lowest_price->discount) && $lowest_price->discount > 0 && $lowest_price->discount <= 100) {
                                $discount_percent = (int) round($lowest_price->discount);
                            }
                        }
                    @endphp
                    <li>
                        <a href="{{ route('front.products.show', ['product' => $product]) }}" class="sld-item" role="option">
                            <span class="sld-thumb">
                                <img src="{{ $product->image ? asset($product->image) : asset('no-image-product.svg') }}" loading="lazy" alt="{{ $product->title }}">
                            </span>
                            <span class="sld-info">
                                <span class="sld-name">{{ $product->title }}</span>
                                @if($product->title_en)
                                    <span class="sld-name-en text-muted">{{ Str::limit($product->title_en, 42) }}</span>
                                @endif
                            </span>
                            <span class="sld-price">
                                @if($discount_percent > 0)
                                    <span class="sld-discount-badge">{{ $discount_percent }}%</span>
                                @endif
                                @if($lowest_price)
                                    <span class="main-color-one-color fw-bold">{{ $product->getLowestPrice() }}</span>
                                @else
                                    <span class="text-muted font-12">ناموجود</span>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($categories->count())
        <div class="sld-section">
            <h6 class="sld-title"><i class="bi bi-diagram-3 me-1"></i>دسته‌بندی‌ها</h6>
            <ul class="sld-list list-unstyled mb-0">
                @foreach($categories as $category)
                    <li>
                        <a href="{{ route('front.products.category-products', ['category' => $category->slug]) }}" class="sld-item" role="option">
                            <span class="sld-thumb">
                                <img src="{{ $category->image ? asset($category->image) : asset('no-image-product.svg') }}" loading="lazy" alt="{{ $category->title }}">
                            </span>
                            <span class="sld-info">
                                <span class="sld-name">{{ $category->title }}</span>
                                <span class="sld-name-en text-muted">دسته‌بندی محصول</span>
                            </span>
                            <span class="sld-price"><i class="bi bi-chevron-left"></i></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($brand)
        <div class="sld-section">
            <h6 class="sld-title"><i class="bi bi-award me-1"></i>برند</h6>
            <ul class="sld-list list-unstyled mb-0">
                <li>
                    <a href="{{ route('front.brands.show', ['brand' => $brand->slug]) }}" class="sld-item" role="option">
                        <span class="sld-thumb">
                            <img src="{{ $brand->image ? asset($brand->image) : asset('no-image-product.svg') }}" loading="lazy" alt="{{ $brand->name }}">
                        </span>
                        <span class="sld-info">
                            <span class="sld-name">{{ $brand->name }}</span>
                        </span>
                        <span class="sld-price"><i class="bi bi-chevron-left"></i></span>
                    </a>
                </li>
                @foreach($brand_categories as $bcat)
                    <li>
                        <a href="{{ route('front.products.category-products', ['category' => $bcat->slug]) }}" class="sld-item" role="option">
                            <span class="sld-thumb">
                                <img src="{{ $bcat->image ? asset($bcat->image) : asset('no-image-product.svg') }}" loading="lazy" alt="{{ $bcat->title }}">
                            </span>
                            <span class="sld-info">
                                <span class="sld-name">{{ $brand->name }} — {{ $bcat->title }}</span>
                            </span>
                            <span class="sld-price"><i class="bi bi-chevron-left"></i></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!$products->count() && !$categories->count() && !$brand)
        <div class="sld-empty text-center py-4">
            <i class="bi bi-search text-muted" style="font-size:1.8rem;"></i>
            <p class="text-muted font-14 mt-2 mb-0">نتیجه‌ای برای «{{ $searchTerm }}» یافت نشد</p>
        </div>
    @endif

    <a href="{{ route('front.products.index', ['q' => $searchTerm]) }}" class="sld-view-all">
        مشاهده همه نتایج برای «{{ $searchTerm }}»
        <i class="bi bi-arrow-left me-1"></i>
    </a>
</div>
