{{-- مدیریت اعلان‌های سراسری — پورت back/notifications/{index,show}.blade.php — پنل جدید (فاز ۷) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                <x-admin-panel::icon name="bell" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-extrabold">مدیریت اعلان‌ها</h2>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">ارسال و مدیریت اعلان‌های کاربران و فروشندگان</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('notifications.create')
                <button type="button" wire:click="openCreateModal" class="ap-btn-primary !py-2">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    اعلان جدید
                </button>
            @endcan
        </div>
    </div>

    {{-- آمار (پورت renderStats قدیمی) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">کل اعلان‌ها</p>
                <span class="ap-stat-icon bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="bell" class="size-4.5" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-2xl font-extrabold">{{ number_format($this->stats['total']) }}</p>
        </div>
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">نمایش پاپ‌آپی</p>
                <span class="ap-stat-icon bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                    <x-admin-panel::icon name="monitor" class="size-4.5" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-2xl font-extrabold">{{ number_format($this->stats['popup']) }}</p>
        </div>
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">ارسال گروهی</p>
                <span class="ap-stat-icon bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                    <x-admin-panel::icon name="users" class="size-4.5" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-2xl font-extrabold">{{ number_format($this->stats['broadcast']) }}</p>
        </div>
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">اولویت بالا</p>
                <span class="ap-stat-icon bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="triangle-alert" class="size-4.5" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-2xl font-extrabold">{{ number_format($this->stats['high']) }}</p>
        </div>
    </div>

    {{-- کارت فیلترها --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر اعلان‌ها
                @if ($search || $priority !== 'all')
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
            <form wire:submit.prevent="applyFilters" class="grid grid-cols-1 gap-3.5 sm:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">جستجو در عنوان یا پیام</label>
                    <input type="text" wire:model.live.debounce.400ms="search" class="ap-input !py-2" placeholder="جستجو در عنوان یا پیام…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">اولویت</label>
                    <select wire:model.live="priority" class="ap-input !py-2">
                        <option value="all">همه</option>
                        <option value="high">فوری</option>
                        <option value="medium">متوسط</option>
                        <option value="low">عادی</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="ap-btn-outline w-full !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        پاک کردن فیلترها
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
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">پیام</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">اولویت</th>
                        <th class="w-20 px-3 py-3 text-center font-bold">پاپ‌آپ</th>
                        <th class="px-3 py-3 text-start font-bold">گیرندگان</th>
                        <th class="w-40 px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->notifications as $notification)
                        @php $meta = \App\Livewire\Admin\Notifications\NotificationIndex::priorityMeta((string) $notification->priority); @endphp
                        <tr wire:key="notification-{{ $notification->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="max-w-52 px-3 py-3">
                                <span class="flex items-center gap-2">
                                    <span class="flex size-7 shrink-0 items-center justify-center rounded-lg {{ $meta['iconClass'] }}">
                                        <x-admin-panel::icon name="{{ $meta['icon'] }}" class="size-3.5" />
                                    </span>
                                    <span class="truncate font-semibold" title="{{ $notification->title }}">{{ $notification->title ?: 'بدون عنوان' }}</span>
                                </span>
                            </td>
                            <td class="max-w-64 px-3 py-3">
                                <p class="truncate text-ink-2 dark:text-night-ink-2" title="{{ $notification->message }}">{{ \Illuminate\Support\Str::limit((string) $notification->message, 80) }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <span class="ap-badge {{ $meta['class'] }}">{{ $meta['label'] }}</span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @can('notifications.update')
                                    <label class="inline-flex cursor-pointer items-center gap-2">
                                        <input type="checkbox" wire:click="togglePopup({{ $notification->id }})"
                                               {{ $notification->popup ? 'checked' : '' }}
                                               aria-label="تغییر وضعیت پاپ‌آپ {{ $notification->title }}"
                                               class="size-4 cursor-pointer rounded border-line text-brand-600">
                                    </label>
                                @else
                                    <span class="fa-digits text-xs {{ $notification->popup ? 'font-semibold text-emerald-600 dark:text-emerald-400' : 'text-ink-3 dark:text-night-ink-2' }}">
                                        {{ $notification->popup ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                @endcan
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @php $targets = $this->targets($notification); @endphp
                                    @if ($targets['users'])
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-2 py-1 text-[11px] font-semibold text-brand-700 dark:bg-brand-500/12 dark:text-brand-300">
                                            <x-admin-panel::icon name="users" class="size-3" />
                                            {{ $targets['users']['mode'] === 'all' ? 'همه کاربران' : 'کاربران انتخابی' }}
                                            (<span class="fa-digits">{{ number_format($targets['users']['count']) }}</span>)
                                        </span>
                                    @endif
                                    @if ($targets['sellers'])
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                                            <x-admin-panel::icon name="store" class="size-3" />
                                            {{ $targets['sellers']['mode'] === 'all' ? 'همه فروشندگان' : 'فروشندگان انتخابی' }}
                                            (<span class="fa-digits">{{ number_format($targets['sellers']['count']) }}</span>)
                                        </span>
                                    @endif
                                    @if (! $targets['users'] && ! $targets['sellers'])
                                        <span class="text-xs text-ink-3 dark:text-night-ink-2">—</span>
                                    @endif
                                    @if ($notification->read_count)
                                        <span class="inline-flex items-center gap-1 rounded-lg bg-surface-2 px-2 py-1 text-[11px] font-semibold text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                            <x-admin-panel::icon name="eye" class="size-3" />
                                            <span class="fa-digits">{{ number_format($notification->read_count) }}</span> خوانده
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <p class="fa-digits whitespace-nowrap font-semibold">{{ $this->jdate($notification->created_at, 'Y/m/d H:i') }}</p>
                                <p class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">{{ $this->jdateAgo($notification->created_at) }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <button type="button" wire:click="openDetail({{ $notification->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="گیرندگان" aria-label="گیرندگان">
                                        <x-admin-panel::icon name="eye" class="size-4" />
                                    </button>
                                    @can('notifications.update')
                                        <button type="button" wire:click="openEditModal({{ $notification->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                                title="ویرایش" aria-label="ویرایش">
                                            <x-admin-panel::icon name="pencil" class="size-4" />
                                        </button>
                                    @endcan
                                    @can('notifications.delete')
                                        <button type="button" wire:click="confirmDelete({{ $notification->id }})"
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
                            <td colspan="7">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="bell" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">اعلانی برای نمایش وجود ندارد</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->notifications->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->notifications->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->notifications->total()) }}</span> اعلان
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->notifications->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->notifications->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->notifications->hasMorePages())
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

    {{-- مودال جزئیات — پورت back/notifications/show.blade.php (فهرست گیرندگان با وضعیت خوانده‌شدن) --}}
    <div x-data="{ open: @entangle('showDetail').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative max-h-[85vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-base font-bold">
                    <x-admin-panel::icon name="bell" class="size-5 text-brand-600 dark:text-brand-400" />
                    گیرندگان اعلان
                    @if ($this->detailNotification)
                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">{{ $this->detailNotification->title ?: 'بدون عنوان' }}</span>
                    @endif
                </h3>
                <button type="button" x-on:click="open=false"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            @if ($this->detailNotification)
                @php $targets = $this->targets($this->detailNotification); @endphp

                <div class="mb-4 flex flex-wrap items-center gap-2 text-[12px]">
                    <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                        اولویت: {{ \App\Livewire\Admin\Notifications\NotificationIndex::priorityMeta((string) $this->detailNotification->priority)['label'] }}
                    </span>
                    @if ($targets['users'])
                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-300">
                            {{ $targets['users']['mode'] === 'all' ? 'ارسال شده به همه کاربران سایت' : 'کاربران انتخابی' }}
                            (<span class="fa-digits">{{ number_format($targets['users']['count']) }}</span>)
                        </span>
                    @endif
                    @if ($targets['sellers'])
                        <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                            {{ $targets['sellers']['mode'] === 'all' ? 'ارسال شده به همه فروشندگان' : 'فروشندگان انتخابی' }}
                            (<span class="fa-digits">{{ number_format($targets['sellers']['count']) }}</span>)
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    {{-- لیست کاربران ارسال شده --}}
                    <div class="rounded-2xl border border-line dark:border-night-line">
                        <p class="border-b border-line px-3 py-2.5 text-[13px] font-bold dark:border-night-line">لیست کاربران ارسال شده</p>
                        <div class="max-h-96 overflow-y-auto">
                            @if ($this->detailUsers && $this->detailUsers->count())
                                <table class="w-full text-start text-[12px]">
                                    <thead>
                                        <tr class="bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                            <th class="px-3 py-2 text-start font-bold">ID</th>
                                            <th class="px-3 py-2 text-start font-bold">نام و نام خانوادگی</th>
                                            <th class="px-3 py-2 text-start font-bold">موبایل</th>
                                            <th class="px-3 py-2 text-center font-bold">وضعیت</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($this->detailUsers as $recipient)
                                            <tr wire:key="detail-user-{{ $recipient->id }}" class="border-b border-line/60 last:border-0 dark:border-night-line/60">
                                                <td class="px-3 py-2 fa-digits font-bold">{{ number_format($recipient->user_id) }}</td>
                                                <td class="max-w-32 px-3 py-2">
                                                    <span class="flex items-center gap-1">
                                                        <span class="truncate font-semibold">
                                                            {{ $recipient->user ? (($recipient->user->fullname ?: $recipient->user->username) ?? '') : 'کاربر حذف‌شده' }}
                                                        </span>
                                                        @if ($recipient->user && \Illuminate\Support\Facades\Route::has('np.users.show'))
                                                            <a href="{{ route('np.users.show', ['user' => $recipient->user]) }}" target="_blank" rel="noopener"
                                                               class="shrink-0 text-brand-600 hover:text-brand-500 dark:text-brand-400" title="مشاهده کاربر">
                                                                <x-admin-panel::icon name="external-link" class="size-3" />
                                                            </a>
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2 fa-digits" dir="ltr">{{ $recipient->user?->mobile ?? '—' }}</td>
                                                <td class="px-3 py-2 text-center">
                                                    @if ($recipient->read)
                                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400" title="seen">
                                                            <x-admin-panel::icon name="eye" class="size-3.5" />
                                                            خوانده شده
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 text-[11px] text-ink-3 dark:text-night-ink-2">
                                                            <x-admin-panel::icon name="eye-off" class="size-3.5" />
                                                            خوانده نشده
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="px-3 py-6 text-center text-xs text-ink-3 dark:text-night-ink-2">این اعلان به کاربران سایت ارسال نشده است!</p>
                            @endif
                        </div>
                    </div>

                    {{-- لیست فروشندگان ارسال شده --}}
                    <div class="rounded-2xl border border-line dark:border-night-line">
                        <p class="border-b border-line px-3 py-2.5 text-[13px] font-bold dark:border-night-line">لیست فروشندگان ارسال شده</p>
                        <div class="max-h-96 overflow-y-auto">
                            @if ($this->detailSellers && $this->detailSellers->count())
                                <table class="w-full text-start text-[12px]">
                                    <thead>
                                        <tr class="bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                            <th class="px-3 py-2 text-start font-bold">ID</th>
                                            <th class="px-3 py-2 text-start font-bold">نام فروشگاه</th>
                                            <th class="px-3 py-2 text-start font-bold">نام و نام خانوادگی</th>
                                            <th class="px-3 py-2 text-start font-bold">موبایل</th>
                                            <th class="px-3 py-2 text-center font-bold">وضعیت</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($this->detailSellers as $recipient)
                                            <tr wire:key="detail-seller-{{ $recipient->id }}" class="border-b border-line/60 last:border-0 dark:border-night-line/60">
                                                <td class="px-3 py-2 fa-digits font-bold">{{ number_format($recipient->seller_id) }}</td>
                                                <td class="max-w-32 px-3 py-2">
                                                    <span class="flex items-center gap-1">
                                                        <span class="truncate font-semibold">{{ $recipient->seller?->seller_info?->business_name ?? '—' }}</span>
                                                        @if ($recipient->seller && \Illuminate\Support\Facades\Route::has('np.sellers.show'))
                                                            <a href="{{ route('np.sellers.show', ['seller' => $recipient->seller]) }}" target="_blank" rel="noopener"
                                                               class="shrink-0 text-brand-600 hover:text-brand-500 dark:text-brand-400" title="مشاهده فروشنده">
                                                                <x-admin-panel::icon name="external-link" class="size-3" />
                                                            </a>
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="max-w-32 px-3 py-2">
                                                    <span class="truncate">{{ $recipient->seller?->fullname ?? '—' }}</span>
                                                </td>
                                                <td class="px-3 py-2 fa-digits" dir="ltr">{{ $recipient->seller?->mobile ?? '—' }}</td>
                                                <td class="px-3 py-2 text-center">
                                                    @if ($recipient->read)
                                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400" title="seen">
                                                            <x-admin-panel::icon name="eye" class="size-3.5" />
                                                            خوانده شده
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 text-[11px] text-ink-3 dark:text-night-ink-2">
                                                            <x-admin-panel::icon name="eye-off" class="size-3.5" />
                                                            خوانده نشده
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="px-3 py-6 text-center text-xs text-ink-3 dark:text-night-ink-2">این اعلان به فروشندگان ارسال نشده است!</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- صفحه‌بندی مشترک گیرندگان (مثل فوتر قدیمی) --}}
                @if ($this->recipientLastPage > 1)
                    <div class="mt-4 flex items-center justify-between gap-3 border-t border-line pt-3 dark:border-night-line">
                        <p class="fa-digits text-xs text-ink-3 dark:text-night-ink-2">صفحه {{ $this->recipientPage }} از {{ $this->recipientLastPage }}</p>
                        <div class="flex items-center gap-1">
                            <button type="button" wire:click="recipientPrev" {{ $this->recipientPage <= 1 ? 'disabled' : '' }}
                                    class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-night-3"
                                    aria-label="صفحه قبل">
                                <x-admin-panel::icon name="chevron-right" class="size-4" />
                            </button>
                            <button type="button" wire:click="recipientNext" {{ $this->recipientPage >= $this->recipientLastPage ? 'disabled' : '' }}
                                    class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-night-3"
                                    aria-label="صفحه بعد">
                                <x-admin-panel::icon name="chevron-left" class="size-4" />
                            </button>
                        </div>
                    </div>
                @endif
            @else
                <div class="py-10 text-center text-sm text-ink-3 dark:text-night-ink-2">رکوردی یافت نشد</div>
            @endif

            <div class="mt-5">
                <button type="button" x-on:click="open=false" class="ap-btn-ghost w-full">بستن</button>
            </div>
        </div>
    </div>

    {{-- مودال ایجاد/ویرایش — پورت فرم nt-form-modal قدیمی --}}
    <div x-data="{ open: @entangle('showFormModal').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-base font-bold">
                    <x-admin-panel::icon name="send" class="size-5 text-brand-600 dark:text-brand-400" />
                    {{ $editingId ? 'ویرایش اعلان' : 'اعلان جدید' }}
                </h3>
                <button type="button" wire:click="closeFormModal"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            <form wire:submit="save" class="space-y-5 p-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold" for="notification-title">عنوان <span class="text-red-500">*</span></label>
                        <input id="notification-title" type="text" wire:model="title" maxlength="190" class="ap-input !py-2" placeholder="مثلاً: تخفیف ویژه پایان فصل">
                        @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="notification-priority">اولویت</label>
                        <select id="notification-priority" wire:model="priorityForm" class="ap-input !py-2">
                            <option value="low">عادی</option>
                            <option value="medium">متوسط</option>
                            <option value="high">فوری</option>
                        </select>
                        @error('priorityForm') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="notification-message">پیام <span class="text-red-500">*</span></label>
                    <textarea id="notification-message" rows="3" wire:model="message" maxlength="2000" class="ap-input" placeholder="متن اعلان…"></textarea>
                    @error('message') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="flex cursor-pointer items-center gap-2 text-[13px]">
                        <input type="checkbox" wire:model="popup"
                               class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                        نمایش به صورت پاپ‌آپ در پنل
                    </label>
                </div>

                @if ($editingId)
                    {{-- حالت ویرایش: گیرندگان ثابت (پورت update قدیمی) --}}
                    @if ($this->editingNotification)
                        @php $editTargets = $this->targets($this->editingNotification); @endphp
                        <div class="space-y-2 rounded-2xl border border-line p-4 dark:border-night-line">
                            <p class="text-[13px] font-bold">گیرندگان</p>
                            <div class="flex flex-wrap items-center gap-1.5">
                                @if ($editTargets['users'])
                                    <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-300">
                                        {{ $editTargets['users']['mode'] === 'all' ? 'همه کاربران' : 'کاربران انتخابی' }}
                                        (<span class="fa-digits">{{ number_format($editTargets['users']['count']) }}</span>)
                                    </span>
                                @endif
                                @if ($editTargets['sellers'])
                                    <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                                        {{ $editTargets['sellers']['mode'] === 'all' ? 'همه فروشندگان' : 'فروشندگان انتخابی' }}
                                        (<span class="fa-digits">{{ number_format($editTargets['sellers']['count']) }}</span>)
                                    </span>
                                @endif
                                @if (! $editTargets['users'] && ! $editTargets['sellers'])
                                    <span class="text-xs text-ink-3 dark:text-night-ink-2">—</span>
                                @endif
                            </div>
                            <p class="flex items-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                                <x-admin-panel::icon name="shield" class="size-3.5" />
                                گیرندگان پس از ایجاد قابل تغییر نیستند؛ فقط محتوای اعلان ویرایش می‌شود.
                            </p>
                        </div>
                    @endif
                @else
                    {{-- حالت ایجاد: کارت‌های انتخاب گیرنده (پورت nt-targets) --}}
                    <div class="space-y-3">
                        <p class="text-[13px] font-bold">گیرندگان <span class="text-red-500">*</span></p>
                        <p class="flex items-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                            <x-admin-panel::icon name="info" class="size-3.5" />
                            گروه مورد نظر را فعال کنید. اگر شخص خاصی انتخاب نشود، اعلان به همه اعضای همان گروه ارسال می‌شود.
                        </p>

                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            {{-- کارت کاربران --}}
                            <div class="space-y-3 rounded-2xl border p-4 transition-colors {{ $sendUsers ? 'border-brand-500 bg-brand-50/40 dark:border-brand-500/50 dark:bg-brand-500/5' : 'border-line dark:border-night-line' }}">
                                <label class="flex cursor-pointer items-center gap-2.5">
                                    <input type="checkbox" wire:model.live="sendUsers"
                                           class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                                        <x-admin-panel::icon name="users" class="size-4" />
                                    </span>
                                    <span>
                                        <span class="block text-[13px] font-bold">ارسال به کاربران سایت</span>
                                        <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">اعضای عادی سایت</span>
                                    </span>
                                </label>
                                @if ($sendUsers)
                                    @include('admin-panel::livewire.admin.discounts.partials.user-picker', [
                                        'label' => 'جستجوی کاربر (نام یا موبایل)',
                                        'placeholder' => 'جستجوی کاربر (نام یا موبایل)…',
                                        'searchField' => 'usersSearch',
                                        'resultsField' => 'usersResults',
                                        'results' => $usersResults,
                                        'selected' => $usersSelected,
                                        'addAction' => 'addUser',
                                        'removeAction' => 'removeUser',
                                        'errorField' => 'sendUsers',
                                    ])
                                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                                        اگر کاربری انتخاب نشود، به همه کاربران سایت ارسال می‌شود.
                                    </p>
                                @endif
                            </div>

                            {{-- کارت فروشندگان --}}
                            <div class="space-y-3 rounded-2xl border p-4 transition-colors {{ $sendSellers ? 'border-brand-500 bg-brand-50/40 dark:border-brand-500/50 dark:bg-brand-500/5' : 'border-line dark:border-night-line' }}">
                                <label class="flex cursor-pointer items-center gap-2.5">
                                    <input type="checkbox" wire:model.live="sendSellers"
                                           class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                                        <x-admin-panel::icon name="store" class="size-4" />
                                    </span>
                                    <span>
                                        <span class="block text-[13px] font-bold">ارسال به فروشندگان</span>
                                        <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">فروشندگان ثبت‌نام‌شده</span>
                                    </span>
                                </label>
                                @if ($sendSellers)
                                    @include('admin-panel::livewire.admin.discounts.partials.user-picker', [
                                        'label' => 'جستجوی فروشنده (نام یا موبایل)',
                                        'placeholder' => 'جستجوی فروشنده (نام یا موبایل)…',
                                        'searchField' => 'sellersSearch',
                                        'resultsField' => 'sellersResults',
                                        'results' => $sellersResults,
                                        'selected' => $sellersSelected,
                                        'addAction' => 'addSeller',
                                        'removeAction' => 'removeSeller',
                                        'errorField' => 'sendUsers',
                                    ])
                                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                                        اگر فروشنده‌ای انتخاب نشود، به همه فروشندگان ارسال می‌شود.
                                    </p>
                                @endif
                            </div>
                        </div>

                        @error('sendUsers') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="flex items-center justify-end gap-2.5 border-t border-line pt-4 dark:border-night-line">
                    <button type="button" wire:click="closeFormModal" class="ap-btn-ghost">انصراف</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save"
                            class="ap-btn">
                        <x-admin-panel::icon name="send" class="size-4" />
                        ذخیره و ارسال
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال تایید حذف --}}
    <div x-data="{ open: @entangle('confirmingDelete').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="trash-2" class="size-6" />
            </div>
            <h3 class="text-base font-bold">حذف اعلان</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                آیا از حذف این اعلان مطمئن هستید؟ ردیف‌های گیرندگان آن هم حذف می‌شود و این عمل قابل بازگشت نیست.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="search, previousPage, nextPage, gotoPage, save, togglePopup, delete, recipientPrev, recipientNext"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
