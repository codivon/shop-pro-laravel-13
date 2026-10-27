{{-- اعلان‌ها (فاز ۱۰۸) — پورت sellers-old/panel/notifications/index.blade.php
     فیلتر وضعیت/نمایش/خوانده‌نشده + کارت‌های بازشو اعلان + علامت‌گذاری «خوانده شد»
     با POST کلاسیک به seller.notifications.read (پاسخ JSON کنترلر قدیمی با fetch مدیریت می‌شود) --}}
<div x-data="{ readCount: 0 }">
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4">
        <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
            <x-admin-panel::icon name="bell" class="size-5 text-brand-600 dark:text-brand-400" />
            اعلان ها
        </h1>
        <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">اینجا می‌توانید اعلان‌های ارسال شده را ببینید</p>
    </div>

    {{-- ================= فیلترها (پورت فرم قدیمی — همان کلیدها priority/sort/unread) ================= --}}
    <section class="ap-card mb-4">
        <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-3">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">وضعیت اعلان‌ها</span>
                <select wire:model.live="priority" class="ap-input w-full">
                    <option value="all" @selected($priority === 'all')>همه</option>
                    <option value="high" @selected($priority === 'high')>بسیار مهم</option>
                    <option value="medium" @selected($priority === 'medium')>مهم</option>
                    <option value="low" @selected($priority === 'low')>عادی</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">حالت نمایش</span>
                <select wire:model.live="sort" class="ap-input w-full">
                    <option value="latest" @selected($sort === 'latest')>جدیدترین</option>
                    <option value="priority" @selected($sort === 'priority')>مهم ترین</option>
                </select>
            </label>
            <label class="flex min-h-11 cursor-pointer items-center gap-2 self-end px-1 text-xs font-bold text-ink-700 dark:text-night-ink-1">
                <input type="checkbox" wire:model.live="unread" class="size-4 rounded border-line accent-emerald-600">
                خوانده نشده
            </label>
        </div>
    </section>

    {{-- ================= لیست اعلان‌ها ================= --}}
    <section class="ap-card overflow-hidden">
        <header class="flex items-center justify-between border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="mail" class="size-4 text-brand-600 dark:text-brand-400" />
                لیست اعلان ها
            </h2>
            <span class="text-[11px] text-ink-500 dark:text-night-ink-2">
                تعداد نتایج: <span class="fa-digits font-bold">{{ number_format($notificationsCount) }}</span>
            </span>
        </header>

        @if ($notifications->count())
            <div class="divide-y divide-line/70 dark:divide-night-line/70">
                @foreach ($notifications as $notification)
                    @php
                        // ردیف pivot این فروشنده — مبنای بج «خوانده نشده» ویوی قدیمی
                        $usersNotifications = \Illuminate\Support\Facades\DB::table('notification_manage_users')
                            ->where(['seller_id' => sellerID(), 'notification_manage_id' => $notification->id])
                            ->first();
                        $isRead = $usersNotifications ? (bool) $usersNotifications->read : true;
                    @endphp
                    <div class="p-4"
                         x-data="{ open: false, isRead: @js($isRead) }">
                        <div class="flex min-h-11 items-center gap-3">
                            <button type="button" x-on:click="open = !open"
                                    class="flex flex-1 items-center gap-3 text-right"
                                    :aria-expanded="open" aria-controls="notification-{{ $notification->id }}-body">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-surface-2 text-ink-500 dark:bg-night-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="bell" class="size-4" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-1.5">
                                        @switch($notification->priorityText()['title'])
                                            @case('عادی')
                                                <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">عادی</span>
                                                @break
                                            @case('مهم')
                                                <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">مهم</span>
                                                @break
                                            @default
                                                <span class="ap-badge bg-red-100 text-red-600 dark:bg-red-500/15 dark:text-red-300">خیلی مهم</span>
                                        @endswitch
                                        @unless ($isRead)
                                            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300" x-show="!isRead">خوانده نشده</span>
                                        @endunless
                                    </span>
                                    <span class="mt-0.5 block truncate text-xs font-bold text-ink-800 dark:text-night-ink-1">{{ $notification->title }}</span>
                                </span>
                            </button>

                            <span class="fa-digits hidden shrink-0 text-[11px] text-ink-500 sm:block dark:text-night-ink-2">
                                {{ jdate($notification->created_at)->format('%d %B %Y') }}
                            </span>

                            {{-- علامت‌گذاری خوانده‌شدن — POST کلاسیک به seller.notifications.read --}}
                            <button type="button" x-show="!isRead" x-cloak
                                    class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-2.5 text-[11px] font-bold"
                                    x-on:click="
                                        const res = await fetch('{{ route('seller.notifications.read', ['notification' => $notification->id]) }}', {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'Accept': 'application/json',
                                            },
                                        });
                                        if (res.ok) {
                                            const data = await res.json().catch(() => ({}));
                                            if (data.status === 'success') { isRead = true; readCount = data.count; }
                                        }"
                                    aria-label="علامت‌گذاری به‌عنوان خوانده‌شده">
                                <x-admin-panel::icon name="check" class="size-3.5" />
                                خوانده شد
                            </button>
                        </div>

                        <div id="notification-{{ $notification->id }}-body" x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="-translate-y-1 opacity-0"
                             x-transition:enter-end="translate-y-0 opacity-100"
                             class="mt-3 rounded-xl border border-line bg-surface-2 p-3 text-xs leading-relaxed text-ink-700 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                            {!! $notification->message !!}
                            <p class="fa-digits mt-2 text-[11px] text-ink-400 dark:text-night-ink-3">{{ jdate($notification->created_at) }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="px-4 py-12 text-center text-sm text-ink-500 dark:text-night-ink-2">
                <x-admin-panel::icon name="inbox" class="mx-auto mb-2 size-8 text-ink-300 dark:text-night-ink-3" />
                چیزی برای نمایش وجود ندارد!
            </div>
        @endif

        <footer class="border-t border-line px-4 py-3 dark:border-night-line">
            {{ $notifications->links() }}
        </footer>
    </section>
</div>
