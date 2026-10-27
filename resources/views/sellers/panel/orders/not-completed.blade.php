<div>
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
                <x-admin-panel::icon name="package" class="size-5 text-brand-600 dark:text-brand-400" />
                محصولات منتظر ارسال
            </h1>
            <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">اینجا می‌توانید تمام محصولاتی که در انتظار ارسال هستند را ببینید.</p>
        </div>
        <a href="{{ $indexUrl }}" wire:navigate
           class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به سفارش‌ها
        </a>
    </div>

    {{-- ================= جدول تنوع‌های منتظر ارسال ================= --}}
    <section class="ap-card overflow-hidden">
        <header class="border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="text-sm font-bold">محصولات منتظر ارسال</h2>
        </header>

        @if($prices->count())
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-right text-xs">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-ink-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                            <th class="px-3 py-3 text-center font-bold"></th>
                            <th class="px-3 py-3 text-center font-bold"></th>
                            <th class="px-3 py-3 font-bold">عنوان</th>
                            <th class="px-3 py-3 text-center font-bold">تعداد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prices as $price)
                            <tr class="border-b border-line/70 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/70 dark:hover:bg-night-2">
                                <td class="px-3 py-3 text-center text-ink-500 dark:text-night-ink-2">
                                    <span class="fa-digits">{{ ($prices->currentPage() - 1) * $prices->perPage() + $loop->iteration }}</span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <img src="{{ $price->product->imageUrl() }}" alt="{{ $price->product->title }}"
                                         class="mx-auto size-12 rounded-lg border border-line object-cover dark:border-night-line">
                                </td>
                                <td class="px-3 py-3">
                                    <a href="{{ $price->product->link() }}" target="_blank"
                                       class="font-bold text-brand-700 hover:underline dark:text-brand-400">
                                        {{ $price->product->title }}
                                    </a>
                                    <p class="mt-1 text-[11px] text-ink-500 dark:text-night-ink-2">
                                        {{ $price->getAttributesName() }}
                                    </p>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                        <span class="fa-digits font-bold">{{ $price->pendingToSend() }}</span>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-4 py-12 text-center text-sm text-ink-500 dark:text-night-ink-2">
                <x-admin-panel::icon name="inbox" class="mx-auto mb-2 size-8 text-ink-300 dark:text-night-ink-3" />
                چیزی برای نمایش وجود ندارد!
            </div>
        @endif

        <footer class="border-t border-line px-4 py-3 dark:border-night-line">
            {{ $prices->links() }}
        </footer>
    </section>
</div>
