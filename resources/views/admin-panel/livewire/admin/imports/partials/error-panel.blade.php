{{-- گزارش خطاهای آخرین درون‌ریزی — فاز ۱۹ (بازطراحی مشترک ۳ صفحه) --}}
{{-- متغیرها: $label --}}
@if ($this->errorData && ! empty($this->errorData['failed_rows']))
    @php
        $totalCount = $this->errorData['total_count'] ?? 0;
        $successCount = $this->errorData['success_count'] ?? 0;
        $failCount = $this->errorData['fail_count'] ?? 0;
        $successRate = $totalCount > 0 ? round(($successCount / $totalCount) * 100, 1) : 0;
    @endphp
    <div class="ap-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-4 dark:border-night-line">
            <h2 class="flex items-center gap-2 text-sm font-bold text-red-600">
                <x-admin-panel::icon name="triangle-alert" class="size-4.5" />
                خطاهای آخرین درون‌ریزی {{ $label }}
            </h2>
            <span class="fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">{{ $this->errorFileDate }}</span>
        </div>

        <div class="grid grid-cols-3 gap-3 p-4 text-center">
            <div class="rounded-2xl bg-emerald-50 p-3 dark:bg-emerald-500/10">
                <span class="fa-digits block text-xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ number_format($successCount) }}</span>
                <span class="text-[11px] text-ink-3 dark:text-night-ink-2">موفق</span>
            </div>
            <div class="rounded-2xl bg-red-50 p-3 dark:bg-red-500/10">
                <span class="fa-digits block text-xl font-extrabold text-red-600 dark:text-red-400">{{ number_format($failCount) }}</span>
                <span class="text-[11px] text-ink-3 dark:text-night-ink-2">ناموفق</span>
            </div>
            <div class="rounded-2xl bg-surface-2 p-3 dark:bg-night-3">
                <span class="fa-digits block text-xl font-extrabold">{{ number_format($successRate) }}٪</span>
                <span class="text-[11px] text-ink-3 dark:text-night-ink-2">نرخ موفقیت</span>
            </div>
        </div>

        <div class="ap-progress mx-4 mb-3 h-2 overflow-hidden rounded-full bg-surface-2 dark:bg-night-3">
            <div class="h-full rounded-full bg-linear-to-l from-emerald-500 to-emerald-400" style="width: {{ $successRate }}%"></div>
        </div>

        <div class="max-h-64 overflow-y-auto border-t border-line px-4 py-3 dark:border-night-line">
            <ul class="space-y-1.5 text-[12px]">
                @foreach (array_slice($this->errorData['failed_rows'] ?? [], 0, 20) as $row)
                    <li class="rounded-xl bg-red-50 px-3 py-2 leading-5 text-red-700 dark:bg-red-500/10 dark:text-red-400">
                        @if (! empty($row['title']))
                            <b>{{ $row['title'] }}</b> —
                        @endif
                        {{ $row['error'] ?? json_encode($row, JSON_UNESCAPED_UNICODE) }}
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="flex items-center justify-between border-t border-line p-3 dark:border-night-line">
            <p class="text-[10.5px] text-ink-3 dark:text-night-ink-2">
                <span class="ltr">{{ $this->errorFileName }}</span>
            </p>
            <button type="button" wire:click="deleteErrorFile({{ \Illuminate\Support\Js::from($this->errorFileName)->toHtml() }})"
                    class="ap-btn-ghost !py-1.5 !text-xs text-red-600 hover:!bg-red-50 dark:hover:!bg-red-500/10">
                <x-admin-panel::icon name="trash-2" class="size-3.5" />
                حذف فایل خطا
            </button>
        </div>
    </div>
@endif
