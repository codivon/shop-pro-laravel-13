{{-- لیست راهنمای سایز — پنل جدید (فاز ۵) | پورت back/sizetypes/index + create --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                <x-admin-panel::icon name="ruler" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-extrabold">راهنمای سایز</h2>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">مدیریت سایزبندی محصولات (نوع سایز و مقادیر)</p>
            </div>
        </div>
        <button type="button" wire:click="$set('showCreate', {{ $showCreate ? 'false' : 'true' }})"
                class="ap-btn-primary !py-2">
            <x-admin-panel::icon name="plus" class="size-4" />
            افزودن راهنمای سایز
        </button>
    </div>

    {{-- کارت ایجاد سریع (پورت create/store قدیمی) --}}
    @if (auth('adminPanel')->user()?->can('products.sizetypes'))
        <div class="ap-card overflow-hidden" x-data="{ open: @entangle('showCreate').live }">
            <button type="button" class="flex w-full items-center justify-between p-4 text-start sm:p-5"
                    x-on:click="open = !open">
                <span class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="plus" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    ایجاد راهنمای سایز جدید
                </span>
                <span class="ap-chevron" :class="open ? 'is-open' : ''">
                    <x-admin-panel::icon name="chevron-left" class="size-4 text-ink-3" />
                </span>
            </button>

            <div x-show="open" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 class="border-t border-line p-4 dark:border-night-line sm:p-5">
                <form wire:submit="store" class="space-y-5">
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="st-title">عنوان</label>
                            <input id="st-title" type="text" wire:model="title" class="ap-input" placeholder="مثلا پیراهن">
                            @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="st-description">توضیحات (اختیاری)</label>
                            <textarea id="st-description" rows="2" wire:model="description" class="ap-input" placeholder="توضیحات راهنمای سایز…"></textarea>
                            @error('description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- ریپیتر مشخصه‌های سایز --}}
                    <div class="space-y-2.5 rounded-2xl border border-line p-4 dark:border-night-line">
                        <p class="text-[13px] font-bold">مشخصه‌های سایز (مانند: قد، دور سینه…)</p>
                        @error('newSizes') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        <div class="space-y-2">
                            @foreach ($newSizes as $i => $row)
                                <div class="flex items-center gap-2" wire:key="new-size-{{ $i }}">
                                    <input type="text" wire:model="newSizes.{{ $i }}" class="ap-input flex-1" placeholder="مثال: قد">
                                    <button type="button" wire:click="removeSizeRow({{ $i }})"
                                            class="rounded-lg p-2 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف مشخصه" aria-label="حذف مشخصه">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                                @error("newSizes.{$i}") <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            @endforeach
                        </div>
                        <button type="button" wire:click="addSizeRow" class="ap-btn-outline !py-1.5 text-[13px]">
                            <x-admin-panel::icon name="plus" class="size-4" />
                            افزودن مشخصه سایز
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="ap-btn-primary !py-2.5" wire:loading.attr="disabled">
                            ذخیره راهنمای سایز
                        </button>
                        <button type="button" x-on:click="open=false" class="ap-btn-ghost !py-2.5">انصراف</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-16 px-3 py-3 text-start font-bold">#</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">توضیحات</th>
                        <th class="px-3 py-3 text-center font-bold">تعداد سایزها</th>
                        <th class="w-40 px-3 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->sizetypes as $sizetype)
                        <tr wire:key="sizetype-{{ $sizetype->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($sizetype->id) }}</td>
                            <td class="px-3 py-3 font-semibold">{{ $sizetype->title }}</td>
                            <td class="max-w-64 px-3 py-3">
                                <p class="truncate text-ink-2 dark:text-night-ink-2">{{ $sizetype->description ?: '—' }}</p>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="fa-digits inline-flex rounded-lg bg-surface-2 px-2.5 py-1 font-bold dark:bg-night-3">
                                    {{ number_format($sizetype->sizes_count) }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-center gap-0.5">
                                    <a href="{{ route('np.sizetypes.values', ['sizetype' => $sizetype]) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="نمایش" aria-label="نمایش">
                                        <x-admin-panel::icon name="eye" class="size-4" />
                                    </a>
                                    <a href="{{ route('np.sizetypes.edit', ['sizetype' => $sizetype]) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="ویرایش" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </a>
                                    <a href="{{ route('np.sizetypes.values', ['sizetype' => $sizetype]) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="مقادیر سایز" aria-label="مقادیر">
                                        <x-admin-panel::icon name="sliders-horizontal" class="size-4" />
                                    </a>
                                    <button type="button" wire:click="confirmDelete({{ $sizetype->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="ruler" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">راهنمای سایزی ثبت نشده است</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->sizetypes->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->sizetypes->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->sizetypes->total()) }}</span> مورد
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->sizetypes->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->sizetypes->onEachSide(1)->toArray()['links'] as $link)
                        @if ($link['label'] === '...')
                            <span class="px-2 text-xs text-ink-3">…</span>
                        @elseif (! is_numeric(trim($link['label'])))
                        @elseif ($link['active'])
                            <span class="fa-digits flex size-8 items-center justify-center rounded-lg bg-brand-600 text-[13px] font-bold text-white">{{ $link['label'] }}</span>
                        @else
                            <button wire:click="gotoPage({{ trim($link['label']) }})"
                                    class="fa-digits flex size-8 items-center justify-center rounded-lg text-[13px] font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3">
                                {{ $link['label'] }}
                            </button>
                        @endif
                    @endforeach

                    @if ($this->sizetypes->hasMorePages())
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

    {{-- مودال تایید حذف --}}
    <div x-data="{ open: @entangle('confirmingDelete').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="trash-2" class="size-6" />
            </div>
            <h3 class="text-base font-bold">حذف راهنمای سایز</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف راهنمای سایز، سایزها و مقادیر مرتبط با آن از بین می‌رود و دیگر قادر به بازیابی آن نخواهید بود.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="store, delete"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
