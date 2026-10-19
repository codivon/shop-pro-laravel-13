{{-- تعرفه‌های روش ارسال — پورت back/tariffs (فاز ۷-ب۲) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ + سلکت روش ارسال (پورت ?carrier= قدیمی) --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="truck" class="size-3.5" />
                روش ارسال: {{ $this->carrier->title }}
            </span>
            <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="tags" class="size-3.5" />
                تعداد تعرفه‌ها: <span class="fa-digits">{{ number_format($this->carrier->tariffs()->count()) }}</span>
            </span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <select wire:model.live="carrierId" class="ap-input !py-2 w-auto text-xs"
                    aria-label="انتخاب روش ارسال">
                @foreach ($this->carriersList as $c)
                    <option value="{{ $c->id }}" {{ $c->id == $this->carrier->id ? 'selected' : '' }}>{{ $c->title }}</option>
                @endforeach
            </select>
            <button type="button" wire:click="$set('showCreateModal', true)" class="ap-btn !py-2">
                <x-admin-panel::icon name="plus" class="size-4" />
                ایجاد تعرفه
            </button>
        </div>
    </div>

    {{-- جدول (پورت back/tariffs/index) --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-12 px-3 py-3 text-start font-bold">ردیف</th>
                        <th class="px-3 py-3 text-start font-bold">نوع منطقه ارسالی</th>
                        <th class="px-3 py-3 text-start font-bold">حداکثر وزن (گرم)</th>
                        <th class="px-3 py-3 text-start font-bold">هزینه ارسال (تومان)</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->rows as $tariff)
                        <tr wire:key="tariff-{{ $tariff->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits text-ink-3 dark:text-night-ink-2">{{ $loop->iteration }}</td>
                            <td class="px-3 py-3">
                                @if ($tariff->type === 'within_province')
                                    <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">درون استانی</span>
                                @else
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">برون استانی</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits font-semibold">{{ number_format($tariff->max_weight) }}</td>
                            <td class="px-3 py-3 fa-digits font-semibold">{{ number_format($tariff->shipping_cost) }}</td>
                            <td class="px-3 py-3 fa-digits text-ink-3 dark:text-night-ink-2">{{ self::jdate($tariff->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <button type="button" wire:click="editTariff({{ $tariff->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="ویرایش تعرفه" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $tariff->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف تعرفه" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="tags" class="size-10 opacity-40" />
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
                    نمایش <span class="fa-digits">{{ number_format($this->rows->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->rows->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->rows->total()) }}</span> تعرفه
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->rows->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->rows->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->rows->hasMorePages())
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

    {{-- مودال ایجاد تعرفه (پورت back/tariffs/create) --}}
    <div x-data="{ open: @entangle('showCreateModal').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">ایجاد تعرفه جدید</h3>
            <p class="mt-1 text-xs text-ink-3 dark:text-night-ink-2">روش ارسال: {{ $this->carrier->title }}</p>

            <form wire:submit="createTariff" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع منطقه ارسالی</label>
                    <select wire:model="createType" class="ap-input !py-2">
                        <option value="within_province">درون استانی</option>
                        <option value="extra_province">برون استانی</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">حداکثر وزن (گرم) <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="createMaxWeight" class="ap-input !py-2 fa-digits">
                    @error('createMaxWeight') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">هزینه ارسال (تومان) <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="createShippingCost" class="ap-input !py-2 fa-digits">
                    @error('createShippingCost') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2.5">
                    <button type="submit" wire:loading.attr="disabled" wire:target="createTariff" class="ap-btn flex-1">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        ایجاد تعرفه
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال ویرایش تعرفه (پورت back/tariffs/edit) --}}
    <div x-data="{ open: @entangle('editingId').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">ویرایش تعرفه</h3>

            <form wire:submit="updateTariff" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع منطقه ارسالی</label>
                    <select wire:model="editType" class="ap-input !py-2">
                        <option value="within_province">درون استانی</option>
                        <option value="extra_province">برون استانی</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">حداکثر وزن (گرم) <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="editMaxWeight" class="ap-input !py-2 fa-digits">
                    @error('editMaxWeight') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">هزینه ارسال (تومان) <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="editShippingCost" class="ap-input !py-2 fa-digits">
                    @error('editShippingCost') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2.5">
                    <button type="submit" wire:loading.attr="disabled" wire:target="updateTariff" class="ap-btn flex-1">
                        <x-admin-panel::icon name="check" class="size-4" />
                        ویرایش تعرفه
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال تایید حذف (متن پورت back/tariffs/index) --}}
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
            <h3 class="text-base font-bold">آیا مطمئن هستید؟</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                آیا میخواهید این تعرفه را حذف کنید؟
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">خیر</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="createTariff, updateTariff, delete, switchCarrier"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
