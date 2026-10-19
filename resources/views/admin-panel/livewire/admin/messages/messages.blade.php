{{-- ارسال پیام گروهی + لیست پیام‌های ارسال‌شده — پورت back/messages/index.blade.php — پنل جدید (فاز ۷) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- کارت‌های آمار (پورت آمار ویوی قدیمی) --}}
    <div class="stagger grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="ap-card p-5">
            <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">کل پیام‌ها</p>
            <p class="fa-digits mt-1.5 text-2xl font-extrabold">{{ number_format($this->messagesTotal) }}</p>
        </div>
        <div class="ap-card p-5">
            <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">کاربران هدف (خبرنامه فعال)</p>
            <p class="fa-digits mt-1.5 text-2xl font-extrabold">{{ number_format($this->usersCount) }}</p>
        </div>
        <div class="ap-card p-5">
            <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">ارسال شده</p>
            <p class="fa-digits mt-1.5 text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ number_format($this->sentCount) }}</p>
        </div>
        <div class="ap-card p-5">
            <p class="text-[13px] font-medium text-ink-2 dark:text-night-ink-2">در انتظار / ناموفق</p>
            <p class="fa-digits mt-1.5 text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ number_format($this->pendingCount) }}</p>
        </div>
    </div>

    {{-- فرم ارسال پیام جدید (پورت store قدیمی) --}}
    <form wire:submit="save" class="ap-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line bg-surface-2 px-5 py-3.5 dark:border-night-line dark:bg-night-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                    <x-admin-panel::icon name="send" class="size-4" />
                </span>
                <div>
                    <h3 class="text-sm font-bold">ارسال پیام جدید</h3>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">عنوان و توضیحات الزامی هستند؛ اگر گیرنده‌ای انتخاب نشود پیام به همه‌ی کاربران می‌رود.</p>
                </div>
            </div>
        </div>

        <div class="space-y-5 p-5">
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="message-title">عنوان <span class="text-red-500">*</span></label>
                    <input id="message-title" type="text" wire:model="title" class="ap-input" placeholder="مثلاً: اطلاع‌رسانی رویداد ویژه">
                    @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- گیرندگان: چیپ‌پیکر (خالی = همه‌ی کاربران با notification=1) --}}
                <div>
                    @include('admin-panel::livewire.admin.discounts.partials.user-picker', [
                        'label' => 'گیرندگان (خالی = همه‌ی کاربران با اعلان فعال)',
                        'searchField' => 'usersSearch',
                        'resultsField' => 'usersResults',
                        'results' => $usersResults,
                        'selected' => $usersSelected,
                        'addAction' => 'addUser',
                        'removeAction' => 'removeUser',
                    ])
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold" for="message-description">توضیحات <span class="text-red-500">*</span></label>
                    <textarea id="message-description" rows="3" wire:model="description" class="ap-input" placeholder="متن پیام…"></textarea>
                    @error('description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- کانال‌های ارسال --}}
            <div>
                <label class="mb-1.5 block text-xs font-semibold">کانال‌های ارسال</label>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-line bg-surface-2 p-3 transition-colors has-checked:border-brand-500 has-checked:bg-brand-50/60 dark:border-night-line dark:bg-night-3 dark:has-checked:bg-brand-500/10">
                        <input type="checkbox" wire:model="email" class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                            <x-admin-panel::icon name="mail" class="size-4" />
                        </span>
                        <span>
                            <span class="block text-[13px] font-bold">ایمیل</span>
                            <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">ارسال از طریق ایمیل</span>
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-line bg-surface-2 p-3 transition-colors has-checked:border-brand-500 has-checked:bg-brand-50/60 dark:border-night-line dark:bg-night-3 dark:has-checked:bg-brand-500/10">
                        <input type="checkbox" wire:model="sms" class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                            <x-admin-panel::icon name="message-square" class="size-4" />
                        </span>
                        <span>
                            <span class="block text-[13px] font-bold">پیامک</span>
                            <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">ارسال با پترن پیامکی</span>
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-line bg-surface-2 p-3 transition-colors has-checked:border-brand-500 has-checked:bg-brand-50/60 dark:border-night-line dark:bg-night-3 dark:has-checked:bg-brand-500/10">
                        <input type="checkbox" wire:model="notification" class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400">
                            <x-admin-panel::icon name="bell" class="size-4" />
                        </span>
                        <span>
                            <span class="block text-[13px] font-bold">نوتیفیکیشن</span>
                            <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">اعلان درون‌برنامه‌ای</span>
                        </span>
                    </label>

                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-line bg-surface-2 p-3 transition-colors has-checked:border-brand-500 has-checked:bg-brand-50/60 dark:border-night-line dark:bg-night-3 dark:has-checked:bg-brand-500/10">
                        <input type="checkbox" wire:model="popup" class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400">
                            <x-admin-panel::icon name="panel-top" class="size-4" />
                        </span>
                        <span>
                            <span class="block text-[13px] font-bold">پاپ‌آپ</span>
                            <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">نمایش پنجره‌ی بازشو</span>
                        </span>
                    </label>
                </div>
            </div>

            {{-- بخش پترن پیامک (فقط با تیک پیامک) — پورت pattern-code-div قدیمی --}}
            <div x-data x-show="$wire.sms" x-cloak class="rounded-xl border border-line bg-surface-2 p-4 dark:border-night-line dark:bg-night-3">
                <div class="mb-3 flex items-start gap-2 rounded-lg bg-sky-50 p-2.5 text-[12px] leading-5 text-sky-800 dark:bg-sky-500/10 dark:text-sky-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    <span>پیامک فقط با پترن ارسال می‌شود؛ عنوان و توضیحات در پیامک درج نمی‌شود.</span>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="message-pattern">کد پترن برای ارسال پیام</label>
                        <input id="message-pattern" type="text" wire:model="user_message_pattern_code" dir="ltr" class="ap-input font-mono">
                    </div>
                    <div class="flex items-end">
                        <button type="button" wire:click="addVariable" class="ap-btn-outline !py-2">
                            <x-admin-panel::icon name="plus" class="size-4" />
                            افزودن متغیر
                        </button>
                    </div>
                </div>

                @if (count($variables))
                    <div class="mt-3 space-y-2">
                        @foreach ($variables as $index => $variable)
                            <div wire:key="msg-var-{{ $index }}" class="grid grid-cols-[1fr_1fr_auto] items-end gap-2">
                                <div>
                                    <label class="mb-1 block text-[11px] font-semibold">اسم متغیر</label>
                                    <input type="text" wire:model="variables.{{ $index }}.name" dir="ltr" class="ap-input !py-2 font-mono" placeholder="code">
                                </div>
                                <div>
                                    <label class="mb-1 block text-[11px] font-semibold">مقدار</label>
                                    <input type="text" wire:model="variables.{{ $index }}.value" class="ap-input !py-2" placeholder="12345">
                                </div>
                                <button type="button" wire:click="removeVariable({{ $index }})"
                                        class="ap-btn-ghost !px-2.5 !py-2.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10"
                                        aria-label="حذف متغیر">
                                    <x-admin-panel::icon name="trash-2" class="size-4" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-3 flex items-start gap-2 rounded-lg bg-amber-50 p-2.5 text-[12px] leading-5 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                    <x-admin-panel::icon name="info" class="mt-0.5 size-4 shrink-0" />
                    <span>در صورتی که پترن شما متغیر ندارد، حتماً متغیرهای موجود را حذف کنید تا پیام ارسال شود.</span>
                </div>
            </div>

            <div class="flex items-center justify-end border-t border-line pt-4 dark:border-night-line">
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn">
                    <x-admin-panel::icon name="send" class="size-4" />
                    ارسال پیام
                </button>
            </div>
        </div>
    </form>

    {{-- پیام‌های ارسال‌شده --}}
    <div class="ap-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line bg-surface-2 px-5 py-3.5 dark:border-night-line dark:bg-night-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                    <x-admin-panel::icon name="list" class="size-4" />
                </span>
                <h3 class="text-sm font-bold">پیام‌های ارسال شده</h3>
            </div>
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                <span class="fa-digits">{{ number_format($this->messagesTotal) }}</span> پیام
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-16 px-3 py-3 text-center font-bold">#</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">متن</th>
                        <th class="w-56 px-3 py-3 text-center font-bold">کانال‌ها</th>
                        <th class="w-40 px-3 py-3 text-center font-bold">گیرندگان</th>
                        <th class="w-28 px-3 py-3 text-center font-bold">تاریخ</th>
                        <th class="w-36 px-3 py-3 text-center font-bold">وضعیت</th>
                        <th class="w-20 px-3 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->messages as $message)
                        <tr wire:key="message-{{ $message->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 text-center fa-digits font-bold text-ink-3">{{ number_format($message->id) }}</td>
                            <td class="max-w-48 px-3 py-3">
                                <p class="truncate font-semibold">{{ $message->title }}</p>
                            </td>
                            <td class="max-w-48 px-3 py-3">
                                <p class="truncate text-ink-2 dark:text-night-ink-2">{{ $message->description }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap items-center justify-center gap-1">
                                    @if ($message->email)
                                        <span class="ap-badge bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400">ایمیل</span>
                                    @endif
                                    @if ($message->sms)
                                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">پیامک</span>
                                    @endif
                                    @if ($message->notification)
                                        <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400">نوتیف</span>
                                    @endif
                                    @if ($message->popup)
                                        <span class="ap-badge bg-violet-50 text-violet-700 dark:bg-violet-500/12 dark:text-violet-400">پاپ‌آپ</span>
                                    @endif
                                    @if (! $message->email && ! $message->sms && ! $message->notification && ! $message->popup)
                                        <span class="text-ink-3">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center fa-digits font-semibold">{{ number_format($message->users_count ?? 0) }}</td>
                            <td class="px-3 py-3 text-center fa-digits whitespace-nowrap text-ink-2 dark:text-night-ink-2">{{ $this->jdate($message->created_at) }}</td>
                            <td class="px-3 py-3 text-center">
                                @if ($message->status_send === 'sent')
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400"><span class="size-1.5 rounded-full bg-emerald-500"></span> ارسال شده</span>
                                @elseif ($message->status_send === 'pending')
                                    <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400"><span class="size-1.5 rounded-full bg-amber-500"></span> منتظر ارسال</span>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400"><span class="size-1.5 rounded-full bg-red-500"></span> ارسال نشده</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-center gap-0.5">
                                    <a href="{{ route('np.messages.show', ['message' => $message]) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="مشاهده" aria-label="مشاهده">
                                        <x-admin-panel::icon name="eye" class="size-4" />
                                    </a>
                                    <button type="button" wire:click="confirmDelete({{ $message->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="send" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">چیزی برای نمایش وجود ندارد!</p>
                                    <p class="text-xs">با ارسال اولین پیام، لیست اینجا نمایش داده می‌شود.</p>
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
                    نمایش <span class="fa-digits">{{ number_format($this->messages->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->messages->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->messages->total()) }}</span> پیام
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->messages->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->messages->onEachSide(1)->toArray()['links'] as $link)
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

                    @if ($this->messages->hasMorePages())
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

    {{-- مودال تایید حذف --}}
    <div x-data="{ open: @entangle('confirmingDelete').live }"
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
            <h3 class="text-base font-bold">حذف پیام</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف پیام، دیگر قادر به بازیابی آن نخواهید بود.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله، حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">خیر</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="save, delete, previousPage, nextPage, gotoPage, usersSearch"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
