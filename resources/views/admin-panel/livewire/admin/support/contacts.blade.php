{{-- تماس با ما — پورت back/contacts/{index,show}.blade.php — پنل جدید (فاز ۷) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                <x-admin-panel::icon name="phone" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-extrabold">تماس با ما</h2>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">پیام‌های ارسالی کاربران از فرم تماس</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                کل پیام‌ها: <span class="fa-digits">{{ number_format($this->totalCount) }}</span>
            </span>
        </div>
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-14 px-3 py-3 text-start font-bold">#</th>
                        <th class="px-3 py-3 text-start font-bold">نام</th>
                        <th class="w-48 px-3 py-3 text-start font-bold">ایمیل</th>
                        <th class="w-52 px-3 py-3 text-start font-bold">موضوع</th>
                        <th class="px-3 py-3 text-start font-bold">پیام</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">تاریخ ارسال</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->contacts as $contact)
                        <tr wire:key="contact-{{ $contact->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($contact->id) }}</td>
                            <td class="max-w-36 px-3 py-3">
                                <p class="truncate font-semibold">{{ $contact->name }}</p>
                            </td>
                            <td class="max-w-48 px-3 py-3" dir="ltr">
                                <p class="truncate text-ink-2 dark:text-night-ink-2">{{ $contact->email ?? '—' }}</p>
                            </td>
                            <td class="max-w-52 px-3 py-3">
                                <p class="truncate font-semibold">{{ $contact->subject }}</p>
                            </td>
                            <td class="max-w-64 px-3 py-3">
                                <p class="truncate text-ink-2 dark:text-night-ink-2">{{ \Illuminate\Support\Str::limit((string) $contact->message, 80) }}</p>
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($contact->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <button type="button" wire:click="openDetail({{ $contact->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="نمایش" aria-label="نمایش">
                                        <x-admin-panel::icon name="eye" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $contact->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="phone" class="size-10 opacity-40" />
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
                    نمایش <span class="fa-digits">{{ number_format($this->contacts->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->contacts->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->contacts->total()) }}</span> پیام
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->contacts->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->contacts->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->contacts->hasMorePages())
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

    {{-- مودال جزئیات — پورت back/contacts/show.blade.php --}}
    <div x-data="{ open: @entangle('showDetail').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="flex items-center gap-2 text-base font-bold">
                    <x-admin-panel::icon name="phone" class="size-5 text-brand-600 dark:text-brand-400" />
                    جزئیات پیام
                </h3>
                <button type="button" x-on:click="open=false"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            @if ($this->detailContact)
                @php $contact = $this->detailContact; @endphp

                <div class="text-[13px]">
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">نام</span>
                        <span class="text-end font-semibold">{{ $contact->name }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">موضوع</span>
                        <span class="text-end font-semibold">{{ $contact->subject }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">ایمیل</span>
                        <span class="text-end font-semibold" dir="ltr">{{ $contact->email ?? '—' }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">شماره تلفن</span>
                        <span class="fa-digits text-end font-semibold" dir="ltr">{{ $contact->mobile ?? '—' }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-4 border-b border-line/60 py-2.5 dark:border-night-line/60">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">تاریخ ارسال</span>
                        <span class="fa-digits text-end font-semibold">{{ $this->jdate($contact->created_at, 'Y/m/d | H:i') }}</span>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-2.5">
                        <span class="shrink-0 text-xs font-bold text-ink-3 dark:text-night-ink-2">پیام</span>
                        <span class="text-end leading-6">{{ $contact->message }}</span>
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
            <h3 class="text-base font-bold">حذف پیام</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف پیام دیگر قادر به بازیابی آن نخواهید بود.
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
    <div wire:loading wire:target="previousPage, nextPage, gotoPage, delete"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
