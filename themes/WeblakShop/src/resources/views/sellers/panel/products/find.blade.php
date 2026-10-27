@extends('front::sellers.panel.layouts.master')
@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/plugins/datatable/datatable.css') }}">
@endpush

@section('content')
    <div class="content-wrapper">

        <div class="content-body">

            <!-- filter start -->
        <!-- filter end -->
            <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
                <div class="c-grid__col">
                    <div class="c-content-page__header">
                        <span class="c-content-page__header-action">جستجو یا درج محصول</span>
                        <span class="c-content-page__header-desc">محصولی که قصد فروش آن را دارید، جستجو کنید. در غیر این‌صورت از "ایجاد کالای جدید" اقدام به درج کالای خود کنید</span>
                    </div>
                </div>
            </div>
            <form id="filter-products-form" method="GET"
                  action="{{ route('seller.products.index') }}">
            <div class="card">
                <div class="card-content collapse show">
                    <div class="card-body">
                        <div class="users-list-filter">
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>عنوان</label>
                                        <fieldset class="form-group">
                                            <input class="form-control datatable-filter" name="title" value="{{ request('title') }}">
                                        </fieldset>
                                    </div>


                                </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-md-3">
                    <section  class="card">
                        <div class="card-body">
                            <div class="users-list-filter">
                                <div class="row">

                                        <div class="col-md-12">
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
                                        <div class="col-md-12">
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

                                        <div class="col-md-12">
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
                            </div>
                        </div>
                    </section>
                </div>

                <div class="col-md-9">
                    <section id="main-card" class="card">
                        <div class="card-header">
                            <h4 class="card-title">جستجو یا درج محصول</h4>
                            <div class="d-flex">
                                <div class="c-ui-paginator__total mr-3 mt-0-8" data-rows="0">
                                    تعداد نتایج: <span><b>0</b> مورد</span>
                                </div>
                                <a href="{{route('seller.products.create')}}"><div class="c-mega-campaigns__btns-green-plus uk-margin-remove">
                                        ایجاد کالای جدید
                                        <i class="fa-solid fa-plus mr-0-5"></i>
                                    </div></a>
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <div class="datatable datatable-bordered datatable-head-custom" id="products_datatable" data-action="{{ route('seller.products.apiIndexFind') }}"></div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
            </form>

        </div>
    </div>
@endsection
@push('scripts')
    <script src="{{ asset('back/app-assets/plugins/datatable/scripts.bundle.js') }}"></script>
    <script src="{{ asset('back/app-assets/plugins/datatable/core.datatable.js') }}"></script>
    <script src="{{ asset('back/app-assets/plugins/datatable/datatable.checkbox.js') }}"></script>

    <script src="{{ theme_asset('js/pages/sellers/products/find.js') }}?v=4"></script>
    <script src="{{ theme_asset('js/pages/sellers/products/filters.js') }}?v=2"></script>
@endpush
