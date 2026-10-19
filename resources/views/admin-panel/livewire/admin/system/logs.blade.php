{{-- لاگ سیستم — بازسازی امن LaravelLogViewer (فاز ۷-ج۴) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="scroll-text" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-bold">لاگ سیستم</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">خواندن امن فایل‌های storage/logs (فقط ۵۰۰ رکورد آخر)</p>
            </div>
        </div>
        <span class="fa-digits ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
            {{ number_format($this->totalCount) }} رکورد پارس‌شده
        </span>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-4">
        {{-- فایل‌های لاگ + فیلتر سطح --}}
        <div class="space-y-4">
            <div class="ap-card overflow-hidden">
                <div class="border-b border-line p-3.5 dark:border-night-line">
                    <h3 class="flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="folder" class="size-4 text-brand-600" />
                        فایل‌های لاگ
                    </h3>
                </div>
                <ul class="max-h-72 divide-y divide-line overflow-y-auto dark:divide-night-line">
                    @forelse ($this->files as $logFile)
                        <li>
                            <button type="button" wire:click="selectFile({{ \Illuminate\Support\Js::from($logFile['name'])->toHtml() }})"
                                    class="flex w-full items-center justify-between gap-2 px-3.5 py-2.5 text-start text-[12px] transition-colors hover:bg-surface-2 dark:hover:bg-night-3 {{ $file === $logFile['name'] ? 'bg-brand-50 dark:bg-brand-500/10' : '' }}">
                                <span class="ltr truncate font-mono">{{ $logFile['name'] }}</span>
                                <span class="ltr shrink-0 text-[10px] text-ink-3 dark:text-night-ink-2">{{ number_format($logFile['size'] / 1024, 0) }} KB</span>
                            </button>
                        </li>
                    @empty
                        <li class="px-3.5 py-6 text-center text-xs text-ink-3 dark:text-night-ink-2">فایلی یافت نشد</li>
                    @endforelse
                </ul>
            </div>

            <div class="ap-card p-3.5">
                <label class="mb-1.5 block text-xs font-semibold">فیلتر سطح</label>
                <select wire:model.live="level" class="ap-input !py-2">
                    <option value="">همه سطوح</option>
                    @foreach (['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $lvl)
                        <option value="{{ $lvl }}">{{ ucfirst($lvl) }} @if(isset($this->levelCounts[$lvl])) ({{ $this->levelCounts[$lvl] }}) @endif</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- محتوای لاگ --}}
        <div class="lg:col-span-3">
            <div class="ap-card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-3.5 dark:border-night-line">
                    <h3 class="ltr flex items-center gap-2 font-mono text-sm font-bold">
                        <x-admin-panel::icon name="file-text" class="size-4 text-brand-600" />
                        {{ $file }}
                    </h3>
                    @if ($this->totalCount > $this->visibleEntries->count())
                        <button type="button" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore"
                                class="ap-btn-outline !py-1.5 !text-xs">
                            نمایش بیشتر
                            <span class="fa-digits">({{ min($this->limit + 200, 500) }} از {{ min($this->totalCount, 500) }})</span>
                        </button>
                    @endif
                </div>

                @if ($this->visibleEntries->count())
                    <div class="divide-y divide-line dark:divide-night-line">
                        @foreach ($this->visibleEntries as $entry)
                            @php
                                $levelStyle = [
                                    'emergency' => 'bg-red-600 text-white',
                                    'alert'     => 'bg-red-600 text-white',
                                    'critical'  => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-400',
                                    'error'     => 'bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400',
                                    'warning'   => 'bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400',
                                    'notice'    => 'bg-sky-50 text-sky-700 dark:bg-sky-500/12 dark:text-sky-400',
                                    'info'      => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400',
                                    'debug'     => 'bg-surface-2 text-ink-3 dark:bg-night-3 dark:text-night-ink-2',
                                ][$entry['level']] ?? 'bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2';
                            @endphp
                            <div class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="ltr ap-badge text-[10px] uppercase {{ $levelStyle }}">{{ $entry['level'] }}</span>
                                    <span class="ltr fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">{{ $entry['date'] }}</span>
                                    <span class="ltr ap-badge bg-surface-2 text-[10px] text-ink-3 dark:bg-night-3 dark:text-night-ink-2">{{ $entry['env'] }}</span>
                                </div>
                                <p class="ltr mt-1.5 break-all text-[12px] leading-5 text-ink-2 dark:text-night-ink-2">{{ \Illuminate\Support\Str::limit($entry['message'], 400) }}</p>
                                @if ($entry['stack'])
                                    <details class="mt-1.5">
                                        <summary class="cursor-pointer text-[11px] font-semibold text-brand-600">نمایش جزئیات</summary>
                                        <pre class="ltr mt-1.5 max-h-52 overflow-auto rounded-xl bg-slate-950/95 p-3 text-[10px] leading-5 text-slate-200">{{ $entry['stack'] }}</pre>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center gap-2 p-12 text-center">
                        <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                            <x-admin-panel::icon name="scroll-text" class="size-6" />
                        </span>
                        <p class="text-sm font-bold">رکوردی برای نمایش وجود ندارد</p>
                        <p class="text-xs text-ink-3 dark:text-night-ink-2">فایل خالی است یا هیچ رکوردی با سطح انتخابی وجود ندارد.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
