@extends('front::user.layouts.master')

@section('user-content')

    {{-- کیف پول — ریسکین‌شده با کارت‌های قالب سانیار (user-panel.html) --}}
    <div class="row">
        @if(session('message') == 'ok')

            <div class="col-lg-12 text-center">
                <div class="alert alert-success mt-4" role="alert">
                    <strong>افزایش موجودی با موفقیت انجام شد</strong>.
                </div>
            </div>

        @elseif(session('transaction-error'))
            <div class="col-lg-12 text-center">
                <div class="alert alert-danger mt-4" role="alert">
                    <strong>{{ session('transaction-error') }}</strong>.
                </div>
            </div>
        @endif
    </div>

    {{-- کارت موجودی --}}
    <div class="content-box">
        <div class="container-fluid py-3">
            <div class="row align-items-center">
                <div class="col-lg-9 col-12">
                    <div class="d-flex align-items-center">
                        <div class="icon ms-3">
                            <i class="bi bi-wallet2 main-color-one-color font-30"></i>
                        </div>
                        <div>
                            <span class="font-14 text-muted d-block">موجودی کیف پول شما</span>
                            <div class="d-flex align-items-baseline mt-1">
                                <span class="font-30 fw-bold me-1">{{ number_format($user->getWallet()->balance()) }}</span>
                                <span class="font-14 text-muted">تومان</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-12">
                    <div class="d-grid gap-2">
                        <span class="btn border-0 main-color-one-bg waves-effect waves-light js-trigger-wallet-modal cursor-pointer"
                              data-bs-toggle="modal" data-bs-target="#new-wallet-show-modal">افزایش اعتبار کیف پول
                            <i class="bi bi-plus-lg text-white"></i>
                        </span>
                        <span class="btn btn-outline-secondary js-trigger-wallet-modal cursor-pointer {{number_format($user->getWallet()->balance())>0 ? '' : 'disabled'}}"
                              data-bs-toggle="modal" data-bs-target="#wallet-show-modal">
                            برداشت از کیف پول
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- تاریخچه --}}
    <div class="ui-boxs mt-3">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold">تاریخچه کیف پول</h4>
                        <span class="btn btn-sm btn-light cursor-pointer" data-bs-toggle="modal" data-bs-target="#new-wallet-show-modal">افزایش موجودی کیف پول</span>
                    </div>
                </div>
                <div class="ui-box-item-desc p-0">
                    <div class="responsive-table p-0">
                        <table class="table main-table rounded-0">
                            <thead class="text-bg-dark bg-opacity-75 text-center">
                            <tr>
                                <th>#</th>
                                <th>مبلغ (تومان)</th>
                                <th>نوع تراکنش</th>
                                <th>تاریخ</th>
                                <th class="text-center">وضعیت</th>
                                <th>جزییات</th>
                            </tr>
                            </thead>
                            <tbody class="text-center">
                            @if($histories->count())
                                @foreach ($histories as $history)
                                    @php
                                        $is_deposit = $history->type == 'deposit';
                                    @endphp
                                    <tr>
                                        <td class="font-14">{{ $loop->iteration }}</td>
                                        <td class="font-14 {{ $is_deposit ? 'text-success' : 'text-danger' }}">{{ number_format($history->amount) }}</td>
                                        <td class="font-14">
                                            @if($history->withdraw)
                                                برداشت وجه
                                                <span class="badge bg-danger ms-1"><i class="bi bi-arrow-left"></i></span>
                                            @else
                                                @if ($is_deposit)
                                                    افزایش اعتبار
                                                    <span class="badge bg-success ms-1"><i class="bi bi-arrow-up"></i></span>
                                                @else
                                                    کاهش اعتبار
                                                    <span class="badge bg-danger ms-1"><i class="bi bi-arrow-down"></i></span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="font-14 ltr">{{ jdate($history->created_at) }}</td>

                                        <td class="font-14">
                                            @if($history->status == 'success')
                                                <span class="badge bg-success">موفق</span>
                                                @if($history->withdraw)
                                                    @if($history->status_pay=="waiting")
                                                        <span class="badge bg-warning">در انتظار پرداخت</span>
                                                    @elseif($history->status_pay=="pay")
                                                        <span class="badge bg-success">پرداخت شده</span>
                                                    @elseif($history->status_pay=="unpay")
                                                        <span class="badge bg-danger">پرداخت نشده</span>
                                                    @endif

                                                @endif
                                            @else
                                                <span class="badge bg-danger">ناموفق</span>
                                            @endif

                                        </td>

                                        <td class="font-14">
                                            <a class="show-history cursor-pointer" data-action="{{ route('front.wallet.show', ['wallet' => $history]) }}" onclick="return false;">
                                                <i class="bi bi-chevron-left main-color-one-color"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach

                            @else
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">شما تا به حال تراکنشی در کیف پول خود نداشته‌اید. </td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="my-paginate mt-4 pb-3">
                        <nav aria-label="Page navigation">
                            {{$histories->links("pagination::bootstrap-4")}}
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    {{-- show Modal --}}
    <div class="modal fade" id="new-wallet-show-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel21">افزایش موجودی کیف پول</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body middle-container">
                    <form id="wallet-create-form" action="{{ route('front.wallet.store') }}" class="setting_form" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 col-12 form-group">
                                <label for="amount">مبلغ (تومان) <span class="text-danger">*</span></label>
                                <input type="number" id="amount" name="amount" class="form-control amount-input" placeholder="مثال : 10,000" required>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="form-group">
                                    <label for="gateway">انتخاب درگاه پرداخت
                                        <span class="text-danger">*</span></label>
                                    <select class="form-select" name="gateway" id="gateway" required>
                                        <option value="">درگاه مورد نظر خود را انتخاب کنید</option>
                                        @foreach ($gateways as $gateway)
                                            <option value="{{ $gateway->key }}">{{ $gateway->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>


                        <hr>
                        <div class="row justify-content-center">
                            <div class="col-md-5">
                                <div class="d-grid">
                                    <button id="submit-btn" class="btn border-0 main-color-one-bg waves-effect waves-light">
                                        افزایش موجودی
                                        <i class="bi bi-check-lg text-white"></i>
                                    </button>
                                </div>
                            </div>
                        </div>


                    </form>


                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="wallet-show-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel21">برداشت از کیف پول</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body middle-container">
                    <form id="wallet-withdraw-form" action="{{ route('front.wallet.withdraw') }}" class="setting_form" data-amount="{{ $user->getWallet()->balance() }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 col-12 form-group">
                                <label for="amount">مبلغ (تومان) <span class="text-danger">*</span></label>
                                <input type="number" id="amount" name="amount" class="form-control amount-input" placeholder="مثال : 10,000" required>
                            </div>
                            <div class="col-md-6 col-12">
                                <div class="font-14 text-muted">موجودی کیف پول:
                                    {{ number_format($user->getWallet()->balance()) }}
                                    تومان
                                </div>
                            </div>
                        </div>


                        <hr>
                        <div class="row justify-content-center">
                            <div class="col-md-5">
                                <div class="d-grid">
                                    <button id="submit-btn" class="btn border-0 main-color-one-bg waves-effect waves-light">
                                        درخواست برداشت
                                        <i class="bi bi-check-lg text-white"></i>
                                    </button>
                                </div>
                            </div>
                        </div>


                    </form>


                </div>
            </div>
        </div>
    </div>
    {{-- show Modal --}}
    <div class="modal fade" id="history-show-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel21">جزئیات تراکنش</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div id="history-detail" class="modal-body">


                </div>
            </div>
        </div>
    </div>

    <script src="{{ theme_asset('js/pages/user/jquery-bs5-modal-compat.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>
    <script src="{{ theme_asset('js/pages/wallet/index.js') }}"></script>
@endpush
