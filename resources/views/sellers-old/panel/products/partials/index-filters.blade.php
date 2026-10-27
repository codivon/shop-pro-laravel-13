<div class="card">
    <div class="card-header filter-card">
        <h4 class="card-title">فیلتر کردن</h4>
        <a class="heading-elements-toggle"><i class="fa fa-ellipsis-v font-medium-3"></i></a>
        <div class="heading-elements">
            <ul class="list-inline mb-0">
                <li><a data-action="collapse"><i class="feather icon-chevron-down"></i></a></li>
            </ul>
        </div>
    </div>
    <div class="card-content collapse show">
        <div class="card-body">
            <div class="users-list-filter">
                <form id="filter-products-form" method="GET"
                      action="{{ $filter_action }}">
                    <div class="row">
                        <div class="col-md-8">
                            <label>عنوان</label>
                            <fieldset class="form-group">
                                <input class="form-control datatable-filter" name="title" value="{{ request('title') }}">
                            </fieldset>
                        </div>

                        <div class="col-md-3">
                            <label>وضعیت موجودی</label>
                            <fieldset class="form-group">
                                <select class="form-control datatable-filter" name="stock">
                                    <option value="all" {{ request('stock') == 'all' ? 'selected' : '' }}>
                                        همه
                                    </option>
                                    <option value="available" {{ request('stock') == 'available' ? 'selected' : '' }}>
                                        موجود
                                    </option>
                                    <option value="unavailable" {{ request('stock') == 'unavailable' ? 'selected' : '' }}>
                                        ناموجود
                                    </option>
                                </select>
                            </fieldset>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>دسته بندی ها</label>
                                <select class="form-control datatable-filter product-category" name="category_id[]" multiple>
                                    @foreach ($categories as $category)
                                        <option
                                            class="l{{ $category->parents()->count() + 1 }} {{ $category->categories()->count() ? 'non-leaf' : '' }}"
                                            data-pup="{{ $category->category_id }}"
                                            {{ ( request()->input('category_id') && in_array($category->id, request()->input('category_id')) ) ? 'selected' : '' }}
                                            value="{{ $category->id }}">{{ $category->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>محصول ویژه</label>
                            <fieldset class="form-group">
                                <select class="form-control datatable-filter" name="special">
                                    <option value="all" {{ request('special') == 'all' ? 'selected' : '' }}>
                                        همه
                                    </option>
                                    <option value="yes" {{ request('special') == 'yes' ? 'selected' : '' }}>
                                        بله
                                    </option>
                                    <option value="no" {{ request('special') == 'no' ? 'selected' : '' }}>
                                        خیر
                                    </option>
                                </select>
                            </fieldset>
                        </div>



                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
