{{-- درون‌ریزی مقالات از اکسل — پورت back/imports/postsExcelImport.blade.php (فاز ۷-ج۴) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex items-center gap-3">
        <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
            <x-admin-panel::icon name="file-text" class="size-6" />
        </span>
        <div>
            <h1 class="text-lg font-bold">افزودن نوشته‌ها از فایل Excel</h1>
            <p class="text-xs text-ink-3 dark:text-night-ink-2">فایل اکسل مقالات را با ترتیب ستون‌های زیر آماده کنید</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-5">
        {{-- انتخاب فیلدها --}}
        <form wire:submit="store" class="ap-card p-4 sm:p-5 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="check" class="size-4 text-brand-600" />
                    فیلدهای مورد نظر را انتخاب کنید
                </h2>
            </div>
            <p class="mt-1 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                ستون‌هایی که می‌خواهید در فایل اکسل وارد کنید را تیک بزنید.
            </p>

            <ul class="mt-3 grid grid-cols-1 gap-1.5">
                @foreach ($fields as $key => $field)
                    <li>
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-line px-3 py-2 text-[13px] transition-colors hover:bg-surface-2 has-[:checked]:border-brand-300 has-[:checked]:bg-brand-50 dark:border-night-line dark:hover:bg-night-3 dark:has-[:checked]:bg-brand-500/10">
                            <input type="checkbox" value="1" wire:model="filters.{{ $key }}"
                                   @if($field["required"]) disabled @endif
                                   class="size-4 rounded border-line text-brand-600 focus:ring-brand-500 dark:border-night-line">
                            <span class="font-semibold">{{ $field['label'] }}</span>
                            @if ($field['required'])
                                <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400 !text-[10px]">الزامی</span>
                            @endif
                            @isset($field['sample'])
                                <span class="ltr ms-auto hidden text-[10px] text-ink-3 dark:text-night-ink-2 sm:inline">{{ $field['sample'] }}</span>
                            @endisset
                        </label>
                    </li>
                @endforeach
            </ul>

            <div class="mt-3 flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-[11px] leading-5 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-3.5 shrink-0" />
                <span>بسیار مهم است که ترتیب ستون‌های فایل اکسل شما دقیقاً با ترتیب فیلدهای بالا یکسان باشد و ردیف اول عناوین ستون‌ها باشد.</span>
            </div>

            <label class="mt-3 flex cursor-pointer items-center gap-2 text-[13px]">
                <input type="checkbox" value="1" wire:model="updateDuplicate"
                       class="size-4 rounded border-line text-brand-600 focus:ring-brand-500 dark:border-night-line">
                <span>بروزرسانی مقاله تکراری (در صورت وجود slug تکراری، اطلاعات مقاله به‌روزرسانی شود)</span>
            </label>

            <div class="mt-4">
                <label class="mb-1.5 block text-xs font-semibold">انتخاب فایل Excel <span class="text-red-500">*</span></label>
                <input type="file" accept=".xlsx,.csv" wire:model="file"
                       class="block w-full cursor-pointer rounded-xl border border-line bg-transparent text-xs text-ink-2
                              file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5
                              file:text-xs file:font-semibold file:text-white hover:file:bg-brand-700 dark:border-night-line">
                @error('file') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                @error('filters.title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="store" class="ap-btn mt-4 w-full !py-2.5">
                <x-admin-panel::icon name="upload" class="size-4" />
                افزودن فایل و ایجاد نوشته‌ها
            </button>
        </form>

        {{-- راهنما + فایل خطا --}}
        <div class="space-y-5 lg:col-span-3">
            <div class="ap-card overflow-hidden">
                <div class="border-b border-line p-4 dark:border-night-line">
                    <h2 class="flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="list" class="size-4 text-brand-600" />
                        ترتیب ستون‌های فایل اکسل
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-[13px]">
                        <thead>
                            <tr class="bg-surface-2 text-xs text-ink-3 dark:bg-night-3 dark:text-night-ink-2">
                                <th class="px-3 py-2.5 text-start font-semibold">#</th>
                                <th class="px-3 py-2.5 text-start font-semibold">ستون (کلید)</th>
                                <th class="px-3 py-2.5 text-start font-semibold">عنوان</th>
                                <th class="px-3 py-2.5 text-start font-semibold">نمونه</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line dark:divide-night-line">
                            @foreach ($fields as $key => $field)
                                <tr>
                                    <td class="fa-digits px-3 py-2 text-ink-3">{{ $loop->iteration }}</td>
                                    <td class="ltr px-3 py-2 font-mono text-[11px]">{{ $key }}</td>
                                    <td class="px-3 py-2 font-semibold">{{ $field['label'] }} @if($field['required'])<span class="text-red-500">*</span>@endif</td>
                                    <td class="ltr px-3 py-2 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $field['sample'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- فایل خطاهای واردات (پورت بخش پایین ویوی قدیمی) --}}
            @if ($this->errorData && !empty($this->errorData['failed_rows']))
                <div class="ap-card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-4 dark:border-night-line">
                        <h2 class="flex items-center gap-2 text-sm font-bold text-red-600">
                            <x-admin-panel::icon name="triangle-alert" class="size-4" />
                            خطاهای آخرین درون‌ریزی مقالات
                        </h2>
                        <span class="text-[11px] text-ink-3 dark:text-night-ink-2">{{ $this->errorFileDate }}</span>
                    </div>
                    @php
                        $totalCount = $this->errorData['total_count'] ?? 0;
                        $successCount = $this->errorData['success_count'] ?? 0;
                        $failCount = $this->errorData['fail_count'] ?? 0;
                        $successRate = $totalCount > 0 ? round(($successCount / $totalCount) * 100, 1) : 0;
                    @endphp
                    <div class="grid grid-cols-3 gap-3 p-4 text-center">
                        <div class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-500/10">
                            <span class="fa-digits block text-lg font-extrabold text-emerald-600">{{ number_format($successCount) }}</span>
                            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">موفق</span>
                        </div>
                        <div class="rounded-xl bg-red-50 p-3 dark:bg-red-500/10">
                            <span class="fa-digits block text-lg font-extrabold text-red-600">{{ number_format($failCount) }}</span>
                            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">ناموفق</span>
                        </div>
                        <div class="rounded-xl bg-surface-2 p-3 dark:bg-night-3">
                            <span class="fa-digits block text-lg font-extrabold">{{ $successRate }}%</span>
                            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">نرخ موفقیت</span>
                        </div>
                    </div>
                    <div class="max-h-64 overflow-y-auto border-t border-line px-4 py-3 dark:border-night-line">
                        <ul class="space-y-1.5 text-[12px]">
                            @foreach (array_slice($this->errorData['failed_rows'] ?? [], 0, 20) as $row)
                                <li class="rounded-lg bg-red-50 px-3 py-2 leading-5 text-red-700 dark:bg-red-500/10 dark:text-red-400">
                                    @if (!empty($row['title']))
                                        <b>{{ $row['title'] }}</b> —
                                    @endif
                                    {{ $row['error'] ?? json_encode($row, JSON_UNESCAPED_UNICODE) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="border-t border-line p-3 dark:border-night-line">
                        <button type="button" wire:click="deleteErrorFile({{ \Illuminate\Support\Js::from($this->errorFileName)->toHtml() }})"
                                class="ap-btn-ghost !py-1.5 !text-xs text-red-600 hover:!bg-red-50 dark:hover:!bg-red-500/10">
                            <x-admin-panel::icon name="trash-2" class="size-3.5" />
                            حذف فایل خطا
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
