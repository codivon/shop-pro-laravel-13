@extends('sellers-old.panel.layouts.master')

@section('content')
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">کیف پول </span>
                <span class="c-content-page__header-desc">برای مدیریت کیف پول خود از این قسمت استفاده نمایید.</span>
            </div>
        </div>
    </div>
    @include('sellers-old.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">
        @if(session('message') == 'ok')

            <div class="col-lg-12 text-center p-0">
                <div class="alert alert-success" role="alert">
                    <strong>افزایش موجودی با موفقیت انجام شد</strong>.
                </div>
            </div>

        @elseif(session('transaction-error'))
            <div class="col-lg-12 text-center p-0">
                <div class="alert alert-danger " role="alert">
                    <strong>{{ session('transaction-error') }}</strong>.
                </div>
            </div>
        @endif
        <div class="row dashboard-steps-3 ">
            <div class="col-12 dashboard-steps-3-item ">

                <div class="c-card">

                    <div class="row" >
                        <div class="col-12">
                            <div class="profile-stats p-1">
                                <div class="row c-wallet__header-card uk-flex uk-flex-between">
                                    <div class="col-md-9 col-sm-12 col-xs-12 uk-flex uk-height-1-1">
                                        <div class="c-wallet__header-card-inventory">
                                <span class="c-wallet--fz-14">
                                    موجودی کیف پول شما
                                </span>
                                            <div class="c-wallet--fz-16 uk-text-bold uk-flex uk-flex-middle mt-2">
                                    <span class="c-wallet--fz-30 uk-margin-small-left">
                                        {{ number_format($seller->getWallet()->balance()) }}
                                    </span>
                                                ریال
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-12 col-xs-12">
                                        <div class="c-wallet__header-card-btn">

                                            <div class="c-wallet__header-card-btn--deposit js-trigger-wallet-modal" data-toggle="modal" data-target="#new-wallet-show-modal">افزایش اعتبار کیف پول
                                                <i class="fa-solid fa-plus"></i>
                                            </div>
                                            <div class="c-wallet__header-card-btn--Withdraw js-trigger-wallet-modal {{number_format($seller->getWallet()->balance())>0 ? '' : 'disabled'}}"  data-toggle="modal" data-target="#wallet-show-modal">
                                                برداشت از کیف پول
                                                <i class="fa-solid fa-arrow-rotate-left"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row dashboard-steps-3 mt-2">
            <div class="col-12 dashboard-steps-3-item ">
                <div class="c-card">
                    <div class="c-card__header">
                        <h2 class="c-card__title">تاریخچه کیف پول</h2>
                    </div>
                    <div class="c-card__body uk-height-1-1 uk-flex-middle">
                        <div class="card-content">
                            <div class="card-body table-responsive p-0">

                                @if($histories->count())
                                    <table class="table table-striped mb-0">
                                        <thead>
                                        <tr>
                                            <th>ردیف</th>
                                            <th>مبلغ (تومان)</th>
                                            <th>نوع تراکنش</th>
                                            <th>تاریخ</th>
                                            <th class="text-center">وضعیت</th>
                                            <th class="text-center">عملیات</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($histories as $history)
                                            @php
                                                $is_deposit = $history->type == 'deposit';
                                            @endphp

                                            <tr id="data-{{$history->id}}">
                                                <td>{{ $loop->iteration }}</td>
                                                <td class="{{ $is_deposit ? 'text-success' : 'text-danger' }}">{{ number_format($history->amount) }}</td>
                                                <td>
                                                    @if($history->withdraw)
                                                        برداشت وجه
                                                        <div class="badge badge-danger ml-1">
                                                            <i class="feather icon-arrow-left"></i>
                                                        </div>
                                                    @else
                                                        @if ($is_deposit)
                                                            افزایش اعتبار
                                                            <div class="badge badge-success ml-1">
                                                                <i class="feather icon-arrow-up"></i>
                                                            </div>
                                                        @else
                                                            کاهش اعتبار
                                                            <div class="badge badge-danger ml-1">
                                                                <i class="feather icon-arrow-down"></i>
                                                            </div>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>{{ jdate($history->created_at) }}</td>
                                                <td class="text-center">
                                                    @if($history->status == 'success')
                                                        <div class="badge badge-success">موفق</div>
                                                        <div class="status-pay">
                                                            @if($history->withdraw)
                                                                @if($history->status_pay=="waiting")
                                                                    <div class="badge badge-warning">در انتظار پرداخت</div>
                                                                @elseif($history->status_pay=="pay")
                                                                    <div class="badge badge-success">پرداخت شده</div>
                                                                @elseif($history->status_pay=="unpay")
                                                                    <div class="badge badge-danger">پرداخت نشده</div>
                                                                @elseif($history->status_pay=="unpay-refund")
                                                                    <div class="badge badge-danger">پرداخت نشده و مبلغ عودت داده شده </div>
                                                                @endif

                                                            @endif
                                                        </div>

                                                    @else
                                                        <div class="badge badge-danger">ناموفق</div>
                                                    @endif
                                                </td>

                                                <td class="text-center">
                                                    <button data-action="{{ route('seller.wallet.show', ['wallet' => $history]) }}" class="btn btn-info waves-effect waves-light show-history">مشاهده</button>
                                                </td>
                                            </tr>
                                        @endforeach

                                        </tbody>
                                    </table>
                                @else
                                    <div class="card-text">
                                        <p>شما تا به حال تراکنشی در کیف پول خود نداشته‌اید.</p>
                                    </div>
                                @endif

                                {{ $histories->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
@include('back.partials.plugins', ['plugins' => [ 'jquery-tagsinput', 'jquery-ui', 'jquery.validate']])
@push('scripts')
    <!-- show Modal -->
    <div class="modal fade" id="new-wallet-show-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel21">افزایش موجودی کیف پول</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body middle-container">
                    <form id="wallet-create-form" action="{{ route('seller.wallet.store') }}" class="setting_form form-checkout" method="POST">
                        @csrf
                        <div class="row form-checkout-row">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>مبلغ ( تومان)<span class="required-star" style="color:red;">*</span></label>
                                    <input type="number" class="form-control amount-input" name="amount" placeholder="مثال : 10,000" required>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label>انتخاب درگاه پرداخت<span class="required-star" style="color:red;">*</span></label>
                                    <select name="gateway" id="gateway" class="form-control">
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
                                <div class="form-checkout-valid-row">
                                    <div class="parent-btn">
                                        <button id="submit-btn" class="c-wallet__header-card-btn--deposit js-trigger-wallet-modal w-100 border-0" >افزایش موجودی
                                            <i class="fa fa-check sign-in"></i>
                                        </button>

                                    </div>
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
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body middle-container">
                    <form id="wallet-withdraw-form" action="{{ route('seller.wallet.withdraw') }}" class="setting_form form-checkout" data-amount="{{ $seller->getWallet()->balance() }}" method="POST">
                        @csrf
                        <div class="row form-checkout-row">
                            <div class="col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label>مبلغ ( تومان)<span class="required-star" style="color:red;">*</span></label>
                                    <input type="number" class="form-control amount-input" name="amount" placeholder="مثال : 10,000" required>
                                </div>
                            </div>

                            <div class="col-md-6 col-sm-12 col-xs-12">
                                <div class="profile-box-title line-height-100">موجودی کیف پول:
                                    {{ number_format($seller->getWallet()->balance()) }}
                                </div>
                            </div>
                        </div>


                        <hr>
                        <div class="row justify-content-center">
                            <div class="col-md-5">
                                <div class="form-checkout-valid-row">
                                    <div class="parent-btn">
                                        <button id="submit-btn" class="c-wallet__header-card-btn--deposit js-trigger-wallet-modal w-100 border-0" >   درخواست برداشت
                                            <i class="fa fa-check sign-in"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </form>


                </div>
            </div>
        </div>
    </div>
    <!-- show Modal -->
    <div class="modal fade" id="history-show-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="myModalLabel21">جزئیات تراکنش</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div id="history-detail" class="modal-body">


                </div>
            </div>
        </div>
    </div>


    <script src="{{ theme_asset('js/pages/sellers/profile/index.js') }}"></script>

@endpush
