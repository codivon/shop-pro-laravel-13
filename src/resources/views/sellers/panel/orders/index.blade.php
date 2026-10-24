{{-- تاریخچه سفارش‌های فروشنده — سانیار فاز I
    قرارداد OrderSellerController@index : $sizeTypes (سازگاری امضا — استفاده نمی‌شود)
    جدول از apiIndex (POST) تغذیه می‌شود: query{fullname,username,id,status,shipping_status,
    product_name,product_id,from_date,to_date} + pagination + sort --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'تاریخچه سفارش‌ها', 'active' => 'orders'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/seller.css') }}?v=3">
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush

@section('seller-content')
    {{-- سربرگ صفحه --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-cart-check main-color-one-color ms-1"></i>تاریخچه سفارش‌ها</h5>
                    <p class="font-13 text-muted mb-0">سفارش‌هایی که اقلام آن‌ها توسط شما فروش رفته است؛ وضعیت ارسال هر مرسوله را از همین‌جا مدیریت کنید.</p>
                </div>
                <a href="{{ route('seller.orders.notCompleted') }}" class="btn btn-outline-primary rounded-3 font-14">
                    <i class="bi bi-hourglass-split ms-1"></i>محصولات منتظر ارسال
                </a>
            </div>
        </div>
    </div>

    {{-- فیلترها --}}
    <div class="content-box mb-3">
        <div class="container-fluid">
            <div class="title-panel py-3">
                <h2 class="font-15 mb-3"><i class="bi bi-funnel main-color-one-color me-1"></i>فیلترها</h2>
                <form id="sfo-filter-form" class="row g-2" onsubmit="return false;">
                    <div class="col-md-3 col-6">
                        <label class="form-label font-13 fw-bold" for="sfo-fullname">نام خریدار</label>
                        <input class="form-control font-13" id="sfo-fullname" name="fullname" placeholder="نام و نام خانوادگی">
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="form-label font-13 fw-bold" for="sfo-username">نام کاربری</label>
                        <input class="form-control font-13" id="sfo-username" name="username" placeholder="username">
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label font-13 fw-bold" for="sfo-id">شماره سفارش</label>
                        <input class="form-control font-13" id="sfo-id" name="id" inputmode="numeric" placeholder="مثال: ۱۲۳">
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label font-13 fw-bold" for="sfo-status">وضعیت پرداخت</label>
                        <select class="form-select font-13" id="sfo-status" name="status">
                            <option value="all">همه</option>
                            <option value="paid">پرداخت شده</option>
                            <option value="unpaid">پرداخت نشده</option>
                            <option value="canceled">لغو شده</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label font-13 fw-bold" for="sfo-shipping">وضعیت ارسال</label>
                        <select class="form-select font-13" id="sfo-shipping" name="shipping_status">
                            <option value="all">همه</option>
                            <option value="w-pending">در انتظار بررسی</option>
                            <option value="pending">در حال بررسی</option>
                            <option value="processing">در حال پردازش</option>
                            <option value="waiting">منتظر ارسال</option>
                            <option value="sent">ارسال شد</option>
                            <option value="post-sent">تحویل به پست</option>
                            <option value="delivered">تحویل داده شد</option>
                            <option value="canceled">لغو شده</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-6">
                        <label class="form-label font-13 fw-bold" for="sfo-product-name">نام محصول</label>
                        <input class="form-control font-13" id="sfo-product-name" name="product_name" placeholder="عنوان کالا">
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label font-13 fw-bold" for="sfo-product-id">شناسه محصول</label>
                        <input class="form-control font-13" id="sfo-product-id" name="product_id" inputmode="numeric" placeholder="مثال: ۵">
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label font-13 fw-bold" for="sfo-from-date">از تاریخ</label>
                        <input class="form-control font-13 ltr-direction" id="sfo-from-date" name="from_date"
                               inputmode="numeric" placeholder="۱۴۰۳-۰۸-۰۱" title="تاریخ شمسی با فرمت YYYY-MM-DD">
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label font-13 fw-bold" for="sfo-to-date">تا تاریخ</label>
                        <input class="form-control font-13 ltr-direction" id="sfo-to-date" name="to_date"
                               inputmode="numeric" placeholder="۱۴۰۳-۰۸-۳۱" title="تاریخ شمسی با فرمت YYYY-MM-DD">
                    </div>
                    <div class="col-md-3 col-12 d-flex align-items-end gap-2">
                        <button type="button" class="btn main-color-one-bg text-white rounded-3 font-13 flex-fill" id="sfo-apply-btn">
                            <i class="bi bi-funnel ms-1"></i>اعمال فیلتر
                        </button>
                        <button type="button" class="btn btn-outline-secondary rounded-3 font-13" id="sfo-reset-btn" aria-label="پاک کردن فیلترها">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- جدول سفارش‌ها --}}
    <div class="content-box" id="main-card">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                <h2 class="font-15 mb-0"><i class="bi bi-table main-color-one-color me-1"></i>لیست سفارش‌ها</h2>
                <div class="d-flex align-items-center gap-2">
                    <span class="font-13 text-muted saniar-dt-total" aria-live="polite">تعداد نتایج: <b>۰</b> مورد</span>
                    <button type="button" class="btn btn-outline-success rounded-3 font-13" data-bs-toggle="modal" data-bs-target="#sfo-export-modal">
                        <i class="bi bi-file-earmark-excel ms-1"></i>خروجی اکسل
                    </button>
                </div>
            </div>

            {{-- نوار عملیات گروهی (با انتخاب ردیف‌ها ظاهر می‌شود) --}}
            <div class="saniar-bulk-bar mb-2" id="sfo-bulk-bar" hidden>
                <div class="d-flex flex-wrap align-items-center gap-2 py-2 px-3 rounded-3">
                    <span class="font-13 fw-bold text-danger"><span id="sfo-selected-count">۰</span> سفارش انتخاب شده</span>
                    <button type="button" class="btn btn-sm main-color-one-bg text-white rounded-3 font-13" data-sfo-print="{{ route('seller.orders.printAll') }}">
                        <i class="bi bi-printer ms-1"></i>پرینت فاکتورها
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-3 font-13" data-sfo-print="{{ route('seller.orders.printAllShippingFormsMin') }}">
                        <i class="bi bi-upc-scan ms-1"></i>پرینت لیبل‌ها
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-3 font-13" data-sfo-print="{{ route('seller.orders.printAllShippingForms') }}">
                        <i class="bi bi-postcard ms-1"></i>پرینت برچسب‌ها
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 font-13" data-bs-toggle="modal" data-bs-target="#sfo-bulk-status-modal">
                        <i class="bi bi-arrow-repeat ms-1"></i>تغییر وضعیت گروهی
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-3 font-13" id="sfo-clear-selection">
                        <i class="bi bi-x-circle ms-1"></i>لغو انتخاب
                    </button>
                </div>
            </div>

            <div class="table-responsive saniar-dt-wrap">
                <table class="table align-middle site-tabel mb-0 saniar-orders-table">
                    <thead class="text-bg-dark bg-opacity-75 text-center">
                    <tr>
                        <th scope="col" class="sfo-check-all-wrap" style="width:36px;">
                            <input class="form-check-input sfo-check-all" type="checkbox" aria-label="انتخاب همه سفارش‌ها">
                        </th>
                        <th scope="col" class="saniar-dt-sort" data-field="id" style="cursor:pointer;">ردیف <i class="bi bi-arrow-down-up font-12"></i></th>
                        <th scope="col" class="text-nowrap">شماره سفارش</th>
                        <th scope="col">خریدار</th>
                        <th scope="col" class="text-nowrap">تاریخ ثبت</th>
                        <th scope="col" class="text-nowrap">مبلغ فروش من</th>
                        <th scope="col">وضعیت پرداخت</th>
                        <th scope="col">وضعیت ارسال</th>
                        <th scope="col">عملیات</th>
                    </tr>
                    </thead>
                    <tbody class="text-center saniar-dt-body">
                    <tr><td colspan="9" class="py-4 text-muted font-14"><span class="spinner-border spinner-border-sm ms-2 align-middle" role="status" aria-hidden="true"></span>در حال بارگذاری…</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- صفحه‌بندی --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                <div class="d-flex align-items-center gap-2">
                    <label class="font-13 text-muted" for="sfo-perpage">تعداد در صفحه:</label>
                    <select class="form-select form-select-sm w-auto" id="sfo-perpage">
                        <option value="10">۱۰</option>
                        <option value="25">۲۵</option>
                        <option value="50">۵۰</option>
                    </select>
                </div>
                <nav aria-label="صفحه‌بندی سفارش‌ها" class="saniar-dt-pagination">
                    <ul class="pagination pagination-sm mb-0"></ul>
                </nav>
            </div>
        </div>
    </div>

    {{-- مودال خروجی اکسل --}}
    <div class="modal fade" id="sfo-export-modal" tabindex="-1" aria-hidden="true" aria-labelledby="sfo-export-title">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-15" id="sfo-export-title"><i class="bi bi-file-earmark-excel main-color-one-color ms-1"></i>فیلدهای خروجی اکسل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <form id="sfo-export-form" action="{{ route('seller.orders.export') }}" method="GET" target="_blank">
                    <div class="modal-body">
                        <div class="row g-2" id="sfo-export-fields">
                            @php
                                $export_fields = [
                                    'row' => 'ردیف', 'id' => 'شماره سفارش', 'user_id' => 'شناسه کاربر',
                                    'name' => 'نام و نام خانوادگی', 'mobile' => 'شماره همراه',
                                    'province' => 'استان', 'city' => 'شهر', 'postal_code' => 'کد پستی',
                                    'address' => 'آدرس کامل', 'carrier' => 'شیوه تحویل',
                                    'created_at' => 'تاریخ ثبت', 'shipping_cost' => 'هزینه ارسال',
                                    'total_discount' => 'تخفیف', 'price' => 'جمع قیمت',
                                    'description' => 'توضیحات سفارش', 'products' => 'لیست محصولات',
                                ];
                            @endphp
                            @foreach($export_fields as $key => $label)
                                <div class="col-md-6 col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="filters[{{ $key }}]" value="1" id="sfo-export-{{ $key }}" checked>
                                        <label class="form-check-label font-13" for="sfo-export-{{ $key }}">{{ $label }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="export_type" value="excel">
                        {{-- فیلترهای جاری روی خروجی اعمال می‌شود (JS هنگام باز شدن مودال مقدار می‌دهد) --}}
                        <div id="sfo-export-query"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-3 font-13" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn main-color-one-bg text-white rounded-3 font-13">
                            <i class="bi bi-download ms-1"></i>دریافت فایل
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- مودال تغییر وضعیت گروهی --}}
    <div class="modal fade" id="sfo-bulk-status-modal" tabindex="-1" aria-hidden="true" aria-labelledby="sfo-bulk-status-title">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-15" id="sfo-bulk-status-title"><i class="bi bi-arrow-repeat main-color-one-color ms-1"></i>تغییر وضعیت ارسال گروهی</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <form id="sfo-bulk-status-form" data-action="{{ route('seller.orders.shippings-status') }}" onsubmit="return false;">
                    <div class="modal-body">
                        <label class="form-label font-13 fw-bold" for="sfo-bulk-status">وضعیت جدید</label>
                        <select class="form-select font-13" id="sfo-bulk-status" name="status">
                            <option value="w-pending">در انتظار بررسی</option>
                            <option value="pending">در حال بررسی</option>
                            <option value="processing">در حال پردازش</option>
                            <option value="waiting">منتظر ارسال</option>
                            <option value="sent">ارسال شد</option>
                        </select>
                        <div class="alert alert-warning font-12 mt-3 mb-0" role="alert">
                            <i class="bi bi-exclamation-triangle ms-1"></i>سفارش‌های پرداخت‌نشده، لغوشده یا رزروشده تغییر نمی‌کنند و خطا می‌گیرند.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-3 font-13" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn main-color-one-bg text-white rounded-3 font-13" id="sfo-bulk-status-submit">
                            <i class="bi bi-check-lg ms-1"></i>ذخیره
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-orders.js') }}?v=1"
            data-saniar-orders="index"
            data-action="{{ route('seller.orders.apiIndex') }}"></script>
@endpush
