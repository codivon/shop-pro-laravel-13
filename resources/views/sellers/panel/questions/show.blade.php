{{-- مشاهده پرسش (فاز ۱۰۸) — پورت sellers-old/panel/questions/show.blade.php
     جدول جزئیات + ویرایش متن (فقط پاسخِ خود فروشنده) + فرم پاسخ کلاسیک
     PUT به seller.questions.update با همان فیلدهای قدیمی (body / replay) --}}
<div x-data="{
        editing: false,
        loading: false,

        // ارسال فرم کلاسیک PUT — کنترلر قدیمی response($question) می‌دهد
        async submitForm() {
            this.loading = true;
            try {
                const res = await fetch(@js($updateUrl), {
                    method: 'POST',
                    body: new FormData(this.$refs.form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });

                if (res.ok) {
                    this.$dispatch('toast', { message: 'با موفقیت ثبت شد.', type: 'success' });
                    setTimeout(() => window.Livewire ? Livewire.navigate(@js($indexUrl)) : (window.location.href = @js($indexUrl)), 800);
                    return;
                }

                const data = await res.json().catch(() => ({}));
                const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'خطا در ثبت، دوباره تلاش کنید.');
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
                <x-admin-panel::icon name="message-circle" class="size-5 text-brand-600 dark:text-brand-400" />
                مشاهده پرسش
            </h1>
            <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">جزئیات پرسش و امکان ثبت پاسخ.</p>
        </div>
        <a href="{{ $indexUrl }}" wire:navigate
           class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به فهرست
        </a>
    </div>

    {{-- ================= فرم کلاسیک PUT — عیناً فیلدهای ویوی قدیمی ================= --}}
    <form x-ref="form" method="POST" action="{{ $updateUrl }}">
        @csrf
        @method('put')

        <section class="ap-card overflow-hidden">
            <header class="border-b border-line px-4 py-3 dark:border-night-line">
                <h2 class="text-sm font-bold">جزئیات {{ $question->comment_id ? 'پاسخ' : 'پرسش' }}</h2>
            </header>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <tbody class="divide-y divide-line/70 dark:divide-night-line/70">
                        <tr>
                            <th class="w-32 bg-surface-2 px-4 py-3 align-top font-bold text-ink-600 dark:bg-night-2 dark:text-night-ink-2">نام</th>
                            <td class="px-4 py-3 font-semibold text-ink-800 dark:text-night-ink-1">
                                @if ($question->user)
                                    [کاربر]{{ $question->user ? $question->user->fullname : $question->name }}
                                @elseif ($question->admin)
                                    [مدیر سایت]
                                @elseif ($question->seller)
                                    [فروشنده]{{ $question->seller ? $question->seller->business_name : '' }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-surface-2 px-4 py-3 align-top font-bold text-ink-600 dark:bg-night-2 dark:text-night-ink-2">در</th>
                            <td class="px-4 py-3">
                                <span class="font-semibold text-ink-800 dark:text-night-ink-1">{{ $question->commentable?->title }}</span>
                                <a class="ms-2 inline-flex items-center text-brand-600 hover:underline dark:text-brand-400" href="{{ $productUrl }}" target="_blank" rel="noopener" aria-label="مشاهده محصول">
                                    <x-admin-panel::icon name="external-link" class="size-3.5" />
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-surface-2 px-4 py-3 align-top font-bold text-ink-600 dark:bg-night-2 dark:text-night-ink-2">متن دیدگاه</th>
                            <td class="px-4 py-3">
                                {{-- متن — یا نمایشی یا قابل‌ویرایش (فقط پاسخِ خود فروشنده) --}}
                                <div x-show="!editing" class="leading-relaxed text-ink-700 dark:text-night-ink-2">
                                    {{ $question->body }}
                                    @if ($question->comment_id != null && $question->seller_id == sellerID())
                                        <div class="mt-2">
                                            <button type="button" x-on:click="editing = true"
                                                    class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-[11px] font-bold">
                                                <x-admin-panel::icon name="pencil" class="size-3.5" />
                                                ویرایش
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <fieldset x-show="editing" x-cloak>
                                    <textarea name="body" rows="4" required class="ap-input w-full">{{ $question->body }}</textarea>
                                </fieldset>
                            </td>
                        </tr>

                        @if (! $question->comment_id)
                            <tr>
                                <th class="bg-surface-2 px-4 py-3 align-top font-bold text-ink-600 dark:bg-night-2 dark:text-night-ink-2">تعداد پاسخ ها</th>
                                <td class="fa-digits px-4 py-3">{{ $question->comments->count() }}</td>
                            </tr>
                            <tr>
                                <th class="bg-surface-2 px-4 py-3 align-top font-bold text-ink-600 dark:bg-night-2 dark:text-night-ink-2">پاسخ</th>
                                <td class="px-4 py-3">
                                    <fieldset>
                                        <textarea name="replay" rows="4" class="ap-input w-full" placeholder="متن پاسخ شما به این پرسش…"></textarea>
                                    </fieldset>
                                </td>
                            </tr>
                        @endif

                        <tr>
                            <th class="bg-surface-2 px-4 py-3 align-top font-bold text-ink-600 dark:bg-night-2 dark:text-night-ink-2">تاریخ ارسال</th>
                            <td class="fa-digits px-4 py-3 text-ink-600 dark:text-night-ink-2">
                                {{ jdate($question->created_at) }} ( {{ jdate($question->created_at)->ago() }} )
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- پاورقی فرم — دکمهٔ ذخیره فقط برای پاسخِ خود فروشنده یا فرم پاسخ پرسش --}}
            @if ($question->seller_id == sellerID() || ! $question->comment_id)
                <footer class="flex items-center justify-start gap-2 border-t border-line px-4 py-3 dark:border-night-line">
                    <button type="button" x-on:click="submitForm()"
                            class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-4 text-xs font-bold"
                            :disabled="loading" x-bind:class="loading && 'opacity-60'">
                        <x-admin-panel::icon name="loader-circle" class="size-4" x-show="loading" x-cloak />
                        ذخیره
                    </button>
                    <a href="{{ $indexUrl }}" wire:navigate
                       class="ap-btn-outline inline-flex min-h-11 items-center rounded-lg px-4 text-xs font-bold">بستن</a>
                </footer>
            @endif
        </section>
    </form>
</div>
