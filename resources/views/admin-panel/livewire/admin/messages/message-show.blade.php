{{-- مشاهده پیام + گیرندگان — پورت back/messages/show.blade.php — پنل جدید (فاز ۷) --}}
<div class="mx-auto max-w-6xl space-y-5">

    {{-- هدر --}}
    <div class="ap-card p-5">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('np.messages.index') }}" wire:navigate
               class="rounded-xl p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3"
               aria-label="بازگشت به ارسال پیام">
                <x-admin-panel::icon name="arrow-right" class="size-5" />
            </a>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="truncate text-base font-extrabold">{{ $message->title }}</h2>
                    @if ($message->status_send === 'sent')
                        <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">ارسال شده</span>
                    @elseif ($message->status_send === 'pending')
                        <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">منتظر ارسال</span>
                    @else
                        <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">ارسال نشده</span>
                    @endif
                </div>
                <p class="mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">مشاهده‌ی جزئیات پیام و وضعیت تحویل به گیرندگان</p>
            </div>
        </div>
    </div>

    {{-- کارت‌های آمار (پورت آمار ویوی قدیمی) --}}
    <div class="stagger grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="ap-card p-5">
            <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">گیرندگان</p>
            <p class="fa-digits mt-1.5 text-2xl font-extrabold">{{ number_format($this->totalRecipients) }}</p>
        </div>
        <div class="ap-card p-5">
            <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">کانال ارسال</p>
            <p class="fa-digits mt-1.5 text-2xl font-extrabold">{{ number_format($this->channelCount) }}</p>
        </div>
        <div class="ap-card p-5">
            <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">تاریخ ارسال</p>
            <p class="fa-digits mt-1.5 text-lg font-extrabold">{{ $this->jdate($message->created_at) }}</p>
        </div>
        <div class="ap-card p-5">
            <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">وضعیت</p>
            <p class="mt-1.5">
                @if ($message->status_send === 'sent')
                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400"><span class="size-1.5 rounded-full bg-emerald-500"></span> ارسال شده</span>
                @elseif ($message->status_send === 'pending')
                    <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400"><span class="size-1.5 rounded-full bg-amber-500"></span> منتظر ارسال</span>
                @else
                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400"><span class="size-1.5 rounded-full bg-red-500"></span> ارسال نشده</span>
                @endif
            </p>
        </div>
    </div>

    {{-- جزئیات پیام (فقط‌خواندنی — پورت فرم readonly ویوی قدیمی) --}}
    <div class="ap-card overflow-hidden">
        <div class="flex items-center gap-2.5 border-b border-line bg-surface-2 px-5 py-3.5 dark:border-night-line dark:bg-night-3">
            <span class="flex size-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="file-text" class="size-4" />
            </span>
            <h3 class="text-sm font-bold">جزئیات پیام</h3>
        </div>

        <div class="grid grid-cols-1 gap-5 p-5 lg:grid-cols-2">
            <div>
                <p class="mb-1 text-[11px] font-bold uppercase tracking-wide text-ink-3 dark:text-night-ink-2">عنوان</p>
                <p class="rounded-xl border border-line bg-surface-2 px-3 py-2.5 text-[13px] font-semibold dark:border-night-line dark:bg-night-3">{{ $message->title }}</p>
            </div>
            <div>
                <p class="mb-1 text-[11px] font-bold uppercase tracking-wide text-ink-3 dark:text-night-ink-2">توضیحات</p>
                <p class="whitespace-pre-line rounded-xl border border-line bg-surface-2 px-3 py-2.5 text-[13px] leading-6 text-ink-2 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">{{ $message->description }}</p>
            </div>
            <div class="lg:col-span-2">
                <p class="mb-1.5 text-[11px] font-bold uppercase tracking-wide text-ink-3 dark:text-night-ink-2">کانال‌های ارسال</p>
                <div class="flex flex-wrap gap-1.5">
                    @if ($message->email)
                        <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">
                            <x-admin-panel::icon name="mail" class="size-3.5" /> ایمیل
                        </span>
                    @endif
                    @if ($message->sms)
                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                            <x-admin-panel::icon name="message-square" class="size-3.5" /> پیامک
                        </span>
                    @endif
                    @if ($message->notification)
                        <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">
                            <x-admin-panel::icon name="bell" class="size-3.5" /> نوتیفیکیشن
                        </span>
                    @endif
                    @if ($message->popup)
                        <span class="ap-badge bg-violet-50 text-violet-700 dark:bg-violet-500/12 dark:text-violet-400">
                            <x-admin-panel::icon name="panel-top" class="size-3.5" /> پاپ‌آپ
                        </span>
                    @endif
                    @if (! $message->email && ! $message->sms && ! $message->notification && ! $message->popup)
                        <span class="text-xs text-ink-3">هیچ کانالی ثبت نشده است.</span>
                    @endif
                </div>
            </div>

            @if ($message->sms)
                <div class="rounded-xl border border-line bg-surface-2 p-4 dark:border-night-line dark:bg-night-3 lg:col-span-2">
                    <div class="mb-3 flex items-start gap-2 rounded-lg bg-sky-50 p-2.5 text-[12px] leading-5 text-sky-800 dark:bg-sky-500/10 dark:text-sky-300">
                        <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                        <span>پیامک فقط با پترن ارسال می‌شود؛ عنوان و توضیحات در پیامک درج نمی‌شوند.</span>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div>
                            <p class="mb-1 text-[11px] font-bold uppercase tracking-wide text-ink-3 dark:text-night-ink-2">کد پترن برای ارسال پیام</p>
                            <p dir="ltr" class="rounded-xl border border-line bg-surface px-3 py-2.5 font-mono text-[13px] dark:border-night-line dark:bg-night-2">{{ $this->patternCode() ?: '—' }}</p>
                        </div>
                    </div>

                    <p class="mb-1.5 mt-4 text-[11px] font-bold uppercase tracking-wide text-ink-3 dark:text-night-ink-2">متغیرهای پترن</p>
                    @if (count($this->smsVariables()))
                        <div class="space-y-2">
                            @foreach ($this->smsVariables() as $varName => $varValue)
                                <div wire:key="msg-var-{{ md5($varName.$varValue) }}" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <div class="rounded-xl border border-line bg-surface px-3 py-2 dark:border-night-line dark:bg-night-2">
                                        <span class="text-[11px] text-ink-3 dark:text-night-ink-2">اسم متغیر: </span>
                                        <span dir="ltr" class="font-mono text-[12px] font-semibold">{{ $varName }}</span>
                                    </div>
                                    <div class="rounded-xl border border-line bg-surface px-3 py-2 dark:border-night-line dark:bg-night-2">
                                        <span class="text-[11px] text-ink-3 dark:text-night-ink-2">مقدار: </span>
                                        <span class="text-[12px] font-semibold">{{ $varValue }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-line bg-surface p-4 text-center text-xs text-ink-3 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2">
                            این پیام متغیر ندارد — ارسال با پترنِ بدون متغیر انجام شده است.
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- گیرندگان (پورت جدول message_user ویوی قدیمی) --}}
    <div class="ap-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line bg-surface-2 px-5 py-3.5 dark:border-night-line dark:bg-night-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-8 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                    <x-admin-panel::icon name="users" class="size-4" />
                </span>
                <h3 class="text-sm font-bold">گیرندگان پیام</h3>
            </div>
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                <span class="fa-digits">{{ number_format($this->totalRecipients) }}</span> کاربر
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-16 px-3 py-3 text-center font-bold">#</th>
                        <th class="px-3 py-3 text-start font-bold">نام و نام خانوادگی</th>
                        <th class="w-28 px-3 py-3 text-center font-bold">تاریخ</th>
                        <th class="w-36 px-3 py-3 text-center font-bold">وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->messageItems as $item)
                        <tr wire:key="msg-item-{{ $item->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 text-center fa-digits font-bold text-ink-3">{{ number_format($item->id) }}</td>
                            <td class="max-w-56 px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-50 text-[11px] font-bold text-brand-700 dark:bg-brand-500/12 dark:text-brand-300">
                                        {{ mb_substr(($item->user?->fullname ?: $item->user?->username) ?? '؟', 0, 1) }}
                                    </span>
                                    <span class="truncate font-semibold">{{ $item->user ? ($item->user->fullname ?: $item->user->username) : 'کاربر حذف شده' }}</span>
                                    @if ($item->user && Route::has('np.users.show'))
                                        <a href="{{ route('np.users.show', ['user' => $item->user]) }}" wire:navigate
                                           class="shrink-0 text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400"
                                           title="مشاهده‌ی پروفایل کاربر">
                                            <x-admin-panel::icon name="external-link" class="size-3.5" />
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center fa-digits whitespace-nowrap text-ink-2 dark:text-night-ink-2">{{ $this->jdate($item->created_at) }}</td>
                            <td class="px-3 py-3 text-center">
                                @if ($item->status === 'seen')
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">دیده شده</span>
                                @else
                                    <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">دیده نشده</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="users" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">چیزی برای نمایش وجود ندارد!</p>
                                    <p class="text-xs">هیچ گیرنده‌ای برای این پیام ثبت نشده است.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی گیرندگان (۵۰ در صفحه مثل قدیمی) --}}
        @if ($this->messageItems->hasPages())
            <div class="border-t border-line px-4 py-3 dark:border-night-line">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs text-ink-3 dark:text-night-ink-2">
                        نمایش <span class="fa-digits">{{ number_format($this->messageItems->firstItem() ?? 0) }}</span> تا
                        <span class="fa-digits">{{ number_format($this->messageItems->lastItem() ?? 0) }}</span> از
                        <span class="fa-digits">{{ number_format($this->messageItems->total()) }}</span> گیرنده
                    </p>
                    <nav class="flex items-center gap-1" aria-label="صفحه‌بندی گیرندگان">
                        @if ($this->messageItems->onFirstPage())
                            <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                        @else
                            <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                                <x-admin-panel::icon name="chevron-right" class="size-4" />
                            </button>
                        @endif

                        @foreach ($this->messageItems->onEachSide(1)->toArray()['links'] as $link)
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

                        @if ($this->messageItems->hasMorePages())
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
    </div>
</div>
