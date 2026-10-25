{{-- نشست‌های فعال ادمین‌ها + دستگاه‌های بلاک‌شده (تب دوم) — پنل جدید (فاز ۷-ب۴) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5" x-data="{ tab: '{{ $tab }}' }">

    {{-- سربرگ + عملیات creator --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                نشست‌های فعال: <span class="fa-digits">{{ number_format($this->sessions->total()) }}</span>
            </span>
            @can('sessions.blocked')
                <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                    بلاک‌ها: <span class="fa-digits">{{ number_format($this->blockedDevices->total()) }}</span>
                </span>
            @endcan
        </div>

        @if (auth('adminPanel')->user()->isCreator())
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="logoutOtherDevices"
                        wire:confirm="آیا از خارج شدن از تمام دستگاه‌های دیگر (به غیر از دستگاه فعلی) اطمینان دارید؟"
                        class="ap-btn-outline !py-2 text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-500/10">
                    <x-admin-panel::icon name="log-out" class="size-4" />
                    خروج از سایر دستگاه‌ها
                </button>
                <button type="button" wire:click="clearInactive"
                        wire:confirm="آیا از حذف نشست‌هایی که بیش از ۳۰ روز فعال نبوده‌اند اطمینان دارید؟"
                        class="ap-btn-outline !py-2">
                    <x-admin-panel::icon name="trash-2" class="size-4" />
                    پاکسازی غیرفعال
                </button>
            </div>
        @endif
    </div>

    {{-- تب‌ها --}}
    <div class="flex gap-1 rounded-xl border border-line bg-surface-2 p-1 dark:border-night-line dark:bg-night-3">
        <button type="button" x-on:click="tab = 'sessions'"
                class="flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2 text-[13px] font-bold transition-colors"
                :class="tab === 'sessions' ? 'bg-surface text-ink shadow-sm dark:bg-night-2 dark:text-night-ink' : 'text-ink-3 dark:text-night-ink-2'">
            <x-admin-panel::icon name="monitor" class="size-4" />
            نشست‌های فعال
        </button>
        @can('sessions.blocked')
            <button type="button" x-on:click="tab = 'blocked'"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg px-4 py-2 text-[13px] font-bold transition-colors"
                    :class="tab === 'blocked' ? 'bg-surface text-ink shadow-sm dark:bg-night-2 dark:text-night-ink' : 'text-ink-3 dark:text-night-ink-2'">
                <x-admin-panel::icon name="shield-off" class="size-4" />
                دستگاه‌های بلاک شده
            </button>
        @endcan
    </div>

    {{-- ================== تب ۱: نشست‌های فعال (پورت index قدیمی) ================== --}}
    <div x-show="tab === 'sessions'" x-cloak>
        <div class="ap-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-4xl text-start text-[13px]">
                    <thead>
                        <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                            <th class="w-14 px-3 py-3 text-start font-bold">#</th>
                            <th class="px-3 py-3 text-start font-bold">ادمین</th>
                            <th class="px-3 py-3 text-start font-bold">دستگاه</th>
                            <th class="px-3 py-3 text-start font-bold">مرورگر</th>
                            <th class="px-3 py-3 text-start font-bold">سیستم عامل</th>
                            <th class="px-3 py-3 text-start font-bold">آی‌پی</th>
                            <th class="px-3 py-3 text-start font-bold">آخرین فعالیت</th>
                            <th class="w-28 px-3 py-3 text-center font-bold">وضعیت</th>
                            <th class="w-36 px-3 py-3 text-center font-bold">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->sessions as $session)
                            @php $isCurrent = $session->session_id == $currentSessionId && $session->admin_id == $currentAdminId; @endphp
                            <tr wire:key="session-{{ $session->id }}"
                                class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                                <td class="px-3 py-3 fa-digits text-ink-3">{{ $loop->iteration }}</td>
                                <td class="px-3 py-3">
                                    <p class="font-bold">{{ $session->admin->fullname ?? $session->admin->username ?? 'نامشخص' }}</p>
                                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2" dir="ltr">{{ $session->admin->email ?? '' }}</p>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-admin-panel::icon name="{{ \App\Livewire\Admin\System\SessionIndex::deviceIcon($session->device_type) }}" class="size-4 text-ink-3" />
                                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">{{ $session->device_type_name }}</span>
                                    </span>
                                    @if ($session->device_name)
                                        <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $session->device_name }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-3">{{ $session->browser_name }}</td>
                                <td class="px-3 py-3">{{ $session->platform_name }}</td>
                                <td class="px-3 py-3 font-mono text-[11px]" dir="ltr">{{ $session->ip_address }}</td>
                                <td class="px-3 py-3 fa-digits whitespace-nowrap">
                                    {{ $this->jdate($session->last_activity, 'd F Y H:i') }}
                                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">{{ $session->last_activity_ago }}</p>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    @if ($isCurrent)
                                        <span class="ap-badge bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400">جلسه فعلی</span>
                                    @else
                                        <span class="ap-badge bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">فعال</span>
                                    @endif
                                    @if ($session->is_primary && $session->isPrimaryProtected())
                                        <span class="ap-badge mt-1 inline-flex items-center gap-1 bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400" title="سیستم اصلی — تا {{ $this->jdate($session->primary_until, 'd F H:i') }} از دستگاه دیگر قابل خروج/مسدودسازی نیست">
                                            <x-admin-panel::icon name="lock" class="size-3" />
                                            سیستم اصلی
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if (! $isCurrent)
                                        @php $protected = $this->isProtectedFromViewer($session); @endphp
                                        <div class="flex items-center justify-center gap-0.5">
                                            @can('sessions.exit')
                                                <button type="button" {{ $protected ? 'disabled' : '' }} wire:click="confirmDelete({{ $session->id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-amber-50 hover:text-amber-600 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-transparent dark:hover:bg-amber-500/10"
                                                        title="{{ $protected ? 'نشست اصلی — تا ۴۸ ساعت از دستگاه دیگر قابل خروج نیست' : 'خروج اجباری' }}"
                                                        aria-label="{{ $protected ? 'خروج اجباری غیرفعال' : 'خروج اجباری' }}">
                                                    <x-admin-panel::icon name="log-out" class="size-4" />
                                                </button>
                                            @endcan

                                            @if (auth('adminPanel')->user()->isCreator() && $session->admin_id != $currentAdminId)
                                                <button type="button" {{ $protected ? 'disabled' : '' }} wire:click="confirmDestroyAllAdmin({{ $session->admin_id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-transparent dark:hover:bg-red-500/10"
                                                        title="{{ $protected ? 'نشست اصلی — تا ۴۸ ساعت از دستگاه دیگر قابل خروج نیست' : 'خروج از تمام دستگاه‌های این ادمین' }}"
                                                        aria-label="{{ $protected ? 'خروج از تمام دستگاه‌ها غیرفعال' : 'خروج از تمام دستگاه‌ها' }}">
                                                    <x-admin-panel::icon name="triangle-alert" class="size-4" />
                                                </button>
                                            @endif

                                            @can('sessions.blocked')
                                                <button type="button" {{ $protected ? 'disabled' : '' }} wire:click="openBlock({{ $session->id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:bg-transparent dark:hover:bg-red-500/10"
                                                        title="{{ $protected ? 'نشست اصلی — تا ۴۸ ساعت از دستگاه دیگر قابل مسدودسازی نیست' : 'بلاک دستگاه' }}"
                                                        aria-label="{{ $protected ? 'بلاک غیرفعال' : 'بلاک دستگاه' }}">
                                                    <x-admin-panel::icon name="shield" class="size-4" />
                                                </button>
                                            @endcan
                                        </div>
                                    @else
                                        <span class="text-xs text-ink-3 dark:text-night-ink-2">جلسه فعلی</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="monitor" class="size-10 opacity-40" />
                                        <p class="text-sm font-medium">نشستی برای نمایش وجود ندارد</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- صفحه‌بندی نشست‌ها --}}
            <div class="border-t border-line px-4 py-3 dark:border-night-line">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-ink-3 dark:text-night-ink-2">
                        نمایش <span class="fa-digits">{{ number_format($this->sessions->firstItem() ?? 0) }}</span> تا
                        <span class="fa-digits">{{ number_format($this->sessions->lastItem() ?? 0) }}</span> از
                        <span class="fa-digits">{{ number_format($this->sessions->total()) }}</span> نشست
                    </p>
                    <nav class="flex items-center gap-1" aria-label="صفحه‌بندی نشست‌ها">
                        @if ($this->sessions->onFirstPage())
                            <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                        @else
                            <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                                <x-admin-panel::icon name="chevron-right" class="size-4" />
                            </button>
                        @endif

                        @foreach ($this->sessions->onEachSide(1)->toArray()['links'] as $link)
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

                        @if ($this->sessions->hasMorePages())
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
    </div>

    {{-- ================== تب ۲: دستگاه‌های بلاک شده (پورت blockedList قدیمی) ================== --}}
    @can('sessions.blocked')
        <div x-show="tab === 'blocked'" x-cloak>
            <div class="ap-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-4xl text-start text-[13px]">
                        <thead>
                            <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                                <th class="w-14 px-3 py-3 text-start font-bold">#</th>
                                <th class="px-3 py-3 text-start font-bold">ادمین</th>
                                <th class="w-32 px-3 py-3 text-start font-bold">نوع بلاک</th>
                                <th class="px-3 py-3 text-start font-bold">مقدار</th>
                                <th class="w-40 px-3 py-3 text-start font-bold">وضعیت</th>
                                <th class="px-3 py-3 text-start font-bold">دلیل</th>
                                <th class="px-3 py-3 text-start font-bold">تاریخ بلاک</th>
                                <th class="w-44 px-3 py-3 text-center font-bold">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->blockedDevices as $block)
                                <tr wire:key="block-{{ $block->id }}"
                                    class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                                    <td class="px-3 py-3 fa-digits text-ink-3">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-3 font-bold">{{ $block->admin->fullname ?? $block->admin->username ?? '' }}</td>
                                    <td class="px-3 py-3">
                                        @php $accountBan = $block->block_type === 'account'; @endphp
                                        @if ($accountBan)
                                            <span class="ap-badge inline-flex items-center gap-1 bg-red-600/10 text-red-700 dark:bg-red-500/15 dark:text-red-400">
                                                <x-admin-panel::icon name="user-lock" class="size-3" />
                                                بن اکانت
                                            </span>
                                        @else
                                            @if ($block->session_id)
                                                <span class="ap-badge bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">دستگاه</span>
                                            @endif
                                            @if ($block->ip_address)
                                                <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">آیپی</span>
                                            @endif
                                            @if (! $block->session_id && ! $block->ip_address)
                                                <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ $block->block_type_text }}</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="max-w-40 px-3 py-3">
                                        @if ($accountBan)
                                            <span class="text-[11px] font-semibold text-red-600 dark:text-red-400">کل حساب کاربری</span>
                                        @else
                                            <code class="truncate text-[11px] text-ink-2 dark:text-night-ink-2" dir="ltr">{{ $block->session_id ?? $block->ip_address ?? '-' }}</code>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">{{ $block->status_text }}</td>
                                    <td class="max-w-48 px-3 py-3">
                                        <p class="truncate text-ink-2 dark:text-night-ink-2">{{ $block->reason ?? '-' }}</p>
                                    </td>
                                    <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($block->created_at, 'd F Y H:i') }}</td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center justify-center gap-0.5">
                                            <button type="button" wire:click="confirmUnblock({{ $block->id }})"
                                                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-green-50 hover:text-green-600 dark:hover:bg-green-500/10"
                                                    title="رفع بلاک" aria-label="رفع بلاک">
                                                <x-admin-panel::icon name="shield-off" class="size-4" />
                                            </button>
                                            @if ($block->admin_id)
                                                <button type="button" wire:click="confirmClearBlocks({{ $block->admin_id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                        title="پاک‌سازی تمام بلاک‌های این ادمین" aria-label="پاک‌سازی بلاک‌های ادمین">
                                                    <x-admin-panel::icon name="trash-2" class="size-4" />
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                            <x-admin-panel::icon name="shield-off" class="size-10 opacity-40" />
                                            <p class="text-sm font-medium">چیزی برای نمایش وجود ندارد!</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- صفحه‌بندی بلاک‌ها --}}
                <div class="border-t border-line px-4 py-3 dark:border-night-line">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-ink-3 dark:text-night-ink-2">
                            نمایش <span class="fa-digits">{{ number_format($this->blockedDevices->firstItem() ?? 0) }}</span> تا
                            <span class="fa-digits">{{ number_format($this->blockedDevices->lastItem() ?? 0) }}</span> از
                            <span class="fa-digits">{{ number_format($this->blockedDevices->total()) }}</span> بلاک
                        </p>
                        <nav class="flex items-center gap-1" aria-label="صفحه‌بندی بلاک‌ها">
                            @if ($this->blockedDevices->onFirstPage())
                                <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                            @else
                                <button wire:click="previousPage('blockedPage')" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                                    <x-admin-panel::icon name="chevron-right" class="size-4" />
                                </button>
                            @endif

                            @foreach ($this->blockedDevices->onEachSide(1)->toArray()['links'] as $link)
                                @if ($link['label'] === '...')
                                    <span class="px-2 text-xs text-ink-3">…</span>
                                @elseif (! is_numeric(trim($link['label'])))
                                    {{-- دکمه‌های قبلی/بعدی بالاتر مدیریت شده‌اند --}}
                                @elseif ($link['active'])
                                    <span class="fa-digits flex size-8 items-center justify-center rounded-lg bg-brand-600 text-[13px] font-bold text-white">{{ $link['label'] }}</span>
                                @else
                                    <button wire:click="gotoPage({{ trim($link['label']) }}, 'blockedPage')"
                                            class="fa-digits flex size-8 items-center justify-center rounded-lg text-[13px] font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3">
                                        {{ $link['label'] }}
                                    </button>
                                @endif
                            @endforeach

                            @if ($this->blockedDevices->hasMorePages())
                                <button wire:click="nextPage('blockedPage')" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه بعد">
                                    <x-admin-panel::icon name="chevron-left" class="size-4" />
                                </button>
                            @else
                                <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-left" class="size-4" /></span>
                            @endif
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    {{-- ================== مودال بلاک دستگاه + بن اکانت — بازطراحی مدرن فاز ۷۲ ================== --}}
    @can('sessions.blocked')
        @php
            $target = $this->blockingSessionModel?->admin;
            $canAccountBan = auth('adminPanel')->user()->isCreator()
                && $target
                && $target->level !== 'creator'
                && $target->id !== auth('adminPanel')->id();
        @endphp
        <div x-data="{ open: @entangle('showBlockModal').live, type: @js($blockType), duration: @js($blockDuration) }"
             x-show="open" x-cloak
             class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
            <div x-show="open" x-on:click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                 class="relative flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-3xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2"
                 role="dialog" aria-modal="true" aria-label="مسدودسازی دسترسی">

                {{-- سربرگ --}}
                <div class="relative overflow-hidden border-b border-line bg-gradient-to-l from-red-50 via-red-50/40 to-transparent px-5 py-4 dark:border-night-line dark:from-red-500/10 dark:via-red-500/5 dark:to-transparent">
                    <div class="flex items-center gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-red-600 text-white shadow-lg shadow-red-600/25">
                            <x-admin-panel::icon name="shield-ban" class="size-5.5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <h3 class="text-[15px] font-extrabold">مسدودسازی دسترسی</h3>
                            <p class="text-[11px] text-ink-3 dark:text-night-ink-2">محدوده و مدت مسدودی را انتخاب کنید</p>
                        </span>
                        <button type="button" x-on:click="open=false"
                                class="rounded-xl p-2 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                aria-label="بستن">
                            <x-admin-panel::icon name="x" class="size-4.5" />
                        </button>
                    </div>
                </div>

                <form wire:submit="blockSession" class="flex-1 space-y-5 overflow-y-auto p-5" wire:loading.class="opacity-60 pointer-events-none" wire:target="blockSession">

                    {{-- کارت هدف --}}
                    @if ($this->blockingSessionModel)
                        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-line bg-surface-2 p-3.5 dark:border-night-line dark:bg-night-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-600/10 text-sm font-extrabold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                {{ mb_substr($target?->fullname ?? $target?->username ?? '؟', 0, 1) }}
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13px] font-bold">{{ $target?->fullname ?? $target?->username ?? 'نامشخص' }}</span>
                                <span class="block truncate text-[11px] text-ink-3 dark:text-night-ink-2" dir="ltr">{{ $target?->email ?? '' }}</span>
                            </span>
                            <span class="flex flex-wrap items-center gap-1.5">
                                <span class="ap-badge bg-surface text-ink-2 dark:bg-night-2 dark:text-night-ink-2">{{ $this->blockingSessionModel->device_type_name }}</span>
                                <span class="ap-badge bg-surface font-mono text-ink-2 dark:bg-night-2 dark:text-night-ink-2" dir="ltr">{{ $this->blockingSessionModel->ip_address }}</span>
                            </span>
                        </div>
                    @endif

                    {{-- نوع بلاک — کارت‌های انتخابی --}}
                    <div>
                        <p class="mb-2 text-xs font-bold">نوع بلاک <span class="text-red-500">*</span></p>
                        <div class="grid grid-cols-2 gap-2.5">
                            @foreach ([
                                ['value' => 'session', 'label' => 'این نشست',      'desc' => 'فقط همین نشست',   'icon' => 'monitor'],
                                ['value' => 'ip',      'label' => 'آیپی',          'desc' => 'همه نشست‌های این IP', 'icon' => 'network'],
                                ['value' => 'device',  'label' => 'دستگاه',        'desc' => 'مرورگر + سیستم‌عامل', 'icon' => 'smartphone'],
                                ['value' => 'browser', 'label' => 'مرورگر',        'desc' => 'همه نسخه‌های مرورگر', 'icon' => 'globe'],
                                ['value' => 'all',     'label' => 'همه موارد',     'desc' => 'دستگاه + آیپی + مرورگر', 'icon' => 'shield-alert'],
                            ] as $opt)
                                <label class="relative block cursor-pointer">
                                    <input type="radio" name="blockType" value="{{ $opt['value'] }}" wire:model="blockType"
                                           class="peer sr-only" x-on:change="type = '{{ $opt['value'] }}'">
                                    {{-- فاز ۸۴ — انتخاب همیشه‌مشخص: علاوه بر peer-checked، بایندینگ مستقیم $wire
                                         (بوردر + پس‌زمینهٔ اینلاین) تا حتی اگر CSS پنل کهنه بود هم معلوم باشد کدام فعال است --}}
                                    <div class="flex items-center gap-2.5 rounded-2xl border p-3 transition-all duration-200 border-line bg-surface-2 hover:border-red-300/60 dark:border-night-line dark:bg-night-3 dark:hover:border-red-500/30 peer-checked:border-red-500 peer-checked:bg-red-50/70 dark:peer-checked:border-red-500/60 dark:peer-checked:bg-red-500/10"
                                         :style="$wire.blockType === '{{ $opt['value'] }}' ? 'border-color:#e11d48;background-color:rgba(225,29,72,.09);box-shadow:0 0 0 3px rgba(225,29,72,.12)' : ''">
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-surface text-ink-3 dark:bg-night-2 dark:text-night-ink-2">
                                            <x-admin-panel::icon name="{{ $opt['icon'] }}" class="size-4" />
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-[12.5px] font-bold">{{ $opt['label'] }}</span>
                                            <span class="block truncate text-[10px] text-ink-3 dark:text-night-ink-2">{{ $opt['desc'] }}</span>
                                        </span>
                                    </div>
                                    <span class="pointer-events-none absolute end-2 top-1/2 flex size-5 -translate-y-1/2 scale-50 items-center justify-center rounded-full bg-red-600 text-white opacity-0 shadow transition-all duration-200 peer-checked:scale-100 peer-checked:opacity-100"
                                          x-show="$wire.blockType === '{{ $opt['value'] }}'" x-cloak
                                          :style="$wire.blockType === '{{ $opt['value'] }}' ? 'opacity:1;transform:translateY(-50%) scale(1)' : 'opacity:0;transform:translateY(-50%) scale(.5)'">
                                        <x-admin-panel::icon name="check" class="size-3" />
                                    </span>
                                </label>
                            @endforeach

                            {{-- بن اکانت — فقط سازنده و فقط برای مدیران --}}
                            @if ($canAccountBan)
                                <label class="relative block col-span-2 cursor-pointer">
                                    <input type="radio" name="blockType" value="account" wire:model="blockType"
                                           class="peer sr-only" x-on:change="type = 'account'">
                                    <div class="flex items-center gap-3 rounded-2xl border p-3.5 transition-all duration-200 border-red-200 bg-gradient-to-l from-red-50/90 via-red-50/40 to-transparent hover:border-red-400/70 dark:border-red-500/20 dark:from-red-500/10 dark:via-red-500/5 dark:to-transparent dark:hover:border-red-500/40 peer-checked:border-red-600 peer-checked:bg-red-50 peer-checked:shadow-md peer-checked:shadow-red-600/10 dark:peer-checked:bg-red-500/10"
                                         :style="$wire.blockType === 'account' ? 'border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.14)' : ''">
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-red-600 text-white shadow-lg shadow-red-600/25">
                                            <x-admin-panel::icon name="user-lock" class="size-5" />
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-center gap-2 text-[13px] font-extrabold">
                                                بن اکانت
                                                <span class="ap-badge bg-red-600/10 text-[10px] text-red-700 dark:bg-red-500/15 dark:text-red-400">ویژه سازنده</span>
                                            </span>
                                            <span class="block text-[10.5px] leading-4 text-ink-3 dark:text-night-ink-2">کل حساب مسدود می‌شود؛ همهٔ نشست‌ها بلافاصله خارج می‌شوند</span>
                                        </span>
                                    </div>
                                    <span class="pointer-events-none absolute end-3 top-1/2 flex size-5 -translate-y-1/2 scale-50 items-center justify-center rounded-full bg-red-600 text-white opacity-0 shadow transition-all duration-200 peer-checked:scale-100 peer-checked:opacity-100"
                                          x-show="$wire.blockType === 'account'" x-cloak
                                          :style="$wire.blockType === 'account' ? 'opacity:1;transform:translateY(-50%) scale(1)' : 'opacity:0;transform:translateY(-50%) scale(.5)'">
                                        <x-admin-panel::icon name="check" class="size-3" />
                                    </span>
                                </label>
                            @endif
                        </div>
                        @error('blockType') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- هشدار بن اکانت --}}
                    @if ($canAccountBan)
                        <div x-show="type === 'account'" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                             class="flex items-start gap-2.5 rounded-2xl border border-red-200 bg-red-50 p-3.5 dark:border-red-500/30 dark:bg-red-500/10">
                            <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-4.5 shrink-0 text-red-600 dark:text-red-400" />
                            <p class="text-[12px] leading-6 text-red-700 dark:text-red-300">
                                <b>بن اکانت</b> کل حساب این مدیر را قفل می‌کند: تمام نشست‌های فعال او بلافاصله خروج می‌شوند و تا رفع مسدودی (فقط توسط سازنده) نمی‌تواند وارد شود.
                            </p>
                        </div>
                    @endif

                    {{-- مدت زمان — فاز ۷۳: بازطراحی کاشی‌های آیکون‌دار + مدت سفارشی --}}
                    <div>
                        <p class="mb-2 text-xs font-bold">مدت زمان بلاک <span class="text-red-500">*</span></p>

                        <div class="grid grid-cols-3 gap-2">
                            @foreach ([
                                ['value' => 'permanent', 'label' => 'دائمی',   'sub' => 'تا رفع دستی', 'icon' => 'infinity'],
                                ['value' => '12hours',   'label' => '۱۲ ساعت', 'sub' => 'نیم‌روز',     'icon' => 'timer'],
                                ['value' => '1day',      'label' => '۱ روز',   'sub' => '۲۴ ساعت',     'icon' => 'clock'],
                                ['value' => '1week',     'label' => '۱ هفته',  'sub' => '۷ روز',       'icon' => 'calendar-days'],
                                ['value' => '1month',    'label' => '۱ ماه',   'sub' => '۳۰ روز',      'icon' => 'calendar'],
                                ['value' => 'custom',    'label' => 'سفارشی',  'sub' => 'دلخواه',      'icon' => 'sliders-horizontal'],
                            ] as $d)
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="blockDuration" value="{{ $d['value'] }}" wire:model="blockDuration"
                                           class="peer sr-only" x-on:change="duration = '{{ $d['value'] }}'">
                                    {{-- فاز ۸۴ — کاشی انتخاب‌شده: بوردر + تیک با بایندینگ مستقیم $wire (مستقل از CSS) --}}
                                    <div class="flex flex-col items-center gap-1 rounded-2xl border p-2.5 text-center text-ink-2 transition-all duration-200 border-line bg-surface-2 hover:border-red-300/60 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2 dark:hover:border-red-500/30 peer-checked:border-red-500 peer-checked:bg-red-50/70 peer-checked:text-red-700 peer-checked:shadow-sm peer-checked:shadow-red-600/10 dark:peer-checked:border-red-500/60 dark:peer-checked:bg-red-500/10 dark:peer-checked:text-red-300"
                                         :style="$wire.blockDuration === '{{ $d['value'] }}' ? 'border-color:#e11d48;background-color:rgba(225,29,72,.09);box-shadow:0 0 0 3px rgba(225,29,72,.12)' : ''">
                                        <span class="shrink-0">
                                            <x-admin-panel::icon name="{{ $d['icon'] }}" class="size-4" />
                                        </span>
                                        <span class="fa-digits text-[12px] font-extrabold leading-none">{{ $d['label'] }}</span>
                                        <span class="text-[9.5px] leading-3 opacity-60">{{ $d['sub'] }}</span>
                                    </div>
                                    <span class="pointer-events-none absolute end-2 top-2 flex size-4.5 items-center justify-center rounded-full bg-red-600 text-white shadow transition-all duration-200"
                                          x-show="$wire.blockDuration === '{{ $d['value'] }}'" x-cloak
                                          :style="$wire.blockDuration === '{{ $d['value'] }}' ? 'opacity:1;transform:scale(1)' : 'opacity:0;transform:scale(.5)'">
                                        <x-admin-panel::icon name="check" class="size-2.5" />
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        {{-- مدت سفارشی — مقدار + واحد --}}
                        <div x-show="duration === 'custom'" x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                             class="mt-2.5">
                            <div class="flex items-center gap-2 rounded-2xl border border-line bg-surface-2 p-2 dark:border-night-line dark:bg-night-3">
                                <input type="number" min="1" max="999" inputmode="numeric" wire:model="blockCustomAmount"
                                       class="ap-input fa-digits !w-24 text-center" placeholder="۱۰"
                                       aria-label="مقدار مدت سفارشی">
                                <div class="grid flex-1 grid-cols-4 gap-1 rounded-xl bg-surface p-1 dark:bg-night-2" role="group" aria-label="واحد مدت">
                                    @foreach ([['v' => 'hours', 'l' => 'ساعت'], ['v' => 'days', 'l' => 'روز'], ['v' => 'weeks', 'l' => 'هفته'], ['v' => 'months', 'l' => 'ماه']] as $u)
                                        <label class="relative cursor-pointer">
                                            <input type="radio" name="blockCustomUnit" value="{{ $u['v'] }}" wire:model="blockCustomUnit"
                                                   class="peer sr-only">
                                            {{-- فاز ۸۴ — واحد انتخاب‌شده با تیک/پس‌زمینهٔ مستقل از CSS --}}
                                            <span class="block rounded-lg py-1.5 text-center text-[11.5px] font-bold text-ink-3 transition-all duration-200 hover:text-ink-2 peer-checked:bg-red-600 peer-checked:text-white peer-checked:shadow-md peer-checked:shadow-red-600/25 dark:text-night-ink-2 dark:hover:text-white"
                                                  :style="$wire.blockCustomUnit === '{{ $u['v'] }}' ? 'background-color:#dc2626;color:#ffffff' : ''">
                                                {{ $u['l'] }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            @error('blockCustomAmount') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            @error('blockCustomUnit') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- توضیح وضعیت انتخاب فعلی --}}
                        <p class="mt-2 flex items-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                            <x-admin-panel::icon name="info" class="size-3.5 shrink-0" />
                            <span x-show="duration === 'permanent'">دسترسی تا زمانی که خودتان رفعش نکنید مسدود می‌ماند</span>
                            <span x-show="duration === 'custom'" x-cloak>مسدودی پس از پایان مدت واردشده، خودکار برداشته می‌شود</span>
                            <span x-show="duration !== 'permanent' && duration !== 'custom'">پس از پایان این مدت، مسدودی خودکار برداشته می‌شود</span>
                        </p>

                        @error('blockDuration') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- دلیل --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-bold" for="block-reason">دلیل بلاک <small class="font-normal text-ink-3">(اختیاری)</small></label>
                        <textarea id="block-reason" wire:model="blockReason" rows="3" class="ap-input !py-2.5" placeholder="دلیل مسدودسازی برای ثبت در گزارش‌ها…"></textarea>
                        @error('blockReason') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- دکمه‌ها --}}
                    <div class="flex items-center gap-2.5 border-t border-line pt-4 dark:border-night-line">
                        <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1 !py-2.5">انصراف</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="blockSession"
                                class="flex flex-1 items-center justify-center gap-2 rounded-xl !py-2.5 text-sm font-bold text-white transition-colors disabled:opacity-50"
                                :class="type === 'account' ? 'bg-red-700 hover:bg-red-800' : 'bg-red-600 hover:bg-red-700'">
                            <x-admin-panel::icon name="shield-ban" class="size-4" />
                            @if ($canAccountBan)
                                <span x-text="type === 'account' ? 'بن اکانت' : 'بلاک شود'"></span>
                            @else
                                <span>بلاک شود</span>
                            @endif
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    {{-- مودال تایید خروج اجباری یک نشست (پورت پیام destroy) --}}
    <div x-data="{ open: @entangle('confirmingDelete').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                <x-admin-panel::icon name="log-out" class="size-6" />
            </div>
            <h3 class="text-base font-bold">آیا مطمئن هستید؟</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                ادمین {{ $this->deletingSession?->admin->fullname ?? $this->deletingSession?->admin->username ?? '' }} از دستگاه خود خارج خواهد شد.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="destroySession" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله، خارج شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- مودال تایید حذف تمام نشست‌های یک ادمین (پورت destroyAllAdminSessions) --}}
    <div x-data="{ open: @entangle('confirmingDestroyAllAdmin').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="triangle-alert" class="size-6" />
            </div>
            <h3 class="text-base font-bold">آیا مطمئن هستید؟</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                تمامی نشست‌های فعال ادمین «{{ $this->destroyingAllAdmin?->fullname ?? $this->destroyingAllAdmin?->username ?? '' }}» به جز جلسه فعلی (اگر خودتان هستید) حذف خواهد شد.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="destroyAllAdminSessions" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله، حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- مودال تایید رفع بلاک (پورت unblockDevice) --}}
    @can('sessions.blocked')
        <div x-data="{ open: @entangle('confirmingUnblock').live }"
             x-show="open" x-cloak
             class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
            <div x-show="open" x-on:click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
                 role="dialog" aria-modal="true">
                <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400">
                    <x-admin-panel::icon name="shield-off" class="size-6" />
                </div>
                <h3 class="text-base font-bold">رفع بلاک</h3>
                <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                    آیا از رفع بلاک این دستگاه اطمینان دارید؟
                </p>
                <div class="mt-5 flex gap-2.5">
                    <button type="button" wire:click="unblockDevice" wire:loading.attr="disabled"
                            class="ap-btn flex-1 bg-green-600 text-white hover:bg-green-700">
                        بله
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">خیر</button>
                </div>
            </div>
        </div>

        {{-- مودال تایید پاک‌سازی بلاک‌های یک ادمین (پورت clearAdminBlocks) --}}
        <div x-data="{ open: @entangle('confirmingClearBlocks').live }"
             x-show="open" x-cloak
             class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
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
                    تمام بلاک‌های ادمین «{{ $this->clearingBlocksAdmin?->fullname ?? $this->clearingBlocksAdmin?->username ?? '' }}» حذف خواهند شد.
                </p>
                <div class="mt-5 flex gap-2.5">
                    <button type="button" wire:click="clearAdminBlocks" wire:loading.attr="disabled"
                            class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                        بله، حذف شود
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </div>
        </div>
    @endcan

    {{-- لودینگ --}}
    <div wire:loading
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
