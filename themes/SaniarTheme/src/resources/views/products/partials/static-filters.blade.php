{{-- فیلترهای استاتیک (موجودی/برند/دسته‌بندی/قیمت) — اسکین سانیار --}}
{{-- در اسکوپ لوپ category-filters-body اجرا می‌شود: $filter، $loop، $category، $products_id، $min_price، $max_price --}}
@switch($filter->filterable->type)
    @case('stock')
        <div class="filter-item">
            <h5 class="filter-item-title">وضعیت کالا</h5>
            <div class="filter-item-content">
                <div class="form-check form-switch">
                    <input class="form-check-input in_stock" type="checkbox" role="switch"
                           id="stock-switcher-{{ $loop->index }}" name="in_stock" {{ request('in_stock') ? 'checked' : '' }}>
                    <label class="form-check-label font-14" for="stock-switcher-{{ $loop->index }}">فقط کالاهای موجود</label>
                </div>
            </div>
        </div>

        @break
    @case('brand')
        @php
            $brands = \App\Models\Brand::whereHas('products', function($q) use ($products_id) {
                $q->whereIn('id', $products_id);
            })->get();

        @endphp

        @if ($brands->count())
            <div class="filter-item">
                <h5 class="filter-item-title d-flex align-items-center justify-content-between" role="button"
                    data-bs-toggle="collapse" data-bs-target="#collapse-brand-{{ $loop->index }}"
                    aria-expanded="{{ request('filters.' . $filter->id) ? 'true' : 'false' }}"
                    aria-controls="collapse-brand-{{ $loop->index }}">
                    <span>برند</span>
                    <i class="bi bi-chevron-down font-14"></i>
                </h5>
                <div class="filter-item-content">
                    <div id="collapse-brand-{{ $loop->index }}"
                         class="collapse {{ request('filters.' . $filter->id) ? 'show' : '' }}">
                        @foreach ($brands as $brand)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="filters[{{ $filter->id }}][{{ $brand->id }}]"
                                       {{ request('filters.' . $filter->id . '.' . $brand->id) ? 'checked' : '' }}
                                       id="{{ $loop->parent->index }}check-{{ $loop->parent->index }}-{{ $loop->index }}">
                                <label class="form-check-label font-14"
                                       for="{{ $loop->parent->index }}check-{{ $loop->parent->index }}-{{ $loop->index }}">{{ $brand->name }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @break
    @case('child_category')

        @if ($category->childrenCategories->count())
            <div class="filter-item">
                <h5 class="filter-item-title d-flex align-items-center justify-content-between" role="button"
                    data-bs-toggle="collapse" data-bs-target="#collapse-cat-{{ $loop->index }}"
                    aria-expanded="{{ request('filters.' . $filter->id) ? 'true' : 'false' }}"
                    aria-controls="collapse-cat-{{ $loop->index }}">
                    <span>دسته بندی</span>
                    <i class="bi bi-chevron-down font-14"></i>
                </h5>
                <div class="filter-item-content">
                    <div id="collapse-cat-{{ $loop->index }}"
                         class="collapse {{ request('filters.' . $filter->id) ? 'show' : '' }}">
                        @foreach ($category->childrenCategories as $chidCategory)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="filters[{{ $filter->id }}][{{ $chidCategory->id }}]"
                                       {{ request('filters.' . $filter->id . '.' . $chidCategory->id) ? 'checked' : '' }}
                                       id="{{ $loop->parent->index }}check-{{ $loop->parent->index }}-{{ $loop->index }}">
                                <label class="form-check-label font-14"
                                       for="{{ $loop->parent->index }}check-{{ $loop->parent->index }}-{{ $loop->index }}">{{ $chidCategory->title }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @break

    @case('price')

        <div class="filter-item">
            <h5 class="filter-item-title">قیمت</h5>
            <div class="filter-item-content">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input price_filter" type="checkbox" role="switch"
                           id="price-switcher-{{ $loop->index }}" name="price_filter" {{ request('price_filter') ? 'checked' : '' }}>
                    <label class="form-check-label font-14" for="price-switcher-{{ $loop->index }}">فیلتر بر اساس قیمت</label>
                </div>

                <div class="filter-price js-price-slider"
                     data-min-selected="{{ request('min_price') ?: $min_price }}"
                     data-max-selected="{{ request('max_price') ?: $max_price }}"
                     data-min="{{ $min_price }}"
                     data-max="{{ $max_price }}">
                    <div class="filter-slider">
                        <div class="price-slider"></div>
                    </div>
                    <ul class="filter-range mb-4">
                        <li>
                            <input type="text" class="js-slider-range-from min-price-filter" disabled>
                            <span>تومان</span>
                        </li>
                        <li class="label fw-bold">تا</li>
                        <li>
                            <input type="text" class="js-slider-range-to max-price-filter" disabled>
                            <span>تومان</span>
                        </li>
                    </ul>
                </div>

                <input type="hidden" name="min_price" value="{{ request('min_price') }}">
                <input type="hidden" name="max_price" value="{{ request('max_price') }}">
            </div>
        </div>
        @break

@endswitch
