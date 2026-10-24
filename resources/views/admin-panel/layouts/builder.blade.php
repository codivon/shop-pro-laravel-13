{{-- Layout تمام‌صفحه‌ی ویجت‌ساز (فاز ۹-ه) — مستقل از سایدبار/تاپ‌بار پنل --}}
<!DOCTYPE html>
<html lang="fa" dir="rtl"
      class="h-full {{ request()->cookie('admin-theme') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="admin-prefix" content="{{ admin_route_prefix() }}">

    <title>@yield('title', 'ویجت‌ساز') | پنل مدیریت {{ option('info_site_title', config('app.name')) }}</title>

    @vite(['resources/admin-panel/css/admin.css', 'resources/admin-panel/js/admin.js'])
    @livewireStyles
</head>
<body class="min-h-screen">
{{ $slot }}

{{-- Toasts (رویداد toast dispatch لایووایر + ap-toast) --}}
<x-admin-panel::toasts />

@livewireScripts
@stack('scripts')
</body>
</html>
