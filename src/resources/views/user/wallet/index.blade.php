{{-- کیف پول — قرارداد WalletController@index : $user, $wallet, $gateways, $histories, $active --}}
@extends('front::user.layouts.master', ['title' => 'کیف پول'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    @if(session('message') == 'ok')
        <div class="alert alert-success rounded-3 font-14" role="alert">
            <i class="bi bi-check-circle-fill"></i> افزایش موجودی با موفقیت انجام شد.
        </div>
    @elseif(session('transaction-error'))
        <div class="alert alert-danger rounded-3 font-14" role="alert">
            <i class="bi bi-x-circle-fill"></i> {{ session('transaction-error') }}
        </div>
    @endif

    {{-- کارم موجودی --}}
    <div class="content-box saniar-wallet-card mb-3">
        <div class="container-fluid">
            <div class="row align-items-center gy-3">
                <div class="col-lg-8">
                    <span class="font-14 text-muted">موجودی کیف پول شما</span>
                    <div class="d-flex align-items-center mt-1">
                        <span class="font-30 fw-bold main-color-one-color ms-2">{{ number_format($wallet->balance()) }}</span>
                        <span class="font-14 text-muted">تومان</span>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn border-0 main-color-one-bg text-white rounded-pill px-4 waves-effect waves-light"
                                data-bs-toggle="modal" data-bs-target="#walletDepositModal">
                            <i class="bi bi-plus-circle me-1"></i> افزایش اعتبار
                        </button>
                        <button type="button" class="btn border-0 {{ $wallet->balance() > 0 ? 'main-color-two-bg text-white' : 'btn-light text-muted disabled' }} rounded-pill px-4"
                                data-bs-toggle="modal" data-bs-target="#walletWithdrawModal"
                                @if($wallet->balance() <= 0) disabled @endif>
                            <i class="bi bi-arrow-left-right me-1"></i> برداشت وجه
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- تاریخچه تراکنش‌ها --}}
    <div class="content-box">
        <div class="container-fluid">
            <div class="title-panel mb-3 d-flex align-items-baseline">
                <i class="bi bi-clock-history font-25 main-color-one-color"></i>
                <h2 class="font-16 ms-2 mb-0">تاریخچه <span class="main-color-one-color">کیف پول</span></h2>
            </div>

            <div class="responsive-table p-0">
                <table class="table main-table rounded-0">
                    <thead class="text-bg-dark bg-opacity-75 text-center">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">مبلغ (تومان)</th>
                        <th scope="col">نوع تراکنش</th>
                        <th scope="col">تاریخ</th>
                        <th scope="col">وضعیت</th>
                        <th scope="col">جزییات</th>
                    </tr>
                    </thead>
                    <tbody class="text-center">
                    @if($histories->count())
                        @foreach ($histories as $history)
                            @php $is_deposit = $history->type == 'deposit'; @endphp
                            <tr>
                                <td class="font-14">{{ $loop->iteration }}</td>
                                <td class="font-14 {{ $is_deposit ? 'text-success' : 'text-danger' }}">{{ number_format($history->amount) }}</td>
                                <td class="font-14">
                                    @if($history->withdraw)
                                        <span class="badge bg-danger rounded-pill">برداشت وجه</span>
                                    @elseif($is_deposit)
                                        <span class="badge bg-success rounded-pill">افزایش اعتبار</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill">کاهش اعتبار</span>
                                    @endif
                                </td>
                                <td class="font-14 ltr-inline">{{ \Morilog\Jalali\Jalalian::fromDateTime($history->created_at)->format('Y/m/d H:i') }}</td>
                                <td class="font-14">
                                    @if($history->status == 'success')
                                        <span class="badge bg-success rounded-pill">موفق</span>
                                        @if($history->withdraw)
                                            @if($history->status_pay == 'waiting')
                                                <span class="badge bg-warning text-dark rounded-pill">در انتظار پرداخت</span>
                                            @elseif($history->status_pay == 'pay')
                                                <span class="badge bg-success rounded-pill">پرداخت شده</span>
                                            @elseif($history->status_pay == 'unpay')
                                                <span class="badge bg-danger rounded-pill">پرداخت نشده</span>
                                            @endif
                                        @endif
                                    @else
                                        <span class="badge bg-danger rounded-pill">ناموفق</span>
                                    @endif
                                </td>
                                <td class="font-14">
                                    <button type="button" class="btn btn-sm btn-light border rounded-3 js-show-history"
                                            data-action="{{ route('front.wallet.show', ['wallet' => $history]) }}"
                                            aria-label="جزئیات تراکنش">
                                        <i class="bi bi-chevron-left main-color-one-color"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="py-5">
                                <i class="bi bi-wallet2 text-muted" style="font-size:2.6rem;"></i>
                                <h3 class="font-16 mt-3">تراکنشی ثبت نشده است</h3>
                                <p class="text-muted font-14 mb-0">شما تا به حال تراکنشی در کیف پول خود نداشته‌اید.</p>
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($histories->hasPages())
        <div class="my-paginate mt-4">
            {{ $histories->links('front::components.paginate') }}
        </div>
    @endif

    {{-- مودال افزایش موجودی --}}
    <div class="modal fade" id="walletDepositModal" tabindex="-1" aria-labelledby="walletDepositLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title font-16" id="walletDepositLabel">
                        <i class="bi bi-plus-circle main-color-one-color me-1"></i> افزایش موجودی کیف پول
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <form id="wallet-create-form" action="{{ route('front.wallet.store') }}" method="POST" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-14 fw-bold" for="wallet-amount">مبلغ (تومان) <span class="text-danger">*</span></label>
                            <input type="number" id="wallet-amount" name="amount" class="form-control rounded-3"
                                   placeholder="مثال : 100,000" min="1000" required>
                        </div>
                        <div class="mb-1">
                            <label class="form-label font-14 fw-bold" for="wallet-gateway">انتخاب درگاه پرداخت <span class="text-danger">*</span></label>
                            <select class="form-select rounded-3" name="gateway" id="wallet-gateway" required>
                                <option value="">درگاه مورد نظر خود را انتخاب کنید</option>
                                @foreach ($gateways as $gateway)
                                    <option value="{{ $gateway->key }}">{{ $gateway->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="wallet-deposit-error" class="font-12 text-danger mt-2 d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn border-0 main-color-one-bg text-white rounded-3 px-4">
                            <i class="bi bi-credit-card me-1"></i> افزایش موجودی
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- مودال برداشت --}}
    <div class="modal fade" id="walletWithdrawModal" tabindex="-1" aria-labelledby="walletWithdrawLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title font-16" id="walletWithdrawLabel">
                        <i class="bi bi-arrow-left-right main-color-one-color me-1"></i> برداشت از کیف پول
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <form id="wallet-withdraw-form" action="{{ route('front.wallet.withdraw') }}" method="POST"
                      data-balance="{{ $wallet->balance() }}" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label font-14 fw-bold" for="withdraw-amount">مبلغ (تومان) <span class="text-danger">*</span></label>
                            <input type="number" id="withdraw-amount" name="amount" class="form-control rounded-3"
                                   placeholder="مثال : 100,000" min="1000" required>
                        </div>
                        <p class="font-13 text-muted mb-0">
                            <i class="bi bi-wallet2 main-color-one-color me-1"></i>
                            موجودی کیف پول: <strong>{{ number_format($wallet->balance()) }}</strong> تومان
                        </p>
                        <div id="wallet-withdraw-error" class="font-12 text-danger mt-2 d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn border-0 main-color-two-bg text-white rounded-3 px-4">
                            <i class="bi bi-send me-1"></i> درخواست برداشت
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- مودال جزئیات تراکنش (محتوای AJAX از wallet/show) --}}
    <div class="modal fade" id="history-show-modal" tabindex="-1" aria-labelledby="historyShowLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content rounded-3">
                <div class="modal-header">
                    <h5 class="modal-title font-16" id="historyShowLabel">
                        <i class="bi bi-receipt main-color-one-color me-1"></i> جزئیات تراکنش
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
                </div>
                <div id="history-detail" class="modal-body"></div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush
@push('scripts')
    <script src="{{ theme_asset('js/pages/user/panel.js') }}?v=1"></script>
@endpush
