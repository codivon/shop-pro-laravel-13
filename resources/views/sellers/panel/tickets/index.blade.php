{{-- درخواست‌های پشتیبانی — فهرست (فاز ۱۰۸) — پورت sellers-old/panel/tickets/index.blade.php --}}
<div>
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
                <x-admin-panel::icon name="life-buoy" class="size-5 text-brand-600 dark:text-brand-400" />
                درخواست‌های شما
            </h1>
            <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">اینجا می‌توانید تمام درخواست‌های خود را ببینید.</p>
        </div>
        <a href="{{ $createUrl }}" wire:navigate
           class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
            <x-admin-panel::icon name="plus" class="size-4" />
            ثبت درخواست پشتیبانی
        </a>
    </div>

    {{-- ================= جدول درخواست‌ها ================= --}}
    <section class="ap-card overflow-hidden">
        <header class="border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="text-sm font-bold">لیست درخواست ها</h2>
        </header>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-right text-xs">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-600 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                        <th class="px-3 py-3 font-bold">#</th>
                        <th class="px-3 py-3 font-bold">شماره درخواست</th>
                        <th class="px-3 py-3 font-bold">موضوع</th>
                        <th class="px-3 py-3 font-bold">تاریخ ثبت درخواست</th>
                        <th class="px-3 py-3 text-center font-bold">اولویت</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت</th>
                        <th class="px-3 py-3 text-center font-bold">جزییات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr class="border-b border-line/70 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/70 dark:hover:bg-night-2">
                            <td class="fa-digits px-3 py-3 text-ink-500 dark:text-night-ink-2">{{ $loop->iteration }}</td>
                            <td class="fa-digits px-3 py-3 font-bold text-brand-700 dark:text-brand-400">{{ $ticket->id }}</td>
                            <td class="px-3 py-3 font-semibold text-ink-800 dark:text-night-ink-1">{{ $ticket->subject }}</td>
                            <td class="fa-digits px-3 py-3 text-ink-600 dark:text-night-ink-2">{{ jdate($ticket->created_at)->format('%d %B %Y') }}</td>
                            <td class="px-3 py-3 text-center">
                                @switch($ticket->priority)
                                    @case('low')
                                        <span class="ap-badge bg-surface-3 text-ink-600 dark:bg-night-3 dark:text-night-ink-2">کم</span>
                                        @break
                                    @case('medium')
                                        <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">متوسط</span>
                                        @break
                                    @default
                                        <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">زیاد</span>
                                @endswitch
                            </td>
                            <td class="px-3 py-3 text-center">
                                @switch($ticket->status)
                                    @case('pending')
                                        <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">در انتظار پاسخ</span>
                                        @break
                                    @case('answered')
                                        <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">پاسخ داده شده</span>
                                        @break
                                    @case('open')
                                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">باز</span>
                                        @break
                                    @default
                                        <span class="ap-badge bg-surface-3 text-ink-600 dark:bg-night-3 dark:text-night-ink-2">بسته</span>
                                @endswitch
                            </td>
                            <td class="px-3 py-3 text-center">
                                <a href="{{ route('seller.tickets.show', ['ticket' => $ticket]) }}" wire:navigate
                                   class="ap-btn-outline inline-flex min-h-11 items-center rounded-lg px-2.5 text-[11px] font-bold">
                                    نمایش جزئیات
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-ink-500 dark:text-night-ink-2">
                                <x-admin-panel::icon name="inbox" class="mx-auto mb-2 size-8 text-ink-300 dark:text-night-ink-3" />
                                چیزی برای نمایش وجود ندارد!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <footer class="border-t border-line px-4 py-3 dark:border-night-line">
            {{ $tickets->links() }}
        </footer>
    </section>
</div>
