{{-- مدیریت مدیران — پنل جدید (فاز ۷-ج) — پورت ۱:۱ back/admins/* (لیست + مودال ایجاد/ویرایش + حذف تکی/گروهی + وضعیت) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-base font-extrabold">لیست مدیران</h1>
            <p class="mt-1 text-xs text-ink-3 dark:text-night-ink-2">مدیریت مدیران پنل، نقش‌ها و وضعیت آن‌ها.</p>
        </div>
        @can('admins.create')
            <button type="button" wire:click="openCreateModal" class="ap-btn !py-2.5">
                <x-admin-panel::icon name="plus" class="size-4" />
                مدیر جدید
            </button>
        @endcan
    </div>

    {{-- نوار عملیات گروهی (پورت datatable-actions قدیمی) — فقط بعد از انتخاب ردیف‌ها نمایش داده می‌شود --}}
    <div @class(['ap-card flex flex-wrap items-center justify-between gap-3 border-s-4 border-s-red-500 p-3.5',
                 '!hidden' => count($selected) === 0])>
        <p class="text-[13px] font-bold text-red-600">
            <span class="fa-digits">{{ number_format(count($selected)) }}</span> مورد انتخاب شده:
        </p>
        @can('admins.delete')
            <button type="button" wire:click="confirmBulkDelete" class="ap-btn bg-red-600 !py-2 text-white hover:bg-red-700">
                <x-admin-panel::icon name="trash-2" class="size-4" />
                حذف همه
            </button>
        @endcan
    </div>

    {{-- کارت فیلترها (پورت filters قدیمی) --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر مدیران
                @if ($fullname || $filterUsername || $filterEmail)
                    <span class="size-2 rounded-full bg-brand-500"></span>
                @endif
            </span>
            <span class="ap-chevron" :class="open ? 'is-open' : ''">
                <x-admin-panel::icon name="chevron-left" class="size-4 text-ink-3" />
            </span>
        </button>

        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="pt-4">
            <form wire:submit="applyFilters" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نام</label>
                    <input type="text" wire:model.live.debounce.400ms="fullname" class="ap-input !py-2" placeholder="نام و نام خانوادگی…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نام کاربری</label>
                    <input type="text" wire:model.live.debounce.400ms="filterUsername" dir="ltr" class="ap-input !py-2" placeholder="username…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">ایمیل</label>
                    <input type="text" wire:model.live.debounce.400ms="filterEmail" dir="ltr" class="ap-input !py-2" placeholder="email@example.com…">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="ap-btn-outline !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        اعمال فیلترها
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        @can('admins.delete')
                            <th class="w-12 px-3 py-3 text-start font-bold">
                                <input type="checkbox" wire:model.live="selectAll"
                                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                       aria-label="انتخاب همه">
                            </th>
                        @endcan
                        <th class="w-16 px-3 py-3 text-start font-bold">ID</th>
                        <th class="w-16 px-3 py-3 text-start font-bold">تصویر</th>
                        <th class="px-3 py-3 text-start font-bold">نام</th>
                        <th class="px-3 py-3 text-start font-bold">نام کاربری</th>
                        <th class="px-3 py-3 text-start font-bold">نقش‌ها</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="w-32 px-3 py-3 text-start font-bold">آخرین ورود</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">تاریخ عضویت</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->admins as $admin)
                        <tr wire:key="admin-{{ $admin->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            @can('admins.delete')
                                <td class="px-3 py-3">
                                    @unless ($admin->level === 'creator' || $admin->id === auth('adminPanel')->id())
                                        <input type="checkbox" wire:model.live="selected" value="{{ $admin->id }}"
                                               class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                               aria-label="انتخاب مدیر {{ $admin->id }}">
                                    @endunless
                                </td>
                            @endcan
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($admin->id) }}</td>
                            <td class="px-3 py-3">
                                <img src="{{ $admin->image ? asset($admin->image) : asset('/back/app-assets/images/portrait/small/default.png') }}"
                                     alt="{{ $admin->fullname }}"
                                     class="size-10 rounded-xl border border-line object-cover dark:border-night-line">
                            </td>
                            <td class="max-w-40 px-3 py-3">
                                <p class="truncate font-semibold">{{ $admin->fullname }}</p>
                            </td>
                            <td class="max-w-36 px-3 py-3">
                                <code class="truncate text-[11px] text-ink-2 dark:text-night-ink-2" dir="ltr">{{ $admin->username }}</code>
                            </td>
                            <td class="max-w-44 px-3 py-3">
                                <div class="flex flex-wrap items-center gap-1">
                                    @forelse ($admin->roles as $role)
                                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">{{ $role->title }}</span>
                                    @empty
                                        <span class="text-ink-3 dark:text-night-ink-2">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                @if ($admin->level === 'creator')
                                    <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400" title="مدیر کل">
                                        مدیر کل
                                    </span>
                                @else
                                    <button type="button" wire:click="toggleStatus({{ $admin->id }})" wire:loading.attr="disabled" wire:target="toggleStatus"
                                            class="ap-badge {{ $admin->status === 'ACTIVE'
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400'
                                                : 'bg-slate-100 text-slate-600 dark:bg-night-3 dark:text-night-ink-2' }}"
                                            title="تغییر وضعیت">
                                        {{ $admin->status === 'ACTIVE' ? 'فعال' : 'غیرفعال' }}
                                    </button>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap text-ink-2 dark:text-night-ink-2">
                                {{ $this->jdate($this->lastLogins[$admin->id] ?? null, 'Y/m/d H:i') }}
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($admin->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    @can('admins.update')
                                        @unless ($admin->level === 'creator')
                                            <button type="button" wire:click="openEditModal({{ $admin->id }})"
                                                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                                    title="ویرایش مدیر" aria-label="ویرایش">
                                                <x-admin-panel::icon name="pencil" class="size-4" />
                                            </button>
                                        @endunless
                                    @endcan
                                    @can('admins.delete')
                                        @unless ($admin->level === 'creator' || $admin->id === auth('adminPanel')->id())
                                            <button type="button" wire:click="confirmDelete({{ $admin->id }})"
                                                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                    title="حذف مدیر" aria-label="حذف">
                                                <x-admin-panel::icon name="trash-2" class="size-4" />
                                            </button>
                                        @endunless
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="user-cog" class="size-10 opacity-40" />
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
                    نمایش <span class="fa-digits">{{ number_format($this->admins->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->admins->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->admins->total()) }}</span> مدیر
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->admins->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->admins->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->admins->hasMorePages())
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

    {{-- مودال ایجاد/ویرایش (پورت create/edit قدیمی) — فاز ۱۲: بستن با کلیک بیرون/ESC/انصراف اصلاح شد --}}
    <div x-data="{ open: @entangle('showFormModal').live }"
         x-show="open" x-cloak
         x-on:keydown.escape.window="open = false"
         class="fixed inset-0 z-[60] flex items-start justify-center overflow-y-auto p-4 sm:items-center">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open = false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative my-4 w-full max-w-2xl rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">{{ $adminId ? 'ویرایش مدیر' : 'ایجاد مدیر جدید' }}</h3>

            <form wire:submit="save" class="mt-4 space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="admin-first-name">نام <span class="text-red-500">*</span></label>
                        <input id="admin-first-name" type="text" wire:model="firstName" class="ap-input" placeholder="نام…">
                        @error('firstName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="admin-last-name">نام خانوادگی <span class="text-red-500">*</span></label>
                        <input id="admin-last-name" type="text" wire:model="lastName" class="ap-input" placeholder="نام خانوادگی…">
                        @error('lastName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="admin-email">آدرس ایمیل</label>
                        <input id="admin-email" type="email" wire:model="email" dir="ltr" class="ap-input" placeholder="email@example.com">
                        @error('email') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="admin-username">شماره همراه <small>(نام کاربری)</small> <span class="text-red-500">*</span></label>
                        <input id="admin-username" type="text" wire:model="username" dir="ltr" class="ap-input" placeholder="0912…">
                        @error('username') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="admin-image">تصویر</label>
                    <input id="admin-image" type="file" accept="image/*" wire:model="image"
                           class="block w-full cursor-pointer rounded-xl border border-line bg-transparent text-xs text-ink-2
                                  file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5
                                  file:text-xs file:font-semibold file:text-white hover:file:bg-brand-700 dark:border-night-line">
                    @error('image') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    @if ($image)
                        <div class="mt-2 flex items-center gap-3 rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                            <img src="{{ $image->temporaryUrl() }}" alt="پیش‌نمایش تصویر" class="size-12 rounded-lg border border-line object-cover dark:border-night-line">
                            <div class="text-xs">
                                <p class="font-semibold">تصویر انتخاب‌شده</p>
                                <button type="button" wire:click="$set('image', null)" class="mt-0.5 flex items-center gap-1 font-semibold text-red-500 hover:underline">
                                    <x-admin-panel::icon name="x" class="size-3.5" />
                                    حذف فایل انتخاب‌شده
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold">انتخاب نقش ها</label>
                    <div class="grid max-h-40 grid-cols-1 gap-2 overflow-y-auto rounded-xl border border-line p-3 sm:grid-cols-2 dark:border-night-line">
                        @forelse ($this->rolesList as $role)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-[13px] transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                                <input type="checkbox" wire:model="roles" value="{{ $role->id }}"
                                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                                <span class="truncate font-medium">{{ $role->title }}</span>
                            </label>
                        @empty
                            <p class="text-xs text-ink-3 dark:text-night-ink-2">نقشی برای انتخاب وجود ندارد.</p>
                        @endforelse
                    </div>
                    @error('roles.*') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="admin-password">گذرواژه @if ($adminId) <span class="text-[11px] font-normal text-ink-3">(خالی = بدون تغییر)</span> @endif <span class="text-red-500">*</span></label>
                        <input id="admin-password" type="password" wire:model="password" dir="ltr" class="ap-input" autocomplete="new-password">
                        @error('password') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="admin-password-confirmation">تکرار گذرواژه <span class="text-red-500">*</span></label>
                        <input id="admin-password-confirmation" type="password" wire:model="passwordConfirmation" dir="ltr" class="ap-input" autocomplete="new-password">
                        @error('passwordConfirmation') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                @if ($adminId)
                    <div class="rounded-xl border border-line bg-surface-2 p-3 text-xs leading-6 text-ink-2 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="info" class="me-1 inline size-3.5 align-middle" />
                        در صورتی که نمیخواهید گذرواژه را عوض کنید، فیلدهای گذرواژه را خالی بگذارید.
                    </div>
                @endif

                <label class="flex w-fit cursor-pointer items-center gap-2 text-[13px] font-medium">
                    <input type="checkbox" wire:model="verifiedAt"
                           class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                    شماره تلفن تایید شده
                </label>

                <div class="flex gap-2.5 pt-1">
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn flex-1 !py-2.5">
                        <x-admin-panel::icon name="check" class="size-4" />
                        {{ $adminId ? 'ویرایش مدیر' : 'ایجاد مدیر' }}
                    </button>
                    <button type="button" x-on:click="open = false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال تایید حذف تکی (پورت delete-modal قدیمی) --}}
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
                با حذف کاربر دیگر قادر به بازیابی آن نخواهید بود
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

    {{-- مودال تایید حذف گروهی (پورت multiple-delete-modal قدیمی) --}}
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
                با حذف کاربران دیگر قادر به بازیابی آنها نخواهید بود
            </p>
            @error('selected')
                <p class="mt-2 rounded-lg bg-red-50 p-2 text-xs font-medium text-red-600 dark:bg-red-500/10">{{ $message }}</p>
            @enderror
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="bulkDelete" wire:loading.attr="disabled" wire:target="bulkDelete"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">خیر</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="save, delete, bulkDelete, toggleStatus, previousPage, nextPage, gotoPage, fullname, filterUsername, filterEmail"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
