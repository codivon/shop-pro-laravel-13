{{-- ریدایرکت‌ها — پورت back/redirects (فاز ۷-ب۲) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="arrow-right-left" class="size-3.5" />
                کل ریدایرکت‌ها: <span class="fa-digits">{{ number_format($this->rows->total()) }}</span>
            </span>
        </div>
        <button type="button" wire:click="$set('showCreateModal', true)" class="ap-btn !py-2">
            <x-admin-panel::icon name="plus" class="size-4" />
            افزودن جدید
        </button>
    </div>

    {{-- نوار انتخاب گروهی --}}
    @if (count($selected))
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface p-3.5 dark:border-night-line dark:bg-night-2">
            <p class="text-[13px] font-semibold">
                <span class="fa-digits text-brand-600 dark:text-brand-400">{{ number_format(count($selected)) }}</span> مورد انتخاب شده:
            </p>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="confirmBulkDelete" class="ap-btn !py-1.5 bg-red-600 text-white hover:bg-red-700">
                    <x-admin-panel::icon name="trash-2" class="size-4" />
                    حذف همه
                </button>
                <button type="button" wire:click="$set('selected', [])" class="ap-btn-ghost !py-1.5">لغو انتخاب‌ها</button>
            </div>
        </div>
    @endif

    {{-- جدول (پورت back/redirects/index) --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-12 px-3 py-3">
                            <input type="checkbox" class="size-4 accent-brand-600" wire:click="toggleSelectAll" aria-label="انتخاب همه">
                        </th>
                        <th class="w-20 px-3 py-3 text-start font-bold">نوع</th>
                        <th class="px-3 py-3 text-start font-bold">ریدایرکت از</th>
                        <th class="px-3 py-3 text-start font-bold">ریدایرکت به</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->rows as $item)
                        <tr wire:key="redirect-{{ $item->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" class="size-4 accent-brand-600" value="{{ $item->id }}"
                                       wire:model="selected" aria-label="انتخاب ریدایرکت {{ $item->id }}">
                            </td>
                            <td class="px-3 py-3">
                                @if ($item->type == 301)
                                    <span class="fa-digits ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">301</span>
                                @elseif ($item->type == 302)
                                    <span class="fa-digits ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400">302</span>
                                @elseif ($item->type == 303)
                                    <span class="fa-digits ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">303</span>
                                @elseif ($item->type == 403)
                                    <span class="fa-digits ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">403</span>
                                @elseif ($item->type == 410)
                                    <span class="fa-digits ap-badge bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400">410</span>
                                @elseif ($item->type == 503)
                                    <span class="fa-digits ap-badge bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400">503</span>
                                @else
                                    <span class="fa-digits ap-badge bg-surface-3 text-ink-3 dark:bg-night-3">{{ $item->type }}</span>
                                @endif
                            </td>
                            <td class="max-w-52 px-3 py-3">
                                <p class="truncate font-mono text-xs" dir="ltr">{{ $item->from }}</p>
                            </td>
                            <td class="max-w-52 px-3 py-3">
                                <p class="truncate font-mono text-xs" dir="ltr">{{ $item->to }}</p>
                            </td>
                            <td class="px-3 py-3 fa-digits text-ink-3 dark:text-night-ink-2">{{ self::jdate($item->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <button type="button" wire:click="editRedirect({{ $item->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="ویرایش ریدایرکت" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $item->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف ریدایرکت" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="arrow-right-left" class="size-10 opacity-40" />
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
                    <span class="fa-digits">{{ number_format($this->rows->total()) }}</span> ریدایرکت
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

    {{-- مودال ایجاد ریدایرکت (پورت back/redirects/create) --}}
    <div x-data="{ open: @entangle('showCreateModal').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">ایجاد ریدایرکت جدید</h3>

            <form wire:submit="createRedirect" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع ریدایرکت</label>
                    <select wire:model="createType" class="ap-input !py-2">
                        <option value="0">بدون وضعیت</option>
                        <option value="301">301</option>
                        <option value="302">302</option>
                        <option value="303">303</option>
                        <option value="403">403</option>
                        <option value="410">410</option>
                        <option value="503">503</option>
                    </select>
                    @error('createType') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">ریدایرکت از <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="createFrom" dir="ltr" class="ap-input !py-2 font-mono text-start text-xs">
                    @error('createFrom') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">ریدایرکت به <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="createTo" dir="ltr" class="ap-input !py-2 font-mono text-start text-xs">
                    @error('createTo') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2.5">
                    <button type="submit" wire:loading.attr="disabled" wire:target="createRedirect" class="ap-btn flex-1">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        افزودن
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال ویرایش ریدایرکت (پورت back/redirects/edit) --}}
    <div x-data="{ open: @entangle('editingId').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">ویرایش ریدایرکت</h3>

            <form wire:submit="updateRedirect" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع ریدایرکت</label>
                    <select wire:model="editType" class="ap-input !py-2">
                        <option value="0">بدون وضعیت</option>
                        <option value="301">301</option>
                        <option value="302">302</option>
                        <option value="303">303</option>
                        <option value="403">403</option>
                        <option value="410">410</option>
                        <option value="503">503</option>
                    </select>
                    @error('editType') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">ریدایرکت از <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="editFrom" dir="ltr" class="ap-input !py-2 font-mono text-start text-xs">
                    @error('editFrom') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">ریدایرکت به <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="editTo" dir="ltr" class="ap-input !py-2 font-mono text-start text-xs">
                    @error('editTo') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2.5">
                    <button type="submit" wire:loading.attr="disabled" wire:target="updateRedirect" class="ap-btn flex-1">
                        <x-admin-panel::icon name="check" class="size-4" />
                        ویرایش ریدایرکت
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال تایید حذف تکی (متن پورت back/redirects/index) --}}
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
                با حذف ریدایرکت دیگر قادر به بازیابی آن نخواهید بود
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

    {{-- مودال تایید حذف گروهی (متن پورت back/redirects/index) --}}
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
            <h3 class="text-base font-bold">آیا مطمئن هستید؟</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف ریدایرکت‌ها دیگر قادر به بازیابی آنها نخواهید بود
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="multipleDestroy" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">خیر</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="createRedirect, updateRedirect, delete, multipleDestroy"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
