<!DOCTYPE html>
<html lang="fa" dir="rtl"
      class="h-full {{ request()->cookie('admin-theme') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $title ?? 'داشبورد') | پنل مدیریت {{ option('info_site_title', config('app.name')) }}</title>

    @vite(['resources/admin-panel/css/admin.css', 'resources/admin-panel/js/admin.js'])
    @livewireStyles
</head>
<body class="min-h-screen">

{{-- بدنه با درایور Alpine سایدبار --}}
<div x-data="apSidebar" class="min-h-screen flex flex-col">

    {{-- پوشش موبایل --}}
    <div x-show="open" x-cloak x-transition.opacity.duration.200ms
         class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden"
         x-on:click="open = false" aria-hidden="true"></div>

    <div class="flex flex-1">
        <x-admin-panel::sidebar />

        {{-- ستون اصلی --}}
        <div class="flex min-w-0 flex-1 flex-col lg:mr-[17rem]">
            <x-admin-panel::topbar />

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>

            <footer class="ap-footer px-4 py-4 sm:px-6 lg:px-8">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 text-xs text-ink-3 dark:text-night-ink-2">
                    <span>پنل مدیریت {{ option('info_site_title', config('app.name')) }} — نسخه جدید (Livewire 4)</span>
                    <span class="fa-digits">© {{ date('Y') }}</span>
                </div>
            </footer>
        </div>
    </div>

    {{-- Toasts --}}
    <x-admin-panel::toasts />
</div>

@if (session('np-toast'))
    @php $npToast = session('np-toast') @endphp
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.ApToast?.{{ in_array($npToast['type'] ?? '', ['success', 'error', 'info']) ? $npToast['type'] : 'info' }}(
                {{ json_encode($npToast['message'] ?? '') }});
        });
    </script>
@endif

@livewireScripts
@stack('scripts')
</body>
</html>
