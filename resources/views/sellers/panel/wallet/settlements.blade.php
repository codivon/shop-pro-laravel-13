{{-- صورت‌حساب تسویه (فاز ۱۰۸) — پورت sellers-old/panel/wallet/settlements.blade.php
     ۴ کارت جمع + جدول ردیف‌به‌ردیف تسویه + دکمهٔ خروجی اکسل (روت قدیمی) --}}
<div>
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
                <x-admin-panel::icon name="file-text" class="size-5 text-brand-600 dark:text-brand-400" />
                صورت‌حساب تسویه
            </h1>
            <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">ردیف‌به‌ردیفِ کمیسیون و سهم هر سفارشِ تسویه‌شده — شفافیت کامل مالی فروش شما.</p>
        </div>
        <a href="{{ $exportUrl }}"
           class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
            <x-admin-panel::icon name="download" class="size-4" />
            خروجی اکسل
        </a>
    </div>

    {{-- ================= جمع‌های صورت‌حساب ================= --}}
    <div class="mb-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="ap-card p-4">
            <p class="mb-1 text-[11px] font-semibold text-ink-500 dark:text-night-ink-2">سفارش‌های تسویه‌شده</p>
            <p class="fa-digits text-xl font-extrabold text-ink-900 dark:text-night-ink-1">{{ number_format($stats['settled_orders']) }}</p>
        </div>
        <div class="ap-card p-4">
            <p class="mb-1 text-[11px] font-semibold text-ink-500 dark:text-night-ink-2">سهم واریزشده به کیف پول</p>
            <p class="fa-digits text-xl font-extrabold text-emerald-600 dark:text-emerald-400">
                {{ number_format($stats['total_net']) }} <small class="text-[10px] font-bold text-ink-400 dark:text-night-ink-2">تومان</small>
            </p>
        </div>
        <div class="ap-card p-4">
            <p class="mb-1 text-[11px] font-semibold text-ink-500 dark:text-night-ink-2">کمیسیون کسرشدهٔ سایت</p>
            <p class="fa-digits text-xl font-extrabold text-ink-900 dark:text-night-ink-1">
                {{ number_format($stats['total_commission']) }} <small class="text-[10px] font-bold text-ink-400 dark:text-night-ink-2">تومان</small>
            </p>
        </div>
        <div class="ap-card p-4">
            <p class="mb-1 text-[11px] font-semibold text-ink-500 dark:text-night-ink-2">برداشت در انتظار پرداخت</p>
            <p class="fa-digits text-xl font-extrabold text-amber-600 dark:text-amber-400">
                {{ number_format($stats['waiting_withdraw_amount']) }} <small class="text-[10px] font-bold text-ink-400 dark:text-night-ink-2">تومان</small>
            </p>
        </div>
    </div>

    {{-- ================= جدول ردیف‌به‌ردیف ================= --}}
    <section class="ap-card overflow-hidden">
        <header class="border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="text-sm font-bold">ریز تسویهٔ سفارش‌ها</h2>
        </header>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-right text-xs">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                        <th class="px-3 py-3 font-bold">#</th>
                        <th class="px-3 py-3 font-bold">سفارش</th>
                        <th class="px-3 py-3 font-bold">تاریخ</th>
                        <th class="px-3 py-3 font-bold">مبلغ سفارش</th>
                        <th class="px-3 py-3 font-bold">درصد کمیسیون</th>
                        <th class="px-3 py-3 font-bold">کمیسیون سایت</th>
                        <th class="px-3 py-3 font-bold">سهم شما</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deposits as $deposit)
                        <tr class="border-b border-line/70 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/70 dark:hover:bg-night-2">
                            <td class="px-3 py-3 text-ink-500 dark:text-night-ink-2">
                                <span class="fa-digits">{{ ($deposits->currentPage() - 1) * $deposits->perPage() + $loop->iteration }}</span>
                            </td>
                            <td class="fa-digits px-3 py-3 font-bold text-brand-700 dark:text-brand-400">#{{ $deposit->order_id }}</td>
                            <td class="fa-digits px-3 py-3 text-ink-600 dark:text-night-ink-2">{{ $deposit->created_at ? jdate($deposit->created_at)->format('Y/m/d') : '-' }}</td>
                            <td class="fa-digits px-3 py-3">{{ number_format((int) ($deposit->order->price ?? 0)) }}</td>
                            <td class="fa-digits px-3 py-3">{{ is_null($deposit->percent) ? '-' : ((int) $deposit->percent.'٪') }}</td>
                            <td class="fa-digits px-3 py-3">{{ number_format((int) $deposit->amount) }}</td>
                            <td class="fa-digits px-3 py-3 font-bold">{{ number_format((int) ($netByOrder[$deposit->order_id] ?? 0)) }}</td>
                            <td class="px-3 py-3 text-center">
                                @if ($deposit->status === 'success')
                                    <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">تسویه‌شده</span>
                                @else
                                    <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">ناموفق</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-ink-500 dark:text-night-ink-2">
                                <x-admin-panel::icon name="inbox" class="mx-auto mb-2 size-8 text-ink-300 dark:text-night-ink-3" />
                                هنوز سفارشی تسویه نشده است — پس از اولین فروش، ریز تسویهٔ هر سفارش اینجا ثبت می‌شود.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="border-t border-line px-4 py-3 dark:border-night-line">
            {{ $deposits->links() }}
        </footer>
    </section>
</div>
