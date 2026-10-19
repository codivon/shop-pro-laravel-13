{{-- لیست ارزها + کارت ایجاد + مودال ویرایش (بازمحاسبه قیمت‌ها) — پنل جدید (فاز ۷-ب۱) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                کل ارزها: <span class="fa-digits">{{ number_format($this->totalCount) }}</span>
            </span>
            <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">نرخ هر ارز به تومان</span>
        </div>
    </div>

    {{-- کارت ایجاد ارز (پورت create/store قدیمی) --}}
    <div class="ap-card overflow-hidden" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between p-4 text-start sm:p-5" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="plus" class="size-4.5 text-brand-600 dark:text-brand-400" />
                ارز جدید
            </span>
            <span class="ap-chevron" :class="open ? 'is-open' : ''">
                <x-admin-panel::icon name="chevron-left" class="size-4 text-ink-3" />
            </span>
        </button>

        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="border-t border-line p-4 sm:p-5 dark:border-night-line">
            <form wire:submit="createCurrency" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="currency-create-title">عنوان <span class="text-red-500">*</span></label>
                    <input id="currency-create-title" type="text" wire:model="createTitle" class="ap-input !py-2" placeholder="مثال: دلار آمریکا">
                    @error('createTitle') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="currency-create-amount">نرخ (تومان) <span class="text-red-500">*</span></label>
                    <input id="currency-create-amount" type="number" step="any" min="0" wire:model="createAmount" dir="ltr"
                           class="ap-input !py-2 text-start font-mono" placeholder="60000">
                    @error('createAmount') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end">
                    <button type="submit" wire:loading.attr="disabled" wire:target="createCurrency" class="ap-btn w-full !py-2">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        ایجاد ارز
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-14 px-3 py-3 text-start font-bold">ردیف</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">نرخ (تومان)</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ ثبت</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->currencies as $currency)
                        <tr wire:key="currency-{{ $currency->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits font-bold">{{ $loop->iteration }}</td>
                            <td class="px-3 py-3">
                                <p class="font-semibold">{{ $currency->title }}</p>
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap font-bold text-brand-700 dark:text-brand-400">
                                {{ number_format((float) $currency->amount()) }}
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap text-ink-2 dark:text-night-ink-2">
                                {{ $this->jdate($currency->created_at) }}
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <button type="button" wire:click="editCurrency({{ $currency->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="ویرایش ارز" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $currency->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف ارز" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="banknote" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">چیزی برای نمایش وجود ندارد!</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->currencies->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->currencies->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->currencies->total()) }}</span> ارز
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->currencies->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->currencies->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->currencies->hasMorePages())
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

    {{-- مودال ویرایش ارز (پورت edit/update — تغییر نرخ، قیمت‌های محصولات این ارز را بازمحاسبه می‌کند) --}}
    <div x-data="{ open: @entangle('editingId').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                <x-admin-panel::icon name="banknote" class="size-6" />
            </div>
            <h3 class="text-base font-bold">ویرایش ارز</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با تغییر نرخ این ارز، قیمت‌های محصولات وابسته به آن به‌صورت خودکار بازمحاسبه می‌شوند.
            </p>

            <form wire:submit="updateCurrency" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="currency-edit-title">عنوان <span class="text-red-500">*</span></label>
                    <input id="currency-edit-title" type="text" wire:model="editTitle" class="ap-input !py-2">
                    @error('editTitle') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="currency-edit-amount">نرخ (تومان) <span class="text-red-500">*</span></label>
                    <input id="currency-edit-amount" type="number" step="any" min="0" wire:model="editAmount" dir="ltr"
                           class="ap-input !py-2 text-start font-mono">
                    @error('editAmount') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2.5">
                    <button type="submit" wire:loading.attr="disabled" wire:target="updateCurrency" class="ap-btn flex-1">
                        <x-admin-panel::icon name="check" class="size-4" />
                        ویرایش ارز
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
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
            <h3 class="text-base font-bold">حذف ارز</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف ارز دیگر قادر به بازیابی آن نخواهید بود؛ ارز محصولات این واحد به «بدون ارز» تغییر می‌کند.
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
    <div wire:loading wire:target="createCurrency, updateCurrency, delete, previousPage, nextPage, gotoPage"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
