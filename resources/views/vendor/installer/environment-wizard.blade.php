@extends('vendor.installer.layouts.master')

@php
    /* همین گارد در فرم محصولات پنل هم هست — رندر مستقل view نباید روی $errors null بترکد */
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp

@section('template_title', 'تنظیمات محیط')

@section('title', 'اطلاعات دیتابیس')
@section('title_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/></svg>
@endsection
@section('title_desc', 'اطلاعات اتصال به MySQL را وارد کنید')

@section('container')
    <form method="post" action="{{ route('LaravelInstaller::environmentSaveWizard') }}">
        @csrf
        <input type="hidden" name="database_connection" value="mysql">

        <div class="ap-grid">
            <div class="ap-field">
                <label for="database_hostname">آدرس دیتابیس (Host)</label>
                <input type="text" name="database_hostname" value="{{ old('database_hostname') ?: '127.0.0.1' }}" id="database_hostname" dir="ltr" placeholder="127.0.0.1">
                @if ($errors->has('database_hostname'))
                    <span class="error-block">{{ $errors->first('database_hostname') }}</span>
                @endif
            </div>

            <div class="ap-field">
                <label for="database_port">پورت</label>
                <input type="number" name="database_port" id="database_port" value="{{ old('database_port') ?: '3306' }}" dir="ltr" placeholder="3306">
                @if ($errors->has('database_port'))
                    <span class="error-block">{{ $errors->first('database_port') }}</span>
                @endif
            </div>

            <div class="ap-field">
                <label for="database_name">نام دیتابیس</label>
                <input type="text" name="database_name" id="database_name" value="{{ old('database_name') }}" dir="ltr" placeholder="مثلا: myshop_db">
                @if ($errors->has('database_name'))
                    <span class="error-block">{{ $errors->first('database_name') }}</span>
                @endif
            </div>

            <div class="ap-field">
                <label for="database_username">نام کاربری</label>
                <input type="text" name="database_username" id="database_username" value="{{ old('database_username') }}" dir="ltr" placeholder="db_user">
                @if ($errors->has('database_username'))
                    <span class="error-block">{{ $errors->first('database_username') }}</span>
                @endif
            </div>

            <div class="ap-field">
                <label for="database_password">رمز عبور</label>
                <input type="password" name="database_password" id="database_password" value="{{ old('database_password') }}" dir="ltr" placeholder="••••••••">
                @if ($errors->has('database_password'))
                    <span class="error-block">{{ $errors->first('database_password') }}</span>
                @endif
            </div>
        </div>

        <div class="ap-alert ap-alert-warn">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>
            <span>دیتابیس باید از قبل در هاست ساخته شده باشد (در سی‌پنل/دایرکت‌ادمین بخش MySQL Databases).</span>
        </div>

        @if ($errors->has('database_connection'))
            <div class="ap-alert ap-alert-error">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                <span>{{ $errors->first('database_connection') }}</span>
            </div>
        @endif

        <div class="ap-actions">
            <button type="submit" id="submit-form" class="ap-btn ap-btn-primary">
                ذخیره و اتصال به دیتابیس
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </button>
        </div>
    </form>

    <div id="installing-message" class="ap-alert ap-alert-ok" style="display:none;margin-top:1.2rem">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ap-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
        <span><strong>در حال ذخیره تنظیمات و اتصال به دیتابیس…</strong><br>لطفا صبر کنید؛ مرحله بعد، ساخت جداول به صورت مرحله‌به‌مرحله انجام می‌شود.</span>
    </div>
@endsection

@section('style')
    .ap-spin { animation: ap-inst-spin 1s linear infinite; }
    @keyframes ap-inst-spin { to { transform: rotate(360deg); } }
@endsection

@section('scripts')
    <script>
        document.querySelector('form').addEventListener('submit', function () {
            document.getElementById('installing-message').style.display = 'flex';
            document.getElementById('submit-form').disabled = true;
        });
    </script>
@endsection
