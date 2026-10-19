{{-- مشاهده تیکت + گفتگو + ارسال پاسخ — پورت back/tickets/show.blade.php — پنل جدید (فاز ۷) --}}
<div class="mx-auto max-w-5xl space-y-5">

    {{-- هدر: اطلاعات تیکت --}}
    <div class="ap-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line pb-4 dark:border-night-line">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('np.tickets.index') }}" wire:navigate
                   class="rounded-xl p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3"
                   aria-label="بازگشت به لیست تیکت‌ها">
                    <x-admin-panel::icon name="arrow-right" class="size-5" />
                </a>
                <div>
                    <h2 class="text-base font-extrabold">
                        شماره تیکت <span class="fa-digits text-brand-600 dark:text-brand-400">#{{ number_format($ticket->id) }}</span>
                    </h2>
                    <p class="mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">{{ $ticket->subject }}</p>
                </div>
            </div>

            @can('tickets.update')
                <div class="relative inline-flex items-center">
                    <span class="pointer-events-none absolute start-3 z-10 size-2 rounded-full {{ $status === 'answered' ? 'bg-brand-500' : ($status === 'open' ? 'bg-emerald-500' : ($status === 'close' ? 'bg-slate-400' : 'bg-amber-500')) }}"></span>
                    <select wire:model.live="status" class="ap-input !py-2 !pe-9 !ps-7 min-w-44 text-xs font-semibold"
                            aria-label="تغییر وضعیت تیکت">
                        <option value="pending" @selected($status === 'pending')>در انتظار پاسخ</option>
                        <option value="answered" @selected($status === 'answered')>پاسخ داده شده</option>
                        <option value="open" @selected($status === 'open')>باز</option>
                        <option value="close" @selected($status === 'close')>بسته</option>
                    </select>
                </div>
            @else
                <span class="ap-badge {{ $status === 'answered'
                    ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400'
                    : ($status === 'open'
                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400'
                        : ($status === 'close'
                            ? 'bg-slate-100 text-slate-600 dark:bg-slate-500/12 dark:text-slate-400'
                            : 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400')) }}">
                    {{ \App\Livewire\Admin\Tickets\TicketIndex::statusLabel($status) }}
                </span>
            @endcan
        </div>

        <div class="grid grid-cols-1 gap-4 pt-4 sm:grid-cols-3">
            <div>
                <p class="mb-1 text-[11px] font-bold uppercase tracking-wide text-ink-3 dark:text-night-ink-2">کاربر مربوطه</p>
                <p class="flex items-center gap-1.5 text-[13px] font-semibold">
                    @if ($ticket->user_id)
                        <x-admin-panel::icon name="user" class="size-4 text-ink-3" />
                        {{ $ticket->user->fullname ?: $ticket->user->mobile }}
                        @if (Route::has('np.users.show'))
                            <a href="{{ route('np.users.show', ['user' => $ticket->user]) }}" wire:navigate
                               class="text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400" title="مشاهده پروفایل کاربر">
                                <x-admin-panel::icon name="external-link" class="size-3.5" />
                            </a>
                        @endif
                    @else
                        <x-admin-panel::icon name="store" class="size-4 text-ink-3" />
                        {{ $ticket->seller->fullname ?? '—' }}
                        @if ($ticket->seller && Route::has('np.sellers.show'))
                            <a href="{{ route('np.sellers.show', ['seller' => $ticket->seller]) }}" wire:navigate
                               class="text-brand-600 transition-colors hover:text-brand-500 dark:text-brand-400" title="مشاهده پروفایل فروشنده">
                                <x-admin-panel::icon name="external-link" class="size-3.5" />
                            </a>
                        @endif
                    @endif
                </p>
            </div>
            <div>
                <p class="mb-1 text-[11px] font-bold uppercase tracking-wide text-ink-3 dark:text-night-ink-2">تاریخ ایجاد</p>
                <p class="fa-digits flex items-center gap-1.5 text-[13px] font-semibold">
                    <x-admin-panel::icon name="clock" class="size-4 text-ink-3" />
                    {{ $this->jdate($ticket->created_at, 'Y/m/d - H:i') }}
                </p>
            </div>
            <div>
                <p class="mb-1 text-[11px] font-bold uppercase tracking-wide text-ink-3 dark:text-night-ink-2">اولویت</p>
                <p class="text-[13px]">
                    @php $priority = $ticket->priority ?? 'low'; @endphp
                    <span class="ap-badge {{ $priority === 'hight'
                        ? 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400'
                        : ($priority === 'medium'
                            ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400'
                            : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400') }}">
                        {{ \App\Livewire\Admin\Tickets\TicketIndex::priorityLabel($priority) }}
                    </span>
                </p>
            </div>
        </div>
    </div>

    {{-- گفتگو --}}
    <div class="ap-card overflow-hidden">
        <div class="flex items-center gap-2.5 border-b border-line bg-surface-2 px-5 py-3.5 dark:border-night-line dark:bg-night-3">
            <span class="flex size-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="message-circle" class="size-4" />
            </span>
            <h3 class="text-sm font-bold">مکالمات تیکت</h3>
        </div>

        <div class="flex max-h-[520px] flex-col gap-4 overflow-y-auto p-5" x-data x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
            @forelse ($this->messages as $message)
                @php $author = $this->messageAuthor($message); @endphp
                <div wire:key="tk-msg-{{ $message->id }}"
                     class="flex items-end gap-2.5 {{ $author['type'] === 'admin' ? 'flex-row-reverse' : '' }}">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $author['type'] === 'admin'
                        ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400'
                        : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400' }}">
                        {{ $author['avatar'] }}
                    </span>
                    <div class="flex max-w-[70%] flex-col gap-1 {{ $author['type'] === 'admin' ? 'items-end' : 'items-start' }}">
                        <p class="px-1 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">{{ $author['label'] }}</p>
                        <div class="whitespace-pre-line rounded-2xl px-4 py-3 text-[13px] leading-6 {{ $author['type'] === 'admin'
                            ? 'rounded-bl bg-brand-50 text-brand-900 dark:bg-brand-500/12 dark:text-brand-100'
                            : 'rounded-br bg-surface-3 text-ink dark:bg-night-3 dark:text-night-ink' }}">
                            {{ $message->message }}

                            @if ($message->files && count($message->files) > 0)
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($message->files as $file)
                                        <a href="{{ asset($file->file) }}" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1 rounded-lg border border-line bg-surface px-2 py-1 text-[11px] font-semibold text-brand-600 transition-colors hover:bg-brand-50 dark:border-night-line dark:bg-night-2 dark:text-brand-400"
                                           wire:key="tk-file-{{ $file->id }}">
                                            <x-admin-panel::icon name="link-2" class="size-3" />
                                            پیوست
                                            <x-admin-panel::icon name="download" class="size-3" />
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <span class="fa-digits px-1 text-[10px] text-ink-3 dark:text-night-ink-2">
                            {{ $this->jdate($message->created_at, 'H:i - Y/m/d') }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-sm text-ink-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="message-circle" class="mx-auto mb-2 size-8 opacity-40" />
                    هنوز پیامی ارسال نشده است
                </div>
            @endforelse
        </div>
    </div>

    {{-- ارسال پاسخ (پورت update قدیمی) --}}
    <div class="ap-card overflow-hidden">
        <div class="flex items-center gap-2.5 border-b border-line bg-surface-2 px-5 py-3.5 dark:border-night-line dark:bg-night-3">
            <span class="flex size-8 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400">
                <x-admin-panel::icon name="send" class="size-4" />
            </span>
            <h3 class="text-sm font-bold">ارسال پاسخ</h3>
        </div>

        <form wire:submit="sendReply" class="space-y-4 p-5">
            <div>
                <label class="mb-1.5 block text-xs font-semibold" for="ticket-reply">متن پیام <span class="text-red-500">*</span></label>
                <textarea id="ticket-reply" wire:model="reply" rows="4" class="ap-input" placeholder="پاسخ خود را اینجا بنویسید…"></textarea>
                @error('reply') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
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
                            <span wire:key="tk-upload-{{ $index }}"
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

            <div class="flex items-center justify-end">
                <button type="submit" wire:loading.attr="disabled" wire:target="sendReply" class="ap-btn">
                    <x-admin-panel::icon name="send" class="size-4" />
                    ارسال پاسخ
                </button>
            </div>
        </form>
    </div>
</div>
