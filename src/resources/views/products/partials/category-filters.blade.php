{{-- فیلترهای صفحه دسته سانیار — $category، $min_price، $max_price (nullable)
     تک‌نمونه: در دسکتاپ ستون col-lg-3، در موبایل offcanvas (offcanvas-lg) — بدون ID تکراری --}}
@php
    $has_filter = $category->getFilter();
    $children = $category->childrenCategories()->published()->orderBy('ordering')->get();
    $child_counts = $children->count() ? front_category_published_counts($children) : [];
    $child_category_filter = null;
    $brand_filter = null;
    if ($has_filter) {
        foreach ($has_filter->related()->orderBy('ordering')->get() as $f) {
            if ($f->filterable_type === \App\Models\StaticFilter::class) {
                if ($f->filterable->type === 'child_category') $child_category_filter = $f;
                if ($f->filterable->type === 'brand') $brand_filter = $f;
            }
        }
    }
    $brands_list = $brand_filter ? saniar_category_brands($category) : collect();
@endphp

<div class="offcanvas-lg offcanvas-start col-lg-3 d-lg-block px-0" tabindex="-1" id="saniarFilters"
     aria-labelledby="saniarFiltersLabel" style="--bs-offcanvas-width: 320px;">

    {{-- هدر offcanvas فقط در موبایل --}}
    <div class="offcanvas-header d-lg-none border-bottom">
        <h5 class="offcanvas-title" id="saniarFiltersLabel">
            <i class="bi bi-funnel main-color-one-color me-1"></i> فیلترها
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="بستن"></button>
    </div>

    <div class="offcanvas-body p-0 p-lg-2">
        <div class="filter-items position-sticky top-0">
            <div class="container-fluid">
                <form id="products-filter-form" action="{{ route('front.products.category-products', ['category' => $category]) }}"
                      method="GET" aria-label="فیلتر محصولات">
                    <input type="hidden" name="price_filter" value="1">

                    {{-- جستجو در دسته --}}
                    <div class="filter-item">
                        <h5 class="filter-item-title">جستجو</h5>
                        <div class="filter-item-content">
                            <div class="search-form">
                                <div class="search-filed">
                                    <input type="text" name="s" value="{{ request('s') }}"
                                           placeholder="جستجوی محصولات ..." class="form-control search-input" aria-label="جستجو در این دسته">
                                    <button type="submit" class="btn search-btn main-color-one-bg rounded-pill" aria-label="جستجو">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- زیردسته‌ها: چک‌باکس اگر فیلتر child_category تعریف شده، وگرنه لینک‌های سریع --}}
                    @if ($children->count())
                        <div class="filter-item">
                            <h5 class="filter-item-title">دسته بندی ها</h5>
                            <div class="filter-item-content">
                                @if ($child_category_filter)
                                    @foreach ($children as $child)
                                        <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                                            <div class="form-check">
                                                <label for="schild-{{ $child->id }}" class="form-check-label">{{ $child->title }}</label>
                                                <input type="checkbox" name="filters[{{ $child_category_filter->id }}][{{ $child->id }}]"
                                                       id="schild-{{ $child->id }}" value="1"
                                                       class="form-check-input"
                                                       @checked(request("filters.{$child_category_filter->id}.{$child->id}"))>
                                            </div>
                                            <div>
                                                <span class="fw-bold font-14">({{ $child_counts[$child->id] ?? 0 }})</span>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <ul class="navbar-nav">
                                        @foreach ($children as $child)
                                            <li class="nav-item">
                                                <a href="{{ route('front.products.category', ['category' => $child]) }}"
                                                   class="d-flex align-items-center justify-content-between w-100 py-1 text-muted">
                                                    <span>{{ $child->title }}</span>
                                                    <span class="fw-bold font-14">({{ $child_counts[$child->id] ?? 0 }})</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- برندها: فقط وقتی فیلتر برند تعریف شده --}}
                    @if ($brand_filter && $brands_list->count())
                        <div class="filter-item">
                            <h5 class="filter-item-title">برند</h5>
                            <div class="filter-item-content">
                                @foreach ($brands_list as $brand)
                                    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                                        <div class="form-check">
                                            <label for="sbrand-{{ $brand->id }}" class="form-check-label">{{ $brand->name }}</label>
                                            <input type="checkbox" name="filters[{{ $brand_filter->id }}][{{ $brand->id }}]"
                                                   id="sbrand-{{ $brand->id }}" value="1"
                                                   class="form-check-input"
                                                   @checked(request("filters.{$brand_filter->id}.{$brand->id}"))>
                                        </div>
                                        @if($brand->image)
                                            <img src="{{ asset($brand->image) }}" alt="{{ $brand->name }}" loading="lazy" height="22" style="max-height:22px;width:auto;">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- قیمت --}}
                    @if (!is_null($min_price) && !is_null($max_price) && $max_price > 0)
                        <div class="filter-item">
                            <h5 class="filter-item-title">قیمت</h5>
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

                    {{-- فیلترهای پویا: Specification و AttributeGroup --}}
                    @if ($has_filter)
                        @foreach ($has_filter->related()->orderBy('ordering')->get() as $filter)
                            @switch($filter->filterable_type)
                                @case(\App\Models\Specification::class)
                                    @php
                                        $spec_values = front_filter_spec_values($category, $filter);
                                    @endphp
                                    @if (count($spec_values))
                                        <div class="filter-item">
                                            <h5 class="filter-item-title">{{ $filter->filterable->name }}</h5>
                                            <div class="filter-item-content">
                                                @foreach ($spec_values as $spec_val)
                                                    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                                                        <div class="form-check">
                                                            <label for="sspec-{{ $filter->id }}-{{ $loop->index }}" class="form-check-label">{{ $spec_val }}</label>
                                                            <input type="checkbox" name="filters[{{ $filter->id }}][{{ $spec_val }}]"
                                                                   id="sspec-{{ $filter->id }}-{{ $loop->index }}" value="1"
                                                                   class="form-check-input"
                                                                   @checked(request("filters.{$filter->id}.{$spec_val}"))>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @break

                                @case(\App\Models\AttributeGroup::class)
                                    @php
                                        $group_values = front_filter_attribute_values($category, $filter);
                                    @endphp
                                    @if ($group_values->count())
                                        <div class="filter-item">
                                            <h5 class="filter-item-title">{{ $filter->filterable->name }}</h5>
                                            <div class="filter-item-content">
                                                @if ($filter->filterable->type == 'color')
                                                    <div class="product-meta-color-items">
                                                        @foreach ($group_values as $group_val)
                                                            <input type="checkbox" class="btn-check" name="filters[{{ $filter->id }}][{{ $group_val->id }}]"
                                                                   id="sattr-{{ $filter->id }}-{{ $group_val->id }}" value="1" autocomplete="off"
                                                                   @checked(request("filters.{$filter->id}.{$group_val->id}"))>
                                                            <label class="btn btt-light" for="sattr-{{ $filter->id }}-{{ $group_val->id }}"
                                                                   title="{{ $group_val->name }}">
                                                                <span style="background-color: {{ $group_val->value }};"></span>
                                                                {{ $group_val->name }}
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    @foreach ($group_values as $group_val)
                                                        <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                                                            <div class="form-check">
                                                                <label for="sattr-{{ $filter->id }}-{{ $group_val->id }}" class="form-check-label">{{ $group_val->name }}</label>
                                                                <input type="checkbox" name="filters[{{ $filter->id }}][{{ $group_val->id }}]"
                                                                       id="sattr-{{ $filter->id }}-{{ $group_val->id }}" value="1"
                                                                       class="form-check-input"
                                                                       @checked(request("filters.{$filter->id}.{$group_val->id}"))>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    @break
                            @endswitch
                        @endforeach
                    @endif

                    {{-- فقط کالاهای موجود --}}
                    <div class="filter-item">
                        <div class="filter-item-content">
                            <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" name="in_stock" value="1"
                                           id="saniarInStock" @checked(request('in_stock'))>
                                    <label class="form-check-label" for="saniarInStock">فقط کالاهای موجود</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- دکمه‌ها --}}
                    <div class="filter-item">
                        <div class="filter-item-content d-grid gap-2">
                            <button type="submit" class="btn border-0 main-color-one-bg text-white rounded-3 py-2">
                                <i class="bi bi-funnel me-1"></i> اعمال فیلتر
                            </button>
                            <a href="{{ route('front.products.category-products', ['category' => $category]) }}"
                               class="btn-outline-site text-center bg-white">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> حذف فیلترها
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
