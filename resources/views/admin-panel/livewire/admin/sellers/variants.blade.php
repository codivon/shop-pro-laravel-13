{{-- تنوع‌های فروشنده — پنل جدید (فاز ۳) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                تعداد کل: <span class="fa-digits">{{ number_format($this->variants->total()) }}</span>
            </span>
            <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                فروشنده: {{ $seller->seller_info?->business_name ?? '#' . $seller->id }}
            </span>
        </div>
        <a href="{{ route('np.sellers.show', $seller) }}" wire:navigate class="ap-btn-outline !py-2">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            پرونده فروشنده
        </a>
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-16 px-3 py-3 text-start font-bold">ID</th>
                        <th class="w-16 px-3 py-3 text-start font-bold">تصویر</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان محصول</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ ایجاد</th>
                        <th class="px-3 py-3 text-center font-bold">تعداد موجودی</th>
                        <th class="px-3 py-3 text-start font-bold">وضعیت انتشار</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->variants as $variant)
                        @php $product = \App\Models\Product::find($variant->product_id); @endphp
                        <tr wire:key="sv-{{ $variant->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            @if ($product)
                                <td class="px-3 py-3 text-ink-3">#{{ number_format($product->id) }}</td>
                                <td class="px-3 py-3">
                                    <img src="{{ $product->image ? asset($product->image) : asset('/empty.svg') }}"
                                         alt="{{ $product->title }}" class="size-10 rounded-xl border border-line object-cover dark:border-night-line" loading="lazy">
                                </td>
                                <td class="max-w-56 px-3 py-3">
                                    <p class="truncate font-semibold">{{ $product->title }}</p>
                                </td>
                                <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($product->created_at) }}</td>
                                <td class="px-3 py-3 text-center">
                                    <span class="fa-digits inline-flex rounded-lg bg-surface-2 px-2.5 py-1 font-bold dark:bg-night-3">{{ number_format($product->prices()->sum('stock')) }}</span>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex flex-wrap items-center gap-1">
                                        @if ($product->isPublished())
                                            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">منتشر شده</span>
                                        @else
                                            <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">پیش‌نویس</span>
                                        @endif
                                        @if ($product->status === 'Accept')
                                            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">تایید شده</span>
                                        @elseif ($product->status === 'Waiting')
                                            <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">در انتظار</span>
                                        @elseif ($product->status === 'Reject')
                                            <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">رد شده</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex items-center gap-0.5">
                                        @if (\Illuminate\Support\Facades\Route::has('front.products.show'))
                                            <a href="{{ route('front.products.show', ['product' => $product]) }}" target="_blank" rel="noopener"
                                               class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                               title="نمایش" aria-label="نمایش">
                                                <x-admin-panel::icon name="external-link" class="size-4" />
                                            </a>
                                        @endif
                                        <a href="{{ route('np.products.edit', $product) }}" wire:navigate
                                           class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                           title="ویرایش" aria-label="ویرایش">
                                            <x-admin-panel::icon name="pencil" class="size-4" />
                                        </a>
                                    </div>
                                </td>
                            @else
                                <td class="px-3 py-3 text-ink-3" colspan="7">محصول مرتبط حذف شده است (تنوع #{{ number_format($variant->id) }})</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="layers" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">تنوعی یافت نشد</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی --}}
        <div class="border-t border-line px-4 py-3 dark:border-night-line">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-ink-3 dark:text-night-ink-2">
                    نمایش <span class="fa-digits">{{ number_format($this->variants->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->variants->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->variants->total()) }}</span> تنوع
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->variants->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->variants->onEachSide(1)->toArray()['links'] as $link)
                        @if ($link['label'] === '...')
                            <span class="px-2 text-xs text-ink-3">…</span>
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

                    @if ($this->variants->hasMorePages())
                        <button wire:click="nextPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه بعد">
                            <x-admin-panel::icon name="chevron-left" class="size-4" />
                        </button>
                    @else
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-left" class="size-4" /></span>
                    @endif
                </nav>
            </div>
        </div>
    </div>
</div>
