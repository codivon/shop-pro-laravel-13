{{-- بدنه فرم فیلتر محصولات دسته‌بندی — مشترک بین سایدبار دسکتاپ و آفسکانس موبایل --}}
{{-- متغیرهای مورد انتظار: $category، $scope (پیشوند یکتا برای آیدی‌ها) --}}
<form class="products-filter-form" action="{{ route('front.products.category-products', ['category' => $category]) }}">

    <div class="filter-item">
        <h5 class="filter-item-title">جستجو</h5>
        <div class="filter-item-content">
            <div class="search-form">
                <div class="search-filed">
                    <input type="text" name="s" value="{{ request('s') }}"
                           placeholder="نام محصول مورد نظر را بنویسید..." class="form-control search-input">
                    <button type="submit" class="btn search-btn main-color-one-bg rounded-pill">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @foreach ($category->getFilter()->related()->orderBy('ordering')->get() as $filter)

        @switch($filter->filterable_type)
            @case('App\Models\Specification')

                @php
                    $spec_values = front_filter_spec_values($category, $filter);
                @endphp

                @if (count($spec_values))
                    <div class="filter-item">
                        <h5 class="filter-item-title d-flex align-items-center justify-content-between" role="button"
                            data-bs-toggle="collapse" data-bs-target="#{{ $scope }}collapse-{{ $loop->index }}"
                            aria-expanded="{{ request('filters.' . $filter->id) ? 'true' : 'false' }}"
                            aria-controls="{{ $scope }}collapse-{{ $loop->index }}">
                            <span>{{ $filter->filterable->name }}</span>
                            <i class="bi bi-chevron-down font-14"></i>
                        </h5>
                        <div class="filter-item-content">
                            <div id="{{ $scope }}collapse-{{ $loop->index }}"
                                 class="collapse {{ request('filters.' . $filter->id) ? 'show' : '' }}">
                                @foreach ($spec_values as $spec_val)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="filters[{{ $filter->id }}][{{ $spec_val }}]"
                                               {{ request('filters.' . $filter->id . '.' . $spec_val) ? 'checked' : '' }}
                                               id="{{ $scope }}check-{{ $loop->parent->index }}-{{ $loop->index }}">
                                        <label class="form-check-label font-14"
                                               for="{{ $scope }}check-{{ $loop->parent->index }}-{{ $loop->index }}">{{ $spec_val }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @break
            @case('App\Models\AttributeGroup')

                @php
                    $group_values = front_filter_attribute_values($category, $filter);
                @endphp

                @if ($group_values->count())
                    <div class="filter-item">
                        <h5 class="filter-item-title d-flex align-items-center justify-content-between" role="button"
                            data-bs-toggle="collapse" data-bs-target="#{{ $scope }}collapse-{{ $loop->index }}"
                            aria-expanded="{{ request('filters.' . $filter->id) ? 'true' : 'false' }}"
                            aria-controls="{{ $scope }}collapse-{{ $loop->index }}">
                            <span>{{ $filter->filterable->name }}</span>
                            <i class="bi bi-chevron-down font-14"></i>
                        </h5>
                        <div class="filter-item-content">
                            <div id="{{ $scope }}collapse-{{ $loop->index }}"
                                 class="collapse {{ request('filters.' . $filter->id) ? 'show' : '' }}">
                                @foreach ($group_values as $group_val)
                                    <div class="form-check">
                                        <input class="form-check-input attribute_group_input" type="checkbox"
                                               name="filters[{{ $filter->id }}][{{ $group_val->id }}]"
                                               {{ request('filters.' . $filter->id . '.' . $group_val->id) ? 'checked' : '' }}
                                               id="{{ $scope }}check-{{ $loop->parent->index }}-{{ $loop->index }}">
                                        <label class="form-check-label font-14"
                                               for="{{ $scope }}check-{{ $loop->parent->index }}-{{ $loop->index }}">{{ $group_val->name }}</label>
                                        @if ($group_val->group->type == "color")
                                            <span class="filter-color" style="background-color: {{ $group_val->value }}"></span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @break
            @case('App\Models\StaticFilter')
                @include('front::products.partials.static-filters')
                @break

        @endswitch
    @endforeach

    <div class="filter-item text-center">
        <button type="submit" class="btn-outline-site border-0 bg-transparent">اعمال فیلتر</button>
    </div>

</form>
