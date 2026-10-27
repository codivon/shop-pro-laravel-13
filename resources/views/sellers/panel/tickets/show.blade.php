{{-- جزئیات درخواست پشتیبانی (فاز ۱۰۸) — پورت sellers-old/panel/tickets/show.blade.php
     مشخصات تیکت + گفت‌وگوی پیام‌ها (فایل‌های پیوست) + فرم پاسخ کلاسیک
     PUT به seller.tickets.update با همان فیلدهای قدیمی (message / upload_files[]) --}}
<div x-data="{
        loading: false,

        // ارسال پاسخ — کنترلر قدیمی response('success') می‌دهد؛ رفرش همین صفحه
        async submitReply() {
            this.loading = true;
            try {
                const res = await fetch(@js($updateUrl), {
                    method: 'POST',
                    body: new FormData(this.$refs.replyForm),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });

                if (res.ok) {
                    this.$dispatch('toast', { message: 'پاسخ شما با موفقیت ثبت شد.', type: 'success' });
                    setTimeout(() => window.Livewire ? Livewire.navigate(window.location.href) : window.location.reload(), 800);
                    return;
                }

                const data = await res.json().catch(() => ({}));
                const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'خطا در ثبت پاسخ، دوباره تلاش کنید.');
                this.$dispatch('toast', { message: firstError, type: 'error' });
            } catch (e) {
                this.$dispatch('toast', { message: 'ارتباط با سرور برقرار نشد.', type: 'error' });
            } finally {
                this.loading = false;
            }
        },
     }">
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
                <x-admin-panel::icon name="life-buoy" class="size-5 text-brand-600 dark:text-brand-400" />
                مشاهده درخواست شماره <span class="fa-digits">{{ $ticket->id }}</span>
            </h1>
            <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">اینجا می‌توانید درخواستی را که ثبت کردید ببینید.</p>
        </div>
        <a href="{{ $indexUrl }}" wire:navigate
           class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به فهرست
        </a>
    </div>

    {{-- ================= مشخصات تیکت ================= --}}
    <section class="ap-card mb-4 p-4 sm:p-6">
        <dl class="grid grid-cols-1 gap-4 text-xs sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <dt class="mb-1 font-semibold text-ink-500 dark:text-night-ink-2">موضوع تیکت:</dt>
                <dd class="font-bold text-ink-800 dark:text-night-ink-1">{{ $ticket->subject }}</dd>
            </div>
            <div>
                <dt class="mb-1 font-semibold text-ink-500 dark:text-night-ink-2">تاریخ ایجاد :</dt>
                <dd class="fa-digits font-bold text-ink-800 dark:text-night-ink-1">{{ jdate($ticket->created_at) }}</dd>
            </div>
            <div>
                <dt class="mb-1 font-semibold text-ink-500 dark:text-night-ink-2">اولویت :</dt>
                <dd class="font-bold text-ink-800 dark:text-night-ink-1">{{ $ticket->priorityText() }}</dd>
            </div>
            <div>
                <dt class="mb-1 font-semibold text-ink-500 dark:text-night-ink-2">وضعیت :</dt>
                <dd>
                    @if ($ticket->status == 'pending')
                        <span class="ap-badge bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">در انتظار پاسخ</span>
                    @elseif ($ticket->status == 'answered')
                        <span class="ap-badge bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">پاسخ داده شده</span>
                    @elseif ($ticket->status == 'open')
                        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">باز</span>
                    @else
                        <span class="ap-badge bg-surface-3 text-ink-600 dark:bg-night-3 dark:text-night-ink-2">بسته</span>
                    @endif
                </dd>
            </div>
        </dl>
    </section>

    {{-- ================= گفت‌وگوی پیام‌ها (پورت chat قدیمی) ================= --}}
    <section class="ap-card mb-4 space-y-4 p-4 sm:p-6">
        @foreach ($ticket->messages as $message)
            @php $own = (bool) $message->admin_id; @endphp
            <article class="flex flex-col {{ $own ? 'items-start' : 'items-end' }}">
                <div class="max-w-full rounded-2xl border p-3.5 text-xs leading-relaxed sm:max-w-[85%]
                            {{ $own
                                ? 'rounded-ss-sm border-brand-200 bg-brand-50 text-brand-900 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-100'
                                : 'rounded-se-sm border-line bg-surface-2 text-ink-700 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2' }}">
                    <p class="mb-1.5 text-[11px] font-extrabold">
                        @if ($message->admin_id)
                            پشتیبانی {{ option('info_site_title', 'او پی شاپ') }}
                        @else
                            شما
                        @endif
                    </p>
                    <p class="whitespace-pre-line">{{ $message->message }}</p>

                    @if ($message->files->count())
                        <p class="mt-2 flex flex-wrap gap-2">
                            @foreach ($message->files as $file)
                                <a href="{{ asset($file->file) }}" target="_blank" rel="noopener" title="{{ asset($file->file) }}"
                                   class="inline-flex min-h-9 items-center gap-1 rounded-lg border border-line bg-surface px-2 text-[11px] font-bold text-ink-600 hover:text-brand-600 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="clipboard-list" class="size-3.5" />
                                    فایل {{ $loop->iteration }}
                                </a>
                            @endforeach
                        </p>
                    @endif

                    <p class="fa-digits mt-2 text-[10px] opacity-70">{{ jdate($message->created_at)->ago() }}</p>
                </div>
            </article>
        @endforeach
    </section>

    {{-- ================= فرم پاسخ کلاسیک — عیناً فیلدهای ویوی قدیمی ================= --}}
    <form x-ref="replyForm" method="POST" action="{{ $updateUrl }}" enctype="multipart/form-data" x-on:submit.prevent>
        @csrf
        @method('put')

        <section class="ap-card p-4 sm:p-6">
            <h2 class="mb-3 text-sm font-bold">ثبت پاسخ جدید</h2>

            <label class="block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">پیام<span class="text-red-500">*</span></span>
                <textarea name="message" rows="4" required class="ap-input w-full"></textarea>
            </label>

            <label class="mt-4 block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">فایل های پیوست</span>
                <input type="file" accept="image/*,.zip" name="upload_files[]" multiple
                       class="ap-input w-full file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white">
                <small class="mt-1 block text-[11px] text-ink-400 dark:text-night-ink-2">فرمت‌های مجاز: png، jpeg، jpg، zip</small>
            </label>

            <div class="mt-5 border-t border-line pt-4 dark:border-night-line">
                <button type="button" x-on:click="submitReply()"
                        class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-5 text-xs font-bold"
                        :disabled="loading" x-bind:class="loading && 'opacity-60'">
                    <x-admin-panel::icon name="loader-circle" class="size-4" x-show="loading" x-cloak />
                    ثبت پاسخ
                </button>
            </div>
        </section>
    </form>
</div>
