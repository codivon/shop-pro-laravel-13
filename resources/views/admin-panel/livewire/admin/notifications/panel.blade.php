{{-- اعلان‌های پنل — پورت back/notifications/panel.blade.php (فاز ۷-ج۴) --}}
<div class="mx-auto max-w-4xl space-y-5">

    {{-- نوار بالا (پورت np-topbar قدیمی) --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="flex items-center gap-2 text-lg font-bold">
            <span class="flex size-10 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="bell-ring" class="size-5" />
            </span>
            اعلان‌های سیستم
            @if ($this->stats['unread'] > 0)
                <span class="fa-digits ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">{{ number_format($this->stats['unread']) }} خوانده‌نشده</span>
            @endif
        </h1>
        @if ($this->stats['unread'] > 0)
            <button type="button" wire:click="markAllRead" wire:loading.attr="disabled" wire:target="markAllRead" class="ap-btn !py-2">
                <x-admin-panel::icon name="check-check" class="size-4" />
                همه را خوانده‌شده کن
            </button>
        @endif
    </div>

    {{-- آمار (پورت np-stats قدیمی) --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="ap-card flex items-center gap-3 p-4">
            <span class="flex size-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/12 dark:text-indigo-400">
                <x-admin-panel::icon name="bell" class="size-5" />
            </span>
            <div>
                <span class="fa-digits block text-lg font-extrabold">{{ number_format($this->stats['total']) }}</span>
                <span class="text-[11px] text-ink-3 dark:text-night-ink-2">کل اعلان‌ها</span>
            </div>
        </div>
        <div class="ap-card flex items-center gap-3 p-4">
            <span class="flex size-10 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">
                <x-admin-panel::icon name="circle-alert" class="size-5" />
            </span>
            <div>
                <span class="fa-digits block text-lg font-extrabold">{{ number_format($this->stats['unread']) }}</span>
                <span class="text-[11px] text-ink-3 dark:text-night-ink-2">خوانده‌نشده</span>
            </div>
        </div>
        <div class="ap-card flex items-center gap-3 p-4">
            <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                <x-admin-panel::icon name="activity" class="size-5" />
            </span>
            <div>
                <span class="fa-digits block text-lg font-extrabold">{{ number_format($this->stats['today']) }}</span>
                <span class="text-[11px] text-ink-3 dark:text-night-ink-2">امروز</span>
            </div>
        </div>
    </div>

    {{-- کارت اصلی با تب‌ها (پورت np-tabs قدیمی) --}}
    <div class="ap-card overflow-hidden">
        <div class="flex items-center gap-1 border-b border-line p-2 dark:border-night-line">
            @foreach (['all' => 'همه', 'unread' => 'خوانده‌نشده', 'read' => 'خوانده‌شده'] as $key => $label)
                <button type="button" wire:click="$set('filter', '{{ $key }}')"
                        class="rounded-xl px-3.5 py-2 text-[13px] font-semibold transition-colors {{ $filter === $key ? 'bg-brand-600 text-white' : 'text-ink-2 hover:bg-surface-2 dark:text-night-ink-2 dark:hover:bg-night-3' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($this->notifications->count())
            <div class="divide-y divide-line dark:divide-night-line">
                @foreach ($this->notifications as $notification)
                    @php
                        $meta = $this->meta($notification);
                    @endphp
                    @continue(!$meta['allowed'])

                    <div class="flex items-start gap-3 p-4 {{ $notification->read_at ? 'opacity-60' : '' }}">
                        {{-- آیکون نوع اعلان (پورت رنگ‌های AdminNotificationTypes) --}}
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl text-white"
                              style="background: linear-gradient(135deg, {{ $meta['c1'] }}, {{ $meta['c2'] }})">
                            <x-admin-panel::icon name="{{ match ($meta['icon']) {
                                'icon-shopping-cart' => 'shopping-cart',
                                'icon-user-plus', 'icon-user-check', 'icon-user' => 'user',
                                'icon-mail' => 'mail',
                                'icon-life-buoy' => 'life-buoy',
                                'icon-message-square', 'icon-message-circle' => 'message-circle',
                                'icon-help-circle' => 'help-circle',
                                'icon-credit-card' => 'credit-card',
                                'icon-package' => 'package',
                                'icon-refresh-cw' => 'refresh-cw',
                                'icon-alert-circle' => 'circle-alert',
                                default => 'bell',
                            } }}" class="size-4.5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-[13px] font-bold">
                                @unless ($notification->read_at)
                                    <span class="size-2 shrink-0 rounded-full bg-brand-500"></span>
                                @endunless
                                {{ $meta['title'] }}
                            </p>
                            <p class="mt-0.5 text-[12px] leading-5 text-ink-2 dark:text-night-ink-2">{{ $notification->data['message'] ?? '' }}</p>
                            <p class="fa-digits mt-1 text-[10px] text-ink-3 dark:text-night-ink-2" title="{{ jdate($notification->created_at) }}">
                                {{ jdate($notification->created_at)->ago() }}
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1.5">
                            @unless ($notification->read_at)
                                <button type="button" wire:click="markRead({{ \Illuminate\Support\Js::from($notification->id)->toHtml() }})"
                                        wire:loading.attr="disabled" wire:target="markRead"
                                        class="rounded-lg p-1.5 text-emerald-600 transition-colors hover:bg-emerald-50 dark:hover:bg-emerald-500/10"
                                        title="خوانده‌شده علامت‌گذاری">
                                    <x-admin-panel::icon name="check" class="size-4" />
                                </button>
                            @endunless

                            @php($notification_link = notification_link($notification))
                            @if ($notification_link)
                                <a href="{{ $notification_link }}" wire:navigate
                                   class="flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-[11px] font-semibold text-brand-600 transition-colors hover:bg-brand-50 dark:hover:bg-brand-500/10">
                                    <x-admin-panel::icon name="eye" class="size-3.5" />
                                    مشاهده
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- صفحه‌بندی --}}
            @if ($this->notifications->hasPages())
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
            @endif
        @else
            <div class="flex flex-col items-center gap-2 p-12 text-center">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                    <x-admin-panel::icon name="bell" class="size-6" />
                </span>
                <p class="text-sm font-bold">{{ $filter === 'unread' ? 'همه اعلان‌ها خوانده شده‌اند!' : 'چیزی برای نمایش وجود ندارد!' }}</p>
                @if ($filter !== 'all')
                    <button type="button" wire:click="$set('filter', 'all')" class="ap-btn-outline mt-1 !py-1.5 !text-xs">
                        نمایش همه اعلان‌ها
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>
