{{-- ثبت درخواست پشتیبانی (فاز ۱۰۸) — پورت sellers-old/panel/tickets/create.blade.php
     فرم کلاسیک POST به seller.tickets.store با عیناً همان فیلدهای قدیمی
     (subject / priority / message / upload_files[]) --}}
<div x-data="{
        loading: false,

        // ارسال فرم کلاسیک — کنترلر قدیمی response('success') می‌دهد؛ بازگشت به فهرست
        async submitForm() {
            this.loading = true;
            try {
                const res = await fetch(@js($storeUrl), {
                    method: 'POST',
                    body: new FormData(this.$refs.form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });

                if (res.ok) {
                    this.$dispatch('toast', { message: 'درخواست پشتیبانی با موفقیت ثبت شد.', type: 'success' });
                    setTimeout(() => window.Livewire ? Livewire.navigate(@js($indexUrl)) : (window.location.href = @js($indexUrl)), 800);
                    return;
                }

                const data = await res.json().catch(() => ({}));
                const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'خطا در ثبت درخواست، دوباره تلاش کنید.');
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
                ثبت درخواست پشتیبانی
            </h1>
            <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">اینجا می‌توانید برای مشکل خود «درخواست پشتیبانی» ثبت کنید.</p>
        </div>
        <a href="{{ $indexUrl }}" wire:navigate
           class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به فهرست
        </a>
    </div>

    {{-- ================= فرم کلاسیک — عیناً فیلدهای ویوی قدیمی ================= --}}
    <form x-ref="form" method="POST" action="{{ $storeUrl }}" enctype="multipart/form-data" x-on:submit.prevent>
        @csrf

        <section class="ap-card p-4 sm:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">موضوع<span class="text-red-500">*</span></span>
                    <input type="text" name="subject" required class="ap-input w-full">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">اولویت<span class="text-red-500">*</span></span>
                    <select name="priority" class="ap-input w-full">
                        <option value="low">کم</option>
                        <option value="medium">متوسط</option>
                        <option value="hight">زیاد</option>
                    </select>
                </label>
            </div>

            <label class="mt-4 block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">پیام<span class="text-red-500">*</span></span>
                <textarea name="message" rows="5" required class="ap-input w-full"></textarea>
            </label>

            <label class="mt-4 block">
                <span class="mb-1 block text-xs font-semibold text-ink-600 dark:text-night-ink-2">فایل های پیوست</span>
                <input type="file" accept="image/*,.zip" name="upload_files[]" multiple
                       class="ap-input w-full file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white">
                <small class="mt-1 block text-[11px] text-ink-400 dark:text-night-ink-2">فرمت‌های مجاز: png، jpeg، jpg، zip</small>
            </label>

            <div class="mt-5 border-t border-line pt-4 dark:border-night-line">
                <button type="button" x-on:click="submitForm()"
                        class="ap-btn-primary inline-flex min-h-11 items-center gap-1.5 rounded-lg px-5 text-xs font-bold"
                        :disabled="loading" x-bind:class="loading && 'opacity-60'">
                    <x-admin-panel::icon name="loader-circle" class="size-4" x-show="loading" x-cloak />
                    ثبت تیکت
                </button>
            </div>
        </section>
    </form>
</div>
