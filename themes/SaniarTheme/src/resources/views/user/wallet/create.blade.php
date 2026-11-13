@extends('front::user.layouts.master')

@section('user-content')

    {{-- افزایش موجودی کیف پول — ریسکین‌شده با فرم‌های قالب سانیار --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <p class="mb-0">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="ui-boxs">
        <div class="ui-box">
            <div class="ui-box-item ui-box-white">
                <div class="ui-box-item-title">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="fw-bold">افزایش موجودی کیف پول</h4>
                        <a href="{{ route('front.wallet.index') }}" class="btn btn-sm btn-light">بازگشت <i class="bi bi-arrow-left"></i></a>
                    </div>
                </div>
                <div class="ui-box-item-desc">
                    <form id="wallet-create-form" action="{{ route('front.wallet.store') }}" class="setting_form" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="amount">مبلغ (تومان)</label>
                                    <input type="number" class="form-control amount-input" name="amount" id="amount">
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="gateway">انتخاب درگاه پرداخت</label>
                                    <select class="form-select" name="gateway" id="gateway" required>
                                        <option value="">انتخاب کنید</option>
                                        @foreach ($gateways as $gateway)
                                            <option value="{{ $gateway->key }}">{{ $gateway->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-center mt-3">
                            <button id="submit-btn" type="submit" class="btn border-0 main-color-one-bg waves-effect waves-light py-2 px-5">
                                <i class="bi bi-arrow-left text-white me-1"></i>
                                افزایش موجودی
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>

    <script src="{{ theme_asset('js/pages/wallet/create.js') }}"></script>
@endpush
