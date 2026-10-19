{{-- فاز ۱۶ — زنگوله اعلان‌ها در هدر پنل جدید (جایگزین لینک به پنل قدیمی) --}}
<div class="relative"
     x-data="{ open: @entangle('open') }"
     x-on:click.outside="open = false"
     x-on:keydown.escape.window="open = false">

    <button type="button"
            wire:click="toggle"
            class="relative rounded-xl p-2.5 text-ink-2 transition-colors hover:bg-surface-3 hover:text-ink dark:text-night-ink-2 dark:hover:bg-night-3 dark:hover:text-night-ink"
            :aria-expanded="open.toString()"
            aria-haspopup="true"
            aria-label="اعلان‌ها" title="اعلان‌ها">
        <x-admin-panel::icon name="bell" class="size-5" />

        @if ($unread > 0)
            @php
                $faDigits = strtr((string) min($unread, 99), ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
            @endphp
            <span wire:key="unread-badge" class="fa-digits absolute -top-0.5 -end-0.5 flex min-w-4.5 items-center justify-center rounded-full bg-red-500 px-1 py-px text-[9px] font-extrabold leading-4 text-white shadow ring-2 ring-surface dark:ring-night-2">
                {{ $unread > 99 ? $faDigits.'+' : $faDigits }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 scale-95"
         class="absolute end-0 top-full z-50 mt-2 w-[21rem] origin-top overflow-hidden rounded-2xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-3"
         role="menu" aria-label="لیست اعلان‌های اخیر">

        {{-- سربرگ --}}
        <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-3 dark:border-night-line">
            <p class="flex items-center gap-1.5 text-[13px] font-bold">
                <x-admin-panel::icon name="bell" class="size-4 text-brand-600 dark:text-brand-400" />
                اعلان‌های اخیر
                @if ($unread > 0)
                    <span class="fa-digits rounded-full bg-red-50 px-1.5 py-0.5 text-[10px] font-extrabold text-red-600 dark:bg-red-500/10 dark:text-red-400">
                        {{ strtr($unread, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) }} جدید
                    </span>
                @endif
            </p>
            <button type="button" wire:click="markAllRead" wire:loading.attr="disabled" wire:target="markAllRead"
                    class="text-[11px] font-bold text-brand-600 transition-colors hover:text-brand-700 disabled:opacity-50 dark:text-brand-400"
                    @if ($unread === 0) disabled @endif>
                خواندن همه
            </button>
        </div>

        {{-- لیست --}}
        <div class="max-h-80 overflow-y-auto">
            @forelse ($this->latest as $notification)
                @php $m = $this->meta($notification); @endphp
                <div wire:key="hdr-notif-{{ $notification->id }}"
                     class="border-b border-line/60 last:border-0 dark:border-night-line/60">
                    @if ($m['link'])
                        <button type="button"
                                wire:click="markAndGo('{{ $notification->id }}', {{ \Illuminate\Support\Js::from($m['link'])->toHtml() }})"
                                wire:loading.attr="disabled" wire:target="markAndGo"
                                class="flex w-full items-start gap-2.5 px-4 py-3 text-start transition-colors hover:bg-surface-2 dark:hover:bg-night-2">
                            <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl text-white shadow-sm"
                                  style="background: linear-gradient(135deg, {{ $m['c1'] }} 0%, {{ $m['c2'] }} 100%);">
                                <x-admin-panel::icon :name="$m['lucide']" class="size-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[12.5px] font-bold text-ink dark:text-night-ink">
                                    {{ $m['title'] }}
                                </span>
                                <span class="mt-0.5 flex items-center gap-1 text-[10.5px] text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="clock" class="size-3" />
                                    {{ \App\Livewire\Admin\Notifications\HeaderNotifications::timeLabel($notification) }}
                                </span>
                            </span>
                            @if ($m['unread'])
                                <span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-500"></span>
                            @endif
                        </button>
                    @else
                        <div class="flex items-start gap-2.5 px-4 py-3 opacity-70">
                            <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl text-white shadow-sm"
                                  style="background: linear-gradient(135deg, {{ $m['c1'] }} 0%, {{ $m['c2'] }} 100%);">
                                <x-admin-panel::icon :name="$m['lucide']" class="size-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[12.5px] font-bold text-ink dark:text-night-ink">{{ $m['title'] }}</span>
                                <span class="mt-0.5 flex items-center gap-1 text-[10.5px] text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="clock" class="size-3" />
                                    {{ \App\Livewire\Admin\Notifications\HeaderNotifications::timeLabel($notification) }}
                                </span>
                            </span>
                            @if ($m['unread'])
                                <span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-500"></span>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="flex flex-col items-center gap-2 px-4 py-10 text-ink-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="bell-off" class="size-8 opacity-40" />
                    <p class="text-xs font-semibold">اعلان جدیدی ندارید</p>
                </div>
            @endforelse
        </div>

        {{-- فوتر --}}
        <a href="{{ route('np.notifications.panel') }}" wire:navigate x-on:click="open = false"
           class="flex items-center justify-center gap-1.5 border-t border-line bg-surface-2/60 px-4 py-2.5 text-[12px] font-bold text-brand-600 transition-colors hover:bg-brand-50 hover:text-brand-700 dark:border-night-line dark:bg-night-2/60 dark:text-brand-400 dark:hover:bg-brand-500/10"
           role="menuitem">
            مشاهده همه اعلان‌ها
            <x-admin-panel::icon name="arrow-left" class="size-3.5" />
        </a>
    </div>

    {{-- به‌روزرسانی دوره‌ای شمارنده (سبک — فقط یک رکوئست شمارش) --}}
    <div wire:poll.60s="refreshCount" class="hidden" aria-hidden="true"></div>
</div>
