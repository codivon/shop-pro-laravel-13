{{-- مدیریت فایل‌ها — پورت back/file-manager.blade.php (فاز ۷-ج۴) --}}
{{-- محتوای اصلی حفظ شده: همان iframe فایل‌منیجر داخل قالب جدید --}}
<div class="mx-auto max-w-7xl space-y-4">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="folder-open" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-bold">مدیریت فایل‌ها</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">فایل‌منیجر پروژه (همان موتور قدیمی داخل قالب جدید)</p>
            </div>
        </div>
    </div>

    {{-- iframe فایل‌منیجر (پورت دقیق — آدرس نسبی np یا روت قدیمی) --}}
    <div class="ap-card overflow-hidden">
        <iframe src="{{ $this->iframeUrl }}" height="700px" width="100%" title="مدیریت فایل‌ها"
                class="block w-full border-0 bg-white"></iframe>
    </div>
</div>
