@extends('front::auth.layouts.master', ['title' => 'تغییر رمز عبور'])

@section('content')
    <div class="auth-form shadow-xl rounded-3 mt-5 bg-white">
        <div class="auth-form-title d-flex align-items-center justify-content-between title-panel mb-4 slider-title-desc-center">
            <h2 class="text-center text-muted title-font">تغییر رمز عبور</h2>
            <a href="{{ route('front.index') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}" width="150"
                     alt="{{ option('info_site_title', config('front.demo.name')) }}">
            </a>
        </div>

        <form id="reset-form" method="POST"
              action="{{ route('front.user.password.update') }}"
              data-ajax
              data-redirect="{{ route('front.user.profile') }}?password=changed"
              data-success-message="رمز عبور شما با موفقیت تغییر کرد.">
            @csrf
            @method('PUT')

            <div class="comment-item position-relative mb-3">
                <input type="password" class="form-control" id="password-old" name="prev_password"
                       placeholder=" " required autocomplete="current-password"
                       aria-label="رمز عبور فعلی">
                <label for="password-old" class="form-label label-float">رمز عبور فعلی</label>
                <button type="button" class="toggle-password" aria-label="نمایش رمز عبور">
                    <i class="bi bi-eye-slash"></i>
                </button>
            </div>

            <div class="comment-item position-relative mb-3">
                <input type="password" class="form-control" id="password" name="password"
                       placeholder=" " minlength="6" required autocomplete="new-password"
                       aria-label="رمز عبور جدید">
                <label for="password" class="form-label label-float">رمز عبور جدید</label>
                <button type="button" class="toggle-password" aria-label="نمایش رمز عبور">
                    <i class="bi bi-eye-slash"></i>
                </button>
            </div>

            <div class="comment-item mb-3">
                <input type="password" class="form-control" id="password-new-again" name="password_confirmation"
                       placeholder=" " minlength="6" required autocomplete="new-password"
                       aria-label="تکرار رمز عبور جدید">
                <label for="password-new-again" class="form-label label-float">تکرار رمز عبور جدید</label>
            </div>

            <div class="form-group mt-4">
                <button type="submit"
                        class="btn btn-main-auth py-3 w-100 rounded-3 waves-effect waves-light">
                    <i class="bi bi-check2-circle"></i> تغییر رمز عبور
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/pages/auth.js') }}?v=1"></script>
@endpush
