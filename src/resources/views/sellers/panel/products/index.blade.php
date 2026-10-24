{{-- مدیریت محصولات فروشنده — سانیار فاز H
    قرارداد ProductSellerController@index : $categories
    جدول از apiIndex (POST) تغذیه می‌شود: query{title, category_id[], stock, special} + pagination --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'مدیریت محصولات', 'active' => 'products'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=2">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush

@section('seller-content')
    {{-- سربرگ صفحه --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-box-seam main-color-one-color ms-1"></i>مدیریت محصولات</h5>
                    <p class="font-13 text-muted mb-0">محصولات و تنوع‌های ثبت‌شدهٔ شما؛ برای فروش محصول جدید از «جستجو یا درج محصول» اقدام کنید.</p>
                </div>
                <a href="{{ route('seller.products.find') }}" class="btn main-color-one-bg text-white rounded-3 font-14">
                    <i class="bi bi-plus-lg ms-1"></i>جستجو یا درج محصول
                </a>
            </div>
        </div>
    </div>

    {{-- فیلترها --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="title-panel py-3">
                <h2 class="font-15 mb-3"><i class="bi bi-funnel main-color-one-color me-1"></i>فیلترها</h2>
                <form id="filter-products-form" class="row g-2" onsubmit="return false;">
                    <div class="col-md-4">
                        <label class="form-label font-13 fw-bold" for="sfp-filter-title">عنوان</label>
                        <input class="form-control" id="sfp-filter-title" name="title"
                               placeholder="جستجو بر اساس عنوان" value="{{ request('title') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-13 fw-bold" for="sfp-filter-category">دسته‌بندی‌ها</label>
                        <select class="form-select" id="sfp-filter-category" name="category_id[]" multiple size="4">
                            @foreach ($categories as $category)
                                @php $depth = $category->parents()->count(); @endphp
                                <option value="{{ $category->id }}"
                                    {{ (request()->input('category_id') && in_array($category->id, request()->input('category_id'))) ? 'selected' : '' }}>
                                    {{ str_repeat('— ', $depth) }}{{ $category->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-13 fw-bold" for="sfp-filter-stock">موجودی</label>
                        <select class="form-select" id="sfp-filter-stock" name="stock">
                            <option value="all">همه</option>
                            <option value="available">موجود</option>
                            <option value="unavailable">ناموجود</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label font-13 fw-bold" for="sfp-filter-special">محصول ویژه</label>
                        <select class="form-select" id="sfp-filter-special" name="special">
                            <option value="all">همه</option>
                            <option value="yes">بله</option>
                            <option value="no">خیر</option>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- جدول محصولات --}}
    <div class="content-box" id="main-card">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                <h2 class="font-15 mb-0"><i class="bi bi-table main-color-one-color me-1"></i>لیست محصولات</h2>
                <span class="font-13 text-muted saniar-dt-total" aria-live="polite">تعداد نتایج: <b>۰</b> مورد</span>
            </div>

            <div class="table-responsive saniar-dt-wrap">
                <table class="table align-middle site-tabel mb-0 saniar-products-table">
                    <thead class="text-bg-dark bg-opacity-75 text-center">
                    <tr>
                        <th scope="col" class="saniar-dt-sort" data-field="id" style="cursor:pointer;">ردیف <i class="bi bi-arrow-down-up font-12"></i></th>
                        <th scope="col">تصویر</th>
                        <th scope="col">عنوان محصول</th>
                        <th scope="col">گروه</th>
                        <th scope="col">برند</th>
                        <th scope="col">وضعیت</th>
                        <th scope="col" class="text-nowrap">تعداد تنوع</th>
                        <th scope="col">عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="text-center saniar-dt-body">
                    <tr><td colspan="8" class="py-4 text-muted font-14 saniar-dt-loading"><span class="spinner-border spinner-border-sm ms-2 align-middle" role="status" aria-hidden="true"></span>در حال بارگذاری…</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- صفحه‌بندی --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                <div class="d-flex align-items-center gap-2">
                    <label class="font-13 text-muted" for="sfp-perpage">تعداد در صفحه:</label>
                    <select class="form-select form-select-sm w-auto" id="sfp-perpage">
                        <option value="10">۱۰</option>
                        <option value="25">۲۵</option>
                        <option value="50">۵۰</option>
                    </select>
                </div>
                <nav aria-label="صفحه‌بندی محصولات" class="saniar-dt-pagination">
                    <ul class="pagination pagination-sm mb-0"></ul>
                </nav>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-products.js') }}?v=1"
            data-saniar-products="index"
            data-action="{{ route('seller.products.apiIndex') }}"></script>
@endpush
