{{-- لیست فیلدهای اختصاصی — پنل جدید (فاز ۵) | پورت back/filds/index + create --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                <x-admin-panel::icon name="list" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-extrabold">فیلدهای اختصاصی</h2>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">فیلدهای سفارشی کاربران، محصولات و مقالات</p>
            </div>
        </div>
        @can('filds.create')
            <button type="button" wire:click="$set('showCreate', {{ $showCreate ? 'false' : 'true' }})"
                    class="ap-btn-primary !py-2">
                <x-admin-panel::icon name="plus" class="size-4" />
                افزودن فیلد جدید
            </button>
        @endcan
    </div>

    {{-- کارت ایجاد سریع (پورت create/store قدیمی) --}}
    @can('filds.create')
        <div class="ap-card overflow-hidden" x-data="{ open: @entangle('showCreate').live }">
            <button type="button" class="flex w-full items-center justify-between p-4 text-start sm:p-5"
                    x-on:click="open = !open">
                <span class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="plus" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    ایجاد فیلد اختصاصی جدید
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
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="fild-belongs">فیلد متعلق است به</label>
                            <select id="fild-belongs" wire:model="belongsTo" class="ap-input !py-2">
                                @foreach (\App\Livewire\Admin\Taxonomy\FildIndex::BELONGS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('belongsTo') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="fild-type">نوع فیلد</label>
                            <select id="fild-type" wire:model.live="type" class="ap-input !py-2">
                                @foreach (\App\Livewire\Admin\Taxonomy\FildIndex::TYPES as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="fild-title">عنوان</label>
                            <input id="fild-title" type="text" wire:model="title" class="ap-input" placeholder="مثال: رنگ دلخواه">
                            @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="fild-value">مقدار پیش‌فرض (اختیاری)</label>
                            <input id="fild-value" type="text" wire:model="value" class="ap-input" placeholder="مقدار پیش‌فرض…">
                            @error('value') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- مقادیر select — فقط برای نوع «انتخابی» (رفتار reactive مثل قدیمی) --}}
                    <div x-show="$wire.type === 'select'" x-cloak>
                        <label class="mb-1.5 block text-xs font-semibold" for="fild-select-options">مقادیر گزینه‌ها (با کاما جدا شود)</label>
                        <textarea id="fild-select-options" rows="2" wire:model="selectOptions" class="ap-input" placeholder="مثلا: قرمز, آبی, سبز"></textarea>
                        @error('selectOptions') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-3">
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-line px-3 py-2.5 text-[13px] transition-colors hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3">
                            <input type="checkbox" wire:model="published"
                                   class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            انتشار
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-line px-3 py-2.5 text-[13px] transition-colors hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3">
                            <input type="checkbox" wire:model="required"
                                   class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            اجباری
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-line px-3 py-2.5 text-[13px] transition-colors hover:bg-surface-2 dark:border-night-line dark:hover:bg-night-3">
                            <input type="checkbox" wire:model="userShow"
                                   class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            نمایش به کاربر
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="ap-btn-primary !py-2.5" wire:loading.attr="disabled">ایجاد فیلد</button>
                        <button type="button" x-on:click="open=false" class="ap-btn-ghost !py-2.5">انصراف</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    {{-- نوار عملیات گروهی --}}
    <div x-show="$wire.selected.length > 0" x-cloak
         class="sticky top-20 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-200 bg-brand-50/95 px-4 py-2.5 shadow-card backdrop-blur dark:border-brand-500/25 dark:bg-brand-950/80">
        <span class="text-[13px] font-bold text-brand-800 dark:text-brand-300">
            <span class="fa-digits" x-text="ApFormat.faDigits($wire.selected.length)"></span> فیلد انتخاب شده
        </span>
        @can('filds.delete')
            <button type="button" wire:click="$set('confirmingBulkDelete', true)"
                    class="ap-btn !px-3 !py-1.5 text-[13px] bg-red-600 text-white hover:bg-red-700">
                <x-admin-panel::icon name="trash-2" class="size-4" />
                حذف همه
            </button>
        @endcan
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-10 px-3 py-3">
                            <input type="checkbox" wire:model.live="selectAll"
                                   class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                   aria-label="انتخاب همه">
                        </th>
                        <th class="w-16 px-2 py-3 text-start font-bold">#</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">متعلق به</th>
                        <th class="px-3 py-3 text-start font-bold">نوع</th>
                        <th class="px-3 py-3 text-center font-bold">اجباری</th>
                        <th class="px-3 py-3 text-center font-bold">انتشار</th>
                        <th class="px-3 py-3 text-center font-bold">نمایش به کاربر</th>
                        <th class="w-24 px-3 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->items as $item)
                        <tr wire:key="fild-{{ $item->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $item->id }}"
                                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                       aria-label="انتخاب فیلد {{ $item->id }}">
                            </td>
                            <td class="px-2 py-3 fa-digits font-bold">{{ number_format($item->id) }}</td>
                            <td class="px-3 py-3 font-semibold">
                                {{ $item->title }}
                                @if ($item->type === 'select' && $item->select_options)
                                    <p class="mt-0.5 truncate text-[11px] text-ink-3 dark:text-night-ink-2">{{ $item->select_options }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                                    {{ \App\Livewire\Admin\Taxonomy\FildIndex::BELONGS[$item->belongs_to] ?? $item->belongs_to }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-ink-2 dark:text-night-ink-2">
                                {{ \App\Livewire\Admin\Taxonomy\FildIndex::TYPES[$item->type] ?? $item->type }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($item->required)
                                    <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">اجباری</span>
                                @else
                                    <span class="text-ink-3">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($item->published)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">منتشر شده</span>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">پیش‌نویس</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($item->user_show)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">نمایش</span>
                                @else
                                    <span class="text-ink-3">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-center gap-0.5">
                                    @can('filds.update')
                                        <a href="{{ route('np.filds.edit', ['fild' => $item]) }}" wire:navigate
                                           class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                           title="ویرایش" aria-label="ویرایش">
                                            <x-admin-panel::icon name="pencil" class="size-4" />
                                        </a>
                                    @endcan
                                    @can('filds.delete')
                                        <button type="button" wire:click="confirmDelete({{ $item->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف" aria-label="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="list" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">فیلدی ثبت نشده است</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->items->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->items->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->items->total()) }}</span> فیلد
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->items->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->items->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->items->hasMorePages())
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

    {{-- مودال تایید حذف تکی --}}
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
            <h3 class="text-base font-bold">حذف فیلد</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف فیلد دیگر قادر به بازیابی آن نخواهید بود و مقادیر ثبت‌شده‌ی آن از بین می‌رود.
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

    {{-- مودال تایید حذف گروهی --}}
    <div x-data="{ open: @entangle('confirmingBulkDelete').live }"
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
            <h3 class="text-base font-bold">حذف گروهی فیلدها</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                <span class="fa-digits font-bold" x-text="ApFormat.faDigits($wire.selected.length)"></span> فیلد انتخاب شده است. با حذف فیلدها دیگر قادر به بازیابی آنها نخواهید بود.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="bulkDelete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="store, delete, bulkDelete"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
