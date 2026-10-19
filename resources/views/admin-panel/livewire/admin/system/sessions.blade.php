{{-- نشست‌های فعال ادمین‌ها + دستگاه‌های بلاک‌شده (تب دوم) — پنل جدید (فاز ۷-ب۴) --}}
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
                                </td>
                                <td class="px-3 py-3">
                                    @if (! $isCurrent)
                                        <div class="flex items-center justify-center gap-0.5">
                                            @can('sessions.exit')
                                                <button type="button" wire:click="confirmDelete({{ $session->id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-500/10"
                                                        title="خروج اجباری" aria-label="خروج اجباری">
                                                    <x-admin-panel::icon name="log-out" class="size-4" />
                                                </button>
                                            @endcan

                                            @if (auth('adminPanel')->user()->isCreator() && $session->admin_id != $currentAdminId)
                                                <button type="button" wire:click="confirmDestroyAllAdmin({{ $session->admin_id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                        title="خروج از تمام دستگاه‌های این ادمین" aria-label="خروج از تمام دستگاه‌ها">
                                                    <x-admin-panel::icon name="triangle-alert" class="size-4" />
                                                </button>
                                            @endif

                                            @can('sessions.blocked')
                                                <button type="button" wire:click="openBlock({{ $session->id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                        title="بلاک دستگاه" aria-label="بلاک دستگاه">
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
                                        @if ($block->session_id)
                                            <span class="ap-badge bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">دستگاه</span>
                                        @endif
                                        @if ($block->ip_address)
                                            <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">آیپی</span>
                                        @endif
                                    </td>
                                    <td class="max-w-40 px-3 py-3">
                                        <code class="truncate text-[11px] text-ink-2 dark:text-night-ink-2" dir="ltr">{{ $block->session_id ?? $block->ip_address ?? '-' }}</code>
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

    {{-- ================== مودال بلاک دستگاه (پورت block-modal قدیمی) ================== --}}
    @can('sessions.blocked')
        <div x-data="{ open: @entangle('showBlockModal').live }"
             x-show="open" x-cloak
             class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
            <div x-show="open" x-on:click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
                 role="dialog" aria-modal="true">
                <div class="mb-4 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                    <x-admin-panel::icon name="shield" class="size-6" />
                </div>
                <h3 class="text-base font-bold">بلاک دستگاه</h3>

                <form wire:submit="blockSession" class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">نوع بلاک <span class="text-red-500">*</span></label>
                        <select wire:model="blockType" class="ap-input !py-2">
                            <option value="session">فقط این نشست</option>
                            <option value="ip">فقط این آیپی</option>
                            <option value="device">فقط این دستگاه (مرورگر + سیستم عامل)</option>
                            <option value="browser">فقط این مرورگر (همه نسخه‌ها)</option>
                            <option value="all">همه موارد (دستگاه + آیپی + مرورگر)</option>
                        </select>
                        @error('blockType') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">مدت زمان بلاک <span class="text-red-500">*</span></label>
                        <select wire:model="blockDuration" class="ap-input !py-2">
                            <option value="permanent">دائمی</option>
                            <option value="1day">1 روز</option>
                            <option value="1week">1 هفته</option>
                            <option value="1month">1 ماه</option>
                        </select>
                        @error('blockDuration') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">دلیل بلاک (اختیاری)</label>
                        <textarea wire:model="blockReason" rows="3" class="ap-input !py-2" placeholder="دلیل بلاک کردن این دستگاه..."></textarea>
                        @error('blockReason') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-2.5">
                        <button type="submit" wire:loading.attr="disabled" wire:target="blockSession"
                                class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                            بلاک شود
                        </button>
                        <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
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
