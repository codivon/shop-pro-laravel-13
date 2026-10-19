{{-- لاگ فعالیت‌ها — پنل جدید (فاز ۷-ب۴) — پورت ۱:۱ back/activity-log/index + partials/modal-details --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                کل فعالیت‌ها: <span class="fa-digits">{{ number_format($this->stats['total']) }}</span>
            </span>
            <span class="ap-badge bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400">
                ایجاد: <span class="fa-digits">{{ number_format($this->stats['created']) }}</span>
            </span>
            <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                ویرایش: <span class="fa-digits">{{ number_format($this->stats['updated']) }}</span>
            </span>
            <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                حذف: <span class="fa-digits">{{ number_format($this->stats['deleted']) }}</span>
            </span>
        </div>
        <button type="button" wire:click="$set('confirmingDeleteOld', true)"
                class="ap-btn-outline !py-2 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
            <x-admin-panel::icon name="trash-2" class="size-4" />
            حذف فعالیت‌های قدیمی
        </button>
    </div>

    {{-- ================== آمار (پورت کامل بخش آمار قدیمی) ================== --}}
    <div class="space-y-4">
        {{-- کارت‌های بازه زمانی --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="ap-card p-4">
                <div class="mb-3 flex items-center justify-between border-b border-line pb-2.5 dark:border-night-line">
                    <span class="flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="calendar" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        آمار امروز
                    </span>
                    <small class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">{{ $this->jdate(now()) }}</small>
                </div>
                <dl class="grid grid-cols-2 gap-2 text-[13px]">
                    <div class="flex items-center justify-between"><dt class="text-ink-2 dark:text-night-ink-2">کل فعالیت‌ها:</dt><dd class="fa-digits font-bold">{{ number_format($this->stats['today']['total']) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-ink-2 dark:text-night-ink-2">ایجاد:</dt><dd class="fa-digits font-bold text-green-600 dark:text-green-400">{{ number_format($this->stats['today']['created']) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-ink-2 dark:text-night-ink-2">ویرایش:</dt><dd class="fa-digits font-bold text-amber-600 dark:text-amber-400">{{ number_format($this->stats['today']['updated']) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-ink-2 dark:text-night-ink-2">حذف:</dt><dd class="fa-digits font-bold text-red-600 dark:text-red-400">{{ number_format($this->stats['today']['deleted']) }}</dd></div>
                </dl>
            </div>

            <div class="ap-card p-4">
                <div class="mb-3 flex items-center justify-between border-b border-line pb-2.5 dark:border-night-line">
                    <span class="flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="calendar-days" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        آمار این هفته
                    </span>
                    <small class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">{{ $this->jdate(now()) }}</small>
                </div>
                <dl class="grid grid-cols-2 gap-2 text-[13px]">
                    <div class="flex items-center justify-between"><dt class="text-ink-2 dark:text-night-ink-2">کل فعالیت‌ها:</dt><dd class="fa-digits font-bold">{{ number_format($this->stats['this_week']['total']) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-ink-2 dark:text-night-ink-2">ایجاد:</dt><dd class="fa-digits font-bold text-green-600 dark:text-green-400">{{ number_format($this->stats['this_week']['created']) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-ink-2 dark:text-night-ink-2">ویرایش:</dt><dd class="fa-digits font-bold text-amber-600 dark:text-amber-400">{{ number_format($this->stats['this_week']['updated']) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-ink-2 dark:text-night-ink-2">حذف:</dt><dd class="fa-digits font-bold text-red-600 dark:text-red-400">{{ number_format($this->stats['this_week']['deleted']) }}</dd></div>
                </dl>
            </div>

            <div class="ap-card p-4">
                <div class="mb-3 flex items-center justify-between border-b border-line pb-2.5 dark:border-night-line">
                    <span class="flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="calendar-days" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        آمار این ماه
                    </span>
                    <small class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">{{ $this->jdate(now(), 'F Y') }}</small>
                </div>
                <dl class="grid grid-cols-2 gap-2 text-[13px]">
                    <div class="flex items-center justify-between"><dt class="text-ink-2 dark:text-night-ink-2">کل فعالیت‌ها:</dt><dd class="fa-digits font-bold">{{ number_format($this->stats['this_month']['total']) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-ink-2 dark:text-night-ink-2">ایجاد:</dt><dd class="fa-digits font-bold text-green-600 dark:text-green-400">{{ number_format($this->stats['this_month']['created']) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-ink-2 dark:text-night-ink-2">ویرایش:</dt><dd class="fa-digits font-bold text-amber-600 dark:text-amber-400">{{ number_format($this->stats['this_month']['updated']) }}</dd></div>
                    <div class="flex items-center justify-between"><dt class="text-ink-2 dark:text-night-ink-2">حذف:</dt><dd class="fa-digits font-bold text-red-600 dark:text-red-400">{{ number_format($this->stats['this_month']['deleted']) }}</dd></div>
                </dl>
            </div>
        </div>

        {{-- کارت‌های تحلیل: بیشترین کاربران فعال + روزهای هفته --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="ap-card p-4">
                <div class="mb-3 flex items-center gap-2 border-b border-line pb-2.5 dark:border-night-line">
                    <x-admin-panel::icon name="users" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    <span class="text-sm font-bold">بیشترین کاربران فعال</span>
                </div>
                <div class="max-h-96 space-y-2 overflow-y-auto">
                    @forelse ($this->stats['top_users'] as $user)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-line bg-surface-2 px-3 py-2 dark:border-night-line dark:bg-night-3">
                            <div class="flex items-center gap-2.5">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                    {{ $user['type'] === 'Admin' ? 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-400' : ($user['type'] === 'Seller' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400') }}">
                                    {{ mb_substr($user['name'], 0, 1) }}
                                </span>
                                <div>
                                    <p class="text-[13px] font-bold">{{ $user['name'] }}</p>
                                    <small class="text-[11px] text-ink-3 dark:text-night-ink-2">{{ $user['type'] }}</small>
                                </div>
                            </div>
                            <span class="fa-digits whitespace-nowrap text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">{{ number_format($user['total']) }} فعالیت</span>
                        </div>
                    @empty
                        <p class="py-6 text-center text-[13px] text-ink-3 dark:text-night-ink-2">هیچ فعالیتی ثبت نشده</p>
                    @endforelse
                </div>
            </div>

            <div class="ap-card p-4">
                <div class="mb-3 flex items-center gap-2 border-b border-line pb-2.5 dark:border-night-line">
                    <x-admin-panel::icon name="chart-column" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    <span class="text-sm font-bold">فعالیت در روزهای هفته</span>
                </div>
                <div class="space-y-2.5">
                    @foreach ($this->stats['daily'] as $dayStat)
                        <div class="flex items-center gap-3">
                            <span class="w-16 shrink-0 text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">{{ $dayStat['day'] }}</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-surface-3 dark:bg-night-3">
                                <div class="h-full rounded-full bg-brand-500" style="width: {{ $this->stats['total'] > 0 ? ($dayStat['total'] / $this->stats['total']) * 100 : 0 }}%"></div>
                            </div>
                            <span class="fa-digits w-10 shrink-0 text-end text-[12px] font-bold">{{ number_format($dayStat['total']) }}</span>
                        </div>
                    @endforeach
                    @if (count($this->stats['daily']) === 0)
                        <p class="py-6 text-center text-[13px] text-ink-3 dark:text-night-ink-2">هیچ فعالیتی ثبت نشده</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ================== فیلترها (پورت فرم فیلتر قدیمی) ================== --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: {{ request()->except('page') ? 'true' : 'false' }} }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر کردن
                @if ($subjectType || $event || $causerId || $search || $fromDate || $toDate)
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
            <form wire:submit="applyFilters" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع مدل</label>
                    <select wire:model="subjectType" class="ap-input !py-2">
                        <option value="">همه</option>
                        @foreach ($this->subjectTypes as $type)
                            <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع عملیات</label>
                    <select wire:model="event" class="ap-input !py-2">
                        <option value="">همه</option>
                        @foreach ($this->events as $event)
                            <option value="{{ $event }}">
                                @switch($event)
                                    @case('created') ایجاد @break
                                    @case('updated') ویرایش @break
                                    @case('deleted') حذف @break
                                    @case('restored') بازیابی @break
                                    @default {{ $event }}
                                @endswitch
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">کاربر</label>
                    <select wire:model="causerId" class="ap-input !py-2">
                        <option value="">همه کاربران</option>
                        @foreach ($this->causers as $causer)
                            <option value="{{ $causer['id'] }}">{{ $causer['full_display'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">جستجو</label>
                    <input type="text" wire:model.live.debounce.400ms="search" class="ap-input !py-2" placeholder="جستجو در توضیحات...">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">از تاریخ</label>
                    <input type="text" wire:model="fromDate" data-jdp data-jdp-only-date="true"
                           class="ap-input !py-2 fa-digits text-center" placeholder="۱۴۰۴/۰۱/۰۱" autocomplete="off">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">تا تاریخ</label>
                    <input type="text" wire:model="toDate" data-jdp data-jdp-only-date="true"
                           class="ap-input !py-2 fa-digits text-center" placeholder="۱۴۰۴/۱۲/۲۹" autocomplete="off">
                </div>

                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-3">
                    <button type="submit" class="ap-btn !py-2">
                        <x-admin-panel::icon name="filter" class="size-4" />
                        فیلتر کردن
                    </button>
                    <button type="button" wire:click="resetFilters" class="ap-btn-outline !py-2">
                        <x-admin-panel::icon name="rotate-ccw" class="size-4" />
                        پاک کردن فیلترها
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================== جدول فعالیت‌ها ================== --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-16 px-3 py-3 text-start font-bold">#</th>
                        <th class="px-3 py-3 text-start font-bold">گزارش</th>
                        <th class="w-44 px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="w-24 px-3 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->activities as $item)
                        <tr wire:key="activity-{{ $item->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits text-ink-3">{{ $item->id }}</td>
                            <td class="px-3 py-3">
                                <div class="leading-7">{!! $item->report !!}</div>

                                @if ($item->extra_description)
                                    <p class="mt-1 ps-4 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $item->extra_description }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $item->date }}</td>
                            <td class="px-3 py-3 text-center">
                                <button type="button" wire:click="openDetail({{ $item->id }})"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-2.5 py-1.5 text-[12px] font-bold text-blue-700 transition-colors hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400"
                                        title="جزئیات">
                                    <x-admin-panel::icon name="eye" class="size-3.5" />
                                    جزئیات
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="history" class="size-10 opacity-40" />
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
                    نمایش <span class="fa-digits">{{ number_format($this->activities->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->activities->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->activities->total()) }}</span> فعالیت
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->activities->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->activities->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->activities->hasMorePages())
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

    {{-- ================== مودال جزئیات فعالیت (پورت show + modal-details قدیمی) ================== --}}
    <div x-data="{ open: @entangle('showDetail').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
                <h3 class="flex items-center gap-2 text-base font-bold">
                    <x-admin-panel::icon name="activity" class="size-5 text-brand-600 dark:text-brand-400" />
                    جزئیات فعالیت
                </h3>
                <button type="button" x-on:click="open=false" class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4.5" />
                </button>
            </div>

            <div class="space-y-4 overflow-y-auto p-5">
                @php $d = $this->detailData; @endphp

                @if ($d)
                    {{-- بخش جزئیات پایه --}}
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                        <span class="ap-badge
                            {{ $d['event_tone'] === 'green' ? 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' : '' }}
                            {{ $d['event_tone'] === 'amber' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : '' }}
                            {{ $d['event_tone'] === 'red' ? 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400' : '' }}
                            {{ $d['event_tone'] === 'blue' ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' : '' }}
                            {{ $d['event_tone'] === 'gray' ? 'bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2' : '' }}">
                            <x-admin-panel::icon name="{{ $d['event_icon'] }}" class="size-3.5" />
                            {{ $d['event'] }}
                        </span>
                        <span class="fa-digits text-[12px] text-ink-3 dark:text-night-ink-2">
                            #{{ $d['id'] }} — {{ $d['created_at'] }}
                            <small class="ms-1">({{ $d['created_at_diff'] }})</small>
                        </span>
                    </div>

                    {{-- گرید اطلاعات اصلی --}}
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-line p-3 dark:border-night-line">
                            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">نوع مدل</span>
                            <p class="mt-1 text-[13px] font-bold">
                                @if (isset($d['subject_link']))
                                    <a href="{{ $d['subject_link'] }}" target="_blank" class="inline-flex items-center gap-1 text-brand-600 hover:underline dark:text-brand-400">
                                        {{ $d['subject_type'] }}
                                        <x-admin-panel::icon name="external-link" class="size-3.5" />
                                    </a>
                                @else
                                    {{ $d['subject_type'] }}
                                @endif
                            </p>
                        </div>
                        <div class="rounded-xl border border-line p-3 dark:border-night-line">
                            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">شناسه مدل</span>
                            <p class="fa-digits mt-1 text-[13px] font-bold">#{{ $d['subject_id'] ?? '—' }}</p>
                        </div>
                        <div class="rounded-xl border border-line p-3 dark:border-night-line">
                            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">زمان ثبت</span>
                            <p class="fa-digits mt-1 text-[13px] font-bold">{{ $d['created_at'] }}</p>
                        </div>
                    </div>

                    {{-- انجام دهنده --}}
                    <div class="rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                        <span class="text-[11px] text-ink-3 dark:text-night-ink-2">انجام دهنده</span>
                        <div class="mt-1.5 flex items-center gap-2.5">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">
                                {{ mb_substr($d['causer_name'], 0, 1) }}
                            </span>
                            <div class="min-w-0">
                                <div class="leading-7">{!! $d['causer_html'] !!}</div>
                                <small class="text-[11px] text-ink-3 dark:text-night-ink-2">{{ $d['causer_type'] }}</small>
                            </div>
                        </div>
                    </div>

                    {{-- موضوع مورد نظر --}}
                    @if (isset($d['subject_html']) && $d['subject_html'] != $d['subject_type'])
                        <div class="rounded-xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">مربوط به</span>
                            <div class="mt-1 leading-7">{!! $d['subject_html'] !!}</div>
                        </div>
                    @endif

                    {{-- توضیحات --}}
                    @if (isset($d['description']) && $d['description'])
                        <div class="rounded-xl border border-line p-3.5 dark:border-night-line">
                            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">توضیحات</span>
                            <p class="mt-1 text-[13px] leading-6">{{ $d['description'] }}</p>
                        </div>
                    @endif

                    @if ($d['event'] != 'deleted' && $d['event'] != 'حذف')
                        @php
                            $translate = fn ($key) => $this->translateFieldName($key);
                            $props = $d['properties'];
                        @endphp

                        {{-- بخش تغییرات انجام شده --}}
                        @if (isset($d['properties']) && ($props['old'] || $props['attributes']))
                            <div class="rounded-xl border border-line p-3.5 dark:border-night-line">
                                <div class="mb-3 flex items-center justify-between border-b border-line pb-2.5 dark:border-night-line">
                                    <span class="flex items-center gap-2 text-sm font-bold">
                                        <x-admin-panel::icon name="pencil" class="size-4 text-brand-600 dark:text-brand-400" />
                                        تغییرات انجام شده
                                    </span>
                                    <span class="ap-badge bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">جزئیات تغییرات فیلدها</span>
                                </div>

                                @if (isset($props['old']) && count($props['old']) > 0)
                                    <div class="max-h-96 space-y-2.5 overflow-y-auto">
                                        @foreach ($props['old'] as $key => $oldValue)
                                            @php
                                                $newValue = $props['attributes'][$key] ?? null;
                                                $translatedKey = $translate($key);
                                                $displayOld = $this->processJsonValue($oldValue, $translate);
                                                $displayNew = $this->processJsonValue($newValue, $translate);
                                            @endphp
                                            <div class="rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                                                <p class="mb-1.5 flex items-center gap-1.5 text-[12px] font-bold">
                                                    <x-admin-panel::icon name="pencil" class="size-3.5 text-ink-3" />
                                                    {{ $translatedKey }}
                                                </p>
                                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                    <div class="rounded-lg bg-red-50 p-2 text-[12px] leading-6 dark:bg-red-500/5">
                                                        <span class="font-bold text-red-600 dark:text-red-400">قبلی:</span>
                                                        <span class="dark:text-night-ink-2">{!! $displayOld !!}</span>
                                                    </div>
                                                    <div class="rounded-lg bg-green-50 p-2 text-[12px] leading-6 dark:bg-green-500/5">
                                                        <span class="font-bold text-green-700 dark:text-green-400">جدید:</span>
                                                        <span class="dark:text-night-ink-2">{!! $displayNew !!}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif (isset($props['attributes']) && count($props['attributes']) > 0)
                                    <div class="mb-2.5 flex items-center gap-2 rounded-xl bg-blue-50 p-3 text-[12px] dark:bg-blue-500/5">
                                        <x-admin-panel::icon name="info" class="size-4 shrink-0 text-blue-600 dark:text-blue-400" />
                                        <div>
                                            <strong>عملیات ایجاد</strong>
                                            <p class="text-ink-3 dark:text-night-ink-2">مقادیر زیر برای این آیتم ثبت شده است</p>
                                        </div>
                                    </div>
                                    <div class="max-h-96 space-y-1.5 overflow-y-auto">
                                        @foreach ($props['attributes'] as $key => $value)
                                            @php
                                                $translatedKey = $translate($key);
                                                $displayValue = $this->processJsonValue($value, $translate);
                                            @endphp
                                            <div class="flex items-start justify-between gap-3 rounded-lg border border-line bg-surface-2 p-2.5 text-[12px] dark:border-night-line dark:bg-night-3">
                                                <span class="shrink-0 font-semibold text-ink-3 dark:text-night-ink-2">{{ $translatedKey }}</span>
                                                <span class="text-end leading-6 dark:text-night-ink-2">{!! $displayValue !!}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- بخش ویژگی‌های تنوع جدید (create_variation) — پورت partial قدیمی --}}
                        @if (isset($d['properties']['action']) && $d['properties']['action'] == 'create_variation')
                            @if (isset($props['attributes']) && count($props['attributes']) > 0)
                                <div class="rounded-xl border border-line p-3.5 dark:border-night-line">
                                    <div class="mb-3 flex items-center justify-between border-b border-line pb-2.5 dark:border-night-line">
                                        <span class="flex items-center gap-2 text-sm font-bold">
                                            <x-admin-panel::icon name="package" class="size-4 text-green-600 dark:text-green-400" />
                                            جزئیات تنوع جدید
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        @foreach ($props['attributes'] as $key => $value)
                                            @php
                                                $translatedKey = $translate($key);
                                                $displayValue = $this->processJsonValue($value, $translate);
                                            @endphp
                                            <div class="flex items-center justify-between gap-2 rounded-lg border border-line bg-surface-2 p-2.5 text-[12px] dark:border-night-line dark:bg-night-3">
                                                <span class="font-semibold text-ink-3 dark:text-night-ink-2">{{ $translatedKey }}</span>
                                                <span class="font-medium dark:text-night-ink-2">{!! $displayValue !!}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif

                        {{-- بخش تنوع حذف شده (delete_variation) — پورت partial قدیمی --}}
                        @if (isset($d['properties']['action']) && $d['properties']['action'] == 'delete_variation')
                            @if (isset($props['old']) && count($props['old']) > 0)
                                <div class="rounded-xl border border-line p-3.5 dark:border-night-line">
                                    <div class="mb-3 flex items-center justify-between border-b border-line pb-2.5 dark:border-night-line">
                                        <span class="flex items-center gap-2 text-sm font-bold">
                                            <x-admin-panel::icon name="trash-2" class="size-4 text-red-600 dark:text-red-400" />
                                            تنوع حذف شده
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        @foreach ($props['old'] as $key => $value)
                                            @php
                                                $translatedKey = $translate($key);
                                                $displayValue = $this->processJsonValue($value, $translate);
                                            @endphp
                                            <div class="flex items-center justify-between gap-2 rounded-lg border border-red-200 bg-red-50 p-2.5 text-[12px] dark:border-red-500/20 dark:bg-red-500/5">
                                                <span class="font-semibold text-red-600 dark:text-red-400">{{ $translatedKey }}</span>
                                                <span class="font-medium text-red-600 dark:text-red-400">{!! $displayValue !!}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endif
                @else
                    <p class="py-10 text-center text-[13px] text-ink-3 dark:text-night-ink-2">فعالیت یافت نشد.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- ================== مودال حذف فعالیت‌های قدیمی (پورت deleteOld قدیمی) ================== --}}
    <div x-data="{ open: @entangle('confirmingDeleteOld').live }"
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
            <h3 class="text-base font-bold">حذف فعالیت‌های قدیمی</h3>
            <div class="mt-4">
                <label class="mb-1.5 block text-xs font-semibold" for="delete-days">تعداد روز:</label>
                <input id="delete-days" type="number" wire:model="deleteDays" min="1" class="ap-input !py-2 fa-digits text-center" placeholder="مثلاً 30">
                @error('deleteDays') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">فعالیت‌های قدیمی‌تر از این تعداد روز حذف خواهند شد.</p>
            </div>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="deleteOld" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    حذف
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
