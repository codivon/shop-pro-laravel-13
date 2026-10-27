<div>
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
                <x-admin-panel::icon name="wallet" class="size-5 text-brand-600 dark:text-brand-400" />
                کیف پول
            </h1>
            <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">برای مدیریت کیف پول خود از این قسمت استفاده نمایید.</p>
        </div>
        @if($settlementsUrl)
            <a href="{{ $settlementsUrl }}" wire:navigate
               class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
                <x-admin-panel::icon name="file-check" class="size-4" />
                صورت‌حساب تسویه
            </a>
        @endif
    </div>

    {{-- ================= پیام‌های تراکنش (همان session های قدیمی) ================= --}}
    @if(session('message') == 'ok')
        <div class="mb-4 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
            <x-admin-panel::icon name="circle-check" class="mt-0.5 size-4 shrink-0" />
            <strong>افزایش موجودی با موفقیت انجام شد</strong>.
        </div>
    @elseif(session('transaction-error'))
        <div class="mb-4 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300">
            <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-4 shrink-0" />
            <strong>{{ session('transaction-error') }}</strong>.
        </div>
    @endif

    {{-- ================= کارت موجودی ================= --}}
    <section class="ap-card mb-4">
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <span class="text-xs font-semibold text-ink-500 dark:text-night-ink-2">موجودی کیف پول شما</span>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="fa-digits text-3xl font-extrabold text-ink-900 dark:text-night-ink-1">
                        {{ number_format($seller->getWallet()->balance()) }}
                    </span>
                    <span class="text-sm font-bold text-ink-500 dark:text-night-ink-2">ریال</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- دکمه‌های مودال‌ها --}}
                <button type="button" x-data x-on:click="$dispatch('open-wallet-deposit')"
                        class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-4 text-xs font-bold">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزایش اعتبار کیف پول
                </button>
                <button type="button" x-data x-on:click="$dispatch('open-wallet-withdraw')"
                        {{ $seller->getWallet()->balance() > 0 ? '' : 'disabled' }}
                        class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-4 text-xs font-bold disabled:cursor-not-allowed disabled:opacity-50">
                    <x-admin-panel::icon name="arrow-left-right" class="size-4" />
                    برداشت از کیف پول
                </button>
            </div>
        </div>
    </section>

    {{-- ================= تاریخچه کیف پول ================= --}}
    <section class="ap-card overflow-hidden">
        <header class="border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="text-sm font-bold">تاریخچه کیف پول</h2>
        </header>

        @if($histories->count())
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-right text-xs">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-ink-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                            <th class="px-3 py-3 font-bold">ردیف</th>
                            <th class="px-3 py-3 font-bold">مبلغ (تومان)</th>
                            <th class="px-3 py-3 font-bold">نوع تراکنش</th>
                            <th class="px-3 py-3 font-bold">تاریخ</th>
                            <th class="px-3 py-3 text-center font-bold">وضعیت</th>
                            <th class="px-3 py-3 text-center font-bold">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($histories as $history)
                            @php $is_deposit = $history->type == 'deposit'; @endphp
                            <tr class="border-b border-line/70 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/70 dark:hover:bg-night-2">
                                <td class="px-3 py-3 text-ink-500 dark:text-night-ink-2">
                                    <span class="fa-digits">{{ ($histories->currentPage() - 1) * $histories->perPage() + $loop->iteration }}</span>
                                </td>
                                <td class="fa-digits px-3 py-3 font-bold {{ $is_deposit ? 'text-emerald-600' : 'text-red-500' }}">
                                    {{ number_format($history->amount) }}
                                </td>
                                <td class="px-3 py-3">
                                    @if($history->withdraw)
                                        <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">برداشت وجه</span>
                                    @elseif($is_deposit)
                                        <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">افزایش اعتبار</span>
                                    @else
                                        <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">کاهش اعتبار</span>
                                    @endif
                                </td>
                                <td class="fa-digits px-3 py-3 text-ink-600 dark:text-night-ink-2">{{ jdate($history->created_at) }}</td>
                                <td class="px-3 py-3 text-center">
                                    @if($history->status == 'success')
                                        <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">موفق</span>
                                        @if($history->withdraw)
                                            @if($history->status_pay == 'waiting')
                                                <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">در انتظار پرداخت</span>
                                            @elseif($history->status_pay == 'pay')
                                                <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">پرداخت شده</span>
                                            @elseif($history->status_pay == 'unpay')
                                                <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">پرداخت نشده</span>
                                            @elseif($history->status_pay == 'unpay-refund')
                                                <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">پرداخت نشده و مبلغ عودت داده شده</span>
                                            @endif
                                        @endif
                                    @else
                                        <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">ناموفق</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <a href="{{ route('seller.wallet.show', ['wallet' => $history]) }}" wire:navigate
                                       class="ap-btn-outline inline-flex min-h-11 items-center rounded-lg px-2.5 text-[11px] font-bold">
                                        مشاهده
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-4 py-12 text-center text-sm text-ink-500 dark:text-night-ink-2">
                <x-admin-panel::icon name="inbox" class="mx-auto mb-2 size-8 text-ink-300 dark:text-night-ink-3" />
                شما تا به حال تراکنشی در کیف پول خود نداشته‌اید.
            </div>
        @endif

        <footer class="border-t border-line px-4 py-3 dark:border-night-line">
            {{ $histories->links() }}
        </footer>
    </section>

    {{-- ================= مودال شارژ کیف پول — فرم کلاسیک POST به کنترلر قدیمی ================= --}}
    <div x-data="{ open: false }" x-on:open-wallet-deposit.window="open = true"
         x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="open = false" x-transition.opacity></div>
        <div class="ap-card relative z-10 w-full max-w-md" x-show="open" x-transition.scale.origin.center>
            <header class="flex items-center justify-between border-b border-line px-4 py-3 dark:border-night-line">
                <h3 class="text-sm font-bold">افزایش موجودی کیف پول</h3>
                <button type="button" x-on:click="open = false" class="min-h-11 rounded p-1 text-ink-500 hover:text-red-500" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </header>
            {{-- submit عادی — کنترلر قدیمی به درگاه پرداخت ریدایرکت می‌کند --}}
            <form action="{{ $storeUrl }}" method="POST" class="space-y-3 p-4">
                @csrf
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">مبلغ ( تومان)<span class="text-red-500">*</span></span>
                    <input type="number" name="amount" required min="10000" placeholder="مثال : 10,000" class="ap-input w-full fa-digits">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">انتخاب درگاه پرداخت<span class="text-red-500">*</span></span>
                    <select name="gateway" class="ap-input w-full">
                        <option value="">درگاه مورد نظر خود را انتخاب کنید</option>
                        @foreach($gateways as $gateway)
                            <option value="{{ $gateway->key }}">{{ $gateway->name }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit"
                        class="ap-btn-primary inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-lg text-xs font-bold">
                    <x-admin-panel::icon name="check" class="size-4" />
                    افزایش موجودی
                </button>
            </form>
        </div>
    </div>

    {{-- ================= مودال برداشت — فرم کلاسیک POST به کنترلر قدیمی ================= --}}
    <div x-data="{ open: false }" x-on:open-wallet-withdraw.window="open = true"
         x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="open = false" x-transition.opacity></div>
        <div class="ap-card relative z-10 w-full max-w-md" x-show="open" x-transition.scale.origin.center>
            <header class="flex items-center justify-between border-b border-line px-4 py-3 dark:border-night-line">
                <h3 class="text-sm font-bold">برداشت از کیف پول</h3>
                <button type="button" x-on:click="open = false" class="min-h-11 rounded p-1 text-ink-500 hover:text-red-500" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </header>
            {{-- submit عادی — کنترلر قدیمی با toast + redirect back پاسخ می‌دهد --}}
            <form action="{{ $withdrawUrl }}" method="POST" class="space-y-3 p-4">
                @csrf
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">مبلغ ( تومان)<span class="text-red-500">*</span></span>
                    <input type="number" name="amount" required min="10000" placeholder="مثال : 10,000" class="ap-input w-full fa-digits">
                </label>
                <p class="rounded-xl bg-surface-2 p-3 text-xs font-bold text-ink-700 dark:bg-night-2 dark:text-night-ink-1">
                    موجودی کیف پول: <span class="fa-digits">{{ number_format($seller->getWallet()->balance()) }}</span>
                </p>
                <button type="submit"
                        class="ap-btn-primary inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-lg text-xs font-bold">
                    <x-admin-panel::icon name="check" class="size-4" />
                    درخواست برداشت
                </button>
            </form>
        </div>
    </div>
</div>
