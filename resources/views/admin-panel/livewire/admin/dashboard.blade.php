{{-- داشبورد — غنی‌سازی فاز ۹-ب: نوار میانبرها + ردیف کارت‌های دوم/سوم + نمودار ثبت‌نام + ۶ لیست --}}
<div class="mx-auto max-w-7xl space-y-6">

    {{-- خوش‌آمد --}}
    <section class="relative overflow-hidden rounded-2xl bg-linear-to-l from-brand-600 to-brand-800 p-6 text-white shadow-[0_16px_40px_-16px_rgb(5_150_105/.5)] sm:p-8">
        <div class="absolute -end-10 -top-14 size-56 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
        <div class="absolute -bottom-20 start-1/3 size-64 rounded-full bg-emerald-300/15 blur-3xl" aria-hidden="true"></div>

        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-white/75">{{ $todayJalali }}</p>
                <h2 class="mt-1.5 text-2xl font-extrabold">سلام {{ auth('adminPanel')->user()->fullname }} 👋</h2>
                <p class="mt-1.5 text-sm text-white/80">خوش آمدید — خلاصه‌ای از وضعیت فروشگاه امروز:</p>
            </div>
            <div class="flex gap-2.5">
                <span class="rounded-xl bg-white/12 px-3.5 py-2 text-[13px] font-bold backdrop-blur">
                    سفارش امروز: <span class="fa-digits">{{ \App\Livewire\Admin\Dashboard::fa($stats['ordersToday']) }}</span>
                </span>
                <span class="rounded-xl bg-white/12 px-3.5 py-2 text-[13px] font-bold backdrop-blur">
                    کاربر جدید: <span class="fa-digits">{{ \App\Livewire\Admin\Dashboard::fa($stats['usersToday']) }}</span>
                </span>
            </div>
        </div>
    </section>

    {{-- نوار میانبرها — هر دکمه فقط با دسترسی متناظر (permها از config/admin-menu.php) --}}
    <section class="ap-card flex flex-wrap items-center gap-2 p-3">
        <span class="hidden items-center gap-1.5 px-2 text-xs font-bold text-ink-3 sm:inline-flex dark:text-night-ink-2">
            <x-admin-panel::icon name="sparkles" class="size-4 text-brand-600 dark:text-brand-400" />
            میانبرها:
        </span>
        @if ($this->can('products.create'))
            <a href="{{ route('np.products.create') }}" wire:navigate
               class="ap-menu-link bg-surface-2 text-[13px] font-bold hover:bg-brand-50 dark:bg-night-3 dark:hover:bg-brand-500/10">
                <x-admin-panel::icon name="box" class="size-4 text-brand-600 dark:text-brand-400" />
                محصول جدید
            </a>
        @endif
        @if ($this->can('orders.create'))
            <a href="{{ route('np.orders.create') }}" wire:navigate
               class="ap-menu-link bg-surface-2 text-[13px] font-bold hover:bg-sky-50 dark:bg-night-3 dark:hover:bg-sky-500/10">
                <x-admin-panel::icon name="shopping-cart" class="size-4 text-sky-600 dark:text-sky-400" />
                سفارش جدید
            </a>
        @endif
        @if ($this->can('posts.create'))
            <a href="{{ route('np.posts.create') }}" wire:navigate
               class="ap-menu-link bg-surface-2 text-[13px] font-bold hover:bg-amber-50 dark:bg-night-3 dark:hover:bg-amber-500/10">
                <x-admin-panel::icon name="notebook-pen" class="size-4 text-amber-600 dark:text-amber-400" />
                پست جدید
            </a>
        @endif
        @if ($this->can('tickets.index'))
            <a href="{{ route('np.tickets.index') }}" wire:navigate
               class="ap-menu-link bg-surface-2 text-[13px] font-bold hover:bg-rose-50 dark:bg-night-3 dark:hover:bg-rose-500/10">
                <x-admin-panel::icon name="life-buoy" class="size-4 text-rose-600 dark:text-rose-400" />
                تیکت‌ها
            </a>
        @endif
        @if ($this->can('settings.information'))
            <a href="{{ route('np.settings.information') }}" wire:navigate
               class="ap-menu-link bg-surface-2 text-[13px] font-bold hover:bg-violet-50 dark:bg-night-3 dark:hover:bg-violet-500/10">
                <x-admin-panel::icon name="settings-2" class="size-4 text-violet-600 dark:text-violet-400" />
                تنظیمات
            </a>
        @endif
        @if ($this->can('themes.settings'))
            <a href="{{ route('np.widgets.index') }}" wire:navigate
               class="ap-menu-link bg-surface-2 text-[13px] font-bold hover:bg-teal-50 dark:bg-night-3 dark:hover:bg-teal-500/10">
                <x-admin-panel::icon name="layout-panel-top" class="size-4 text-teal-600 dark:text-teal-400" />
                ویجت‌ها
            </a>
        @endif
    </section>

    {{-- کارت‌های آماری — ردیف اول --}}
    <section class="stagger grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="ap-card group p-5 transition-shadow hover:shadow-pop">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="users" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">کاربران</p>
                    <p class="fa-digits mt-0.5 text-2xl font-extrabold">{{ \App\Livewire\Admin\Dashboard::fa($stats['users']) }}</p>
                </div>
            </div>
            <p class="mt-3 text-[11px] text-ink-3 dark:text-night-ink-2">امروز:
                <span class="fa-digits font-bold text-brand-600 dark:text-brand-400">+{{ \App\Livewire\Admin\Dashboard::fa($stats['usersToday']) }}</span>
            </p>
        </div>

        <div class="ap-card group p-5 transition-shadow hover:shadow-pop">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="box" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">محصولات</p>
                    <p class="fa-digits mt-0.5 text-2xl font-extrabold">{{ \App\Livewire\Admin\Dashboard::fa($stats['products']) }}</p>
                </div>
            </div>
            <p class="mt-3 text-[11px] text-ink-3 dark:text-night-ink-2">مجموع کل محصولات فروشگاه</p>
        </div>

        <div class="ap-card group p-5 transition-shadow hover:shadow-pop">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                    <x-admin-panel::icon name="shopping-cart" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">سفارشات</p>
                    <p class="fa-digits mt-0.5 text-2xl font-extrabold">{{ \App\Livewire\Admin\Dashboard::fa($stats['orders']) }}</p>
                </div>
            </div>
            <p class="mt-3 text-[11px] text-ink-3 dark:text-night-ink-2">
                پرداخت‌شده: <span class="fa-digits font-bold text-brand-600 dark:text-brand-400">{{ \App\Livewire\Admin\Dashboard::fa($stats['paidOrders']) }}</span>
            </p>
        </div>

        <div class="ap-card group p-5 transition-shadow hover:shadow-pop">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-rose-50 text-rose-600 dark:bg-rose-500/12 dark:text-rose-400">
                    <x-admin-panel::icon name="store" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">فروشندگان</p>
                    <p class="fa-digits mt-0.5 text-2xl font-extrabold">{{ \App\Livewire\Admin\Dashboard::fa($stats['sellers']) }}</p>
                </div>
            </div>
            <p class="mt-3 text-[11px] text-ink-3 dark:text-night-ink-2">
                در انتظار تایید: <span class="fa-digits font-bold text-amber-600 dark:text-amber-400">{{ \App\Livewire\Admin\Dashboard::fa($sellerStats['waiting']) }}</span>
            </p>
        </div>
    </section>

    {{-- کارت‌های آماری — ردیف دوم (فاز ۹-ب) --}}
    <section class="stagger grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="ap-card group p-5 transition-shadow hover:shadow-pop">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="banknote" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">درآمد امروز</p>
                    <p class="fa-digits mt-0.5 text-2xl font-extrabold">{{ \App\Livewire\Admin\Dashboard::toman($revenueStats['today']) }}</p>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between gap-2 text-[11px]">
                <span class="min-w-0 truncate text-ink-3 dark:text-night-ink-2">دیروز:
                    <span class="fa-digits font-bold">{{ \App\Livewire\Admin\Dashboard::toman($revenueStats['yesterday']) }}</span>
                </span>
                @if ($revenueStats['direction'] === 'up')
                    <span class="ap-badge shrink-0 bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                        <x-admin-panel::icon name="trending-up" class="size-3.5" />
                        <span class="fa-digits" dir="ltr">+{{ \App\Livewire\Admin\Dashboard::fa(abs($revenueStats['percent'] ?? 100)) }}%</span>
                    </span>
                @elseif ($revenueStats['direction'] === 'down')
                    <span class="ap-badge shrink-0 bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">
                        <x-admin-panel::icon name="trending-down" class="size-3.5" />
                        <span class="fa-digits" dir="ltr">-{{ \App\Livewire\Admin\Dashboard::fa(abs($revenueStats['percent'] ?? 0)) }}%</span>
                    </span>
                @else
                    <span class="ap-badge shrink-0 bg-surface-2 text-ink-3 dark:bg-night-3 dark:text-night-ink-2">بدون تغییر</span>
                @endif
            </div>
        </div>

        <div class="ap-card group p-5 transition-shadow hover:shadow-pop">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="clock" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">سفارش‌های در انتظار</p>
                    <p class="fa-digits mt-0.5 text-2xl font-extrabold">{{ \App\Livewire\Admin\Dashboard::fa($orderFlags['pending']) }}</p>
                </div>
            </div>
            <p class="mt-3 text-[11px] text-ink-3 dark:text-night-ink-2">
                پرداخت‌شده آماده ارسال:
                <span class="fa-digits font-bold text-sky-600 dark:text-sky-400">{{ \App\Livewire\Admin\Dashboard::fa($orderFlags['unshipped']) }}</span>
            </p>
        </div>

        <div class="ap-card group p-5 transition-shadow hover:shadow-pop">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-rose-50 text-rose-600 dark:bg-rose-500/12 dark:text-rose-400">
                    <x-admin-panel::icon name="package" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">محصولات ناموجود</p>
                    <p class="fa-digits mt-0.5 text-2xl font-extrabold">{{ \App\Livewire\Admin\Dashboard::fa($inventoryStats['outOfStock']) }}</p>
                </div>
            </div>
            <p class="mt-3 text-[11px] text-ink-3 dark:text-night-ink-2">
                کم‌موجودی (۵ یا کمتر):
                <span class="fa-digits font-bold text-amber-600 dark:text-amber-400">{{ \App\Livewire\Admin\Dashboard::fa($inventoryStats['lowStock']) }}</span>
            </p>
        </div>

        <div class="ap-card group p-5 transition-shadow hover:shadow-pop">
            <div class="flex items-center gap-4">
                <span class="ap-stat-icon bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                    <x-admin-panel::icon name="message-square" class="size-5.5" />
                </span>
                <div class="min-w-0">
                    <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">دیدگاه‌های در انتظار تایید</p>
                    <p class="fa-digits mt-0.5 text-2xl font-extrabold">{{ \App\Livewire\Admin\Dashboard::fa($commentStats['pending']) }}</p>
                </div>
            </div>
            <p class="mt-3 text-[11px] text-ink-3 dark:text-night-ink-2">
                کل دیدگاه‌های محصولات:
                <span class="fa-digits font-bold text-violet-600 dark:text-violet-400">{{ \App\Livewire\Admin\Dashboard::fa($commentStats['productTotal']) }}</span>
            </p>
        </div>
    </section>

    {{-- کارت‌های آماری — ردیف سوم (کوچک‌تر) --}}
    <section class="stagger grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="ap-card p-4 transition-shadow hover:shadow-pop">
            <div class="flex items-center gap-3">
                <span class="ap-stat-icon size-9 bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                    <x-admin-panel::icon name="life-buoy" class="size-4.5" />
                </span>
                <div class="min-w-0">
                    <p class="truncate text-[12px] font-medium text-ink-2 dark:text-night-ink-2">تیکت‌های باز</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ \App\Livewire\Admin\Dashboard::fa($ticketStats['open']) }}</p>
                </div>
            </div>
            <p class="mt-2.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                از کل <span class="fa-digits font-bold">{{ \App\Livewire\Admin\Dashboard::fa($ticketStats['total']) }}</span> تیکت
            </p>
        </div>

        <div class="ap-card p-4 transition-shadow hover:shadow-pop">
            <div class="flex items-center gap-3">
                <span class="ap-stat-icon size-9 bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="star" class="size-4.5" />
                </span>
                <div class="min-w-0">
                    <p class="truncate text-[12px] font-medium text-ink-2 dark:text-night-ink-2">میانگین امتیاز محصولات</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ \App\Livewire\Admin\Dashboard::fa(number_format($commentStats['ratingAvg'], 1)) }} <span class="text-xs font-bold text-ink-3 dark:text-night-ink-2">از ۵</span></p>
                </div>
            </div>
            <p class="mt-2.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                از <span class="fa-digits font-bold">{{ \App\Livewire\Admin\Dashboard::fa($commentStats['ratingCount']) }}</span> دیدگاه تاییدشده
            </p>
        </div>

        <div class="ap-card p-4 transition-shadow hover:shadow-pop">
            <div class="flex items-center gap-3">
                <span class="ap-stat-icon size-9 bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="wallet" class="size-4.5" />
                </span>
                <div class="min-w-0">
                    <p class="truncate text-[12px] font-medium text-ink-2 dark:text-night-ink-2">کیف پول کاربران</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ \App\Livewire\Admin\Dashboard::toman($walletStats['balance']) }}</p>
                </div>
            </div>
            <p class="mt-2.5 text-[11px] text-ink-3 dark:text-night-ink-2">مجموع موجودی کیف پول کاربران</p>
        </div>

        <div class="ap-card p-4 transition-shadow hover:shadow-pop">
            <div class="flex items-center gap-3">
                <span class="ap-stat-icon size-9 bg-rose-50 text-rose-600 dark:bg-rose-500/12 dark:text-rose-400">
                    <x-admin-panel::icon name="store" class="size-4.5" />
                </span>
                <div class="min-w-0">
                    <p class="truncate text-[12px] font-medium text-ink-2 dark:text-night-ink-2">فروشندگان در انتظار تایید</p>
                    <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ \App\Livewire\Admin\Dashboard::fa($sellerStats['waiting']) }}</p>
                </div>
            </div>
            <p class="mt-2.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                کل: <span class="fa-digits font-bold">{{ \App\Livewire\Admin\Dashboard::fa($sellerStats['total']) }}</span> غرفه
            </p>
        </div>
    </section>

    {{-- نمودار فروش ۳۰ روز اخیر — Apache ECharts --}}
    <section class="ap-card overflow-hidden">
        <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
            <div class="flex items-center gap-2.5">
                <x-admin-panel::icon name="file-chart-column" class="size-4.5 text-brand-600 dark:text-brand-400" />
                <h3 class="text-sm font-bold">روند فروش ۳۰ روز اخیر</h3>
            </div>
            <div class="flex items-center gap-3 text-[11px] font-semibold">
                <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-brand-500"></span>فروش (تومان)</span>
                <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-sky-500"></span>تعداد سفارش</span>
            </div>
        </header>
        <div data-chart="sales" data-options="{{ json_encode($salesOptions) }}" class="h-80 w-full"></div>
    </section>

    {{-- ردیف نمودارها: ثبت‌نام کاربران + وضعیت سفارشات (فاز ۹-ب) --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="ap-card overflow-hidden">
            <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
                <div class="flex items-center gap-2.5">
                    <x-admin-panel::icon name="users" class="size-4.5 text-violet-600 dark:text-violet-400" />
                    <h3 class="text-sm font-bold">ثبت‌نام کاربران — ۳۰ روز اخیر</h3>
                </div>
                <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                    امروز: <span class="fa-digits font-bold">{{ \App\Livewire\Admin\Dashboard::fa($stats['usersToday']) }}</span>
                </span>
            </header>
            <div data-chart="users" data-options="{{ json_encode($usersOptions) }}" class="h-72 w-full"></div>
        </section>

        <section class="space-y-6">
            <div class="ap-card overflow-hidden">
                <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
                    <div class="flex items-center gap-2.5">
                        <x-admin-panel::icon name="chart-pie" class="size-4.5 text-sky-600 dark:text-sky-400" />
                        <h3 class="text-sm font-bold">وضعیت سفارشات</h3>
                    </div>
                </header>
                <div data-chart="status" data-options="{{ json_encode($statusOptions) }}" class="h-64 w-full"></div>
            </div>

            @if (count($topProducts))
                <div class="ap-card overflow-hidden">
                    <header class="flex items-center gap-2.5 border-b border-line px-5 py-4 dark:border-night-line">
                        <x-admin-panel::icon name="trophy" class="size-4.5 text-amber-600 dark:text-amber-400" />
                        <h3 class="text-sm font-bold">پرفروش‌ترین محصولات (۳۰ روز)</h3>
                    </header>
                    <div data-chart="topProducts" data-options="{{ json_encode($topProductsOptions) }}" class="h-56 w-full px-2"></div>
                </div>
            @endif
        </section>
    </div>

    {{-- لیست‌ها — grid ۲ ستونه (فاز ۹-ب) --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

        {{-- آخرین سفارشات --}}
        <section class="ap-card">
            <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
                <div class="flex items-center gap-2.5">
                    <x-admin-panel::icon name="shopping-cart" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    <h3 class="text-sm font-bold">آخرین سفارشات</h3>
                </div>
                @if ($this->can('orders.index'))
                    <a href="{{ route('np.orders.index') }}" wire:navigate
                       class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-bold text-brand-700 transition-colors hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10">
                        مشاهده همه
                        <x-admin-panel::icon name="chevron-left" class="size-3.5" />
                    </a>
                @endif
            </header>

            <div class="max-h-96 overflow-y-auto p-2.5">
                @forelse ($recentOrders as $order)
                    <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                            <x-admin-panel::icon name="receipt" class="size-4.5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-bold">{{ $order['name'] }}</p>
                            <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                                #{{ \App\Livewire\Admin\Dashboard::fa($order['id']) }} — {{ \App\Livewire\Admin\Dashboard::fa($order['date']) }}
                            </p>
                        </div>
                        <span class="fa-digits text-[13px] font-extrabold">{{ \App\Livewire\Admin\Dashboard::toman($order['price']) }}</span>
                        <span class="ap-badge
                            {{ $order['status'] === 'paid' ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400' : '' }}
                            {{ $order['status'] === 'unpaid' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' : '' }}
                            {{ in_array($order['status'], ['canceled']) ? 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400' : '' }}">
                            {{ $order['statusLabel'] }}
                        </span>
                    </div>
                @empty
                    <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="package-search" class="size-8 opacity-50" />
                        <p class="text-sm">هنوز سفارشی ثبت نشده است</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- آخرین دیدگاه‌ها --}}
        <section class="ap-card">
            <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
                <div class="flex items-center gap-2.5">
                    <x-admin-panel::icon name="message-square" class="size-4.5 text-violet-600 dark:text-violet-400" />
                    <h3 class="text-sm font-bold">آخرین دیدگاه‌ها</h3>
                </div>
                @if ($this->can('products.comments'))
                    <a href="{{ route('np.comments.products') }}" wire:navigate
                       class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-bold text-violet-700 transition-colors hover:bg-violet-50 dark:text-violet-400 dark:hover:bg-violet-500/10">
                        مشاهده همه
                        <x-admin-panel::icon name="chevron-left" class="size-3.5" />
                    </a>
                @endif
            </header>

            <div class="max-h-96 overflow-y-auto p-2.5">
                @forelse ($commentStats['items'] as $comment)
                    <div class="flex items-start gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-violet-50 text-[13px] font-extrabold text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                            {{ mb_substr($comment['author'], 0, 1) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-[13px] font-bold">{{ $comment['author'] }}</p>
                                <span class="ap-badge shrink-0 bg-surface-2 text-ink-3 dark:bg-night-3 dark:text-night-ink-2">{{ $comment['targetType'] }}</span>
                            </div>
                            <p class="mt-0.5 line-clamp-2 text-[11.5px] leading-5 text-ink-2 dark:text-night-ink-2">{{ $comment['body'] }}</p>
                            <p class="mt-1 truncate text-[11px] text-ink-3 dark:text-night-ink-2">
                                روی: <span class="font-bold">{{ $comment['targetTitle'] }}</span>
                                — <span class="fa-digits">{{ \App\Livewire\Admin\Dashboard::fa($comment['date']) }}</span>
                            </p>
                        </div>
                        <span class="ap-badge shrink-0
                            {{ $comment['status'] === 'pending' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' : '' }}
                            {{ in_array($comment['status'], ['accepted', 'approved']) ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400' : '' }}
                            {{ in_array($comment['status'], ['rejected', 'unconfirmed']) ? 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400' : '' }}">
                            {{ $comment['statusLabel'] }}
                        </span>
                    </div>
                @empty
                    <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="message-square" class="size-8 opacity-50" />
                        <p class="text-sm">هنوز دیدگاهی ثبت نشده است</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- آخرین تیکت‌ها --}}
        <section class="ap-card">
            <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
                <div class="flex items-center gap-2.5">
                    <x-admin-panel::icon name="life-buoy" class="size-4.5 text-sky-600 dark:text-sky-400" />
                    <h3 class="text-sm font-bold">آخرین تیکت‌ها</h3>
                </div>
                @if ($this->can('tickets.index'))
                    <a href="{{ route('np.tickets.index') }}" wire:navigate
                       class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-bold text-sky-700 transition-colors hover:bg-sky-50 dark:text-sky-400 dark:hover:bg-sky-500/10">
                        مشاهده همه
                        <x-admin-panel::icon name="chevron-left" class="size-3.5" />
                    </a>
                @endif
            </header>

            <div class="max-h-96 overflow-y-auto p-2.5">
                @forelse ($ticketStats['items'] as $ticket)
                    <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                            <x-admin-panel::icon name="messages-square" class="size-4.5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-bold">{{ $ticket['subject'] }}</p>
                            <p class="mt-0.5 truncate text-[11px] text-ink-3 dark:text-night-ink-2">
                                {{ $ticket['user'] }} — <span class="fa-digits">{{ \App\Livewire\Admin\Dashboard::fa($ticket['date']) }}</span>
                            </p>
                        </div>
                        <span class="ap-badge shrink-0
                            {{ $ticket['priority'] === 'hight' ? 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400' : '' }}
                            {{ $ticket['priority'] === 'medium' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' : '' }}
                            {{ ! in_array($ticket['priority'], ['hight', 'medium']) ? 'bg-surface-2 text-ink-3 dark:bg-night-3 dark:text-night-ink-2' : '' }}">
                            {{ $ticket['priorityLabel'] }}
                        </span>
                        <span class="ap-badge shrink-0
                            {{ $ticket['status'] === 'pending' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' : '' }}
                            {{ $ticket['status'] === 'answered' ? 'bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400' : '' }}
                            {{ $ticket['status'] === 'open' ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400' : '' }}
                            {{ ! in_array($ticket['status'], ['pending', 'answered', 'open']) ? 'bg-surface-2 text-ink-3 dark:bg-night-3 dark:text-night-ink-2' : '' }}">
                            {{ $ticket['statusLabel'] }}
                        </span>
                    </div>
                @empty
                    <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="life-buoy" class="size-8 opacity-50" />
                        <p class="text-sm">تیکتی ثبت نشده است</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- جدیدترین کاربران --}}
        <section class="ap-card">
            <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
                <div class="flex items-center gap-2.5">
                    <x-admin-panel::icon name="users" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    <h3 class="text-sm font-bold">جدیدترین کاربران</h3>
                </div>
                @if ($this->can('users.index'))
                    <a href="{{ route('np.users.index') }}" wire:navigate
                       class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-bold text-brand-700 transition-colors hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10">
                        مشاهده همه
                        <x-admin-panel::icon name="chevron-left" class="size-3.5" />
                    </a>
                @endif
            </header>

            <div class="max-h-96 overflow-y-auto p-2.5">
                @forelse ($newestUsers as $user)
                    <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                        @if ($user['avatar'])
                            <img src="{{ $user['avatar'] }}" alt="{{ $user['name'] }}" loading="lazy"
                                 class="size-9 shrink-0 rounded-full object-cover ring-2 ring-surface-2 dark:ring-night-3" />
                        @else
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-[13px] font-extrabold text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                                {{ mb_substr($user['name'], 0, 1) }}
                            </span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-bold">{{ $user['name'] }}</p>
                            <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2" dir="ltr">
                                {{ $user['mobile'] ? \App\Livewire\Admin\Dashboard::fa($user['mobile']) : '—' }}
                            </p>
                        </div>
                        <span class="fa-digits shrink-0 text-[11px] text-ink-3 dark:text-night-ink-2">{{ \App\Livewire\Admin\Dashboard::fa($user['date']) }}</span>
                    </div>
                @empty
                    <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="users" class="size-8 opacity-50" />
                        <p class="text-sm">کاربری ثبت‌نام نکرده است</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- محصولات کم‌موجودی --}}
        <section class="ap-card">
            <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
                <div class="flex items-center gap-2.5">
                    <x-admin-panel::icon name="package" class="size-4.5 text-amber-600 dark:text-amber-400" />
                    <h3 class="text-sm font-bold">محصولات کم‌موجودی</h3>
                </div>
                @if ($this->can('products.index'))
                    <a href="{{ route('np.products.index') }}" wire:navigate
                       class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-bold text-amber-700 transition-colors hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-500/10">
                        مشاهده همه
                        <x-admin-panel::icon name="chevron-left" class="size-3.5" />
                    </a>
                @endif
            </header>

            <div class="max-h-96 overflow-y-auto p-2.5">
                @forelse ($inventoryStats['items'] as $product)
                    <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                            <x-admin-panel::icon name="package" class="size-4.5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-bold">{{ $product['title'] }}</p>
                            <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">{{ \App\Livewire\Admin\Dashboard::toman($product['price']) }}</p>
                        </div>
                        <span class="ap-badge shrink-0
                            {{ $product['stock'] <= 2 ? 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' }}">
                            موجودی: <span class="fa-digits">{{ \App\Livewire\Admin\Dashboard::fa($product['stock']) }}</span>
                        </span>
                    </div>
                @empty
                    <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="package-check" class="size-8 opacity-50" />
                        <p class="text-sm">همه محصولات موجودی کافی دارند</p>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- آخرین تراکنش‌ها --}}
        <section class="ap-card">
            <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
                <div class="flex items-center gap-2.5">
                    <x-admin-panel::icon name="credit-card" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    <h3 class="text-sm font-bold">آخرین تراکنش‌ها</h3>
                </div>
                @if ($this->can('payments.transactions.index'))
                    <a href="{{ route('np.transactions.index') }}" wire:navigate
                       class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-bold text-brand-700 transition-colors hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10">
                        مشاهده همه
                        <x-admin-panel::icon name="chevron-left" class="size-3.5" />
                    </a>
                @endif
            </header>

            <div class="max-h-96 overflow-y-auto p-2.5">
                @forelse ($latestTransactions as $transaction)
                    <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                            <x-admin-panel::icon name="banknote" class="size-4.5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-bold">{{ $transaction['user'] }}</p>
                            <p class="mt-0.5 truncate text-[11px] text-ink-3 dark:text-night-ink-2">
                                {{ $transaction['type'] }}@if ($transaction['direction']) · {{ $transaction['direction'] }} @endif
                                — <span class="fa-digits">{{ \App\Livewire\Admin\Dashboard::fa($transaction['date']) }}</span>
                            </p>
                        </div>
                        <span class="fa-digits shrink-0 text-[13px] font-extrabold">{{ \App\Livewire\Admin\Dashboard::toman($transaction['amount']) }}</span>
                        @if ($transaction['status'])
                            <span class="ap-badge shrink-0 bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">موفق</span>
                        @else
                            <span class="ap-badge shrink-0 bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">ناموفق</span>
                        @endif
                    </div>
                @empty
                    <div class="flex flex-col items-center gap-2 py-10 text-ink-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="credit-card" class="size-8 opacity-50" />
                        <p class="text-sm">تراکنشی ثبت نشده است</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
