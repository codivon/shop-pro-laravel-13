{{-- نرخ کمیسیون (فاز ۱۰۸) — پورت WalletSellerController@showCommissionRates.
     کنترلر قدیمی عیناً همان صفحهٔ کمیسیون را سرو می‌کرد (DRY)؛ اینجا هم همان
     جدول با سربرگ «نرخ کمیسیون» رندر می‌شود. --}}
<div>
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4">
        <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
            <x-admin-panel::icon name="badge-percent" class="size-5 text-brand-600 dark:text-brand-400" />
            راهنمای کمیسیون
        </h1>
        <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">نرخ کمیسیون مؤثر هر دسته‌بندی — کمیسیونِ مشخص‌شدهٔ خود دسته، در غیر این‌صورت ارث‌بری از والد، در غیر این‌صورت پیش‌فرض صفر.</p>
    </div>

    {{-- ================= خلاصهٔ کمیسیون ================= --}}
    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="ap-card flex items-center gap-4 p-5">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="percent" class="size-5" />
            </span>
            <div>
                <p class="text-xs font-semibold text-ink-500 dark:text-night-ink-2">حداکثر کمیسیون</p>
                <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ number_format($summary['max']) }}٪</p>
            </div>
        </div>
        <div class="ap-card flex items-center gap-4 p-5">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                <x-admin-panel::icon name="chart-line" class="size-5" />
            </span>
            <div>
                <p class="text-xs font-semibold text-ink-500 dark:text-night-ink-2">میانگین کمیسیون</p>
                <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ number_format($summary['avg']) }}٪</p>
            </div>
        </div>
        <div class="ap-card flex items-center gap-4 p-5">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                <x-admin-panel::icon name="tags" class="size-5" />
            </span>
            <div>
                <p class="text-xs font-semibold text-ink-500 dark:text-night-ink-2">تعداد دسته‌بندی‌ها</p>
                <p class="fa-digits mt-0.5 text-xl font-extrabold">{{ number_format($summary['count']) }}</p>
            </div>
        </div>
    </div>

    {{-- ================= جدول درختی کمیسیون ================= --}}
    <section class="ap-card overflow-hidden">
        <header class="border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="text-sm font-bold">جدول کمیسیون دسته‌بندی‌ها</h2>
        </header>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-right text-xs">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                        <th class="px-3 py-3 font-bold">#</th>
                        <th class="px-3 py-3 font-bold">نام دسته‌بندی</th>
                        <th class="px-3 py-3 font-bold">درصد کمیسیون</th>
                        <th class="px-3 py-3 font-bold">نوع کمیسیون</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-b border-line/70 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/70 dark:hover:bg-night-2">
                            <td class="fa-digits whitespace-nowrap px-3 py-3 text-ink-500 dark:text-night-ink-2">{{ $row['index'] }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center gap-1.5" style="padding-inline-start: {{ $row['level'] * 22 }}px;">
                                    @if ($row['level'] > 0)
                                        <x-admin-panel::icon name="chevron-left" class="size-3.5 shrink-0 text-amber-500" />
                                    @endif
                                    <span class="font-semibold {{ $row['level'] === 0 ? 'text-ink-900 dark:text-night-ink-1' : 'text-ink-700 dark:text-night-ink-2' }}">{{ $row['name'] }}</span>
                                    @if ($row['commission_type'] === 'inherited')
                                        <span class="text-ink-400 dark:text-night-ink-3" title="از دسته والد به ارث رسیده">
                                            <x-admin-panel::icon name="chevron-up" class="size-3" />
                                        </span>
                                    @endif
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                @if ($row['commission'] > 0)
                                    <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                        <span class="fa-digits">{{ $row['commission'] }}٪</span>
                                    </span>
                                @elseif ($row['commission'] == 0 && $row['commission_type'] == 'explicit_zero')
                                    <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">۰٪ (بدون کمیسیون)</span>
                                @elseif ($row['commission'] == 0 && $row['commission_type'] == 'default_zero')
                                    <span class="ap-badge bg-surface-3 text-ink-600 dark:bg-night-3 dark:text-night-ink-2">۰٪ (تعریف نشده)</span>
                                @else
                                    <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                        <span class="fa-digits">{{ $row['commission'] }}٪</span>
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @switch($row['commission_type'])
                                    @case('explicit')
                                        <span class="inline-flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">
                                            <x-admin-panel::icon name="circle-check" class="size-3.5" /> کمیسیون مستقیم
                                        </span>
                                        @break
                                    @case('explicit_zero')
                                        <span class="inline-flex items-center gap-1 font-semibold text-red-500">
                                            <x-admin-panel::icon name="circle-off" class="size-3.5" /> صفر عمدی
                                        </span>
                                        @break
                                    @case('inherited')
                                        <span class="inline-flex items-center gap-1 font-semibold text-amber-600 dark:text-amber-400">
                                            <x-admin-panel::icon name="share-2" class="size-3.5" /> ارث بری شده
                                        </span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center gap-1 font-semibold text-ink-500 dark:text-night-ink-2">
                                            <x-admin-panel::icon name="circle-alert" class="size-3.5" /> پیش‌فرض صفر
                                        </span>
                                @endswitch
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($row['commission'] > 0)
                                    <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">فعال</span>
                                @elseif ($row['commission'] == 0 && $row['commission_type'] == 'explicit_zero')
                                    <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">معاف از کمیسیون</span>
                                @else
                                    <span class="ap-badge bg-surface-3 text-ink-600 dark:bg-night-3 dark:text-night-ink-2">بدون کمیسیون</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-ink-500 dark:text-night-ink-2">
                                <x-admin-panel::icon name="inbox" class="mx-auto mb-2 size-8 text-ink-300 dark:text-night-ink-3" />
                                دسته‌بندی‌ای برای نمایش وجود ندارد!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- ================= راهنمای علائم ================= --}}
    <div class="ap-card mt-4 p-4 sm:p-5">
        <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
            <x-admin-panel::icon name="info" class="size-4 text-brand-600 dark:text-brand-400" />
            راهنمای علائم:
        </h3>
        <ul class="grid grid-cols-1 gap-2 text-xs text-ink-600 sm:grid-cols-2 dark:text-night-ink-2">
            <li class="flex items-center gap-2">
                <span class="size-2.5 shrink-0 rounded-full bg-emerald-500"></span>
                کمیسیون مستقیم - مقدار تعیین شده برای این دسته
            </li>
            <li class="flex items-center gap-2">
                <span class="size-2.5 shrink-0 rounded-full bg-amber-500"></span>
                کمیسیون ارثی - از دسته والد به ارث برده شده
            </li>
            <li class="flex items-center gap-2">
                <span class="size-2.5 shrink-0 rounded-full bg-ink-300 dark:bg-night-ink-3"></span>
                پیش‌فرض صفر - هیچ کمیسیونی تعیین نشده
            </li>
            <li class="flex items-center gap-2">
                <span class="size-2.5 shrink-0 rounded-full bg-red-500"></span>
                صفر عمدی - مدیر این دسته را بدون کمیسیون قرار داده
            </li>
        </ul>
    </div>
</div>
