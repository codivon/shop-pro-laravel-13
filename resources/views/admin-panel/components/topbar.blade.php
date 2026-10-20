{{-- تاپ‌بار پنل جدید --}}
@php
    $admin = auth('adminPanel')->user();
    /* فاز ۱۹: عنوان واقعی صفحه از #[Title] کامپوننت Livewire ($title در layout)
       — قبلاً همیشه «داشبورد» نشان داده می‌شد چون yieldContent('title') برای
       صفحات Livewire پر نمی‌شد. */
    $pageTitle = trim($__env->yieldContent('title') ?: ($title ?? ''));

    /*
    | فاز ۱۹ — آیکون عنوان صفحه: از منو (route → icon) با تطبیق خانواده.
    | همه‌ی صفحات زیرمجموعه (show/edit/movements/…) آیکونِ آیتم لیستِ خانواده‌ی
    | خود را می‌گیرند؛ صفحات بیرون از منو fallback می‌گیرند.
    */
    $routeIconMap = [];
    $collectIcons = function ($items) use (&$collectIcons, &$routeIconMap) {
        foreach ($items as $it) {
            if (! empty($it['route']) && ! empty($it['icon'])) {
                $routeIconMap[$it['route']] = $it['icon'];
            }
            if (! empty($it['children'])) $collectIcons($it['children']);
        }
    };
    foreach (config('admin-menu.groups', []) as $g) $collectIcons($g['items']);

    $currentRoute = request()->route()?->getName();
    $pageIcon = $routeIconMap[$currentRoute] ?? null;
    if (! $pageIcon && $currentRoute) {
        // تطبیق خانواده: بلندترین پیشوند مسیرِ موجود در منو
        $parts = explode('.', $currentRoute);
        while (count($parts) > 1) {
            array_pop($parts);
            $family = implode('.', $parts);
            if ($family === 'np') break;
            if (isset($routeIconMap[$family . '.index'])) { $pageIcon = $routeIconMap[$family . '.index']; break; }
            foreach ($routeIconMap as $mapRoute => $mapIcon) {
                if (str_starts_with($mapRoute, $family . '.')) { $pageIcon = $mapIcon; break 2; }
            }
        }
    }
    $pageIcon = $pageIcon ?: 'layout-dashboard';
@endphp

<header class="sticky top-0 z-30 border-b border-line bg-surface/85 backdrop-blur-md dark:border-night-line dark:bg-night-2/85">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">

        {{-- فاز ۱۹: لودر گرد سراسری — هم‌گام با نوار پیشرفت بالای صفحه (بالای پنل، سمت راست) --}}
        <div id="ap-nav-spinner" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
            </svg>
        </div>

        {{-- همبرگر موبایل --}}
        <button type="button" class="rounded-xl p-2 text-ink-2 hover:bg-surface-3 lg:hidden dark:text-night-ink-2 dark:hover:bg-night-3"
                x-on:click="open = true" aria-label="باز کردن منو">
            <x-admin-panel::icon name="menu" class="size-5" />
        </button>

        {{-- عنوان صفحه + آیکون (فاز ۱۹) --}}
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-500/25">
                    <x-admin-panel::icon name="{{ $pageIcon }}" class="size-4.5" />
                </span>
                <h1 class="truncate text-[15px] font-bold leading-6">{{ $pageTitle ?: 'داشبورد' }}</h1>
            </div>
            @hasSection('subtitle')
                <p class="truncate text-xs text-ink-3 dark:text-night-ink-2">@yield('subtitle')</p>
            @endif
        </div>

        {{-- اکشن‌ها --}}
        <div class="flex items-center gap-1 sm:gap-1.5">

            {{-- مشاهده سایت --}}
            <a href="{{ url('/') }}" target="_blank" rel="noopener"
               class="hidden rounded-xl p-2.5 text-ink-2 transition-colors hover:bg-surface-3 hover:text-ink sm:block dark:text-night-ink-2 dark:hover:bg-night-3 dark:hover:text-night-ink"
               aria-label="مشاهده سایت" title="مشاهده سایت">
                <x-admin-panel::icon name="globe" class="size-5" />
            </a>

            {{-- تمام صفحه --}}
            <button type="button" x-data="apFullscreen" x-on:click="toggle()"
                    class="rounded-xl p-2.5 text-ink-2 transition-colors hover:bg-surface-3 hover:text-ink dark:text-night-ink-2 dark:hover:bg-night-3 dark:hover:text-night-ink"
                    :aria-label="active ? 'خروج از تمام‌صفحه' : 'تمام‌صفحه'" title="تمام‌صفحه">
                <x-admin-panel::icon name="fullscreen" class="size-5" x-show="!active" x-cloak />
                <x-admin-panel::icon name="minimize" class="size-5" x-show="active" x-cloak />
            </button>

            {{-- تم روشن/تاریک --}}
            <button type="button" x-data x-on:click="ApTheme.toggle()"
                    class="rounded-xl p-2.5 text-ink-2 transition-colors hover:bg-surface-3 hover:text-ink dark:text-night-ink-2 dark:hover:bg-night-3 dark:hover:text-night-ink"
                    aria-label="تغییر تم روشن/تاریک" title="تغییر تم">
                <span class="hidden dark:block">
                    <x-admin-panel::icon name="sun" class="size-5" />
                </span>
                <span class="dark:hidden">
                    <x-admin-panel::icon name="moon" class="size-5" />
                </span>
            </button>

            {{-- اعلان‌ها — فاز ۱۶: دراپ‌داون اعلان‌های اخیر + شمارنده (به‌جای لینک به پنل قدیمی) --}}
            @if (auth('adminPanel')->user()?->can('notifications.panel'))
                <livewire:admin.notifications.header-notifications />
            @endif

            <span class="mx-1 hidden h-6 w-px bg-line sm:block dark:bg-night-line" aria-hidden="true"></span>

            {{-- منوی پروفایل --}}
            <div class="relative" x-data="apDropdown" x-on:click.outside="close()" x-on:keydown.escape.window="close()">
                <button type="button" class="flex items-center gap-2 rounded-xl p-1.5 transition-colors hover:bg-surface-3 dark:hover:bg-night-3"
                        x-on:click="toggle()" :aria-expanded="open.toString()" aria-haspopup="menu">
                    <img src="{{ $admin->imageUrl ?? '' }}" alt="{{ $admin->fullname }}" width="32" height="32"
                         class="size-8 rounded-lg object-cover ring-2 ring-brand-500/25">
                    <span class="hidden max-w-28 truncate text-[13px] font-semibold sm:block">{{ $admin->fullname }}</span>
                    <x-admin-panel::icon name="chevron-down" class="size-4 text-ink-3" />
                </button>

                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 scale-95"
                     class="absolute end-0 top-full z-40 mt-2 w-60 origin-top rounded-2xl border border-line bg-surface p-1.5 shadow-pop dark:border-night-line dark:bg-night-3"
                     role="menu">
                    <div class="border-b border-line px-3 py-2.5 dark:border-night-line">
                        <p class="truncate text-[13px] font-bold">{{ $admin->fullname }}</p>
                        <p class="mt-0.5 truncate text-[11px] text-ink-3 dark:text-night-ink-2">{{ $admin->email ?? '' }}</p>
                    </div>
                    <a href="{{ route('admin.admin.profile.show') }}"
                       class="mt-1.5 flex items-center gap-2.5 rounded-xl px-3 py-2 text-[13px] font-medium text-ink-2 transition-colors hover:bg-surface-2 hover:text-ink dark:text-night-ink-2 dark:hover:bg-night-2 dark:hover:text-night-ink"
                       role="menuitem">
                        <x-admin-panel::icon name="user" class="size-4" />
                        ویرایش پروفایل
                    </a>
                    <a href="{{ url('admin-old/' . admin_route_prefix()) }}"
                       class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-[13px] font-medium text-ink-2 transition-colors hover:bg-surface-2 hover:text-ink dark:text-night-ink-2 dark:hover:bg-night-2 dark:hover:text-night-ink"
                       role="menuitem">
                        <x-admin-panel::icon name="package-search" class="size-4" />
                        پنل مدیریت قدیمی
                    </a>
                    @if ($admin->id === 1)
                        <a href="{{ route('admin.cache-clear') }}"
                           class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-[13px] font-medium text-ink-2 transition-colors hover:bg-surface-2 hover:text-ink dark:text-night-ink-2 dark:hover:bg-night-2 dark:hover:text-night-ink"
                           role="menuitem">
                            <x-admin-panel::icon name="refresh-cw" class="size-4" />
                            پاک‌سازی کش
                        </a>
                    @endif
                    <div class="my-1.5 border-t border-line dark:border-night-line"></div>
                    <a href="{{ route('np.logout') }}" wire:navigate
                       class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-[13px] font-semibold text-red-600 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10"
                       role="menuitem">
                        <x-admin-panel::icon name="log-out" class="size-4" />
                        خروج از حساب
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
