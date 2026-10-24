{{-- جستجو یا درج محصول — سانیار فاز H
    قرارداد ProductSellerController@find : $categories
    جدول از apiIndexFind (POST، همهٔ محصولات منتشرشده) تغذیه می‌شود --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'جستجو یا درج محصول', 'active' => 'products-find'])

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
                    <h5 class="fw-bold mb-1"><i class="bi bi-search main-color-one-color ms-1"></i>جستجو یا درج محصول</h5>
                    <p class="font-13 text-muted mb-0">محصولی که قصد فروش آن را دارید جستجو کنید و تنوع اضافه کنید؛ در غیر این‌صورت کالای جدید ثبت کنید.</p>
                </div>
                <a href="{{ route('seller.products.create') }}" class="btn main-color-one-bg text-white rounded-3 font-14">
                    <i class="bi bi-plus-lg ms-1"></i>ایجاد کالای جدید
                </a>
            </div>
        </div>
    </div>

    {{-- فیلتر عنوان --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <form id="filter-products-form" class="row g-2 align-items-end py-3" onsubmit="return false;">
                <div class="col-md-8">
                    <label class="form-label font-13 fw-bold" for="sfp-filter-title">عنوان</label>
                    <input class="form-control" id="sfp-filter-title" name="title"
                           placeholder="نام کالای موردنظر را بنویسید" value="{{ request('title') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label font-13 fw-bold" for="sfp-filter-category">دسته‌بندی</label>
                    <select class="form-select" id="sfp-filter-category" name="category_id[]" multiple size="3">
                        @foreach ($categories as $category)
                            @php $depth = $category->parents()->count(); @endphp
                            <option value="{{ $category->id }}"
                                {{ (request()->input('category_id') && in_array($category->id, request()->input('category_id'))) ? 'selected' : '' }}>
                                {{ str_repeat('— ', $depth) }}{{ $category->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    {{-- جدول جستجو --}}
    <div class="content-box" id="main-card">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                <h2 class="font-15 mb-0"><i class="bi bi-table main-color-one-color me-1"></i>نتایج جستجو</h2>
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
                        <th scope="col">کمترین قیمت</th>
                        <th scope="col">عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="text-center saniar-dt-body">
                    <tr><td colspan="6" class="py-4 text-muted font-14 saniar-dt-loading"><span class="spinner-border spinner-border-sm ms-2 align-middle" role="status" aria-hidden="true"></span>در حال بارگذاری…</td></tr>
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
                <nav aria-label="صفحه‌بندی نتایج" class="saniar-dt-pagination">
                    <ul class="pagination pagination-sm mb-0"></ul>
                </nav>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-products.js') }}?v=1"
            data-saniar-products="find"
            data-action="{{ route('seller.products.apiIndexFind') }}"></script>
@endpush
