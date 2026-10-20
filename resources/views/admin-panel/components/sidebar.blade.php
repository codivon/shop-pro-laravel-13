{{-- سایدبار پنل جدید — فاز ۰ --}}
@php
    $menu = config('admin-menu.groups');
    $admin = auth('adminPanel')->user();
    $siteTitle = option('info_site_title', config('app.name'));
    // فاز ۱۲: منوهای وابسته به سیستم چندفروشندگی فقط وقتی فعال است نمایش داده می‌شوند
    $vendorEnabled = option('multi_vendor_system_status', 'false') == 'true';

    /*
    | فاز ۱۹ — نقشه‌ی کلیدهای پارامتری هر روت (روی کل منو):
    | برای تشخیص «آیتم بدون پارامتر» (مثل «همه سفارشات») از آیتم‌های
    | هم‌روتِ پارامتردار (مثل «پرداخت شده») — تا هر دو هم‌زمان فعال نشوند.
    */
    $menuParamKeys = [];
    $collectParamKeys = function ($items) use (&$collectParamKeys, &$menuParamKeys) {
        foreach ($items as $it) {
            if (! empty($it['route']) && ! empty($it['params'])) {
                foreach (array_keys($it['params']) as $pk) {
                    $menuParamKeys[$it['route']][] = $pk;
                }
            }
            if (! empty($it['children'])) $collectParamKeys($it['children']);
        }
    };
    foreach ($menu as $g) $collectParamKeys($g['items']);
    $menuParamKeys = collect($menuParamKeys)->map(fn ($keys) => array_values(array_unique($keys)))->all();
@endphp

<aside
    class="fixed inset-y-0 right-0 z-50 flex w-[17rem] flex-col border-l border-line bg-surface transition-transform duration-300 lg:translate-x-0
           dark:border-night-line dark:bg-night-2"
    {{-- فاز ۲۰ — رفع «در موبایل سایدبار باز نمی‌شود»: این aside قبلاً x-data="apSidebar"
         خودش را داشت؛ یعنی دو اسکوپ جدا وجود داشت (wrapper در layout + اینجا). همبرگر
         موبایل، openِ wrapper را true می‌کرد اما openِ همین aside همچنان false می‌ماند و
         منو با translate-x-full بیرون صفحه می‌ماند. حالا بدون x-data، از اسکوپِ wrapper
         (layout.blade.php) ارث‌بری می‌شود — همبرگر/پوشش/دکمه بستن همه یک state دارند. --}}
    x-bind:class="open ? 'translate-x-0' : 'translate-x-full'"
    aria-label="منوی اصلی پنل مدیریت"
>
    {{-- برند --}}
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-line px-5 dark:border-night-line">
        <span class="flex size-9 items-center justify-center rounded-xl bg-brand-600 text-white shadow-[0_6px_16px_-6px_rgb(5_150_105/.6)]">
            <x-admin-panel::icon name="store" class="size-5" />
        </span>
        <div class="min-w-0 leading-tight">
            <p class="truncate text-sm font-extrabold">{{ $siteTitle }}</p>
            <p class="text-[11px] text-ink-3 dark:text-night-ink-2">پنل مدیریت</p>
        </div>
        {{-- بستن موبایل --}}
        <button type="button" class="ms-auto rounded-lg p-1.5 text-ink-3 hover:bg-surface-3 lg:hidden dark:text-night-ink-2 dark:hover:bg-night-3"
                x-on:click="open = false" aria-label="بستن منو">
            <x-admin-panel::icon name="x" class="size-5" />
        </button>
    </div>

    {{-- جستجوی منو --}}
    <div class="px-4 pt-4">
        <div class="relative">
            <x-admin-panel::icon name="search" class="pointer-events-none absolute inset-y-0 end-3 my-auto size-4 text-ink-3" />
            <input type="search" x-model.debounce.150ms="search" placeholder="جستجو در منو..."
                   class="ap-input !py-2 ps-3 pe-9 text-[13px]" aria-label="جستجوی منو">
        </div>
    </div>

    {{-- آیتم‌های منو --}}
    <nav x-ref="nav" x-on:scroll.passive="saveScroll()" class="flex-1 overflow-y-auto overflow-x-hidden px-3 pb-6 pt-1">
        @foreach ($menu as $group)
            @php
                // فاز ۱۲: گروه‌های وابسته به چندفروشندگی + آیتم‌ها بر اساس دسترسی
                if (! empty($group['vendor']) && ! $vendorEnabled) continue;

                $visibleItems = collect($group['items'])->filter(function ($item) use ($admin, $vendorEnabled) {
                    if (! empty($item['vendor']) && ! $vendorEnabled) return false;
                    if (!empty($item['creator_only'])) {
                        return $admin && ($admin->id === 1 || $admin->level === 'creator');
                    }
                    if (empty($item['permission'])) return true;
                    return $admin && $admin->can($item['permission']);
                })->values();

                if ($visibleItems->isEmpty()) continue;

                /*
                | فاز ۲۰ — جستجوی دقیق منو: به‌جای متن تختِ کل گروه (که جفت‌شدن کلمات دو
                | منوی مختلف در آن فالس‌پازیو می‌ساخت)، متنِ هر آیتم به‌صورت جداگانه
                | چک می‌شود و فقط آیتم‌های منطبق + گروهِ دارای منطبق نمایش داده می‌شوند.
                */
                $groupItemTexts = $visibleItems->map(function ($i) {
                    return $i['label'].' '.collect($i['children'] ?? [])->pluck('label')->implode(' ');
                })->values();
            @endphp

            <div x-show="window.apMenuSearch.hasArray(@js([$group['title'], ...$groupItemTexts->all()]), search)"
                 x-transition.opacity.duration.150ms>
                <p class="ap-menu-title">{{ $group['title'] }}</p>

                <ul class="space-y-0.5">
                    @foreach ($visibleItems as $item)
                        @if (!empty($item['children']))
                            {{-- آیتم با زیرمنو --}}
                            @php
                                $childItems = collect($item['children'])
                                    ->filter(fn ($c) => ! empty($c['vendor']) && ! $vendorEnabled ? false : (empty($c['permission']) || ($admin && $admin->can($c['permission']))))
                                    ->values();
                            @endphp
                            @if ($childItems->isNotEmpty())
                                @php $parentLabel = $item['label']; @endphp
                                {{-- فاز ۲۰ — پدر در جستجو فقط وقتی دیده می‌شود که خودش یا یکی از فرزندانش منطبق باشد --}}
                                <li x-show="search === '' || window.apMenuSearch.has(@js($parentLabel), search) || @js($childItems->pluck('label')->values()->all()).some((t) => window.apMenuSearch.has(t, search))">
                                    <div x-data="apMenuGroup(false)">
                                        <button type="button" class="ap-menu-link w-full" x-on:click="toggle()"
                                                :aria-expanded="open.toString()" aria-haspopup="true">
                                            <x-admin-panel::icon :name="$item['icon']" class="size-[18px] shrink-0 opacity-80" />
                                            <span class="flex-1 text-start">{{ $item['label'] }}</span>
                                            @if (isset($item['live']))
                                                <span class="size-2 rounded-full bg-brand-500 animate-pulse-dot"></span>
                                            @endif
                                            <span class="ap-chevron inline-flex" :class="open ? 'is-open' : ''">
                                                <x-admin-panel::icon name="chevron-left" class="size-4 opacity-60" />
                                            </span>
                                        </button>
                                        {{-- فاز ۲۰ — حین جستجو زیرمنو همیشه باز می‌ماند تا آیتم منطبق دیده شود؛ با پاک‌کردن جستجو، حالت باز/بسته‌ی قبلی بازمی‌گردد (state تغییری نکرده) --}}
                                        <div x-show="open || search !== ''" x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 -translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             x-transition:leave="transition ease-in duration-150"
                                             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                                            <ul class="mt-0.5 space-y-0.5 ms-4 border-s-2 border-line/70 ps-2.5 dark:border-night-line/70">
                                                {{-- فاز ۱۹: @each اسکوپ را پاس نمی‌دهد؛ foreach + include تا $menuParamKeys به menu-item برسد --}}
                                                {{-- فاز ۲۰ — هر فرزند فقط با لیبل خودش (یا لیبل پدر) در جستجو دیده می‌شود --}}
                                                @foreach ($childItems as $item)
                                                    <li x-show="search === '' || window.apMenuSearch.has(@js($parentLabel), search) || window.apMenuSearch.has(@js($item['label']), search)">
                                                        @include('admin-panel::components.menu-item', ['item' => $item])
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </li>
                            @endif
                        @else
                            {{-- فاز ۲۰ — آیتم برگ فقط با لیبل خودش در جستجو دیده می‌شود --}}
                            <li x-show="search === '' || window.apMenuSearch.has(@js($item['label']), search)">
                                @include('admin-panel::components.menu-item', ['item' => $item])
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endforeach

        {{-- منوی ماژول‌ها (پکیج‌ها) — مطابق پنل قدیمی --}}
        @foreach (\App\Services\ModuleMenuRegistry::all() as $menuView)
            @if (view()->exists($menuView))
                <div class="menu-group">
                    <p class="ap-menu-title">پکیج‌ها</p>
                    @include($menuView)
                </div>
            @endif
        @endforeach
    </nav>

    {{-- پایین سایدبار: پروفایل --}}
    <div class="border-t border-line p-3 dark:border-night-line">
        <div class="flex items-center gap-3 rounded-xl bg-surface-2 p-2.5 dark:bg-night-3">
            <img src="{{ $admin->imageUrl ?? '' }}" alt="{{ $admin->fullname }}" width="40" height="40"
                 class="size-10 rounded-lg object-cover ring-2 ring-brand-500/25">
            <div class="min-w-0 flex-1 leading-tight">
                <p class="truncate text-[13px] font-bold">{{ $admin->fullname }}</p>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                    {{ $admin->level === 'creator' ? 'برنامه‌نویس' : 'مدیر' }}
                </p>
            </div>
            <a href="{{ route('np.logout') }}" wire:navigate
               class="rounded-lg p-2 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:text-night-ink-2 dark:hover:bg-red-500/10 dark:hover:text-red-400"
               aria-label="خروج از حساب" title="خروج از حساب">
                <x-admin-panel::icon name="log-out" class="size-[18px]" />
            </a>
        </div>
    </div>
</aside>
