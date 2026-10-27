{{-- داشبورد پنل فروشندگان مدرن (فاز ۱۰۸) — پورت sellers-old/panel/index.blade.php
     رشته‌های تست‌شدهٔ فاز ۱۰۳ عیناً حفظ شده‌اند: «نرخ کمیسیون اختصاصی شما: X٪»،
     «کمیسیون طبق جدول دسته‌بندی‌ها»، «پیش‌فرض سایت X٪» و کلاس vset-card --}}
<div class="mx-auto max-w-7xl space-y-5" x-data>

    {{-- ============ وضعیت سه‌گانه (مدارک/قرارداد/پنل) ============ --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach ([
            ['title' => 'وضعیت مدارک', 's' => $status['documents'], 'icon' => 'file-check'],
            ['title' => 'وضعیت قرارداد', 's' => $status['contract'], 'icon' => 'file-signature'],
            ['title' => 'وضعیت پنل', 's' => $status['work'], 'icon' => 'shield-check'],
        ] as $item)
        <div class="flex items-center gap-3 rounded-2xl border p-4 dark:border-night-line
                    {{ $item['s']['key'] === 'success'
                        ? 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-500/25 dark:bg-emerald-500/10'
                        : ($item['s']['key'] === 'warning'
                            ? 'border-amber-200 bg-amber-50/60 dark:border-amber-500/25 dark:bg-amber-500/10'
                            : 'border-red-200 bg-red-50/60 dark:border-red-500/25 dark:bg-red-500/10') }}">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl
                        {{ $item['s']['key'] === 'success'
                            ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
                            : ($item['s']['key'] === 'warning'
                                ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400'
                                : 'bg-red-500/15 text-red-600 dark:text-red-400') }}">
                <x-admin-panel::icon name="{{ $item['icon'] }}" class="size-5" />
            </span>
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2">{{ $item['title'] }}</p>
                <p class="truncate text-[13px] font-extrabold text-ink dark:text-night-ink">{{ $item['s']['label'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ============ آمار تسویه (فاز ۱۰۳ — گیت از تنظیمات چندفروشندگی) ============
         کلاس vset-card عیناً حفظ شده (تست فاز ۱۰۳: assertDontSee('vset-card') وقتی سوییچ خاموش است) --}}
    @if (! is_null($vendorStats))
        @php
            $vsItems = [
                [
                    'title' => 'موجودی قابل برداشت',
                    'value' => number_format($vendorStats['balance']).' تومان',
                    'icon'  => 'wallet',
                    'accent' => 'bg-emerald-500/12 text-emerald-600 dark:text-emerald-400',
                    'link'  => route('seller.wallet.index'),
                ],
                [
                    'title' => 'فروش موفق',
                    'value' => number_format($vendorStats['total_sales']).' تومان',
                    'icon'  => 'shopping-bag',
                    'accent' => 'bg-violet-500/12 text-violet-600 dark:text-violet-400',
                    'link'  => null,
                ],
                [
                    'title' => 'کمیسیون پرداخت‌شده',
                    'value' => number_format($vendorStats['total_commission']).' تومان',
                    'icon'  => 'percent',
                    'accent' => 'bg-amber-500/12 text-amber-600 dark:text-amber-400',
                    'link'  => null,
                    // نرخ مؤثر خودِ فروشنده (فاز ۱۰۳) — override اختصاصی ادمین > جدول دسته‌بندی‌ها > پیش‌فرض سراسری
                    'note' => ! is_null($vendorStats['seller_override_commission'] ?? null)
                        ? 'نرخ کمیسیون اختصاصی شما: '.(int) $vendorStats['seller_override_commission'].'٪ (روی همهٔ فروش‌ها)'
                        : (! is_null($vendorStats['default_commission'] ?? null)
                            ? 'کمیسیون طبق جدول دسته‌بندی‌ها؛ پیش‌فرض سایت '.(int) $vendorStats['default_commission'].'٪'
                            : 'کمیسیون طبق جدول دسته‌بندی‌ها'),
                    'note_link' => route('seller.commission'),
                ],
                [
                    'title' => 'درخواست در انتظار',
                    'value' => $vendorStats['waiting_withdraw_count'] > 0
                        ? number_format($vendorStats['waiting_withdraw_count']).' درخواست / '.number_format($vendorStats['waiting_withdraw_amount']).' تومان'
                        : 'درخواستی در انتظار نیست',
                    'icon'  => 'hourglass',
                    'accent' => 'bg-sky-500/12 text-sky-600 dark:text-sky-400',
                    'link'  => route('seller.wallet.index'),
                ],
            ];
        @endphp
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 vset-cards">
            @foreach ($vsItems as $vsItem)
                <div class="vset-card ap-card flex flex-col gap-3 p-5">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl {{ $vsItem['accent'] }}">
                            <x-admin-panel::icon name="{{ $vsItem['icon'] }}" class="size-4.5" />
                        </span>
                        <span class="text-[13px] font-bold text-ink-2 dark:text-night-ink-2">{{ $vsItem['title'] }}</span>
                    </div>
                    <p class="fa-digits text-[15px] font-extrabold leading-7 text-ink dark:text-night-ink">{{ $vsItem['value'] }}</p>
                    @if (! empty($vsItem['note']))
                        <p class="text-[11px] font-semibold leading-5 text-ink-3 dark:text-night-ink-2">
                            @if (! empty($vsItem['note_link']))
                                <a href="{{ $vsItem['note_link'] }}" wire:navigate class="text-amber-600 transition-colors hover:underline dark:text-amber-400">{{ $vsItem['note'] }}</a>
                            @else
                                {{ $vsItem['note'] }}
                            @endif
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- ============ کارت‌های سریع ============ --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <a href="{{ route('seller.products.create') }}" wire:navigate
           class="ap-card flex flex-col items-center justify-center gap-3 p-8 text-center transition-all hover:-translate-y-0.5 hover:shadow-lg">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-brand-500/12 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <x-admin-panel::icon name="plus" class="size-6" />
            </span>
            <p class="text-sm font-extrabold text-ink dark:text-night-ink">افزودن محصول جدید</p>
        </a>
        <a href="{{ route('seller.products.index') }}" wire:navigate
           class="ap-card flex flex-col items-center justify-center gap-3 p-8 text-center transition-all hover:-translate-y-0.5 hover:shadow-lg">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-violet-500/12 text-violet-600 dark:bg-violet-500/15 dark:text-violet-400">
                <x-admin-panel::icon name="layers" class="size-6" />
            </span>
            <p class="text-sm font-extrabold text-ink dark:text-night-ink">
                تنوع‌های فعال در پروموشن‌ها
                <span class="fa-digits ms-1 rounded-lg bg-surface-2 px-2 py-0.5 text-xs font-bold text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ number_format($variantsCount) }}</span>
            </p>
        </a>
        <div class="ap-card flex flex-col items-center justify-center gap-3 p-8 text-center">
            <span class="flex size-12 items-center justify-center rounded-2xl bg-emerald-500/12 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                <x-admin-panel::icon name="badge-check" class="size-6" />
            </span>
            <p class="text-sm font-extrabold text-ink dark:text-night-ink">وضعیت پرداخت <small class="text-xs font-bold text-emerald-600 dark:text-emerald-400">فعال</small></p>
        </div>
    </div>

    {{-- ============ آمار بازدید فروشگاه ============ --}}
    <div class="ap-card p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                    <x-admin-panel::icon name="eye" class="size-5" />
                </span>
                <div>
                    <h2 class="text-sm font-extrabold text-ink dark:text-night-ink">آمار بازدید فروشگاه</h2>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">بازدید صفحات فروشگاه شما در /store</p>
                </div>
            </div>
            <a href="{{ route('seller.statistics.index') }}" wire:navigate class="ap-btn-outline !py-2 !text-xs">
                گزارش کامل آمار
                <x-admin-panel::icon name="chevron-left" class="size-3.5" />
            </a>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
            @foreach ([
                ['label' => 'بازدید امروز', 'value' => number_format($todayVisits), 'icon' => 'calendar-days', 'accent' => 'bg-brand-500/12 text-brand-600 dark:text-brand-400'],
                ['label' => 'کل بازدیدها', 'value' => number_format($totalVisits), 'icon' => 'eye', 'accent' => 'bg-violet-500/12 text-violet-600 dark:text-violet-400'],
                ['label' => 'بازدیدکنندهٔ یکتا', 'value' => number_format($uniqueVisitors), 'icon' => 'users', 'accent' => 'bg-sky-500/12 text-sky-600 dark:text-sky-400'],
                ['label' => 'میانگین روزانه', 'value' => number_format($averageVisits), 'icon' => 'chart-line', 'accent' => 'bg-amber-500/12 text-amber-600 dark:text-amber-400'],
                ['label' => 'تغییر نسبت به دیروز', 'value' => ($changePercent >= 0 ? '+' : '').number_format($changePercent, 1).'٪', 'icon' => $changePercent >= 0 ? 'trending-up' : 'trending-down', 'accent' => $changePercent >= 0 ? 'bg-emerald-500/12 text-emerald-600 dark:text-emerald-400' : 'bg-red-500/12 text-red-600 dark:text-red-400'],
            ] as $card)
                <div class="rounded-2xl border border-line p-4 dark:border-night-line">
                    <span class="flex size-9 items-center justify-center rounded-xl {{ $card['accent'] }}">
                        <x-admin-panel::icon name="{{ $card['icon'] }}" class="size-4.5" />
                    </span>
                    <p class="fa-digits mt-3 text-lg font-extrabold text-ink dark:text-night-ink">{{ $card['value'] }}</p>
                    <p class="mt-0.5 text-[11px] font-bold text-ink-3 dark:text-night-ink-2">{{ $card['label'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- نمودار ۷ روز اخیر --}}
        <div class="mt-4" x-data x-init="if (window.ApECharts) { var el = $refs.chart; var c = ApECharts.init(el); c.setOption({
            grid: { top: 10, right: 10, bottom: 24, left: 44 },
            xAxis: { type: 'category', data: @json(collect($dailyVisits)->pluck('label')), axisLabel: { fontSize: 10 } },
            yAxis: { type: 'value', axisLabel: { fontSize: 10 } },
            tooltip: { trigger: 'axis' },
            series: [{ type: 'line', smooth: true, data: @json(collect($dailyVisits)->pluck('value')), areaStyle: { opacity: 0.12 }, lineStyle: { width: 3 }, itemStyle: { color: '#10b981' } }],
        }); var ro = function () { c.resize(); }; window.addEventListener('resize', ro); }">
            <div x-ref="chart" class="h-56 w-full" dir="ltr" aria-hidden="true"></div>
        </div>
    </div>

    {{-- ============ مدیریت موجودی انبار (پل به مرکز موجودی) ============ --}}
    <div class="ap-card p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="warehouse" class="size-5" />
                </span>
                <div>
                    <h2 class="text-sm font-extrabold text-ink dark:text-night-ink">مدیریت موجودی انبار</h2>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">وضعیت تنوع‌های محصول شما</p>
                </div>
            </div>
            @if (\App\Support\MultiVendorSettings::inventoryCenterEnabled())
                <a href="{{ route('seller.inventory.index') }}" wire:navigate class="ap-btn-outline !py-2 !text-xs">مرکز موجودی</a>
            @endif
        </div>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-line p-4 dark:border-night-line">
                <p class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2">تعداد کل تنوع‌ها</p>
                <p class="fa-digits mt-1 text-xl font-extrabold text-ink dark:text-night-ink">{{ number_format($variantsCount) }}</p>
            </div>
            <div class="rounded-2xl border border-line p-4 dark:border-night-line">
                <p class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2">محصولات من</p>
                <p class="fa-digits mt-1 text-xl font-extrabold text-ink dark:text-night-ink">{{ number_format($productsCount) }}</p>
            </div>
            <div class="rounded-2xl border border-line p-4 dark:border-night-line">
                <p class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2">بدون رقیب (تنها فروشنده)</p>
                <p class="fa-digits mt-1 text-xl font-extrabold text-ink dark:text-night-ink">{{ number_format($noCompetitorCount) }}</p>
            </div>
        </div>
    </div>

    {{-- ============ ارزش سفارش‌های موفق (مبنای واحد: order_items.seller_id — فاز ۱۰۶ الف) ============ --}}
    <div class="ap-card flex flex-wrap items-center justify-between gap-4 p-5 sm:p-6">
        <div class="flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/25">
                <x-admin-panel::icon name="hand-coins" class="size-5.5" />
            </span>
            <div>
                <p class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2">ارزش سفارشات موفق</p>
                <p class="fa-digits text-lg font-extrabold text-ink dark:text-night-ink">{{ number_format($paidOrdersValue) }} تومان</p>
            </div>
        </div>
        <a href="{{ route('seller.orders.index') }}" wire:navigate class="ap-btn-outline !py-2 !text-xs">
            مشاهده سفارش‌ها
            <x-admin-panel::icon name="chevron-left" class="size-3.5" />
        </a>
    </div>
</div>
