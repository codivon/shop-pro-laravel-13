{{-- فایل‌های آپلودشده‌ی درون‌ریزی با قابلیت حذف — فاز ۱۹ --}}
<div class="ap-card overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-4 dark:border-night-line">
        <h2 class="flex items-center gap-2 text-sm font-bold">
            <x-admin-panel::icon name="folder-open" class="size-4.5 text-brand-600 dark:text-brand-400" />
            فایل‌های آپلودشده
        </h2>
        <span class="fa-digits ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">{{ number_format(count($this->uploadedFiles)) }} فایل</span>
    </div>

    @if (count($this->uploadedFiles))
        <ul class="divide-y divide-line/70 dark:divide-night-line/70">
            @foreach ($this->uploadedFiles as $file)
                <li wire:key="upfile-{{ md5($file['name'] . $file['date']) }}" class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-surface-2 dark:hover:bg-night-3/60">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400">
                        <x-admin-panel::icon name="file-spreadsheet" class="size-4.5" />
                    </span>
                    <div class="min-w-0 flex-1 leading-tight">
                        <p class="ltr truncate text-[12px] font-bold">{{ $file['name'] }}</p>
                        <p class="fa-digits mt-0.5 text-[10.5px] text-ink-3 dark:text-night-ink-2">
                            {{ number_format(ceil($file['size'] / 1024)) }} کیلوبایت — {{ jdate($file['date'], 'd F Y | H:i') }}
                        </p>
                    </div>
                    <button type="button" wire:click="deleteUploadedFile({{ \Illuminate\Support\Js::from($file['name'])->toHtml() }})"
                            wire:loading.attr="disabled" wire:target="deleteUploadedFile"
                            class="rounded-lg p-2 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                            title="حذف فایل" aria-label="حذف {{ $file['name'] }}">
                        <x-admin-panel::icon name="trash-2" class="size-4" />
                    </button>
                </li>
            @endforeach
        </ul>
    @else
        <div class="flex flex-col items-center gap-1.5 py-8 text-ink-3 dark:text-night-ink-2">
            <x-admin-panel::icon name="inbox" class="size-8 opacity-40" />
            <p class="text-[12.5px] font-medium">فایل آپلودشده‌ای وجود ندارد</p>
        </div>
    @endif
</div>
