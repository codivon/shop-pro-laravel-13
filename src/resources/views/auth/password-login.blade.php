@extends('front::auth.layouts.master', ['title' => 'ورود به سایت'])

@php
    $redirect_url = request("redirect") ?: \Illuminate\Support\Facades\Redirect::intended()->getTargetUrl();
@endphp

@section('content')

    <div class="row justify-content-center">
        <div class="col-lg-4">

            {{-- کارت ورود با رمز عبور — ساختار register.html قالب سانیار --}}
            <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
                <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
                    <h2 class="text-center h4 text-muted title-font">ورود</h2>
                    <a href="{{ route('front.index') }}">
                        <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="120" alt="{{ option('info_site_title', 'او پی شاپ') }}">
                    </a>
                </div>

                @if(session('SettFirstOnePassword'))
                    <div class="alert text-center alert-warning font-13">رمز عبور جدید را وارد کنید</div>
                @endif

                <form action="{{ route('front.user.CheckPassword') }}" id="login" method="post">
                    @csrf
                  <input name="username" value="{{session('ShowPasswordForm_Mobile_Email')}}" type="hidden">
                    <div class="comment-item mb-3">
                        <input autofocus required name="password" type="password" id="email-phone" value="{{old('password')}}" class="form-control" placeholder=" ">
                        <label for="email-phone" class="form-label label-float">رمز عبور را وارد کنید</label>
                    </div>
                    <input name="remember" type="hidden"  value="1">
                    @error('password')
                    <span class="invalid-feedback" role="alert" style="margin: 10px 0;">
                                        <strong>{{ $message }}</strong>
                                    </span>
                    @enderror
                    @if(session('admin_login_error'))
                        <span class="invalid-feedback" role="alert" style="margin: 10px 0;">
                                        <strong>{{ session('admin_login_error')  }}</strong>
                                    </span>
                        @endif

                    @if(!session('SettFirstOnePassword'))
                        <div class="d-flex justify-content-between flex-wrap mb-3">
                            @if (option('forgot_password_link', 'on') == 'on')
                                <a href="{{route('password.request')}}" class="form-edit-number">رمز خود را فراموش کرده ام</a>
                            @endif
                            @if (option('login_with_code', 'on') == 'on')
                                <a href="{{route('login-with-code.request')}}" class="form-edit-number">ورود با رمز یکبار مصرف</a>
                            @endif
                        </div>
                    @endif

                    @if(session('SettFirstOnePassword') and option('user_referrals_enable', "false") == "true")
                        <label class="form-label mt-2">کد معرف را وارد کنید (اختیاری)</label>
                        <div class="comment-item mb-3">
                            <input name="referralCode" type="text"  value="{{@request('ref')}}" class="form-control" placeholder=" ">
                            <label class="form-label label-float">کد معرف</label>
                        </div>
                        @error('referralCode')
                        <span class="invalid-feedback" role="alert" style="margin: 10px 0;">
                                        <strong>{{ $message }}</strong>
                                    </span>
                        @enderror
                    @endif


                    <div class="form-group">
                        <button type="submit" class="main-color-one-bg py-3 btn w-100 rounded-3 border-0">
                            تایید
                        </button>
                    </div>

                </form>

            </div>

            <p class="loginTermsDesc mt-3">با ورود و یا ثبت نام در {{ option('info_site_title', 'او پی شاپ') }} شما <a class="underlined main-color-one-color fw-bold" href="/rules/">شرایط و
                قوانین</a> استفاده از سرویس‌های سایت و <a class="underlined main-color-one-color fw-bold" href="/privacy-polices/">قوانین حریم
                خصوصی</a> آن را می‌پذیرید.</p>

        </div>
    </div>

@endsection

@push('scripts')
    <script>
        var redirect_url = '{{ $redirect_url }}';
    </script>

@endpush

@php session()->forget('ShowPasswordForm_Mobile_Email'); @endphp
@php session()->forget('admin_login_error'); @endphp
