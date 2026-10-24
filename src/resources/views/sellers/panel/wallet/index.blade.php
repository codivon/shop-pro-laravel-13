{{-- کیف پول فروشنده — سانیار فاز I
    قرارداد WalletSellerController@index :
    $seller, $wallet, $gateways, $histories (paginate 20) --}}
@extends('front::sellers.panel.layouts.master', ['title' => 'کیف پول و واریزی‌ها', 'active' => 'wallet'])

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
                    <h5 class="fw-bold mb-1"><i class="bi bi-wallet2 main-color-one-color ms-1"></i>کیف پول و واریزی‌ها</h5>
                    <p class="font-13 text-muted mb-0">موجودی، شارژ آنلاین، درخواست برداشت و گردش مالی حساب فروشندگی شما.</p>
                </div>
                <a href="{{ route('seller.commission') }}" class="btn btn-outline-primary rounded-3 font-14">
                    <i class="bi bi-percent ms-1"></i>جدول کمیسیون‌ها
                </a>
            </div>
        </div>
    </div>

    {{-- پیام‌های سشن --}}
    @if(session('message') == 'ok')
        <div class="alert alert-success font-14" role="alert">
            <i class="bi bi-check-circle ms-1"></i><strong>افزایش موجودی با موفقیت انجام شد.</strong>
        </div>
    @endif
    @if(session('transaction-error'))
        <div class="alert alert-danger font-14" role="alert">
            <i class="bi bi-x-circle ms-1"></i><strong>{{ session('transaction-error') }}</strong>
        </div>
    @endif

    {{-- کارت موجودی --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="saniar-wallet-hero">
                <div class="row g-3 align-items-center">
                    <div class="col-md-7">
                        <span class="font-14 text-muted d-block"><i class="bi bi-wallet2 ms-1"></i>موجودی قابل استفاده کیف پول شما</span>
                        <div class="d-flex align-items-baseline gap-2 mt-2">
                            <span class="saniar-wallet-amount">{{ number_format($wallet->balance()) }}</span>
                            <span class="font-14 text-muted">تومان</span>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn main-color-one-bg text-white rounded-3 font-14 flex-fill"
                                    data-bs-toggle="modal" data-bs-target="#sfo-deposit-modal">
                                <i class="bi bi-plus-lg ms-1"></i>افزایش اعتبار
                            </button>
                            <button type="button" class="btn btn-outline-danger rounded-3 font-14 flex-fill {{ $wallet->balance() > 0 ? '' : 'disabled' }}"
                                    data-bs-toggle="modal" data-bs-target="#sfo-withdraw-modal">
                                <i class="bi bi-arrow-up-left ms-1"></i>درخواست برداشت
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- تاریخچه تراکنش‌ها --}}
    <div class="content-box">
        <div class="container-fluid">
            <div class="py-3 border-bottom">
                <h6 class="fw-bold mb-0 font-14"><i class="bi bi-clock-history main-color-one-color ms-1"></i>تاریخچه کیف پول</h6>
            </div>
            @if($histories->count())
                <div class="table-responsive py-2">
                    <table class="table align-middle site-tabel mb-2">
                        <thead class="text-bg-dark bg-opacity-75 text-center">
                        <tr>
                            <th scope="col" style="width:60px;">ردیف</th>
                            <th scope="col">مبلغ (تومان)</th>
                            <th scope="col">نوع تراکنش</th>
                            <th scope="col" class="text-nowrap">تاریخ</th>
                            <th scope="col">وضعیت</th>
                            <th scope="col">عملیات</th>
                        </tr>
                        </thead>
                        <tbody class="text-center">
                        @foreach($histories as $history)
                            @php $is_deposit = $history->type == 'deposit'; @endphp
                            <tr>
                                <td class="font-13">{{ ($histories->currentPage() - 1) * $histories->perPage() + $loop->iteration }}</td>
                                <td class="font-13 fw-bold {{ $is_deposit ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($history->amount) }}
                                    <i class="bi {{ $is_deposit ? 'bi-arrow-down-short' : 'bi-arrow-up-short' }}"></i>
                                </td>
                                <td class="font-13">
                                    @if($history->withdraw)
                                        <span class="badge rounded-pill danger-label">برداشت وجه</span>
                                    @elseif($is_deposit)
                                        <span class="badge rounded-pill success-label">افزایش اعتبار</span>
                                    @else
                                        <span class="badge rounded-pill warning-label">کاهش اعتبار</span>
                                    @endif
                                </td>
                                <td class="font-13">{{ jdate($history->created_at)->format('Y/m/d H:i') }}</td>
                                <td class="font-13">
                                    @if($history->status == 'success')
                                        <span class="badge rounded-pill success-label">موفق</span>
                                        @if($history->withdraw)
                                            @if($history->status_pay == 'waiting')<span class="badge rounded-pill warning-label mt-1">در انتظار پرداخت</span>
                                            @elseif($history->status_pay == 'pay')<span class="badge rounded-pill success-label mt-1">پرداخت شده</span>
                                            @elseif($history->status_pay == 'unpay')<span class="badge rounded-pill danger-label mt-1">پرداخت نشده</span>
                                            @elseif($history->status_pay == 'unpay-refund')<span class="badge rounded-pill danger-label mt-1">عودت وجه</span>
                                            @endif
                                        @endif
                                    @else
                                        <span class="badge rounded-pill danger-label">ناموفق</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('seller.wallet.show', ['wallet' => $history]) }}"
                                       class="btn btn-sm btn-outline-primary rounded-3 font-12">مشاهده</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="py-3">
                    {{ $histories->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-receipt main-color-one-color font-40 d-block mb-2"></i>
                    <p class="font-14 text-muted mb-0">شما تاکنون تراکنشی در کیف پول خود نداشته‌اید.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- مودال افزایش اعتبار --}}
    <div class="modal fade" id="sfo-deposit-modal" tabindex="-1" aria-hidden="true" aria-labelledby="sfo-deposit-title">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-15" id="sfo-deposit-title"><i class="bi bi-plus-lg main-color-one-color ms-1"></i>افزایش موجودی کیف پول</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <form action="{{ route('seller.wallet.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-13 fw-bold" for="sfo-deposit-amount">مبلغ (تومان) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control font-13" id="sfo-deposit-amount" name="amount"
                                   min="10000" max="500000000" placeholder="مثال: 100000" required>
                            <div class="font-12 text-muted mt-1">حداقل ۱۰,۰۰۰ و حداکثر ۵۰۰,۰۰۰,۰۰۰ تومان</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label font-13 fw-bold" for="sfo-deposit-gateway">درگاه پرداخت <span class="text-danger">*</span></label>
                            <select class="form-select font-13" id="sfo-deposit-gateway" name="gateway" required>
                                <option value="">درگاه مورد نظر خود را انتخاب کنید</option>
                                @foreach($gateways as $gateway)
                                    <option value="{{ $gateway->key }}">{{ $gateway->name }}</option>
                                @endforeach
                            </select>
                            @if($gateways->isEmpty())
                                <div class="alert alert-warning font-12 mt-2 mb-0" role="alert">
                                    <i class="bi bi-exclamation-triangle ms-1"></i>در حال حاضر درگاه پرداختی فعال نیست؛ با پشتیبانی تماس بگیرید.
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-3 font-13" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn main-color-one-bg text-white rounded-3 font-13">
                            <i class="bi bi-credit-card ms-1"></i>پرداخت و افزایش موجودی
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- مودال درخواست برداشت --}}
    <div class="modal fade" id="sfo-withdraw-modal" tabindex="-1" aria-hidden="true" aria-labelledby="sfo-withdraw-title">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-15" id="sfo-withdraw-title"><i class="bi bi-arrow-up-left text-danger ms-1"></i>درخواست برداشت از کیف پول</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <form action="{{ route('seller.wallet.withdraw') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info font-13" role="alert">
                            <i class="bi bi-info-circle ms-1"></i>موجودی فعلی شما: <strong>{{ number_format($wallet->balance()) }}</strong> تومان
                        </div>
                        <div class="mb-2">
                            <label class="form-label font-13 fw-bold" for="sfo-withdraw-amount">مبلغ برداشت (تومان) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control font-13" id="sfo-withdraw-amount" name="amount"
                                   min="10000" max="50000000" placeholder="مثال: 500000" required>
                            <div class="font-12 text-muted mt-1">حداقل ۱۰,۰۰۰ و حداکثر ۵۰,۰۰۰,۰۰۰ تومان — پس از ثبت، درخواست شما برای مدیریت ارسال می‌شود.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-3 font-13" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-danger rounded-3 font-13">
                            <i class="bi bi-check-lg ms-1"></i>ثبت درخواست برداشت
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
