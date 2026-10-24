{{-- لیست تیکت‌ها — پورت back/tickets/index.blade.php — پنل جدید (فاز ۷) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="life-buoy" class="size-3.5" />
                کل تیکت‌ها: <span class="fa-digits">{{ number_format($this->tickets->total()) }}</span>
            </span>
            <a href="{{ route('np.tickets.create') }}" wire:navigate class="ap-btn !py-2">
                <x-admin-panel::icon name="plus" class="size-4" />
                تیکت جدید
            </a>
        </div>
    </div>

    {{-- کارت فیلترها --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: {{ $search || $status ? 'true' : 'false' }} }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر تیکت‌ها
                @if ($search || $status)
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
                    <label class="mb-1.5 block text-xs font-semibold">جستجوی موضوع</label>
                    <input type="text" wire:model.live.debounce.400ms="search" class="ap-input !py-2" placeholder="موضوع تیکت…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت</label>
                    <select wire:model.live="status" class="ap-input !py-2">
                        <option value="">همه</option>
                        <option value="pending" @selected($status === 'pending')>در انتظار پاسخ</option>
                        <option value="answered" @selected($status === 'answered')>پاسخ داده شده</option>
                        <option value="open" @selected($status === 'open')>باز</option>
                        <option value="close" @selected($status === 'close')>بسته</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="ap-btn-outline !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        بازنشانی فیلترها
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
                        <th class="w-16 px-3 py-3 text-start font-bold">ID</th>
                        <th class="px-3 py-3 text-start font-bold">موضوع</th>
                        <th class="w-32 px-3 py-3 text-start font-bold">نوع کاربر</th>
                        <th class="w-44 px-3 py-3 text-start font-bold">کاربر مربوطه</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">اولویت</th>
                        <th class="w-32 px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->tickets as $ticket)
                        <tr wire:key="ticket-{{ $ticket->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($ticket->id) }}</td>
                            <td class="max-w-56 px-3 py-3">
                                <a href="{{ route('np.tickets.show', ['ticket' => $ticket]) }}" wire:navigate
                                   class="truncate font-semibold transition-colors hover:text-brand-600">
                                    {{ $ticket->subject }}
                                </a>
                            </td>
                            <td class="px-3 py-3">
                                @if ($ticket->user_id)
                                    <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">کاربر سایت</span>
                                @else
                                    <span class="ap-badge bg-violet-50 text-violet-700 dark:bg-violet-500/12 dark:text-violet-400">فروشنده</span>
                                @endif
                            </td>
                            <td class="max-w-44 px-3 py-3">
                                @if ($ticket->user_id)
                                    <a href="{{ route('np.users.show', ['user' => $ticket->user]) }}" wire:navigate
                                       class="truncate font-semibold transition-colors hover:text-brand-600">
                                        {{ $ticket->user->fullname ?: $ticket->user->mobile }}
                                    </a>
                                @elseif ($ticket->seller)
                                    <a href="{{ route('np.sellers.show', ['seller' => $ticket->seller]) }}" wire:navigate
                                       class="truncate font-semibold transition-colors hover:text-brand-600">
                                        {{ $ticket->seller->fullname }}
                                    </a>
                                @else
                                    <span class="text-ink-3">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @php $priority = $ticket->priority ?? 'low'; @endphp
                                <span class="ap-badge {{ $priority === 'hight'
                                    ? 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400'
                                    : ($priority === 'medium'
                                        ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400'
                                        : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400') }}">
                                    {{ \App\Livewire\Admin\Tickets\TicketIndex::priorityLabel($priority) }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                @php $status = $ticket->status; @endphp
                                <span class="inline-flex items-center gap-1.5 ap-badge {{ $status === 'answered'
                                    ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400'
                                    : ($status === 'open'
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400'
                                        : ($status === 'close'
                                            ? 'bg-slate-100 text-slate-600 dark:bg-slate-500/12 dark:text-slate-400'
                                            : 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400')) }}">
                                    <span class="size-1.5 rounded-full {{ $status === 'answered' ? 'bg-brand-500' : ($status === 'open' ? 'bg-emerald-500' : ($status === 'close' ? 'bg-slate-400' : 'bg-amber-500')) }}"></span>
                                    {{ \App\Livewire\Admin\Tickets\TicketIndex::statusLabel($status) }}
                                </span>
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($ticket->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <a href="{{ route('np.tickets.show', ['ticket' => $ticket]) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="نمایش تیکت" aria-label="نمایش">
                                        <x-admin-panel::icon name="eye" class="size-4" />
                                    </a>
                                    <button type="button" wire:click="confirmDelete({{ $ticket->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف تیکت" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="life-buoy" class="size-10 opacity-40" />
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
                    نمایش <span class="fa-digits">{{ number_format($this->tickets->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->tickets->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->tickets->total()) }}</span> تیکت
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->tickets->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->tickets->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->tickets->hasMorePages())
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
            <h3 class="text-base font-bold">حذف تیکت</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف تیکت دیگر قادر به بازیابی آن نخواهید بود؛ همه‌ی پیام‌ها و فایل‌های پیوست آن هم حذف می‌شوند.
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
    <div wire:loading wire:target="search, status, previousPage, nextPage, gotoPage, delete, applyFilters"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
