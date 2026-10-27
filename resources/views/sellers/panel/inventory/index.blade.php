{{-- مرکز موجودی فروشنده (فاز ۱۰۸) — پورت sellers-old/panel/inventory/index.blade.php
     ۴ کارت آمار + جدول وضعیت موجودی تنوع‌ها (بحرانی‌ترها اول) + تاریخچهٔ حرکت انبار — فقط-خواندنی --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- ۴ کارت آمار --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['label' => 'کل تنوع‌های قیمت', 'value' => number_format($stats['total_variants']), 'icon' => 'layers', 'accent' => 'bg-violet-500/12 text-violet-600 dark:text-violet-400'],
            ['label' => 'ناموجود', 'value' => number_format($stats['out_of_stock']), 'icon' => 'package-x', 'accent' => 'bg-red-500/12 text-red-600 dark:text-red-400'],
            ['label' => 'کم‌موجود (۱ تا ۲ عدد)', 'value' => number_format($stats['low_stock']), 'icon' => 'package-minus', 'accent' => 'bg-amber-500/12 text-amber-600 dark:text-amber-400'],
            ['label' => 'حرکت انبار ۳۰ روز اخیر', 'value' => number_format($stats['recent_movements']), 'icon' => 'arrow-left-right', 'accent' => 'bg-brand-500/12 text-brand-600 dark:text-brand-400'],
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

    {{-- جدول وضعیت موجودی — بحرانی‌ترها اول --}}
    <div class="ap-card p-5">
        <h2 class="text-sm font-extrabold text-ink dark:text-night-ink">وضعیت موجودی تنوع‌ها</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full min-w-[640px] text-[12px]">
                <thead class="bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                    <tr>
                        <th class="rounded-s-xl p-3 text-start font-bold">محصول</th>
                        <th class="p-3 text-start font-bold">ویژگی‌های تنوع</th>
                        <th class="p-3 text-start font-bold">قیمت</th>
                        <th class="p-3 text-start font-bold">موجودی</th>
                        <th class="rounded-e-xl p-3 text-start font-bold">وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($prices as $price)
                        <tr class="border-t border-line dark:border-night-line">
                            <td class="max-w-40 p-3">
                                <p class="truncate font-bold text-ink dark:text-night-ink">{{ $price->product?->title ?? 'محصول حذف‌شده' }}</p>
                            </td>
                            <td class="max-w-48 p-3 font-semibold text-ink-2 dark:text-night-ink-2">
                                {{ $price->getAttributesName() ?: '—' }}
                            </td>
                            <td class="fa-digits p-3 font-extrabold text-ink dark:text-night-ink">{{ number_format($price->salePrice() ?? $price->price()) }} <small class="text-[10px] font-bold text-ink-3">تومان</small></td>
                            <td class="fa-digits p-3 font-extrabold text-ink dark:text-night-ink">{{ number_format($price->stock) }}</td>
                            <td class="p-3">
                                @if ($price->stock < 1)
                                    <span class="rounded-lg bg-red-500/12 px-2 py-0.5 text-[10px] font-bold text-red-600 dark:text-red-400">ناموجود</span>
                                @elseif ($price->stock < 3)
                                    <span class="rounded-lg bg-amber-500/12 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400">کم‌موجود</span>
                                @else
                                    <span class="rounded-lg bg-emerald-500/12 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">موجود</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="border-t border-line dark:border-night-line">
                            <td colspan="5" class="p-8 text-center">
                                <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                                    <x-admin-panel::icon name="package-open" class="size-6" />
                                </span>
                                <p class="mt-2 text-[13px] font-bold text-ink-2 dark:text-night-ink-2">هنوز تنوعی برای محصولات شما ثبت نشده است</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $prices->onEachSide(1)->links() }}</div>
    </div>

    {{-- تاریخچهٔ حرکت انبار --}}
    <div class="ap-card p-5">
        <h2 class="text-sm font-extrabold text-ink dark:text-night-ink">آخرین حرکت‌های انبار</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full min-w-[560px] text-[12px]">
                <thead class="bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                    <tr>
                        <th class="rounded-s-xl p-3 text-start font-bold">نوع</th>
                        <th class="p-3 text-start font-bold">محصول</th>
                        <th class="p-3 text-start font-bold">تعداد</th>
                        <th class="p-3 text-start font-bold">موجودی پس از حرکت</th>
                        <th class="p-3 text-start font-bold">توضیح</th>
                        <th class="rounded-e-xl p-3 text-start font-bold">تاریخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr class="border-t border-line dark:border-night-line">
                            <td class="p-3">
                                <span class="rounded-lg bg-sky-500/12 px-2 py-0.5 text-[10px] font-bold text-sky-600 dark:text-sky-400">{{ $movement->type_label }}</span>
                            </td>
                            <td class="max-w-36 p-3 font-bold text-ink dark:text-night-ink">
                                @if ($movement->product)
                                    <a href="{{ route('front.products.show', ['product' => $movement->product->slug]) }}" target="_blank" rel="noopener" title="{{ $movement->product->title }}" class="transition-colors hover:text-brand-600 dark:hover:text-brand-400">
                                        {{ \Illuminate\Support\Str::limit($movement->product->title, 28) }}
                                    </a>
                                @else
                                    <span class="font-semibold text-ink-3 dark:text-night-ink-2">محصول حذف‌شده</span>
                                @endif
                            </td>
                            <td class="fa-digits p-3 font-extrabold text-ink dark:text-night-ink">{{ number_format($movement->quantity) }}</td>
                            <td class="fa-digits p-3 font-bold text-ink-2 dark:text-night-ink-2">{{ number_format($movement->stock_after) }}</td>
                            <td class="max-w-40 p-3 font-semibold text-ink-3 dark:text-night-ink-2">{{ \Illuminate\Support\Str::limit($movement->description ?? '—', 40) }}</td>
                            <td class="fa-digits p-3 font-semibold text-ink-3 dark:text-night-ink-2">{{ jdate($movement->created_at)->format('%Y/%m/%d H:i') }}</td>
                        </tr>
                    @empty
                        <tr class="border-t border-line dark:border-night-line">
                            <td colspan="6" class="p-6 text-center text-[12px] font-bold text-ink-3 dark:text-night-ink-2">حرکت انباری ثبت نشده است</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
