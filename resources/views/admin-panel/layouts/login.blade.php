<!DOCTYPE html>
<html lang="fa" dir="rtl"
      class="h-full {{ request()->cookie('admin-theme') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'ورود') | پنل مدیریت {{ option('info_site_title', config('app.name')) }}</title>

    @vite(['resources/admin-panel/css/admin.css', 'resources/admin-panel/js/admin.js'])
    @livewireStyles
</head>
<body class="min-h-screen">
{{ $slot }}

{{-- Toasts (برای خطاهای عمومی) --}}
<x-admin-panel::toasts />

@livewireScripts
@stack('scripts')
</body>
</html>
