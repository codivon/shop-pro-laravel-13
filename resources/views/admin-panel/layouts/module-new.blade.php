{{--
    لایه‌ی «پل» — صفحات کلاسیک پکیج‌ها (Blade) داخل کروم پنل جدید (Livewire)
    --------------------------------------------------------------------------
    پکیج‌ها با @extends شرطی بین این لایه و back.layouts.master سوییچ می‌کنند:
        @extends(\Modules\X\Support\ActivePanel::useNewPanel() ? 'admin-panel::layouts.module' : 'back.layouts.master')
    تشخیص پنل فعال: کوکی ap_panel (هر پنل هنگام لود ست می‌کند) + آدرس admin-old.
    --------------------------------------------------------------------------
    ساختار:
      • کروم پنل جدید: سایدبار + تاپ‌بار + فوتر + توست‌ها (دقیقاً مثل layout.blade.php)
      • استک کامل CSS/JS پنل قدیمی: محتوای بوت‌استرپی پکیج‌ها بدون هیچ تغییری رندر می‌شود
      • ترتیب CSS: اول پنل جدید (preflight زیر برود) → بعد بوت‌استرپ قدیمی → بعد کمپات
--}}
@php
    $apSiteTitle = option('info_site_title', config('app.name'));
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full {{ request()->cookie('admin-theme') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="admin-prefix" content="{{ admin_route_prefix() }}">

    {{-- کوکی «پنل فعال» — منوها و صفحات پکیج با همین کوکی بین دو پنل سوییچ می‌کنند --}}
    <script>document.cookie = 'ap_panel=new; path=/; max-age=31536000; SameSite=Lax';</script>

    <title>@yield('title', $title ?? $apSiteTitle) | پنل مدیریت {{ $apSiteTitle }}</title>

    {{-- ۱) CSS پنل جدید — زودتر لود می‌شود تا preflight تیلویند زیر reboot بوت‌استرپ برود --}}
    @vite(['resources/admin-panel/css/admin.css', 'resources/admin-panel/js/admin.js'])
    @livewireStyles

    {{-- ۲) متا + استک کامل CSS پنل قدیمی — دقیقاً مثل back.layouts.master --}}
    @include('back.partials.meta')

    <!-- BEGIN: Vendor CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/vendors/css/vendors-rtl.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/vendors/css/ui/prism.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/vendors/css/extensions/toastr.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/vendors/css/forms/select/select2.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/plugins/select2totree/select2totree.css') }}">

    <!-- END: Vendor CSS-->

    <!-- BEGIN: Theme CSS-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEypE+enwcOiGjk/bSuGGKHEyjSoQ1zVisanQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/bootstrap-extended.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/colors.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/components.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/themes/dark-layout.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/themes/semi-dark-layout.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/plugins/extensions/toastr.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/plugins/animate/animate.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('back/assets/css/new-style.css') }}">
    <!-- BEGIN: Page CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('back/app-assets/css-rtl/core/menu/menu-types/vertical-menu.css') }}">

    {{-- استایل‌های خود صفحه‌ی پکیج --}}
    @stack('styles')
    <!-- END: Page CSS-->

    @include('back.partials.global-css')

    {{-- ۳) کمپات: نشت reboot بوت‌استرپ به کروم پنل جدید خنثی می‌شود --}}
    {{-- :where() صفرکردن specificity — فقط قواعد element-level بوت‌استرپ را می‌بُرد،
        نه utilityهای کلاسی تیلویند؛ بعد از هر دو CSS لود می‌شود --}}
    <style>
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer) :where(p, h1, h2, h3, h4, h5, h6) { margin: 0; }
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer) :where(ul, ol) { margin: 0; padding: 0; list-style: none; }
        :where(#ap-chrome-side, #ap-chrome-top, .ap-footer) :where(a, a:hover, a:focus, a:active) { text-decoration: none; color: inherit; }
        :where(#ap-chrome-side) :where(label) { margin-bottom: 0; }
        :where(#ap-chrome-side, #ap-chrome-top) :where(img) { vertical-align: middle; }
    </style>

    <!-- Scripts -->
    <script>
        window.Laravel = {!! json_encode([
            'user' => auth('adminPanel')->user(),
            'csrfToken' => csrf_token(),
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'pusher' => [
                'key' => config('broadcasting.connections.pusher.key'),
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            ],
        ]) !!};
    </script>

</head>

{{-- بدنه با درایور Alpine سایدبار (مثل layout.blade.php) --}}
<body class="min-h-screen">
<div x-data="apSidebar" class="min-h-screen flex flex-col">

    {{-- پوشش موبایل --}}
    <div x-show="open" x-cloak x-transition.opacity.duration.200ms
         class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden"
         x-on:click="open = false" aria-hidden="true"></div>

    <div class="flex flex-1">
        <div id="ap-chrome-side">
            <x-admin-panel::sidebar />
        </div>

        {{-- ستون اصلی --}}
        <div class="flex min-w-0 flex-1 flex-col lg:mr-[17rem]">
            <div id="ap-chrome-top">
                <x-admin-panel::topbar :title="($title ?? null)" />
            </div>

            {{-- محتوای صفحه‌ی پکیج (بوت‌استرپ قدیمی) --}}
            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                @yield('content')
            </main>

            <footer class="ap-footer px-4 py-4 sm:px-6 lg:px-8">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 text-xs text-ink-3 dark:text-night-ink-2">
                    <span>پنل مدیریت {{ $apSiteTitle }} — نسخه جدید (Livewire 4)</span>
                    <span class="fa-digits">© {{ date('Y') }}</span>
                </div>
            </footer>
        </div>
    </div>

    {{-- Toasts --}}
    <x-admin-panel::toasts />
</div>

{{-- مودال‌های عمومی مستر قدیمی — اسکریپت‌های مشترک (scripts.js و…) به آن‌ها وابسته‌اند --}}
<div class="modal fade text-left" id="main-errors-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">متن خطا</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body ltr text-right">
                <div class="px-1">
                    <div class="error-content"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade text-left" id="password-confirm-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">تایید رمز عبور</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <p>مدت زمان فعالیت شما بیشتر از {{ config('auth.password_timeout') / 60 }} دقیقه است لطفا رمز عبور خود را مجدد وارد کنید</p>
                <form id="password-confirm-form" method="POST" action="{{ route('password.confirm') }}">
                    @csrf

                    <fieldset class="form-label-group position-relative has-icon-left">
                        <input type="password" class="form-control" name="password" required autocomplete="current-password" placeholder="گذرواژه">
                        <div class="form-control-position">
                            <i class="feather icon-lock"></i>
                        </div>
                        <label for="user-password">گذرواژه</label>
                    </fieldset>
                    <button type="submit" class="btn btn-primary float-right btn-inline">تایید</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- استک کامل JS پنل قدیمی — دقیقاً مثل back.layouts.master --}}
<!-- BEGIN: Vendor JS-->
<script src="{{ asset('back/app-assets/vendors/js/vendors.min.js') }}"></script>
<script src="{{ asset('back/app-assets/vendors/js/forms/select/select2.full.min.js') }}"></script>
<script src="{{ asset('back/app-assets/plugins/select2totree/select2totree.js') }}"></script>
<script src="{{ asset('back/app-assets/vendors/js/extensions/toastr.min.js') }}"></script>
<script src="{{ asset('back/app-assets/vendors/js/extensions/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('back/app-assets/plugins/json-viewer/jquery.json-editor.min.js') }}"></script>
<!-- BEGIN Vendor JS-->

<!-- BEGIN: Page Vendor JS-->
<script src="{{ asset('back/app-assets/vendors/js/ui/prism.min.js') }}"></script>
<!-- END: Page Vendor JS-->

<!-- BEGIN: Theme JS-->
<script src="{{ asset('back/app-assets/js/core/app-menu.js') }}"></script>
<script src="{{ asset('back/app-assets/js/core/app.js') }}"></script>

@stack('plugin-scripts')

<script src="{{ asset('back/assets/js/helpers.js') }}?v=8"></script>
<script src="{{ asset('back/assets/js/customToast.js') }}?v=8"></script>
<script src="{{ asset('back/assets/js/scripts.js') }}?v=11"></script>

<!-- END: Theme JS-->

<script>
    var BASE_URL = "{{ route('admin.dashboard') }}";
    var FRONT_URL = "{{ Route::has('front.index') ? route('front.index') : url('/') }}";
    var WEB_PUSH_NOTIFICATION = {{ auth()->user() && method_exists(auth()->user(), 'pushSubscriptions') && auth()->user()->pushSubscriptions()->first() ? 'true' : 'false' }};
    var APP_FONT_FAMILY = "{{ user_option('theme_font', 'Vazir') }}";
</script>

<!-- BEGIN: Page JS-->
@stack('scripts')
<!-- END: Page JS-->

@if (file_exists(public_path('mix-manifest.json')))
    <script src="{{ asset(mix('js/app.js')) }}"></script>
@endif
<script src="{{ asset('back/assets/js/echo.js') }}"></script>
<script src="{{ asset('back/assets/js/web-push.js') }}"></script>

@if (session('toast-success'))
    <script>
        showCustomToast('{{ session('toast-success') }}','success')
    </script>
@endif
@if (session('toast-error'))
    <script>
        showCustomToast('{{ session('toast-error') }}','error')
    </script>
@endif
@if (session('toast-warning'))
    <script>
        showCustomToast('{{ session('toast-warning') }}','warning')
    </script>
@endif

@livewireScripts

</body>
</html>
@php session()->forget('toast-success'); @endphp
@php session()->forget('toast-error'); @endphp
@php session()->forget('toast-warning'); @endphp
