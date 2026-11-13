
@switch($filter->filterable->type)
    @case('stock')
        <div class="col-12 my-3">
            <div class="parent-switcher">
                <label class="ui-statusswitcher">
                    <input type="checkbox" id="switcher-stock" class="in_stock" name="in_stock" {{ request('in_stock') ? 'checked' : '' }}>
                    <span class="ui-statusswitcher-slider">
                        <span class="ui-statusswitcher-slider-toggle"></span>
                    </span>
                </label>
                <label class="label-switcher" for="switcher-stock">فقط کالاهای موجود</label>
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
            <div class="card">
                <div class="card-header" id="heading-{{ $loop->index }}">
                    <h2 class="mb-0">
                        <button class="btn btn-block text-right collapsed" type="button"
                            data-toggle="collapse" data-target="#collapse-{{ $loop->index }}"
                            aria-expanded="false" aria-controls="collapse-{{ $loop->index }}">
                            برند
                            <i class="mdi mdi-chevron-down"></i>
                        </button>
                    </h2>
                </div>

                <div id="collapse-{{ $loop->index }}" class="collapse {{ request('filters.' . $filter->id) ? 'show' : '' }}" aria-labelledby="heading-{{ $loop->index }}">
                    <div class="card-body">
                        @foreach ($brands as $brand)
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" name="filters[brands][{{ $brand->id }}]" {{ request('filters.brands.' .$brand->id) ? 'checked' : '' }}
                                    id="customCheck-{{ $loop->parent->index }}-{{ $loop->index }}" value="{{ $brand->id }}">
                                <label class="custom-control-label"
                                    for="customCheck-{{ $loop->parent->index }}-{{ $loop->index }}">{{ $brand->name }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @break
    @case('child_category')

        @if (count($categorise))
            <div class="card">
                <div class="card-header" id="heading-{{ $loop->index }}">
                    <h2 class="mb-0">
                        <button class="btn btn-block text-right collapsed" type="button"
                            data-toggle="collapse" data-target="#collapse-{{ $loop->index }}"
                            aria-expanded="false" aria-controls="collapse-{{ $loop->index }}">
                            دسته بندی
                            <i class="mdi mdi-chevron-down"></i>
                        </button>
                    </h2>
                </div>

                <div id="collapse-{{ $loop->index }}" class="collapse {{ request('filters.' . $filter->id) ? 'show' : '' }}" aria-labelledby="heading-{{ $loop->index }}">
                    <div class="card-body">
                        @foreach ($categorise as $chidCategory)
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" name="filters[categories][{{ $chidCategory->id }}]" {{ request('filters.categories.' .$chidCategory->id) ? 'checked' : '' }}
                                    id="customCheck-{{ $loop->parent->index }}-{{ $loop->index }}" value="{{ $chidCategory->id }}">
                                <label class="custom-control-label"
                                    for="customCheck-{{ $loop->parent->index }}-{{ $loop->index }}">{{ $chidCategory->title }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @break

    @case('price')

        <div class="col-12 my-4">
            <div class="parent-switcher mb-3">
                <label class="ui-statusswitcher">
                    <input type="checkbox" id="switcher-price" class="price_filter" name="price_filter" {{ request('price_filter') ? 'checked' : '' }}>
                    <span class="ui-statusswitcher-slider">
                        <span class="ui-statusswitcher-slider-toggle"></span>
                    </span>
                </label>
                <label class="label-switcher" for="switcher-price">فیلتر بر اساس قیمت: </label>
            </div>
            <div class="mt-2 mb-2">
                <div id="slider-non-linear-step"></div>
            </div>
            <div class="mt-2 mb-2 text-center pt-2 font-size-14">
                <span>قیمت: </span>
                <span class="example-val" id="slider-non-linear-step-value"></span> تومان
            </div>
            <input type="hidden" name="min_price" value="{{ request('min_price') }}">
            <input type="hidden" name="max_price" value="{{ request('max_price') }}">
        </div>
        @break

@endswitch
