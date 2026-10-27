{{-- سیگنال‌های تقاضا (فاز ۱۰۸) — پورت sellers-old/panel/demand/index.blade.php
     ۴ کارت آمار + جدول تقاضا به‌ازای هر محصول + خوراک آخرین سیگنال‌ها (موبایل ماسک‌شده) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- ۴ کارت آمار --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['label' => 'مشتریان منتظر موجودی', 'value' => number_format($stats['waiting_stock']), 'icon' => 'package-plus', 'accent' => 'bg-brand-500/12 text-brand-600 dark:text-brand-400'],
            ['label' => 'منتظر کاهش قیمت', 'value' => number_format($stats['waiting_price']), 'icon' => 'badge-percent', 'accent' => 'bg-amber-500/12 text-amber-600 dark:text-amber-400'],
            ['label' => 'محصولات دارای تقاضا', 'value' => number_format($stats['products_with_demand']), 'icon' => 'boxes', 'accent' => 'bg-violet-500/12 text-violet-600 dark:text-violet-400'],
            ['label' => 'سیگنال‌های ۷ روز اخیر', 'value' => number_format($stats['recent_7days']), 'icon' => 'activity', 'accent' => 'bg-sky-500/12 text-sky-600 dark:text-sky-400'],
        ] as $card)
            <div class="ap-card p-4">
                <span class="flex size-9 items-center justify-center rounded-xl {{ $card['accent'] }}">
                    <x-admin-panel::icon name="{{ $card['icon'] }}" class="size-4.5" />
                </span>
                <p class="fa-digits mt-3 text-xl font-extrabold text-ink dark:text-night-ink">{{ $card['value'] }}</p>
                <p class="mt-0.5 text-[11px] font-bold text-ink-3 dark:text-night-ink-2">{{ $card['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        {{-- جدول تقاضا به‌ازای هر محصول --}}
        <div class="ap-card p-5 xl:col-span-2">
            <h2 class="text-sm font-extrabold text-ink dark:text-night-ink">تقاضا به‌ازای هر محصول</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[560px] text-[12px]">
                    <thead class="bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                        <tr>
                            <th class="rounded-s-xl p-3 text-start font-bold">محصول</th>
                            <th class="p-3 text-start font-bold">منتظر موجودی</th>
                            <th class="p-3 text-start font-bold">منتظر کاهش قیمت</th>
                            <th class="p-3 text-start font-bold">موجودی فعلی</th>
                            <th class="rounded-e-xl p-3 text-start font-bold">آخرین سیگنال</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr class="border-t border-line dark:border-night-line">
                                <td class="max-w-44 p-3">
                                    <p class="truncate font-bold text-ink dark:text-night-ink">{{ $product->title }}</p>
                                    <span class="mt-0.5 inline-block rounded-lg px-1.5 py-0.5 text-[10px] font-bold
                                                {{ $product->in_stock ? 'bg-emerald-500/12 text-emerald-600 dark:text-emerald-400' : 'bg-red-500/12 text-red-600 dark:text-red-400' }}">
                                        {{ $product->in_stock ? 'موجود' : 'ناموجود' }}
                                    </span>
                                </td>
                                <td class="fa-digits p-3 font-extrabold text-ink dark:text-night-ink">{{ number_format($product->demand_stock) }}</td>
                                <td class="fa-digits p-3 font-extrabold text-ink dark:text-night-ink">{{ number_format($product->demand_alerts) }}</td>
                                <td class="fa-digits p-3 font-bold text-ink-2 dark:text-night-ink-2">{{ number_format($product->in_stock ? 1 : 0) }}</td>
                                <td class="p-3 font-semibold text-ink-3 dark:text-night-ink-2">
                                    {{ $product->demand_last_at ? jdate($product->demand_last_at)->format('%Y/%m/%d') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-line dark:border-night-line">
                                <td colspan="5" class="p-8 text-center">
                                    <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                                        <x-admin-panel::icon name="bell-off" class="size-6" />
                                    </span>
                                    <p class="mt-2 text-[13px] font-bold text-ink-2 dark:text-night-ink-2">هنوز سیگنال تقاضایی ثبت نشده است</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $products->onEachSide(1)->links() }}</div>
        </div>

        {{-- خوراک آخرین سیگنال‌ها --}}
        <div class="ap-card p-5">
            <h2 class="text-sm font-extrabold text-ink dark:text-night-ink">آخرین سیگنال‌ها</h2>
            <ul class="mt-3 max-h-[28rem] space-y-2.5 overflow-y-auto sp-scroll">
                @forelse ($recent as $item)
                    <li class="rounded-xl border border-line p-3 dark:border-night-line">
                        <div class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-1.5 rounded-lg px-2 py-0.5 text-[10px] font-bold
                                        {{ $item->type === 'stock' ? 'bg-brand-500/12 text-brand-600 dark:text-brand-400' : 'bg-amber-500/12 text-amber-600 dark:text-amber-400' }}">
                                <x-admin-panel::icon name="{{ $item->type === 'stock' ? 'package-plus' : 'badge-percent' }}" class="size-3" />
                                {{ $item->type === 'stock' ? 'انتظار موجودی' : 'انتظار کاهش قیمت' }}
                            </span>
                            @if (! $item->seen)
                                <span class="rounded-lg bg-red-500/12 px-1.5 py-0.5 text-[10px] font-bold text-red-600 dark:text-red-400">جدید</span>
                            @endif
                        </div>
                        <p class="mt-2 truncate text-[12px] font-bold text-ink dark:text-night-ink">{{ $item->product?->title ?? '—' }}</p>
                        <p class="mt-1 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
                            {{ $item->name ?: 'مشتری مهمان' }} — <span class="fa-digits" dir="ltr">{{ $item->mobile }}</span>
                            @if ($item->target_price)
                                — هدف: {{ number_format($item->target_price) }} تومان
                            @endif
                        </p>
                        <p class="mt-1 text-[10px] font-semibold text-ink-3/80 dark:text-night-ink-2/80">{{ jdate($item->created_at)->format('%Y/%m/%d H:i') }}</p>
                    </li>
                @empty
                    <li class="rounded-xl border border-line p-6 text-center text-[12px] font-bold text-ink-3 dark:border-night-line dark:text-night-ink-2">
                        سیگنالی وجود ندارد
                    </li>
                @endforelse
            </ul>
            <p class="mt-3 rounded-xl bg-surface-2 p-2.5 text-center text-[10px] font-semibold text-ink-3 dark:bg-night-3 dark:text-night-ink-2">
                شمارهٔ موبایل مشتریان به دلایل حریم خصوصی ماسک شده است
            </p>
        </div>
    </div>
</div>
