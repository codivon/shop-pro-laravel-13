@extends('front::user.layouts.master')

@section('user-content')
    <div class="headline-profile page-profile-order">



    </div>

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

    <div class="row">
        <div class="col-12">
            <div class="profile-stats p-3">
                <div class="row c-wallet__header-card uk-flex uk-flex-between">
                    <div class=" col-lg-9 col-sm-9 col-xs-12 uk-flex uk-height-1-1">
                        <div class="c-wallet__header-card-inventory">
                                <span class="c-wallet--fz-14">
                                    موجودی کیف پول شما
                                </span>
                            <div class="c-wallet--fz-16 uk-text-bold uk-flex uk-flex-middle mt-2">
                                    <span class="c-wallet--fz-30 uk-margin-small-left">
                                        {{ number_format($user->getWallet()->balance()) }}
                                    </span>
                                تومان
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-3 col-xs-12">
                        <div class="c-wallet__header-card-btn">
                            <div class="c-wallet__header-card-btn--deposit js-trigger-wallet-modal" data-toggle="modal" data-target="#new-wallet-show-modal">افزایش اعتبار کیف پول
                                <i class="fa-solid fa-plus"></i>
                            </div>
                            <div class="c-wallet__header-card-btn--Withdraw js-trigger-wallet-modal {{number_format($user->getWallet()->balance())>0 ? '' : 'disabled'}}"  data-toggle="modal" data-target="#wallet-show-modal">
                                برداشت از کیف پول
                                <i class="fa-solid fa-arrow-rotate-left"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h6 class="mt-4 mb-3">تاریخچه کیف پول</h6>
    <div class="profile-stats page-profile-order wallet-profile">

        <div class="table-orders">
            <table class="table">
                <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>مبلغ (تومان)</th>
                    <th>نوع تراکنش</th>
                    <th>تاریخ</th>
                    <th class="text-center">وضعیت</th>
                    <th>جزییات</th>
                </tr>
                </thead>
                <tbody>
                @if($histories->count())
                    @foreach ($histories as $history)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            @php
                                $is_deposit = $history->type == 'deposit';
                            @endphp

                            <td class="{{ $is_deposit ? 'text-success' : 'text-danger' }}">{{ number_format($history->amount) }}</td>
                            <td>
                                @if($history->withdraw)
                                    برداشت وجه
                                    <div class="badge badge-danger ml-1">
                                        <i class="mdi mdi-arrow-left"></i>
                                    </div>
                                @else
                                    @if ($is_deposit)
                                        افزایش اعتبار
                                        <div class="badge badge-success ml-1">
                                            <i class="mdi mdi-arrow-up"></i>
                                        </div>
                                    @else
                                        کاهش اعتبار
                                        <div class="badge badge-danger ml-1">
                                            <i class="mdi mdi-arrow-down"></i>
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="ltr">{{ jdate($history->created_at) }}</td>

                            <td class="text-center">
                                @if($history->status == 'success')
                                    <div class="badge badge-success">موفق</div>
                                    @if($history->withdraw)
                                        @if($history->status_pay=="waiting")
                                            <div class="badge badge-warning">در انتظار پرداخت</div>
                                        @elseif($history->status_pay=="pay")
                                            <div class="badge badge-success">پرداخت شده</div>
                                        @elseif($history->status_pay=="unpay")
                                            <div class="badge badge-danger">پرداخت نشده</div>
                                        @endif

                                    @endif
                                @else
                                    <div class="badge badge-danger">ناموفق</div>
                                @endif

                            </td>

                            <td class="details-link">
                                <a class="show-history" data-action="{{ route('front.wallet.show', ['wallet' => $history]) }}" onclick="return false;">
                                    <i class="mdi mdi-chevron-left"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach

                @else
                    <tr>
                        <td colspan="7">شما تا به حال تراکنشی در کیف پول خود نداشته‌اید. </td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
    <div class="page-profile headline-profile-favorites wallet-profile">
        <div class="page-navigation">
            <div class="page-navigation-title">تاریخچه کیف پول</div>
            <span class="add-address-link float-left cursor-pointer "  data-toggle="modal" data-target="#new-wallet-show-modal">افزایش موجودی کیف پول</span>

        </div>
        <div class="profile-orders">
            @if(count($histories))
                @foreach ($histories as $history)
                    <div class="collapse">
                        <div class="profile-orders-item">
                            <div class="profile-orders-header">
                                <a href="" onclick="return false;" data-action="{{ route('front.wallet.show', ['wallet' => $history]) }}" class="profile-orders-header-details show-history">
                                    <div class="profile-orders-header-summary">
                                        <div class="profile-orders-header-row">
                                            <span class="profile-orders-header-id">{{ $loop->iteration }}</span>
                                            <span class="profile-orders-header-state">
                                                @if($history->status == 'success')
                                                    <div class="badge badge-success">موفق</div>
                                                    @if($history->withdraw)
                                                        @if($history->status_pay=="waiting")
                                                            <div class="badge badge-warning">در انتظار پرداخت</div>
                                                        @elseif($history->status_pay=="pay")
                                                            <div class="badge badge-success">پرداخت شده</div>
                                                        @elseif($history->status_pay=="unpay")
                                                            <div class="badge badge-danger">پرداخت نشده</div>
                                                        @endif

                                                    @endif
                                                @else
                                                    <div class="badge badge-danger">ناموفق</div>
                                                @endif
                                    </span>
                                        </div>
                                    </div>
                                </a>
                                <hr class="ui-separator">
                                <div class="profile-orders-header-data">
                                    <div class="profile-info-row">
                                        <div class="profile-info-label">مبلغ (تومان)</div>
                                        <div class="profile-info-value {{ $is_deposit ? 'text-success' : 'text-danger' }}">{{ number_format($history->amount) }}</div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-label">نوع تراکنش</div>
                                        <div class="profile-info-value">
                                            @if($history->withdraw)
                                                برداشت وجه
                                                <div class="badge badge-danger ml-1">
                                                    <i class="mdi mdi-arrow-left"></i>
                                                </div>
                                            @else
                                                @if ($is_deposit)
                                                    افزایش اعتبار
                                                    <div class="badge badge-success ml-1">
                                                        <i class="mdi mdi-arrow-up"></i>
                                                    </div>
                                                @else
                                                    کاهش اعتبار
                                                    <div class="badge badge-danger ml-1">
                                                        <i class="mdi mdi-arrow-down"></i>
                                                    </div>
                                                @endif
                                            @endif

                                        </div>
                                    </div>
                                    <div class="profile-info-row">
                                        <div class="profile-info-label">تاریخ</div>
                                        <div class="profile-info-value">{{ jdate($history->created_at) }}</div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="collapse">
                    <div class="profile-orders-item text-center">
                        شما تا به حال تراکنشی در کیف پول خود نداشته‌اید.
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="pager pager-back-none">
        {{$histories->links("pagination::bootstrap-4")}}
    </div>

@endsection

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
                    <form id="wallet-create-form" action="{{ route('front.wallet.store') }}" class="setting_form form-checkout" method="POST">
                        @csrf
                        <div class="row form-checkout-row">
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group">
                                <label for="amount">مبلغ (تومان) <span class="required-star" style="color:red;">*</span></label>
                                <input type="number" id="amount" name="amount" class="input-name-checkout amount-input mb-0" placeholder="مثال : 10,000" required>
                            </div>
                            <div class="col-md-6 col-sm-12 col-xs-12">
                                <div class="form-checkout-valid-row w-100 form-checkout form-group">
                                    <label for="gateway">انتخاب درگاه پرداخت
                                        <span class="required-star" style="color:red;">*</span></label>
                                    <select class="right" name="gateway" id="gateway" required>
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
                                        <button id="submit-btn" class="dk-btn dk-btn-info">
                                            افزایش موجودی
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
                    <form id="wallet-withdraw-form" action="{{ route('front.wallet.withdraw') }}" class="setting_form form-checkout" data-amount="{{ $user->getWallet()->balance() }}" method="POST">
                        @csrf
                        <div class="row form-checkout-row">
                            <div class="col-md-6 col-sm-12 col-xs-12 form-group">
                                <label for="amount">مبلغ (تومان) <span class="required-star" style="color:red;">*</span></label>
                                <input type="number" id="amount" name="amount" class="input-name-checkout amount-input mb-0" placeholder="مثال : 10,000" required>
                            </div>
                            <div class="col-md-6 col-sm-12 col-xs-12">
                                <div class="profile-box-title line-height-100">موجودی کیف پول:
                                    {{ number_format($user->getWallet()->balance()) }}
                                    تومان
                                </div>
                            </div>
                        </div>


                        <hr>
                        <div class="row justify-content-center">
                            <div class="col-md-5">
                                <div class="form-checkout-valid-row">
                                    <div class="parent-btn">
                                        <button id="submit-btn" class="dk-btn dk-btn-info">
                                            درخواست برداشت
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

    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>
    <script src="{{ theme_asset('js/pages/wallet/index.js') }}"></script>
@endpush
