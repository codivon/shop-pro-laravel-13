@extends('front::auth.layouts.master', ['title' => 'ثبت نام در سایت'])

@section('content')

    <!-- Start main-content -->
    <main class="main-content dt-sl mt-4 mb-3">
        <div class="container main-container">

            <div class="row">
                <div class="col-xl-4 col-lg-5 col-md-7 col-12 mx-auto">
                    <div class="form-ui dt-sl dt-sn pt-4">
                        <div class="section-title title-wide mb-1 no-after-title-wide">
                            <h2 class="font-weight-bold">ثبت نام در سایت</h2>
                        </div>

                        <form id="register-form" action="{{ route('register') }}" method="POST">
                            @csrf
                            <div class="form-row-title">
                                <h3>نام</h3>
                            </div>
                            <div class="form-row form-group">
                                <input type="text" name="first_name" class="input-ui pr-2" placeholder="  نام خود را وارد نمایید">
                            </div>
                            <div class="form-row-title">
                                <h3>نام خانوادگی</h3>
                            </div>
                            <div class="form-row form-group">
                                <input type="text" name="last_name" class="input-ui pr-2" placeholder="  نام خانوادگی خود را وارد نمایید">
                            </div>
                            <div class="form-row-title">
                                <h3>شماره موبایل</h3>
                            </div>
                            <div class="form-row with-icon form-group">
                                <input type="text" name="username" class="input-ui pr-2" placeholder="  شماره موبایل خود را وارد نمایید">
                                <i class="mdi mdi-account-circle-outline"></i>
                            </div>

                            <div class="form-row-title">
                                <h3>رمز عبور</h3>
                            </div>
                            <div class="form-row with-icon form-group">
                                <input id="password" type="password" name="password" class="input-ui pr-2" placeholder="رمز عبور خود را وارد نمایید">
                                <i class="mdi mdi-lock-open-variant-outline"></i>
                            </div>

                            <div class="form-row-title">
                                <h3>تکرار رمز عبور</h3>
                            </div>
                            <div class="form-row form-group">
                                <input type="password" name="password_confirmation" class="input-ui pr-2" placeholder="تکرار رمز عبور خود را وارد نمایید">
                            </div>

                            @if(\App\Services\SecuritySettings::captchaEnabled('register'))
                            <div class="row pr-4">
                                <x-captcha />
                            </div>
                            @endif

                            <div class="form-row mt-3">
                                <button class="btn-primary-cm btn-with-icon mx-auto w-100">
                                    <i class="mdi mdi-account-circle-outline"></i>
                                    ثبت نام در سایت
                                </button>
                            </div>
                            <div class="form-footer text-right mt-3">
                                <span class="d-block font-weight-bold">قبلا ثبت نام کرده اید؟</span>
                                <a href="{{ route('login') }}" class="d-inline-block mr-3 mt-2">وارد شوید</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </main>
    <!-- End main-content -->

@endsection

@push('scripts')
    <script>
        var redirect_url = '{{ request("redirect") ?: Redirect::intended()->getTargetUrl() }}';
    </script>

    <script src="{{ theme_asset('js/pages/register.js') }}?v=2"></script>
@endpush
