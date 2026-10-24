{{-- لینک‌های فوتر — گروه‌بندی از config('front.linkGroups') + کارت ایجاد + مودال ویرایش + ترتیب ↑↓ --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
@php
    $groupsList = $this->groups;
@endphp
<div class="mx-auto max-w-6xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
            کل لینک‌ها: <span class="fa-digits">{{ number_format($this->links->count()) }}</span>
        </span>
        <div class="flex items-center gap-2">
            <p class="hidden text-[11px] text-ink-3 dark:text-night-ink-2 sm:block">
                ترتیب نمایش لینک‌ها در هر گروه با دکمه‌های ▲▼ تنظیم می‌شود.
            </p>
            @can('links.groups')
                <a href="{{ route('np.links.groups') }}" wire:navigate class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="layers" class="size-4" />
                    مدیریت گروه‌ها
                </a>
            @endcan
        </div>
    </div>

    {{-- کارت ایجاد لینک (پورت create/store قدیمی) --}}
    @can('links.create')
        <div class="ap-card p-4 sm:p-5">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="plus" class="size-4.5 text-brand-600 dark:text-brand-400" />
                لینک جدید
            </h2>
            <form wire:submit="storeLink" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="link-create-title">عنوان <span class="text-red-500">*</span></label>
                    <input id="link-create-title" type="text" wire:model="createTitle" class="ap-input !py-2" placeholder="عنوان لینک…">
                    @error('createTitle') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="link-create-link">لینک <span class="text-red-500">*</span></label>
                    <input id="link-create-link" type="text" wire:model="createLink" dir="ltr"
                           class="ap-input !py-2 font-mono !text-xs" placeholder="/pages/about">
                    @error('createLink') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="link-create-group">گروه <span class="text-red-500">*</span></label>
                    <select id="link-create-group" wire:model="createGroupId" class="ap-input !py-2">
                        <option value="">انتخاب گروه…</option>
                        @foreach ($groupsList as $group)
                            <option value="{{ $group['key'] }}">{{ $this->groupTitle($group['key']) }}</option>
                        @endforeach
                    </select>
                    @error('createGroupId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end">
                    <button type="submit" wire:loading.attr="disabled" wire:target="storeLink" class="ap-btn w-full !py-2">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        ایجاد لینک
                    </button>
                </div>
            </form>
        </div>
    @endcan

    {{-- کارت هر گروه --}}
    @forelse ($groupsList as $groupIndex => $group)
        @php
            $groupLinks = $this->linksInGroup($group['key']);
        @endphp
        <div class="ap-card overflow-hidden" wire:key="group-{{ $group['key'] }}">
            <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-3 dark:border-night-line">
                <h2 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="link-2" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    {{ $this->groupTitle($group['key']) }}
                    <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2" dir="ltr">
                        <code class="text-[10px]">key: {{ $group['key'] }}</code>
                    </span>
                </h2>
            </div>

            @if (count($groupLinks))
                <div class="overflow-x-auto">
                    <table class="w-full min-w-2xl text-start text-[13px]">
                        <thead>
                            <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                                <th class="w-16 px-3 py-3 text-start font-bold">ردیف</th>
                                <th class="px-3 py-3 text-start font-bold">عنوان</th>
                                <th class="px-3 py-3 text-start font-bold">لینک</th>
                                <th class="w-40 px-3 py-3 text-center font-bold">ترتیب</th>
                                <th class="w-24 px-3 py-3 text-start font-bold">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groupLinks as $link)
                                <tr wire:key="link-{{ $link->id }}"
                                    class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                                    <td class="px-3 py-3 fa-digits font-bold">{{ number_format($loop->iteration) }}</td>
                                    <td class="max-w-48 px-3 py-3">
                                        <p class="truncate font-semibold">{{ $link->title }}</p>
                                    </td>
                                    <td class="max-w-64 px-3 py-3">
                                        <code class="block truncate text-[11px] text-ink-2 dark:text-night-ink-2" dir="ltr" title="{{ $link->link }}">{{ $link->link }}</code>
                                    </td>
                                    <td class="px-3 py-3">
                                        @can('links.update')
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" wire:click="moveUp({{ $link->id }})"
                                                        @class(['rounded-lg p-1.5 transition-colors hover:bg-surface-3 dark:hover:bg-night-3', 'pointer-events-none opacity-30' => $loop->first])
                                                        title="یک پله بالا" aria-label="بالا بردن ترتیب">
                                                    <x-admin-panel::icon name="chevron-up" class="size-4 text-ink-2 dark:text-night-ink-2" />
                                                </button>
                                                <button type="button" wire:click="moveDown({{ $link->id }})"
                                                        @class(['rounded-lg p-1.5 transition-colors hover:bg-surface-3 dark:hover:bg-night-3', 'pointer-events-none opacity-30' => $loop->last])
                                                        title="یک پله پایین" aria-label="پایین بردن ترتیب">
                                                    <x-admin-panel::icon name="chevron-down" class="size-4 text-ink-2 dark:text-night-ink-2" />
                                                </button>
                                            </div>
                                        @endcan
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-0.5">
                                            @can('links.update')
                                                <button type="button" wire:click="openEdit({{ $link->id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                                        title="ویرایش لینک" aria-label="ویرایش">
                                                    <x-admin-panel::icon name="pencil" class="size-4" />
                                                </button>
                                            @endcan
                                            @can('links.delete')
                                                <button type="button" wire:click="confirmDelete({{ $link->id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                        title="حذف لینک" aria-label="حذف">
                                                    <x-admin-panel::icon name="trash-2" class="size-4" />
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-4 py-6">
                    <p class="text-[13px] text-ink-3 dark:text-night-ink-2">چیزی برای نمایش وجود ندارد!</p>
                </div>
            @endif
        </div>
    @empty
        <div class="ap-card flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
            <x-admin-panel::icon name="link-2" class="size-10 opacity-40" />
            <p class="text-sm font-medium">گروهی برای نمایش وجود ندارد</p>
        </div>
    @endforelse

    {{-- مودال ویرایش لینک (پورت edit/update قدیمی) --}}
    @can('links.update')
        <div x-data="{ open: @entangle('editingLinkId').live }"
             x-show="open" x-cloak
             class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open = null" x-transition.opacity aria-hidden="true"></div>
            <div x-show="open" x-on:click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
                 role="dialog" aria-modal="true">
                <h3 class="text-base font-bold">ویرایش لینک</h3>
                <form wire:submit="updateLink" class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="link-edit-title">عنوان <span class="text-red-500">*</span></label>
                        <input id="link-edit-title" type="text" wire:model="editTitle" class="ap-input">
                        @error('editTitle') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="link-edit-link">لینک <span class="text-red-500">*</span></label>
                        <input id="link-edit-link" type="text" wire:model="editLink" dir="ltr" class="ap-input font-mono !text-xs">
                        @error('editLink') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="link-edit-group">گروه <span class="text-red-500">*</span></label>
                        <select id="link-edit-group" wire:model="editGroupId" class="ap-input">
                            <option value="">انتخاب گروه…</option>
                            @foreach ($groupsList as $group)
                                <option value="{{ $group['key'] }}">{{ $this->groupTitle($group['key']) }}</option>
                            @endforeach
                        </select>
                        @error('editGroupId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2.5 pt-1">
                        <button type="submit" wire:loading.attr="disabled" wire:target="updateLink" class="ap-btn flex-1">
                            <x-admin-panel::icon name="check" class="size-4" />
                            ذخیره تغییرات
                        </button>
                        <button type="button" x-on:click="open = null" class="ap-btn-ghost flex-1">انصراف</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    {{-- مودال تایید حذف (متن قدیمی حفظ شد) --}}
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
            <h3 class="text-base font-bold">حذف لینک</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف لینک دیگر قادر به بازیابی آن نخواهید بود.
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
    <div wire:loading wire:target="storeLink, updateLink, delete, moveUp, moveDown"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
