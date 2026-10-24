{{-- محصولات منتظر ارسال — پنل جدید (فاز ۲) | پورت back/orders/not-completed --}}
<div class="mx-auto max-w-6xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('np.orders.index') }}" wire:navigate
               class="rounded-xl p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3"
               aria-label="بازگشت به لیست سفارشات">
                <x-admin-panel::icon name="arrow-right" class="size-5" />
            </a>
            <div>
                <h2 class="text-base font-extrabold">محصولات منتظر ارسال</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">نسخه‌هایی از محصولات فیزیکی که در سفارش‌های در جریان باید ارسال شوند</p>
            </div>
        </div>
        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
            تعداد نسخه‌ها: <span class="fa-digits">{{ number_format($prices->total()) }}</span>
        </span>
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-2xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-14 px-3 py-3 text-start font-bold">#</th>
                        <th class="w-16 px-2 py-3 text-start font-bold">تصویر</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان محصول</th>
                        <th class="px-3 py-3 text-start font-bold">ویژگی‌ها</th>
                        <th class="w-28 px-3 py-3 text-center font-bold">تعداد منتظر ارسال</th>
                        <th class="w-16 px-3 py-3 text-center font-bold">مشاهده</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prices as $index => $price)
                        <tr wire:key="price-{{ $price->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits text-ink-3 dark:text-night-ink-2">
                                {{ $prices->firstItem() + $index }}
                            </td>
                            <td class="px-2 py-3">
                                <img src="{{ $price->product->imageUrl() }}" alt="{{ $price->product->title }}" width="44" height="44"
                                     class="size-11 rounded-lg border border-line object-cover dark:border-night-line" loading="lazy">
                            </td>
                            <td class="max-w-64 px-3 py-3">
                                <p class="truncate font-semibold">{{ $price->product->title }}</p>
                            </td>
                            <td class="max-w-56 px-3 py-3 text-[12px] text-ink-3 dark:text-night-ink-2">
                                {{ $price->getAttributesName() ?: '—' }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="ap-badge fa-digits bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                                    {{ number_format($price->pendingToSend()) }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <a href="{{ $price->product->link() }}" target="_blank" rel="noopener"
                                   class="inline-flex rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                   title="مشاهده در فروشگاه" aria-label="مشاهده در فروشگاه">
                                    <x-admin-panel::icon name="external-link" class="size-4" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="package-search" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">محصولی در انتظار ارسال نیست — آفرین!</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی --}}
        @if ($prices->hasPages())
            <div class="border-t border-line px-4 py-3 dark:border-night-line">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-ink-3 dark:text-night-ink-2">
                        نمایش <span class="fa-digits">{{ number_format($prices->firstItem() ?? 0) }}</span> تا
                        <span class="fa-digits">{{ number_format($prices->lastItem() ?? 0) }}</span> از
                        <span class="fa-digits">{{ number_format($prices->total()) }}</span>
                    </p>
                    <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                        @if ($prices->onFirstPage())
                            <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                        @else
                            <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                                <x-admin-panel::icon name="chevron-right" class="size-4" />
                            </button>
                        @endif

                        @foreach ($prices->onEachSide(1)->toArray()['links'] as $link)
                            @if ($link['label'] === '...')
                                <span class="px-2 text-xs text-ink-3 dark:text-night-ink-2">…</span>
                            @elseif (! is_numeric(trim($link['label'])))
                                {{-- دکمه‌های قبلی/بعدی بالاتر مدیریت شده‌اند --}}
                            @elseif ($link['active'])
                                <span class="fa-digits flex size-8 items-center justify-center rounded-lg bg-brand-600 text-[13px] font-bold text-white">{{ $link['label'] }}</span>
                            @else
                                <button wire:click="gotoPage({{ trim($link['label']) }})"
                                        class="fa-digits flex size-8 items-center justify-center rounded-lg text-[13px] font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3">
                                    {{ $link['label'] }}
                                </button>
                            @endif
                        @endforeach

                        @if ($prices->hasMorePages())
                            <button wire:click="nextPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه بعد">
                                <x-admin-panel::icon name="chevron-left" class="size-4" />
                            </button>
                        @else
                            <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-left" class="size-4" /></span>
                        @endif
                    </nav>
                </div>
            </div>
        @endif
    </div>

    {{-- لودینگ صفحه‌بندی --}}
    <div wire:loading wire:target="previousPage, nextPage, gotoPage"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
