{{-- تنظیمات فروشندگان — نمایش میزان کمیسیون (فاز ۳) --}}
<div class="mx-auto max-w-5xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="percent" class="size-5" />
            </span>
            <div>
                <h1 class="text-base font-black">نمایش میزان کمیسیون</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">نمایش میزان کمیسیون فروشندگان در صفحه فروشندگان است؛ درصد کمیسیون واقعی از دسته‌بندی‌ها خوانده می‌شود</p>
            </div>
        </div>
        <button type="button" wire:click="openModal" class="ap-btn-primary">
            <x-admin-panel::icon name="plus" class="size-4" />
            افزودن مورد جدید
        </button>
    </div>

    {{-- لیست --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-14 px-3 py-3 text-start font-bold">#</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">توضیحات</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">آیکون</th>
                        <th class="w-24 px-3 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->items as $item)
                        <tr wire:key="comm-{{ $item->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 text-ink-3">{{ $loop->iteration }}</td>
                            <td class="px-3 py-3 font-semibold">{{ $item->title }}</td>
                            <td class="max-w-96 px-3 py-3 text-ink-2 dark:text-night-ink-2"><p class="truncate">{{ \Illuminate\Support\Str::limit($item->description, 120) ?? '—' }}</p></td>
                            <td class="px-3 py-3">
                                @if ($item->icon)
                                    <x-admin-panel::icon name="{{ $item->icon }}" class="size-5 text-brand-600 dark:text-brand-400" />
                                @else
                                    <span class="text-ink-3">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-center gap-0.5">
                                    <button type="button" wire:click="edit({{ $item->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="ویرایش" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="$set('confirmingDelete', {{ $item->id }})"
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
                                    <x-admin-panel::icon name="percent" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">هنوز موردی ثبت نشده است</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- مودال ایجاد/ویرایش --}}
    <div x-data="{ open: @entangle('modal').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-lg rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">{{ $editingId ? 'ویرایش مورد' : 'افزودن مورد جدید' }}</h3>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">عنوان <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="title" class="ap-input">
                    @error('title') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">توضیحات</label>
                    <textarea wire:model="description" rows="3" class="ap-input"></textarea>
                    @error('description') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">آیکون (نام آیکون Lucide)</label>
                    <input type="text" wire:model="icon" class="ap-input" dir="ltr" placeholder="percent">
                    @error('icon') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="ap-btn-primary flex-1">
                    ذخیره
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost">انصراف</button>
            </div>
        </div>
    </div>

    {{-- مودال حذف --}}
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
            <h3 class="text-base font-bold">حذف رکورد</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">این مورد پس از حذف قابل بازیابی نخواهد بود.</p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>
</div>
