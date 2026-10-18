{{-- اعلان‌های کاربر — پنل جدید (فاز ۴) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                تعداد کل: <span class="fa-digits">{{ number_format($this->notifications->total()) }}</span>
            </span>
            <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                کاربر: <span class="fa-digits">{{ $user->fullname ?? '#' . $user->id }}</span>
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('np.users.show', $user) }}" wire:navigate class="ap-btn-outline !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                پرونده کاربر
            </a>
            <button type="button" wire:click="openNotifModal" class="ap-btn-primary">
                <x-admin-panel::icon name="plus" class="size-4" />
                اعلان جدید
            </button>
        </div>
    </div>

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-4xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-14 px-3 py-3 text-start font-bold">#</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">پیام</th>
                        <th class="px-3 py-3 text-start font-bold">اولویت</th>
                        <th class="px-3 py-3 text-center font-bold">پاپ‌آپ</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت بازدید</th>
                        <th class="px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="w-16 px-3 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->notifications as $notification)
                        @php $p = $notification->priorityText(); @endphp
                        <tr wire:key="un-{{ $notification->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 text-ink-3">{{ $loop->iteration + ($this->notifications->firstItem() ?? 1) - 1 }}</td>
                            <td class="max-w-44 px-3 py-3"><p class="truncate font-semibold">{{ $notification->title ?: 'بدون عنوان' }}</p></td>
                            <td class="max-w-80 px-3 py-3 text-ink-2 dark:text-night-ink-2"><p class="truncate">{{ \Illuminate\Support\Str::limit(strip_tags($notification->message), 120) }}</p></td>
                            <td class="px-3 py-3">
                                <span class="ap-badge {{ $p['color'] === 'success' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400' : ($p['color'] === 'warning' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400' : 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400') }}">{{ $p['title'] }}</span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($notification->popup)
                                    <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">پاپ‌آپ</span>
                                @else
                                    <span class="text-ink-3 dark:text-night-ink-2">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if ($this->notifRead($notification))
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">خوانده شد</span>
                                @else
                                    <span class="ap-badge bg-slate-100 text-slate-600 dark:bg-night-3 dark:text-night-ink-2">خوانده نشده</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits whitespace-nowrap">{{ $this->jdate($notification->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-center">
                                    <button type="button" wire:click="editNotif({{ $notification->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="ویرایش" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="bell" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">هنوز اعلانی برای این کاربر ارسال نشده است</p>
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
    </div>

    {{-- مودال ایجاد/ویرایش اعلان --}}
    <div x-data="{ open: @entangle('notifModal').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-lg rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">{{ $editingNotifId ? 'ویرایش اعلان' : 'اعلان جدید' }}</h3>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">عنوان</label>
                    <input type="text" wire:model="notifTitle" class="ap-input">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">اولویت</label>
                    <select wire:model="notifPriority" class="ap-input">
                        <option value="low">عادی</option>
                        <option value="medium">مهم</option>
                        <option value="high">خیلی مهم</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">متن پیام <span class="text-red-500">*</span></label>
                    <textarea wire:model="notifMessage" rows="4" class="ap-input"></textarea>
                    @error('notifMessage') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <label class="flex cursor-pointer items-center gap-2.5 text-[13px]">
                    <input type="checkbox" wire:model="notifPopup" class="size-4 rounded-md border-line text-brand-600 dark:border-night-line">
                    نمایش به صورت پاپ‌آپ در پنل کاربر
                </label>
            </div>

            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="{{ $editingNotifId ? 'updateNotif' : 'storeNotif' }}" wire:loading.attr="disabled"
                        wire:target="{{ $editingNotifId ? 'updateNotif' : 'storeNotif' }}" class="ap-btn-primary flex-1">
                    {{ $editingNotifId ? 'ذخیره تغییرات' : 'ایجاد اعلان' }}
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="previousPage, nextPage, gotoPage, storeNotif, updateNotif"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
