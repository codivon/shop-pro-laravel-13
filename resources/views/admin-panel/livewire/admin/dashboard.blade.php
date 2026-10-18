{{-- داشبورد — فاز ۰ (نمودارهای کامل در فاز ۱) --}}
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

    {{-- کارت‌های آماری --}}
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
            <p class="mt-3 text-[11px] text-ink-3 dark:text-night-ink-2">غرفه‌های فعال بازار</p>
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

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">

        {{-- آخرین سفارشات --}}
        <section class="ap-card xl:col-span-3">
            <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
                <div class="flex items-center gap-2.5">
                    <x-admin-panel::icon name="shopping-cart" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    <h3 class="text-sm font-bold">آخرین سفارشات</h3>
                </div>
                <a href="{{ route('np.orders.index') }}" wire:navigate
                   class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-bold text-brand-700 transition-colors hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10">
                    مشاهده همه
                    <x-admin-panel::icon name="chevron-left" class="size-3.5" />
                </a>
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
                                #{{ \App\Livewire\Admin\Dashboard::fa($order['id']) }} — {{ $order['date'] }}
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

        {{-- وضعیت سفارشات + دسترسی سریع --}}
        <section class="space-y-6 xl:col-span-2">
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

            <div class="ap-card p-5">
                <h3 class="mb-4 text-sm font-bold">دسترسی سریع</h3>
                <div class="grid grid-cols-2 gap-2.5">
                    <a href="{{ route('np.products.create') }}" wire:navigate class="ap-menu-link bg-surface-2 dark:bg-night-3">
                        <x-admin-panel::icon name="box" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        <span>محصول جدید</span>
                    </a>
                    <a href="{{ route('np.products.index') }}" wire:navigate class="ap-menu-link bg-surface-2 dark:bg-night-3">
                        <x-admin-panel::icon name="shopping-bag" class="size-4.5 text-sky-600 dark:text-sky-400" />
                        <span>محصولات</span>
                    </a>
                    <a href="{{ route('admin.sellers.index') }}" class="ap-menu-link bg-surface-2 dark:bg-night-3">
                        <x-admin-panel::icon name="store" class="size-4.5 text-rose-600 dark:text-rose-400" />
                        <span>فروشندگان</span>
                    </a>
                    <a href="{{ route('admin.statistics.orders') }}" class="ap-menu-link bg-surface-2 dark:bg-night-3">
                        <x-admin-panel::icon name="chart-column" class="size-4.5 text-amber-600 dark:text-amber-400" />
                        <span>گزارشات</span>
                    </a>
                </div>
            </div>
        </section>
    </div>
</div>
