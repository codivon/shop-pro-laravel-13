
<div class="dt-sn mb-3">
    <form id="products-filter-form" action="{{ route('front.showSellerStore', ['seller' => $seller]) }}">
        <div class="col-12">
            <div class="section-title text-sm-title title-wide mb-1 no-after-title-wide">
                <h2>فیلتر محصولات</h2>
            </div>
        </div>
        <div class="col-12 mb-3">
            <div class="widget-search">
                <input type="text" name="s" value="{{ request('s') }}"
                       placeholder="نام محصول مورد نظر را بنویسید...">
                <button class="btn-search-widget">
                    <img src="{{ theme_asset('img/theme/search.png') }}" alt="search buttom">
                </button>
            </div>
        </div>
        <div class="col-12 filter-product mb-3">
            <div class="accordion" id="accordionExample">


                @foreach ($filterable as $filter)

                    @switch($filter->filterable_type)
                        @case('App\Models\Specification')

                            @php
                                $spec_values = \DB::table('product_specification')->whereIn('product_id', $products_id)->where('specification_id', $filter->filterable_id)->get()->unique('value')->pluck('value')->toArray();
                                $spec_values = get_separated_values($spec_values, $filter->separator);
                            @endphp

                            @if (count($spec_values))
                                <div class="card">
                                    <div class="card-header" id="heading-{{ $loop->index }}">
                                        <h2 class="mb-0">
                                            <button class="btn btn-block text-right collapsed" type="button"
                                                    data-toggle="collapse" data-target="#collapse-{{ $loop->index }}"
                                                    aria-expanded="false" aria-controls="collapse-{{ $loop->index }}">
                                                {{ $filter->filterable->name }}
                                                <i class="mdi mdi-chevron-down"></i>
                                            </button>
                                        </h2>
                                    </div>

                                    <div id="collapse-{{ $loop->index }}"
                                         class="collapse {{ request('filters.' . $filter->id) ? 'show' : '' }}"
                                         aria-labelledby="heading-{{ $loop->index }}">
                                        <div class="card-body">
                                            @foreach ($spec_values as $spec_val)
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                           name="filters[{{ $filter->id }}][{{ $spec_val }}]"
                                                           {{ request('filters.' . $filter->id . '.' .$spec_val) ? 'checked' : '' }}
                                                           id="customCheck-{{ $loop->parent->index }}-{{ $loop->index }}">
                                                    <label class="custom-control-label"
                                                           for="customCheck-{{ $loop->parent->index }}-{{ $loop->index }}">{{ $spec_val }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @break
                        @case('App\Models\AttributeGroup')

                            @php
                                $prices = \DB::table('prices')->whereIn('product_id', $products_id)->pluck('id');
                                $attributes = \DB::table('attribute_price')->whereIn('price_id', $prices)->pluck('attribute_id');
                                $group_values = \App\Models\Attribute::where('attribute_group_id', $filter->filterable_id)->whereIn('id', $attributes)->get();
                            @endphp

                            @if ($group_values->count())
                                <div class="card">
                                    <div class="card-header" id="heading-{{ $loop->index }}">
                                        <h2 class="mb-0">
                                            <button class="btn btn-block text-right collapsed" type="button"
                                                    data-toggle="collapse" data-target="#collapse-{{ $loop->index }}"
                                                    aria-expanded="false" aria-controls="collapse-{{ $loop->index }}">
                                                {{ $filter->filterable->name }}
                                                <i class="mdi mdi-chevron-down"></i>
                                            </button>
                                        </h2>
                                    </div>

                                    <div id="collapse-{{ $loop->index }}"
                                         class="collapse {{ request('filters.' . $filter->id) ? 'show' : '' }}"
                                         aria-labelledby="heading-{{ $loop->index }}">
                                        <div class="card-body">
                                            @foreach ($group_values as $group_val)
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox"
                                                           class="custom-control-input attribute_group_input"
                                                           name="filters[{{ $filter->id }}][{{ $group_val->id }}]"
                                                           {{ request('filters.' . $filter->id . '.' .$group_val->id) ? 'checked' : '' }}
                                                           id="customCheck-{{ $loop->parent->index }}-{{ $loop->index }}">
                                                    <label class="custom-control-label"
                                                           for="customCheck-{{ $loop->parent->index }}-{{ $loop->index }}">{{ $group_val->name }}</label>
                                                    @if ($group_val->group->type == "color")
                                                        <span class="filter-color"
                                                              style="background-color: {{ $group_val->value }}"></span>
                                                    @endif
                                                </div>
                                            @endforeach

                                        </div>
                                    </div>
                                </div>
                            @endif

                            @break
                        @case('App\Models\StaticFilter')
                            @include('front::products.partials.static-seller-filters')
                            @break

                    @endswitch

                @endforeach

            </div>
        </div>

        <div class="col-12">
            <button class="btn btn-info btn-block" type="submit">
                فیلتر
            </button>
        </div>
    </form>
</div>

