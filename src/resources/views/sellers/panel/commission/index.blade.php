{{-- جدول کمیسیون دسته‌بندی‌ها — سانیار فاز I
    قرارداد WalletSellerController@commission : $categoriesWithCommission
    (کالکشن آرایه‌های [id,name,commission,commission_type,source_category,children] با ارث‌بری از والد) --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'کمیسیون و هزینه‌ها', 'active' => 'commission'])

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
                    <h5 class="fw-bold mb-1"><i class="bi bi-percent main-color-one-color ms-1"></i>کمیسیون و هزینه‌ها</h5>
                    <p class="font-13 text-muted mb-0">درصد کمیسیون هر گروه کالایی؛ مقادیر تعیین‌نشده از دستهٔ والد به ارث می‌رسند.</p>
                </div>
                <a href="{{ route('seller.wallet.index') }}" class="btn btn-outline-primary rounded-3 font-14">
                    <i class="bi bi-wallet2 ms-1"></i>کیف پول
                </a>
            </div>
        </div>
    </div>

    {{-- خلاصه آماری --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-4">
            <div class="seller-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="stat-icon"><i class="bi bi-arrow-up"></i></span>
                </div>
                <span class="stat-value d-block">{{ $categoriesWithCommission->max('commission') ?? 0 }}%</span>
                <span class="stat-label d-block">حداکثر کمیسیون</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="seller-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="stat-icon"><i class="bi bi-graph-up"></i></span>
                </div>
                <span class="stat-value d-block">{{ round($categoriesWithCommission->avg('commission') ?? 0) }}%</span>
                <span class="stat-label d-block">میانگین کمیسیون</span>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="seller-stat-card">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="stat-icon"><i class="bi bi-tags"></i></span>
                </div>
                <span class="stat-value d-block">{{ number_format($categoriesWithCommission->count()) }}</span>
                <span class="stat-label d-block">تعداد دسته‌بندی‌های ریشه</span>
            </div>
        </div>
    </div>

    {{-- جدول درختی --}}
    <div class="content-box">
        <div class="container-fluid">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 py-3 border-bottom">
                <h6 class="fw-bold mb-0 font-14"><i class="bi bi-diagram-3 main-color-one-color ms-1"></i>جدول کمیسیون دسته‌بندی‌ها</h6>
                <div class="input-group input-group-sm" style="max-width:260px;">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" class="form-control font-13" id="sfc-search" placeholder="جستجوی دسته‌بندی…" aria-label="جستجوی دسته‌بندی">
                </div>
            </div>
            <div class="table-responsive py-2">
                <table class="table align-middle site-tabel mb-2" id="sfc-commission-table">
                    <thead class="text-bg-dark bg-opacity-75 text-center">
                    <tr>
                        <th scope="col" style="width:90px;">#</th>
                        <th scope="col">نام دسته‌بندی</th>
                        <th scope="col" class="text-nowrap">درصد کمیسیون</th>
                        <th scope="col" class="text-nowrap">نوع کمیسیون</th>
                        <th scope="col">وضعیت</th>
                    </tr>
                    </thead>
                    <tbody class="text-center">
                    @foreach($categoriesWithCommission as $index => $category)
                        @include('front::sellers.panel.commission.category-row', ['category' => $category, 'level' => 0, 'index' => $index + 1])
                    @endforeach
                    </tbody>
                </table>
            </div>

            {{-- راهنمای علائم --}}
            <div class="alert alert-info font-13 mb-3" role="alert">
                <h6 class="font-14 fw-bold mb-2"><i class="bi bi-info-circle ms-1"></i>راهنمای علائم:</h6>
                <ul class="mb-0 font-13">
                    <li><span class="badge rounded-pill success-label">مستقیم</span> مقدار تعیین‌شده برای خود دسته</li>
                    <li><span class="badge rounded-pill warning-label">ارثی</span> از دستهٔ والد به ارث رسیده</li>
                    <li><span class="badge rounded-pill secondary-label">پیش‌فرض صفر</span> هیچ کمیسیونی تعیین نشده</li>
                    <li><span class="badge rounded-pill danger-label">صفر عمدی</span> مدیر این دسته را بدون کمیسیون قرار داده</li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/sellers/seller-commission.js') }}?v=1"></script>
@endpush
