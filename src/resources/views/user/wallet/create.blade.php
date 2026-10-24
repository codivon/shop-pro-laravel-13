{{-- افزایش موجودی (صفحه مستقل — قرارداد WalletController@create : $gateways؛ POST به wallet.store) --}}
@extends('front::user.layouts.master', ['title' => 'افزایش موجودی کیف پول'])

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('user-content')
    <div class="content-box">
        <div class="container-fluid">
            <div class="title-panel mb-4">
                <h2 class="font-16 mb-0">
                    <i class="bi bi-plus-circle main-color-one-color me-1"></i>
                    افزایش موجودی <span class="main-color-one-color">کیف پول</span>
                </h2>
            </div>

            <form id="wallet-create-form" action="{{ route('front.wallet.store') }}" method="POST" class="row g-3" novalidate>
                @csrf
                <div class="col-md-6">
                    <label class="form-label font-14 fw-bold" for="wallet-amount">مبلغ (تومان) <span class="text-danger">*</span></label>
                    <input type="number" id="wallet-amount" name="amount" class="form-control rounded-3"
                           placeholder="مثال : 100,000" min="1000" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label font-14 fw-bold" for="wallet-gateway">انتخاب درگاه پرداخت <span class="text-danger">*</span></label>
                    <select class="form-select rounded-3" name="gateway" id="wallet-gateway" required>
                        <option value="">درگاه مورد نظر خود را انتخاب کنید</option>
                        @foreach ($gateways as $gateway)
                            <option value="{{ $gateway->key }}">{{ $gateway->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <div id="wallet-deposit-error" class="font-12 text-danger mt-2 d-none" role="alert"></div>
                    <button type="submit" class="btn border-0 main-color-one-bg text-white rounded-3 px-5">
                        <i class="bi bi-credit-card me-1"></i> افزایش موجودی
                    </button>
                    <a href="{{ route('front.wallet.index') }}" class="btn btn-light border rounded-3 px-4">بازگشت به کیف پول</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/pages/user-panel.css') }}?v=1">
@endpush
@push('scripts')
    <script src="{{ theme_asset('js/pages/user/panel.js') }}?v=1"></script>
@endpush
