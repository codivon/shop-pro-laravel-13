{{-- ویژگی‌های یک گروه + ایجاد سریع — پنل جدید (فاز ۵) --}}
<div class="mx-auto max-w-5xl space-y-5">

    {{-- هدر گروه --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                <x-admin-panel::icon name="layers" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-extrabold">ویژگی‌های گروه: {{ $attributeGroup->name }}</h2>
                <div class="mt-0.5 flex items-center gap-2 text-[11px] text-ink-3 dark:text-night-ink-2">
                    @if ($attributeGroup->type === 'color')
                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">گروه رنگ</span>
                    @else
                        <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">گروه دکمه (چندانتخابی)</span>
                    @endif
                    <span>— <span class="fa-digits">{{ number_format($this->attributesList->total()) }}</span> ویژگی</span>
                </div>
            </div>
        </div>
        <a href="{{ route('np.attribute-groups.index') }}" wire:navigate class="ap-btn-ghost !py-2">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به گروه‌ها
        </a>
    </div>

    {{-- کارت ایجاد ویژگی جدید (پورت create قدیمی به‌صورت کارت درون‌صفحه‌ای) --}}
    @can('attributes.create')
        <div class="ap-card p-4 sm:p-5">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="plus" class="size-4.5 text-brand-600 dark:text-brand-400" />
                ویژگی جدید
            </h2>
            <form wire:submit="createAttribute" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="attribute-create-name">نام <span class="text-red-500">*</span></label>
                    <input id="attribute-create-name" type="text" wire:model="attributeName" class="ap-input !py-2" placeholder="مثال: قرمز">
                    @error('attributeName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                @if ($attributeGroup->type === 'color')
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="attribute-create-value">مقدار (کد رنگ)</label>
                        <input id="attribute-create-value" type="color" wire:model="attributeValue"
                               class="h-[38px] w-full cursor-pointer rounded-xl border border-line bg-transparent p-1 dark:border-night-line">
                        @error('attributeValue') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
                <div class="flex items-end">
                    <button type="submit" wire:loading.attr="disabled" wire:target="createAttribute" class="ap-btn w-full !py-2">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        ایجاد ویژگی
                    </button>
                </div>
            </form>
        </div>
    @endcan

    {{-- فیلتر جستجو --}}
    <div class="ap-card flex flex-wrap items-center gap-3 p-4">
        <div class="relative flex-1">
            <x-admin-panel::icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
            <input type="text" wire:model.live.debounce.400ms="search" class="ap-input !py-2 ps-9" placeholder="جستجوی نام ویژگی…">
        </div>
        @if ($search)
            <button type="button" wire:click="applyFilters" class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                بازنشانی
            </button>
        @endif
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-16 px-3 py-3 text-start font-bold">ID</th>
                        <th class="px-3 py-3 text-start font-bold">نام</th>
                        <th class="px-3 py-3 text-start font-bold">{{ $attributeGroup->type === 'color' ? 'مقدار / رنگ' : 'مقدار' }}</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->attributesList as $attribute)
                        <tr wire:key="attribute-{{ $attribute->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($attribute->id) }}</td>
                            <td class="max-w-48 px-3 py-3">
                                <p class="truncate font-semibold">{{ $attribute->name }}</p>
                            </td>
                            <td class="px-3 py-3">
                                @if ($attributeGroup->type === 'color' && $attribute->value)
                                    <div class="flex items-center gap-2">
                                        <input type="color" value="{{ $attribute->value }}" disabled tabindex="-1"
                                               class="size-5 cursor-default rounded border border-line p-0 dark:border-night-line"
                                               aria-label="نمایش رنگ {{ $attribute->name }}">
                                        <code class="text-[11px]" dir="ltr">{{ $attribute->value }}</code>
                                    </div>
                                @else
                                    <span class="text-ink-3 dark:text-night-ink-2">{{ $attribute->value ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <a href="{{ route('np.attributes.edit', ['attribute' => $attribute]) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="ویرایش ویژگی" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </a>
                                    @can('attributes.delete')
                                        <button type="button" wire:click="confirmDelete({{ $attribute->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف ویژگی" aria-label="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="list" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">ویژگی‌ای برای این گروه ثبت نشده است</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->attributesList->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->attributesList->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->attributesList->total()) }}</span> ویژگی
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->attributesList->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->attributesList->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->attributesList->hasMorePages())
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
            <h3 class="text-base font-bold">حذف ویژگی</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف ویژگی دیگر قادر به بازیابی آن نخواهید بود.
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
    <div wire:loading wire:target="search, previousPage, nextPage, gotoPage, createAttribute, delete"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
