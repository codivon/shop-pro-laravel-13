{{-- فیلترهای فروشگاه فروشنده سانیار (فاز G) — قرارداد MainController@showSellerStore :
    $seller, $filterable (Filterableهای فیلتر فروشنده)، $products_id, $min_price, $max_price, $categorise
    سازوکار: GET به همان صفحه با price_filter/min_price/max_price/in_stock/s/filters[...] (scopeFilter میزبان) --}}

<div class="offcanvas-lg offcanvas-start col-lg-3 d-lg-block px-0" tabindex="-1" id="saniarFilters"
     aria-labelledby="saniarFiltersLabel" style="--bs-offcanvas-width: 320px;">

    <div class="offcanvas-header d-lg-none border-bottom">
        <h5 class="offcanvas-title" id="saniarFiltersLabel">
            <i class="bi bi-funnel main-color-one-color me-1"></i> فیلترها
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="بستن"></button>
    </div>

    <div class="offcanvas-body p-0 p-lg-2">
        <div class="filter-items position-sticky top-0">
            <div class="container-fluid">
                <form id="products-filter-form" action="{{ route('front.showSellerStore', ['seller' => $seller]) }}"
                      method="GET" aria-label="فیلتر محصولات فروشگاه">
                    <input type="hidden" name="price_filter" value="on">

                    {{-- جستجو --}}
                    <div class="filter-item">
                        <h5 class="filter-item-title">جستجو</h5>
                        <div class="filter-item-content">
                            <div class="search-form">
                                <div class="search-filed">
                                    <input type="text" name="s" value="{{ request('s') }}"
                                           placeholder="جستجوی محصولات ..." class="form-control search-input" aria-label="جستجو در این فروشگاه">
                                    <button type="submit" class="btn search-btn main-color-one-bg rounded-pill" aria-label="جستجو">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- فقط کالاهای موجود --}}
                    <div class="filter-item">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="in_stock"
                                   id="inStockSwitch" {{ request('in_stock') ? 'checked' : '' }}>
                            <label class="form-check-label font-14" for="inStockSwitch">فقط کالاهای موجود</label>
                        </div>
                    </div>

                    {{-- محدوده قیمت --}}
                    @if($min_price !== null && $max_price !== null && $max_price > 0)
                        <div class="filter-item">
                            <h5 class="filter-item-title">محدوده قیمت</h5>
                            <div class="filter-item-content">
                                <div class="filter-price">
                                    <div class="filter-slider">
                                        <div class="price-slider"
                                             data-min="{{ (int) floor($min_price ?? 0) }}"
                                             data-max="{{ (int) ceil($max_price ?? 0) }}"></div>
                                    </div>
                                    <ul class="filter-range mb-4">
                                        <li>
                                            <input type="text" readonly value="{{ (int) (request('min_price') ?: floor($min_price ?? 0)) }}"
                                                   name="min_price" data-value="{{ (int) (request('min_price') ?: floor($min_price ?? 0)) }}"
                                                   class="js-slider-range-from min-price-filter" aria-label="حداقل قیمت">
                                            <span>تومان</span>
                                        </li>
                                        <li class="label fw-bold">تا</li>
                                        <li>
                                            <input type="text" readonly value="{{ (int) (request('max_price') ?: ceil($max_price ?? 0)) }}"
                                                   name="max_price" data-value="{{ (int) (request('max_price') ?: ceil($max_price ?? 0)) }}"
                                                   class="js-slider-range-to max-price-filter" aria-label="حداکثر قیمت">
                                            <span>تومان</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- فیلترهای داینامیک فروشنده (Specification / AttributeGroup / StaticFilter) --}}
                    @foreach($filterable ?? [] as $filter)
                        @switch($filter->filterable_type)
                            @case('App\\Models\\Specification')
                                @php
                                    $spec_values = \DB::table('product_specification')->whereIn('product_id', $products_id)
                                        ->where('specification_id', $filter->filterable_id)->get()->unique('value')->pluck('value')->toArray();
                                    $spec_values = get_separated_values($spec_values, $filter->separator);
                                @endphp
                                @if(count($spec_values))
                                    <div class="filter-item">
                                        <div class="accordion accordion-flush">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed font-14 fw-bold" type="button"
                                                            data-bs-toggle="collapse" data-bs-target="#sfSpec{{ $filter->id }}"
                                                            aria-expanded="{{ request('filters.' . $filter->id) ? 'true' : 'false' }}">
                                                        {{ $filter->filterable->name }}
                                                    </button>
                                                </h2>
                                                <div id="sfSpec{{ $filter->id }}" class="accordion-collapse collapse {{ request('filters.' . $filter->id) ? 'show' : '' }}">
                                                    <div class="accordion-body pt-0">
                                                        @foreach($spec_values as $spec_val)
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input" type="checkbox"
                                                                       name="filters[{{ $filter->id }}][{{ $spec_val }}]"
                                                                       {{ request('filters.' . $filter->id . '.' . $spec_val) ? 'checked' : '' }}
                                                                       id="sfSpec{{ $filter->id }}{{ $loop->index }}">
                                                                <label class="form-check-label font-13" for="sfSpec{{ $filter->id }}{{ $loop->index }}">{{ $spec_val }}</label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @break

                            @case('App\\Models\\AttributeGroup')
                                @php
                                    $sf_prices = \DB::table('prices')->whereIn('product_id', $products_id)->pluck('id');
                                    $sf_attributes = \DB::table('attribute_price')->whereIn('price_id', $sf_prices)->pluck('attribute_id');
                                    $group_values = \App\Models\Attribute::where('attribute_group_id', $filter->filterable_id)->whereIn('id', $sf_attributes)->get();
                                @endphp
                                @if($group_values->count())
                                    <div class="filter-item">
                                        <div class="accordion accordion-flush">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed font-14 fw-bold" type="button"
                                                            data-bs-toggle="collapse" data-bs-target="#sfAttr{{ $filter->id }}"
                                                            aria-expanded="{{ request('filters.' . $filter->id) ? 'true' : 'false' }}">
                                                        {{ $filter->filterable->name }}
                                                    </button>
                                                </h2>
                                                <div id="sfAttr{{ $filter->id }}" class="accordion-collapse collapse {{ request('filters.' . $filter->id) ? 'show' : '' }}">
                                                    <div class="accordion-body pt-0">
                                                        @foreach($group_values as $group_val)
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input" type="checkbox"
                                                                       name="filters[{{ $filter->id }}][{{ $group_val->id }}]"
                                                                       {{ request('filters.' . $filter->id . '.' . $group_val->id) ? 'checked' : '' }}
                                                                       id="sfAttr{{ $filter->id }}{{ $loop->index }}">
                                                                <label class="form-check-label font-13" for="sfAttr{{ $filter->id }}{{ $loop->index }}">
                                                                    {{ $group_val->name }}
                                                                    @if($group_val->group->type == "color")
                                                                        <span class="d-inline-block rounded-circle ms-1 align-middle"
                                                                              style="width:12px;height:12px;background-color: {{ $group_val->value }};"></span>
                                                                    @endif
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @break

                            @case('App\\Models\\StaticFilter')
                                @if($filter->filterable->type == 'brand')
                                    @php
                                        $sf_brands = \App\Models\Brand::whereHas('products', fn($q) => $q->whereIn('id', $products_id))->get();
                                    @endphp
                                    @if($sf_brands->count())
                                        <div class="filter-item">
                                            <div class="accordion accordion-flush">
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button collapsed font-14 fw-bold" type="button"
                                                                data-bs-toggle="collapse" data-bs-target="#sfBrand{{ $filter->id }}"
                                                                aria-expanded="{{ request('filters.brands') ? 'true' : 'false' }}">
                                                            برند
                                                        </button>
                                                    </h2>
                                                    <div id="sfBrand{{ $filter->id }}" class="accordion-collapse collapse {{ request('filters.brands') ? 'show' : '' }}">
                                                        <div class="accordion-body pt-0">
                                                            @foreach($sf_brands as $brand)
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input" type="checkbox"
                                                                           name="filters[brands][{{ $brand->id }}]"
                                                                           {{ request('filters.brands.' . $brand->id) ? 'checked' : '' }}
                                                                           id="sfBrand{{ $filter->id }}{{ $loop->index }}">
                                                                    <label class="form-check-label font-13" for="sfBrand{{ $filter->id }}{{ $loop->index }}">{{ $brand->name }}</label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @elseif($filter->filterable->type == 'child_category' && count($categorise))
                                    <div class="filter-item">
                                        <div class="accordion accordion-flush">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed font-14 fw-bold" type="button"
                                                            data-bs-toggle="collapse" data-bs-target="#sfCat{{ $filter->id }}"
                                                            aria-expanded="{{ request('filters.' . $filter->id) ? 'true' : 'false' }}">
                                                        دسته‌بندی‌ها
                                                    </button>
                                                </h2>
                                                <div id="sfCat{{ $filter->id }}" class="accordion-collapse collapse {{ request('filters.' . $filter->id) ? 'show' : '' }}">
                                                    <div class="accordion-body pt-0">
                                                        @foreach($categorise as $cat)
                                                            <div class="form-check mb-2">
                                                                <input class="form-check-input" type="checkbox"
                                                                       name="filters[{{ $filter->id }}][{{ $cat->id }}]"
                                                                       {{ request('filters.' . $filter->id . '.' . $cat->id) ? 'checked' : '' }}
                                                                       id="sfCat{{ $filter->id }}{{ $loop->index }}">
                                                                <label class="form-check-label font-13" for="sfCat{{ $filter->id }}{{ $loop->index }}">{{ $cat->title }}</label>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @break
                        @endswitch
                    @endforeach

                    <button type="submit" class="btn main-color-one-bg text-white w-100 rounded-3 mt-2 py-2">
                        <i class="bi bi-funnel ms-1"></i>اعمال فیلتر
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
