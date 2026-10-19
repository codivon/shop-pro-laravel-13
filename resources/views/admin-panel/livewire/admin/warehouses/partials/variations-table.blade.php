{{-- جدول تنوع‌های سایر انبارها — پورت partials/_variations_table قدیمی --}}
<table class="w-full text-start text-[13px]">
    <thead>
        <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
            <th class="px-3 py-3 text-start font-bold">انبار</th>
            <th class="px-3 py-3 text-start font-bold">ویژگی‌ها</th>
            <th class="px-3 py-3 text-center font-bold">قیمت</th>
            <th class="px-3 py-3 text-center font-bold">قیمت با تخفیف</th>
            <th class="px-3 py-3 text-center font-bold">موجودی</th>
            <th class="px-3 py-3 text-center font-bold">فروش</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($variations as $price)
            <tr wire:key="other-var-{{ $price->warehouse_id }}-{{ $price->id }}"
                class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                <td class="px-3 py-3">
                    <strong>{{ $price->warehouse_name ?? 'نامشخص' }}</strong>
                    <small class="fa-digits block text-ink-3 dark:text-night-ink-2">کد: {{ $price->warehouse_code ?? '-' }}</small>
                    @if (! empty($price->seller_name))
                        <small class="block text-ink-3 dark:text-night-ink-2">{{ $price->seller_name }}</small>
                    @endif
                </td>
                <td class="max-w-40 px-3 py-3">
                    @if ($price->attributes && $price->attributes->count())
                        <div class="flex flex-wrap gap-1">
                            @foreach ($price->attributes as $attr)
                                <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ $attr->name }}</span>
                            @endforeach
                        </div>
                    @else
                        <span class="text-xs text-ink-3 dark:text-night-ink-2">بدون ویژگی</span>
                    @endif
                </td>
                <td class="fa-digits px-3 py-3 text-center">{{ number_format($price->price) }} تومان</td>
                <td class="fa-digits px-3 py-3 text-center">
                    @if (($price->discount_price ?? 0) && $price->discount_price < $price->price)
                        <span class="text-emerald-600 dark:text-emerald-400">{{ number_format($price->discount_price) }} تومان</span>
                        <small class="block text-red-500">({{ $price->discount }}%)</small>
                    @else
                        <span class="text-ink-3 dark:text-night-ink-2">-</span>
                    @endif
                </td>
                <td class="px-3 py-3 text-center">
                    @if ($price->stock > 0)
                        <span class="fa-digits ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">{{ number_format($price->stock) }}</span>
                    @else
                        <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400">ناموجود</span>
                    @endif
                </td>
                <td class="fa-digits px-3 py-3 text-center">{{ number_format($price->sold_count ?? 0) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
