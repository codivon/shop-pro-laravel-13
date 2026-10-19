{{-- ایجاد تیکت — پورت back/tickets/create.blade.php — پنل جدید (فاز ۷) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-4xl space-y-5">

    {{-- هدر --}}
    <div class="ap-card p-5">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('np.tickets.index') }}" wire:navigate
               class="rounded-xl p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3"
               aria-label="بازگشت به لیست تیکت‌ها">
                <x-admin-panel::icon name="arrow-right" class="size-5" />
            </a>
            <div>
                <h2 class="text-base font-extrabold">ایجاد تیکت جدید</h2>
                <p class="mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">موضوع، اولویت، کاربر مربوطه و متن پیام الزامی هستند.</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="ap-card space-y-5 p-5 sm:p-6">

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-xs font-semibold" for="ticket-subject">موضوع <span class="text-red-500">*</span></label>
                <input id="ticket-subject" type="text" wire:model="subject" class="ap-input" placeholder="مثلاً: مشکل در پرداخت سفارش">
                @error('subject') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold" for="ticket-priority">اولویت <span class="text-red-500">*</span></label>
                <select id="ticket-priority" wire:model="priority" class="ap-input">
                    <option value="low" @selected($priority === 'low')>کم</option>
                    <option value="medium" @selected($priority === 'medium')>متوسط</option>
                    <option value="hight" @selected($priority === 'hight')>زیاد</option>
                </select>
                @error('priority') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold">کاربر مربوطه <span class="text-red-500">*</span></label>

                @if ($recipientId)
                    <div class="flex items-center justify-between gap-2 rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                        <div class="flex items-center gap-2">
                            <span class="ap-badge {{ $recipientType === 'seller'
                                ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/12 dark:text-violet-400'
                                : 'bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400' }}">
                                {{ $recipientType === 'seller' ? 'فروشنده' : 'کاربر سایت' }}
                            </span>
                            <span class="text-[13px] font-semibold">{{ $recipientLabel }}</span>
                        </div>
                        <button type="button" wire:click="removeRecipient"
                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                aria-label="حذف کاربر مربوطه">
                            <x-admin-panel::icon name="x" class="size-4" />
                        </button>
                    </div>
                @else
                    <div x-data="{ open: false }" x-on:click.outside="open = false" class="relative">
                        <div class="relative">
                            <x-admin-panel::icon name="search" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                            <input type="text" wire:model.live.debounce.350ms="search" x-on:focus="open = true"
                                   placeholder="جستجو بر اساس نام، نام کاربری، موبایل یا ایمیل…" autocomplete="off"
                                   class="ap-input !py-2 pe-9" aria-label="جستجوی کاربر مربوطه">
                        </div>
                        @error('recipientId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

                        <div x-show="open && ($wire.results.length > 0 || $wire.search.length > 1)" x-cloak
                             class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-line bg-surface p-1 shadow-pop dark:border-night-line dark:bg-night-2">
                            @forelse ($results as $result)
                                <button type="button" wire:click="selectRecipient({{ $result['id'] }}, '{{ $result['type'] }}')" x-on:click="open = false"
                                        wire:key="recipient-{{ $result['type'] }}-{{ $result['id'] }}"
                                        class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-start text-[13px] transition-colors hover:bg-brand-50 dark:hover:bg-brand-500/10">
                                    <span class="truncate font-semibold">{{ $result['label'] }}</span>
                                    <span class="shrink-0 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $result['meta'] }}</span>
                                </button>
                            @empty
                                <p class="px-3 py-2.5 text-center text-xs text-ink-3 dark:text-night-ink-2">کاربری یافت نشد — حداقل ۲ حرف وارد کنید</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold" for="ticket-message">پیام <span class="text-red-500">*</span></label>
                <textarea id="ticket-message" wire:model="message" rows="5" class="ap-input" placeholder="متن تیکت…"></textarea>
                @error('message') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold">فایل‌های پیوست</label>
                <input type="file" multiple accept=".png,.jpg,.jpeg,.zip"
                       wire:model="files"
                       class="block w-full cursor-pointer rounded-xl border border-dashed border-line bg-surface-2 p-3 text-xs text-ink-2
                              file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5
                              file:text-xs file:font-semibold file:text-white hover:file:bg-brand-700 dark:border-night-line dark:bg-night-3">
                <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">فرمت‌های مجاز: png ،jpeg ،jpg و zip — حداکثر ۲۰۰ مگابایت</p>
                @error('files') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                @error('files.*') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

                @if (count($files))
                    <div class="mt-2.5 flex flex-wrap gap-1.5">
                        @foreach ($files as $index => $file)
                            <span wire:key="tc-upload-{{ $index }}"
                                  class="inline-flex items-center gap-1 rounded-lg bg-brand-50 py-1 pe-1.5 ps-2.5 text-xs font-semibold text-brand-700 dark:bg-brand-500/12 dark:text-brand-300">
                                <span class="max-w-40 truncate">{{ $file->getClientOriginalName() }}</span>
                                <button type="button" wire:click="removeFile({{ $index }})"
                                        class="rounded-md p-0.5 transition-colors hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-500/15"
                                        aria-label="حذف فایل">
                                    <x-admin-panel::icon name="x" class="size-3.5" />
                                </button>
                            </span>
                        @endforeach
                    </div>
                @endif

                <div wire:loading wire:target="files" class="mt-2 flex items-center gap-2 text-xs font-semibold text-brand-600 dark:text-brand-400">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                    </svg>
                    در حال آپلود فایل…
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-line pt-4 dark:border-night-line">
            <a href="{{ route('np.tickets.index') }}" wire:navigate class="ap-btn-ghost">انصراف</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn">
                <x-admin-panel::icon name="plus" class="size-4" />
                ایجاد تیکت
            </button>
        </div>
    </form>
</div>
