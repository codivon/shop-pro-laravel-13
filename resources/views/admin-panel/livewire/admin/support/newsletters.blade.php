{{-- خبرنامه — پورت back/newsletters/{index,show}.blade.php — پنل جدید (فاز ۷) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                <x-admin-panel::icon name="mail" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-extrabold">خبرنامه</h2>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">لیست مشترکین خبرنامه (ایمیل و شماره موبایل)</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="$set('exportModal', true)" class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="download" class="size-4" />
                خروجی اکسل
            </button>
        </div>
    </div>

    {{-- آمار ۵ کارته (پورت index قدیمی) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">کل مشترکین</p>
                <span class="ap-stat-icon bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="users" class="size-4.5" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-2xl font-extrabold">{{ number_format($this->statistics['total']) }}</p>
        </div>
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">فعال</p>
                <span class="ap-stat-icon bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                    <x-admin-panel::icon name="check" class="size-4.5" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-2xl font-extrabold">{{ number_format($this->statistics['active']) }}</p>
        </div>
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">غیرفعال</p>
                <span class="ap-stat-icon bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">
                    <x-admin-panel::icon name="x" class="size-4.5" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-2xl font-extrabold">{{ number_format($this->statistics['inactive']) }}</p>
        </div>
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">ایمیل</p>
                <span class="ap-stat-icon bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                    <x-admin-panel::icon name="mail" class="size-4.5" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-2xl font-extrabold">{{ number_format($this->statistics['email']) }}</p>
        </div>
        <div class="ap-card p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <p class="text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">شماره موبایل</p>
                <span class="ap-stat-icon bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                    <x-admin-panel::icon name="smartphone" class="size-4.5" />
                </span>
            </div>
            <p class="fa-digits mt-2 text-2xl font-extrabold">{{ number_format($this->statistics['mobile']) }}</p>
        </div>
    </div>

    {{-- کارت فیلترها --}}
    <div class="ap-card p-4 sm:p-5" x-data="{ open: false }">
        <button type="button" class="flex w-full items-center justify-between text-start" x-on:click="open = !open">
            <span class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="filter" class="size-4.5 text-brand-600 dark:text-brand-400" />
                فیلتر مشترکین
                @if ($search || $contactType || $status || $deviceType)
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
            <form wire:submit.prevent="applyFilters" class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">جستجو</label>
                    <input type="text" wire:model.live.debounce.400ms="search" class="ap-input !py-2" placeholder="ایمیل یا شماره موبایل…">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع اشتراک</label>
                    <select wire:model.live="contactType" class="ap-input !py-2">
                        <option value="">همه</option>
                        <option value="email">ایمیل</option>
                        <option value="mobile">شماره موبایل</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">وضعیت</label>
                    <select wire:model.live="status" class="ap-input !py-2">
                        <option value="">همه</option>
                        <option value="active">فعال</option>
                        <option value="inactive">غیرفعال</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">نوع دستگاه</label>
                    <select wire:model.live="deviceType" class="ap-input !py-2">
                        <option value="">همه</option>
                        <option value="desktop">دسکتاپ</option>
                        <option value="mobile">موبایل</option>
                        <option value="tablet">تبلت</option>
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

    {{-- نوار عملیات گروهی --}}
    <div x-show="$wire.selected.length > 0" x-cloak
         class="sticky top-20 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-200 bg-brand-50/95 px-4 py-2.5 shadow-card backdrop-blur dark:border-brand-500/25 dark:bg-brand-950/80">
        <span class="text-[13px] font-bold text-brand-800 dark:text-brand-300">
            <span class="fa-digits" x-text="ApFormat.faDigits($wire.selected.length)"></span> مشترک انتخاب شده
        </span>
        @can('newsletters.delete')
            <button type="button" wire:click="confirmBulkDelete"
                    class="ap-btn !px-3 !py-1.5 text-[13px] bg-red-600 text-white hover:bg-red-700">
                <x-admin-panel::icon name="trash-2" class="size-4" />
                حذف موارد انتخاب شده
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
                        <th class="w-16 px-3 py-3 text-start font-bold">شناسه</th>
                        <th class="px-3 py-3 text-start font-bold">ایمیل / شماره موبایل</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">نوع</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="w-40 px-3 py-3 text-start font-bold">دستگاه / مرورگر / سیستم‌عامل</th>
                        <th class="w-36 px-3 py-3 text-start font-bold">IP</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">تاریخ ثبت‌نام</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->subscribers as $subscriber)
                        <tr wire:key="newsletter-{{ $subscriber->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <input type="checkbox" wire:model.live="selected" value="{{ $subscriber->id }}"
                                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                       aria-label="انتخاب مشترک {{ $subscriber->id }}">
                            </td>
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($subscriber->id) }}</td>
                            <td class="max-w-56 px-3 py-3">
                                <span class="flex items-center gap-2">
                                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full border border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                                        <x-admin-panel::icon name="{{ $subscriber->contact_type === 'email' ? 'mail' : 'phone' }}" class="size-3.5" />
                                    </span>
                                    <strong dir="ltr" class="truncate font-semibold">{{ $subscriber->formatted_contact ?? $subscriber->contact }}</strong>
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                @if ($subscriber->contact_type === 'email')
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">ایمیل</span>
                                @elseif ($subscriber->contact_type === 'mobile')
                                    <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">شماره موبایل</span>
                                @else
                                    <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">نامشخص</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if ($subscriber->is_active)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">فعال</span>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400">غیرفعال</span>
                                @endif
                            </td>
                            <td class="max-w-40 px-3 py-3">
                                <p class="truncate text-ink-2 dark:text-night-ink-2">
                                    @if ($subscriber->device_type === 'mobile')
                                        <x-admin-panel::icon name="smartphone" class="me-1 inline size-3.5" /> موبایل
                                    @elseif ($subscriber->device_type === 'tablet')
                                        <x-admin-panel::icon name="smartphone" class="me-1 inline size-3.5" /> تبلت
                                    @elseif ($subscriber->device_type === 'desktop')
                                        <x-admin-panel::icon name="monitor" class="me-1 inline size-3.5" /> دسکتاپ
                                    @else
                                        —
                                    @endif
                                    <span class="fa-digits">({{ $subscriber->browser ?? '-' }} / {{ $subscriber->os ?? '-' }})</span>
                                </p>
                            </td>
                            <td class="px-3 py-3" dir="ltr">
                                <code class="text-[11px] text-ink-2 dark:text-night-ink-2">{{ $subscriber->ip_address ?? '-' }}</code>
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($subscriber->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    @can('newsletters.show')
                                        <button type="button" wire:click="openDetail({{ $subscriber->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                                title="جزئیات" aria-label="جزئیات">
                                            <x-admin-panel::icon name="eye" class="size-4" />
                                        </button>
                                    @endcan
                                    @can('newsletters.delete')
                                        <button type="button" wire:click="confirmDelete({{ $subscriber->id }})"
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
                            <td colspan="9">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="mail" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">هیچ مشترکی یافت نشد!</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->subscribers->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->subscribers->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->subscribers->total()) }}</span> مشترک
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->subscribers->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->subscribers->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->subscribers->hasMorePages())
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

    {{-- مودال جزئیات — پورت back/newsletters/show.blade.php --}}
    <div x-data="{ open: @entangle('showDetail').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative max-h-[85vh] w-full max-w-xl overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-base font-bold">
                    <x-admin-panel::icon name="mail" class="size-5 text-brand-600 dark:text-brand-400" />
                    جزئیات مشترک
                </h3>
                <button type="button" x-on:click="open=false"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            @if ($this->detailSubscriber)
                @php $newsletter = $this->detailSubscriber; @endphp

                <div class="grid grid-cols-1 gap-4 text-[13px] sm:grid-cols-2">
                    <div class="space-y-0">
                        <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                            <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">شناسه</span>
                            <span class="fa-digits text-end font-semibold">{{ number_format($newsletter->id) }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                            <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">ایمیل/شماره</span>
                            <strong class="text-end font-semibold" dir="ltr">{{ $newsletter->formatted_contact ?? $newsletter->contact }}</strong>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                            <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">نوع</span>
                            <span class="text-end">
                                @if ($newsletter->contact_type === 'email')
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">ایمیل</span>
                                @else
                                    <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">شماره موبایل</span>
                                @endif
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                            <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">وضعیت</span>
                            <span class="text-end">
                                @if ($newsletter->is_active)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">فعال</span>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/12 dark:text-red-400">غیرفعال</span>
                                @endif
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-2.5">
                            <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">تاریخ ثبت‌نام</span>
                            <span class="fa-digits text-end font-semibold">{{ $this->jdate($newsletter->created_at, 'Y/m/d | H:i:s') }}</span>
                        </div>
                    </div>
                    <div class="space-y-0">
                        <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                            <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">IP</span>
                            <code class="text-end text-[12px]" dir="ltr">{{ $newsletter->ip_address ?? '-' }}</code>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                            <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">مرورگر</span>
                            <span class="text-end font-semibold">{{ $newsletter->browser ?? '-' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                            <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">سیستم‌عامل</span>
                            <span class="text-end font-semibold">{{ $newsletter->os ?? '-' }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                            <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">دستگاه</span>
                            <span class="text-end font-semibold">
                                @if ($newsletter->device_type === 'mobile')
                                    <x-admin-panel::icon name="smartphone" class="me-1 inline size-3.5" /> موبایل
                                @elseif ($newsletter->device_type === 'tablet')
                                    <x-admin-panel::icon name="smartphone" class="me-1 inline size-3.5" /> تبلت
                                @elseif ($newsletter->device_type === 'desktop')
                                    <x-admin-panel::icon name="monitor" class="me-1 inline size-3.5" /> دسکتاپ
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-2.5">
                            <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">صفحه ثبت‌نام</span>
                            @if ($newsletter->landing_page)
                                <a href="{{ $newsletter->landing_page }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1 font-semibold text-brand-600 hover:text-brand-500 dark:text-brand-400">
                                    نمایش صفحه
                                    <x-admin-panel::icon name="external-link" class="size-3.5" />
                                </a>
                            @else
                                <span class="text-end font-semibold">—</span>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="py-10 text-center text-sm text-ink-3 dark:text-night-ink-2">رکوردی یافت نشد</div>
            @endif

            <div class="mt-5">
                <button type="button" x-on:click="open=false" class="ap-btn-ghost w-full">بستن</button>
            </div>
        </div>
    </div>

    {{-- مودال خروجی اکسل — پورت exportModal قدیمی (روت قدیمی admin.newsletters.export) --}}
    <div x-data="{ open: @entangle('exportModal').live }"
         x-show="open" x-cloak
         x-on:open-url.window="window.open($event.detail.url, '_blank')"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-base font-bold">
                    <x-admin-panel::icon name="download" class="size-5 text-brand-600 dark:text-brand-400" />
                    خروجی اکسل
                </h3>
                <button type="button" x-on:click="open=false"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            <div class="space-y-4 text-[13px]">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="newsletter-export-type">نوع خروجی <span class="text-red-500">*</span></label>
                    <select id="newsletter-export-type" wire:model="exportType" class="ap-input !py-2">
                        <option value="">انتخاب کنید…</option>
                        <option value="all">همه مشترکین</option>
                        <option value="email">فقط ایمیل‌ها</option>
                        <option value="mobile">فقط شماره موبایل‌ها</option>
                        <option value="active">مشترکین فعال</option>
                        <option value="inactive">مشترکین غیرفعال</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="newsletter-export-format">فرمت خروجی</label>
                    <select id="newsletter-export-format" wire:model="exportFormat" class="ap-input !py-2">
                        <option value="xlsx">Excel (xlsx)</option>
                        <option value="csv">CSV</option>
                    </select>
                </div>
                <div class="flex gap-2.5 pt-1">
                    <button type="button" wire:click="doExport" wire:loading.attr="disabled" wire:target="doExport"
                            class="ap-btn flex-1">
                        <x-admin-panel::icon name="download" class="size-4" />
                        دانلود
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </div>
        </div>
    </div>

    {{-- مودال تایید حذف گروهی --}}
    <div x-data="{ open: @entangle('confirmingBulkDelete').live }"
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
            <h3 class="text-base font-bold">حذف مشترکین انتخاب‌شده</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف مشترکین، اطلاعات آنها برای همیشه پاک خواهد شد.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="bulkDelete" wire:loading.attr="disabled" wire:target="bulkDelete"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- مودال تایید حذف تکی --}}
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
            <h3 class="text-base font-bold">حذف مشترک</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف این مشترک، اطلاعات او برای همیشه پاک خواهد شد.
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
    <div wire:loading wire:target="search, previousPage, nextPage, gotoPage, delete, bulkDelete, doExport"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
