{{-- آپلود قالب جدید — پورت back/themes/create.blade.php (فاز ۷-ج۴) — بازطراحی drag&drop فاز ۹-ه (کامپوننت ThemeCreate دست‌نخورده) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-3xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('np.themes.index') }}" class="ap-btn-ghost !p-2" title="بازگشت به لیست قالب‌ها">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
            </a>
            <span class="flex size-11 items-center justify-center rounded-2xl bg-linear-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-500/25">
                <x-admin-panel::icon name="upload" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-bold">افزودن قالب جدید</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">فایل zip قالب را آپلود کنید</p>
            </div>
        </div>
    </div>

    {{-- فرم آپلود (پورت store قدیمی) --}}
    <form wire:submit="store" class="space-y-5">
        <div class="ap-card p-4 sm:p-5">

            {{-- ناحیه‌ی drag & drop --}}
            <div x-data="{
                        dragging: false,
                        drop(e) {
                            this.dragging = false;
                            const files = e.dataTransfer?.files;
                            if (! files?.length) { return; }
                            const input = this.$refs.fileInput;
                            input.files = files;
                            input.dispatchEvent(new Event('change'));
                        }
                    }"
                 x-on:dragover.prevent="dragging = true"
                 x-on:dragleave.prevent="if (! $event.relatedTarget || ! $el.contains($event.relatedTarget)) { dragging = false; }"
                 x-on:drop.prevent="drop($event)"
                 x-on:click="$refs.fileInput.click()"
                 role="button" tabindex="0"
                 x-on:keydown.enter.prevent="$refs.fileInput.click()"
                 :aria-label="'انتخاب فایل قالب'"
                 class="relative flex cursor-pointer flex-col items-center justify-center gap-3 rounded-3xl border-2 border-dashed p-8 text-center transition-all duration-200 sm:p-10"
                 :class="dragging
                            ? 'border-brand-500 bg-brand-50/70 scale-[1.01] dark:bg-brand-500/10'
                            : 'border-line bg-surface-2/60 hover:border-brand-500/60 hover:bg-brand-50/40 dark:border-night-line dark:bg-night-3/40 dark:hover:bg-brand-500/5'">

                <input type="file" accept=".zip" wire:model="file" x-ref="fileInput" class="sr-only" aria-label="فایل zip قالب">

                <span class="flex size-16 items-center justify-center rounded-3xl bg-linear-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-500/25 transition-transform duration-200"
                      :class="dragging ? 'scale-110' : ''">
                    <x-admin-panel::icon name="upload" class="size-8" />
                </span>

                <p class="text-sm font-bold">
                    فایل قالب را اینجا رها کنید یا <span class="text-brand-600 underline decoration-brand-300 underline-offset-4 dark:text-brand-400">انتخاب کنید</span>
                </p>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                    فقط فایل با فرمت <code class="ltr rounded bg-surface-3 px-1.5 py-0.5 font-bold dark:bg-night-3">.zip</code> — حداقل شامل پوشه‌ی src و config قالب
                </p>

                @error('file') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- فایل انتخاب‌شده --}}
            @if ($file)
                <p class="flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50/70 px-3.5 py-2.5 text-xs font-semibold text-brand-700 dark:border-brand-500/25 dark:bg-brand-500/10 dark:text-brand-400">
                    <x-admin-panel::icon name="file-archive" class="size-4 shrink-0" />
                    فایل انتخاب‌شده:
                    <span class="ltr truncate">{{ $file->getClientOriginalName() }}</span>
                </p>
            @endif

            <p class="mt-4 flex items-start gap-2 rounded-xl bg-sky-50 p-3 text-[11px] leading-5 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400">
                <x-admin-panel::icon name="info" class="mt-0.5 size-3.5 shrink-0" />
                پس از آپلود، قالب استخراج، نصب و به‌عنوان قالب فعال تنظیم می‌شود (پورت دقیق رفتار قدیمی: dump-autoload، publish و shop:link خودکار اجرا می‌شود).
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" wire:loading.attr="disabled" wire:target="store" class="ap-btn !py-2.5">
                <x-admin-panel::icon name="plus" class="size-4" />
                افزودن قالب
            </button>
            <span wire:loading wire:target="store" class="flex items-center gap-2 text-xs text-ink-3 dark:text-night-ink-2">
                <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                </svg>
                در حال پردازش قالب… (ممکن است چند ثانیه طول بکشد)
            </span>
        </div>
    </form>
</div>
