{{-- نقش‌ها (مقام‌ها) — پنل جدید (فاز ۷-ج) — پورت ۱:۱ back/roles/* (لیست + مودال ایجاد/ویرایش با درخت دسترسی + حذف) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-base font-extrabold">لیست مقام ها</h1>
            <p class="mt-1 text-xs text-ink-3 dark:text-night-ink-2">مدیریت نقش‌های مدیران و دسترسی‌های هر نقش.</p>
        </div>
        @can('roles.create')
            <button type="button" wire:click="openCreateModal" class="ap-btn !py-2.5">
                <x-admin-panel::icon name="plus" class="size-4" />
                ایجاد مقام
            </button>
        @endcan
    </div>

    {{-- جدول (پورت index قدیمی + تعداد مدیران طبق اسپک) --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-16 px-3 py-3 text-start font-bold">ID</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">توضیحات</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">تعداد مدیران</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">تاریخ ثبت</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->roles as $role)
                        <tr wire:key="role-{{ $role->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($role->id) }}</td>
                            <td class="max-w-40 px-3 py-3">
                                <p class="truncate font-semibold">{{ $role->title }}</p>
                            </td>
                            <td class="max-w-64 px-3 py-3">
                                <p class="truncate text-ink-2 dark:text-night-ink-2">{{ $role->description ?: '—' }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <span class="fa-digits inline-flex min-w-7 justify-center rounded-lg bg-surface-2 px-2 py-1 font-bold dark:bg-night-3">{{ number_format($role->admins_count) }}</span>
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($role->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    @can('roles.update')
                                        <button type="button" wire:click="openEditModal({{ $role->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                                title="ویرایش مقام" aria-label="ویرایش">
                                            <x-admin-panel::icon name="pencil" class="size-4" />
                                        </button>
                                    @endcan
                                    @can('roles.delete')
                                        <button type="button" wire:click="confirmDelete({{ $role->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف مقام" aria-label="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="key-round" class="size-10 opacity-40" />
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
                    نمایش <span class="fa-digits">{{ number_format($this->roles->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->roles->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->roles->total()) }}</span> مقام
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->roles->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->roles->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->roles->hasMorePages())
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

    {{-- مودال ایجاد/ویرایش (پورت create/edit قدیمی با درخت دسترسی) --}}
    <div x-data="{ open: @entangle('showFormModal').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto p-4 sm:items-center">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="closeFormModal" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative my-4 w-full max-w-3xl rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">{{ $roleId ? 'ویرایش مقام' : 'ایجاد مقام' }}</h3>

            <form wire:submit="save" class="mt-4 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="role-title">عنوان <span class="text-red-500">*</span></label>
                        <input id="role-title" type="text" wire:model="title" class="ap-input" placeholder="مثلاً: پشتیبان فروشگاه">
                        @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="role-description">توضیحات</label>
                        <input id="role-description" type="text" wire:model="description" class="ap-input" placeholder="توضیح اختیاری…">
                    </div>
                </div>

                <div>
                    <p class="mb-2 text-[13px] font-bold">دسترسی ها</p>
                    @error('permissions.*')
                        <p class="mb-2 rounded-lg bg-red-50 p-2 text-xs font-medium text-red-600 dark:bg-red-500/10">{{ $message }}</p>
                    @enderror

                    <div class="max-h-72 space-y-2.5 overflow-y-auto pe-1">
                        @foreach ($this->permissionTree as $permission)
                            <div class="rounded-xl border border-line dark:border-night-line" x-data="{ open: @js(in_array($permission->id, array_map('intval', $this->permissions))) }">
                                <div class="flex items-center gap-2 p-3">
                                    <label class="flex flex-1 cursor-pointer items-center gap-2 text-[13px] font-bold">
                                        {{-- والد: مثل JS قدیمی با تغییر تیک، فرزندان هم تیک می‌خورند و گروه باز می‌شود --}}
                                        <input type="checkbox"
                                               :checked="$wire.permissions.map(Number).includes({{ $permission->id }})"
                                               x-on:change="open = $event.target.checked; $wire.toggleParent({{ $permission->id }})"
                                               class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                                        {{ $permission->title }}
                                    </label>
                                    @if ($permission->permissions->isNotEmpty())
                                        <button type="button" class="rounded-lg p-1 text-ink-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3" x-on:click="open = !open" aria-label="باز و بسته کردن">
                                            <span class="ap-chevron block" :class="open ? 'is-open' : ''">
                                                <x-admin-panel::icon name="chevron-down" class="size-4" />
                                            </span>
                                        </button>
                                    @endif
                                </div>
                                @if ($permission->permissions->isNotEmpty())
                                    <div x-show="open" x-cloak
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                         class="border-t border-line px-3 py-3 dark:border-night-line">
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                            @foreach ($permission->permissions as $item)
                                                <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-[13px] transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                                                    <input type="checkbox" wire:model="permissions" value="{{ $item->id }}"
                                                           @checked(in_array($item->id, array_map('intval', $this->permissions)))
                                                           class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                                                    <span class="truncate">{{ $item->title }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-2.5 pt-1">
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn flex-1 !py-2.5">
                        <x-admin-panel::icon name="check" class="size-4" />
                        {{ $roleId ? 'ویرایش مقام' : 'ایجاد مقام' }}
                    </button>
                    <button type="button" x-on:click="closeFormModal" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال تایید حذف (پورت delete-modal قدیمی) --}}
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
                با حذف مقام دیگر قادر به بازیابی آن نخواهید بود
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">خیر</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="save, delete, previousPage, nextPage, gotoPage"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
