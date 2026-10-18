{{-- لیست کاربران — پنل جدید (فاز ۴) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-black">کاربران</h1>
            <p class="fa-digits mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">
                <span class="fa-digits">{{ number_format($this->users->total()) }}</span> کاربر (بدون مدیر اصلی)
            </p>
        </div>
        <div class="flex items-center gap-2">
            @can('users.export')
                <button type="button" wire:click="$set('exportModal', true)" class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="download" class="size-4" />
                    خروجی گرفتن از لیست
                </button>
            @endcan
            @can('users.create')
                <a href="{{ route('np.users.create') }}" wire:navigate class="ap-btn-primary">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    کاربر جدید
                </a>
            @endcan
        </div>
    </div>

    {{-- کارت فیلترها --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: true }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر کاربران
                @if ($fullname || $email || $username || $level)
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
            <form wire:submit.prevent="applyFilters" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نام و نام خانوادگی</label>
                    <input type="text" wire:model.live.debounce.400ms="fullname" class="ap-input !py-2" placeholder="جستجوی نام…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نام کاربری</label>
                    <input type="text" wire:model.live.debounce.400ms="username" class="ap-input !py-2 fa-digits" placeholder="۰۹۱۲…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">ایمیل</label>
                    <input type="text" wire:model.live.debounce.400ms="email" class="ap-input !py-2" placeholder="example@mail.com" dir="ltr">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نقش</label>
                    <select wire:model.live="level" class="ap-input !py-2">
                        <option value="">همه</option>
                        <option value="admin">مدیر</option>
                        <option value="user">کاربر عادی</option>
                    </select>
                </div>
                <div class="flex items-end lg:col-span-4">
                    <button type="submit" class="ap-btn-outline !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        بازنشانی فیلترها
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- نوار عملیات گروهی --}}
    <div x-show="$wire.selected.length > 0" x-cloak
         class="sticky top-20 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-200 bg-brand-50/95 px-4 py-2.5 shadow-card backdrop-blur dark:border-brand-500/25 dark:bg-brand-950/80">
        <span class="text-[13px] font-bold text-brand-800 dark:text-brand-300">
            <span class="fa-digits" x-text="ApFormat.faDigits($wire.selected.length)"></span> کاربر انتخاب شده
        </span>
        @can('users.delete')
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
                        <th class="w-16 px-2 py-3 text-start font-bold">ID</th>
                        <th class="w-16 px-3 py-3 text-start font-bold">تصویر</th>
                        <th class="px-3 py-3 text-start font-bold">نام و نام خانوادگی</th>
                        <th class="px-3 py-3 text-start font-bold">نام کاربری</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ عضویت</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->users as $user)
                        <tr wire:key="user-{{ $user->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $user->id }}"
                                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                       aria-label="انتخاب کاربر {{ $user->id }}">
                            </td>
                            <td class="px-2 py-3 fa-digits font-bold">{{ number_format($user->id) }}</td>
                            <td class="px-3 py-3">
                                <img src="{{ $user->imageUrl() }}" alt="{{ $user->fullname ?? 'کاربر' }}"
                                     class="size-10 rounded-xl border border-line object-cover dark:border-night-line">
                            </td>
                            <td class="max-w-44 px-3 py-3">
                                <p class="truncate font-semibold">{{ $user->fullname ?? '—' }}</p>
                            </td>
                            <td class="px-3 py-3 fa-digits">{{ $user->username }}</td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($user->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <a href="{{ route('np.users.show', $user) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="نمایش کاربر" aria-label="نمایش">
                                        <x-admin-panel::icon name="eye" class="size-4" />
                                    </a>
                                    <a href="{{ route('np.users.edit', $user) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="ویرایش کاربر" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </a>
                                    @can('users.delete')
                                        <button type="button" wire:click="confirmDelete({{ $user->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف کاربر" aria-label="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="users" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">کاربری با این فیلترها پیدا نشد</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->users->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->users->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->users->total()) }}</span> کاربر
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->users->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->users->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->users->hasMorePages())
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
            <h3 class="text-base font-bold">حذف کاربر</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف کاربر دیگر قادر به بازیابی آن نخواهید بود.
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
            <h3 class="text-base font-bold">حذف گروهی کاربران</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                <span class="fa-digits font-bold" x-text="ApFormat.faDigits($wire.selected.length)"></span> کاربر انتخاب شده است. با حذف کاربرها دیگر قادر به بازیابی آنها نخواهید بود.
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

    {{-- مودال خروجی — پورت users-export-modal قدیمی --}}
    <div x-data="{ open: @entangle('exportModal').live }"
         x-on:open-url.window="window.open($event.detail.url, '_blank')"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-lg rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">فیلدهای مورد نظر را انتخاب کنید</h3>

            <div class="mt-4 grid grid-cols-2 gap-3">
                @foreach ($this->exportColumnList as $column => $label)
                    <label class="flex cursor-pointer items-center gap-2.5 text-[13px]">
                        <input type="checkbox" wire:click="toggleExportColumn('{{ $column }}')"
                               @checked(in_array($column, $this->exportColumns))
                               class="size-4 rounded-md border-line text-brand-600 dark:border-night-line">
                        {{ $label }}
                    </label>
                @endforeach
            </div>

            <div class="mt-4">
                <label class="mb-1.5 block text-xs font-semibold">نوع خروجی</label>
                <select wire:model="exportType" class="ap-input">
                    <option value="excel">اکسل</option>
                    <option value="print">چاپ</option>
                </select>
                <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">خروجی فیلترهای فعلی لیست را با همین ستون‌ها می‌گیرد.</p>
            </div>

            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="exportUsers" wire:loading.attr="disabled"
                        class="ap-btn-primary flex-1">
                    <x-admin-panel::icon name="download" class="size-4" />
                    خروجی گرفتن
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="fullname, email, username, level, previousPage, nextPage, gotoPage, delete, bulkDelete, exportUsers"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
