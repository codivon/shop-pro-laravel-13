{{-- لاگ های سیستم — فاز ۲۰: بازطراحی کامل ترمینالی مثل نمونه‌ی کاربر
     سربرگ ترمینال تیره با سه نقطه رنگی + نام فایل + حجم + فیلتر سطح + مرتب‌سازی + جستجو
     رکوردها با بج سطح، تاریخ، متن دوخطی، بازکردن جزئیات، حذف تکی و تیک‌خوردن
     دانلود لاگ + پاک کردن کل لاگ (بدون target=_blank — صفحه‌ی داخلی) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ صفحه --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="scroll-text" class="size-6" />
            </span>
            <div>
                <h1 class="text-lg font-bold">لاگ های سیستم</h1>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">آخرین رویدادها و خطاهای ثبت‌شده در فایل لاگ سیستم؛ برای مشاهده جزئیات روی هر ریف کلیک کنید.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="fa-digits ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                {{ number_format($this->totalCount) }} رکورد
            </span>
            <button type="button" wire:click="downloadLog" wire:loading.attr="disabled" wire:target="downloadLog"
                    class="ap-btn-outline !py-2 !text-xs">
                <x-admin-panel::icon name="download" class="size-4" />
                دانلود لاگ
            </button>
            <button type="button" wire:click="clearLog" wire:loading.attr="disabled" wire:target="clearLog"
                    wire:confirm="کل محتوای فایل لاگ «{{ $file }}» پاک شود؟ این عمل غیرقابل بازگشت است."
                    class="ap-btn-ghost !py-2 !text-xs !text-red-600 hover:!bg-red-50 dark:!text-red-400 dark:hover:!bg-red-500/10">
                <x-admin-panel::icon name="trash-2" class="size-4" />
                پاک کردن لاگ
            </button>
        </div>
    </div>

    {{-- انتخاب فایل لاگ --}}
    @if ($this->files->count() > 1)
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-ink-3 dark:text-night-ink-2">فایل لاگ:</span>
            @foreach ($this->files as $logFile)
                <button type="button" wire:click="selectFile('{{ $logFile['name'] }}')"
                        class="ltr rounded-xl border px-3 py-1.5 font-mono text-[11px] transition-colors {{ $file === $logFile['name'] ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-500/50 dark:bg-brand-500/10 dark:text-brand-400' : 'border-line bg-surface text-ink-2 hover:border-brand-300 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2' }}">
                    {{ $logFile['name'] }}
                    <span class="fa-digits ms-1 text-[9px] text-ink-3 dark:text-night-ink-2">{{ number_format($logFile['size'] / 1024, 0) }}KB</span>
                </button>
            @endforeach
        </div>
    @endif

    {{-- (نوار حذف گروهی داخل کارت ترمینال است — در اسکوپ sel) --}}

    {{-- کارت ترمینال --}}
    {{-- فاز ۲۰: انتخاب رکوردها سمت کلاینت (Alpine) — درس فاز ۱۷: wire:model آرایه‌ای
         deferred تا commit بعدی سینک نمی‌شود؛ اینجا نوار حذف گروهی باید فوری بیاید --}}
    <div x-data="{
             sel: [],
             toggleSel(uid) { this.sel = this.sel.includes(uid) ? this.sel.filter((u) => u !== uid) : [...this.sel, uid]; },
             selectAllVisible() {
                 const uids = [...this.$root.querySelectorAll('[data-log-check]')].map((b) => b.value);
                 const allOn = uids.length > 0 && uids.every((u) => this.sel.includes(u));
                 this.sel = allOn ? this.sel.filter((u) => !uids.includes(u)) : [...new Set([...this.sel, ...uids])];
             },
             deleteSelected() {
                 if (!this.sel.length) return;
                 const uids = [...this.sel];
                 this.sel = [];
                 $wire.deleteEntries(uids);
             },
         }"
         class="overflow-hidden rounded-3xl border border-line bg-surface shadow-card dark:border-night-line dark:bg-night-2"
         wire:key="log-terminal-{{ $file }}">

        {{-- سربرگ ترمینال (تیره) --}}
        <div class="flex flex-wrap items-center gap-3 bg-slate-950 px-4 py-3">
            <div class="flex items-center gap-1.5" aria-hidden="true">
                <span class="size-3 rounded-full bg-red-500"></span>
                <span class="size-3 rounded-full bg-amber-400"></span>
                <span class="size-3 rounded-full bg-emerald-500"></span>
            </div>

            <div class="ltr flex items-baseline gap-2">
                <span class="font-mono text-sm font-bold text-slate-100">{{ $file }}</span>
                <span class="fa-digits text-[10px] text-slate-400">{{ number_format($this->currentFileSize / 1024, 1) }} KB</span>
            </div>

            <div class="ms-auto flex flex-wrap items-center gap-2">
                {{-- فیلتر سطح --}}
                <select wire:model.live="level" aria-label="فیلتر سطح لاگ"
                        class="h-9 rounded-xl border border-slate-700 bg-slate-900 px-2.5 text-[11px] text-slate-200 outline-none focus:border-brand-500">
                    <option value="">همه سطوح</option>
                    @foreach (['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $lvl)
                        <option value="{{ $lvl }}">{{ ucfirst($lvl) }} @if(isset($this->levelCounts[$lvl])) ({{ number_format($this->levelCounts[$lvl]) }}) @endif</option>
                    @endforeach
                </select>

                {{-- مرتب‌سازی --}}
                <select wire:model.live="sort" aria-label="ترتیب نمایش"
                        class="h-9 rounded-xl border border-slate-700 bg-slate-900 px-2.5 text-[11px] text-slate-200 outline-none focus:border-brand-500">
                    <option value="newest">جدیدترین</option>
                    <option value="oldest">قدیمی‌ترین</option>
                </select>

                {{-- جستجو --}}
                <div class="relative">
                    <x-admin-panel::icon name="search" class="pointer-events-none absolute inset-y-0 end-2.5 my-auto size-3.5 text-slate-400" />
                    <input type="search" wire:model.live.debounce.400ms="search" placeholder="جستجو در لاگ..." aria-label="جستجو در لاگ"
                           class="h-9 w-44 rounded-xl border border-slate-700 bg-slate-900 ps-3 pe-8 text-[11px] text-slate-200 placeholder:text-slate-500 outline-none focus:border-brand-500">
                </div>
            </div>
        </div>

        {{-- نوار حذف گروهی (فوری — سمت کلاینت) --}}
        <div x-show="sel.length > 0" x-cloak
             class="flex items-center justify-between gap-3 border-b border-red-200 bg-red-50/70 px-4 py-3 dark:border-red-500/25 dark:bg-red-500/10">
            <p class="flex items-center gap-2 text-xs font-bold text-red-700 dark:text-red-400">
                <x-admin-panel::icon name="alert-triangle" class="size-4" />
                <span class="fa-digits" x-text="sel.length.toLocaleString('fa-IR')"></span> رکورد انتخاب شده است
            </p>
            <div class="flex items-center gap-2">
                <button type="button" x-on:click="sel = []" class="ap-btn-ghost !py-1.5 !text-xs">لغو انتخاب</button>
                <button type="button" x-on:click="if (confirm('رکوردهای انتخاب‌شده از فایل لاگ حذف شوند؟')) deleteSelected()"
                        class="ap-btn-primary !bg-red-600 !py-1.5 !text-xs hover:!bg-red-700">
                    <x-admin-panel::icon name="trash-2" class="size-3.5" />
                    حذف انتخاب‌شده‌ها
                </button>
            </div>
        </div>

        {{-- نوار انتخاب همه --}}
        @if ($this->visibleEntries->count())
            <div class="flex items-center justify-between gap-3 border-b border-line bg-surface-2/60 px-4 py-2 dark:border-night-line dark:bg-night-3/40">
                <label class="flex cursor-pointer items-center gap-2 text-[11px] font-semibold text-ink-2 dark:text-night-ink-2">
                    <input type="checkbox" x-on:change="selectAllVisible()"
                           :checked="sel.length > 0 && sel.length >= document.querySelectorAll('[data-log-check]').length"
                           class="size-3.5 accent-brand-600">
                    انتخاب همه (<span class="fa-digits">{{ number_format($this->visibleEntries->count()) }}</span> رکورد نمایش‌داده‌شده)
                </label>
                @if ($this->totalCount > $this->visibleEntries->count())
                    <button type="button" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore"
                            class="ap-btn-ghost !py-1 !text-[11px]">
                        نمایش بیشتر <span class="fa-digits">({{ number_format($this->visibleEntries->count()) }} از {{ number_format($this->totalCount) }})</span>
                    </button>
                @endif
            </div>
        @endif

        {{-- رکوردها --}}
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
                    <div x-data="{ open: false }"
                         class="group/row px-4 py-3 transition-colors hover:bg-surface-2/50 dark:hover:bg-night-3/40"
                         wire:key="log-row-{{ $loop->index }}-{{ $entry['uid'] }}">

                        <div class="flex items-start gap-3">
                            {{-- تیک انتخاب (سمت کلاینت) --}}
                            <label class="mt-0.5 shrink-0 cursor-pointer" title="انتخاب برای حذف">
                                <input type="checkbox" data-log-check value="{{ $entry['uid'] }}"
                                       :checked="sel.includes('{{ $entry['uid'] }}')"
                                       x-on:change="toggleSel('{{ $entry['uid'] }}')"
                                       class="size-3.5 accent-brand-600" aria-label="انتخاب این رکورد">
                            </label>

                            {{-- بدنه رکورد — کلیک = باز/بسته شدن جزئیات --}}
                            <div class="min-w-0 flex-1 cursor-pointer select-none" x-on:click="open = !open">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="ltr ap-badge text-[10px] font-bold uppercase {{ $levelStyle }}">{{ $entry['level'] }}</span>
                                    <span class="ltr fa-digits text-[11px] text-ink-3 dark:text-night-ink-2">{{ $entry['date'] }}</span>
                                    <span class="ltr ap-badge bg-surface-2 text-[10px] text-ink-3 dark:bg-night-3 dark:text-night-ink-2">{{ $entry['env'] }}</span>
                                </div>
                                <p class="ltr mt-1.5 line-clamp-2 break-all text-left font-mono text-[12px] leading-5 text-ink-2 dark:text-night-ink-2"
                                   x-show="!open">{{ \Illuminate\Support\Str::limit($entry['message'], 400) }}</p>

                                {{-- جزئیات باز شده --}}
                                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150"
                                     x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                                    <p class="ltr mt-1.5 break-all text-left font-mono text-[12px] leading-5 text-ink dark:text-night-ink">{{ $entry['message'] }}</p>
                                    @if ($entry['stack'])
                                        <pre class="ltr mt-2 max-h-64 overflow-auto text-left rounded-2xl bg-slate-950/95 p-3.5 font-mono text-[10px] leading-5 text-slate-200 shadow-inner">{{ $entry['stack'] }}</pre>
                                    @endif
                                </div>
                            </div>

                            {{-- ابزارهای ردیف --}}
                            <div class="flex shrink-0 items-center gap-1">
                                @if ($entry['stack'] !== '')
                                    <button type="button" x-on:click="open = !open"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3"
                                            :aria-expanded="open ? 'true' : 'false'" aria-label="نمایش جزئیات">
                                        <x-admin-panel::icon name="chevron-down" class="size-4 transition-transform duration-200" x-bind:class="open ? 'rotate-180' : ''" />
                                    </button>
                                @endif
                                <button type="button" wire:click="deleteEntries(['{{ $entry['uid'] }}'])"
                                        wire:loading.attr="disabled" wire:target="deleteEntries"
                                        wire:confirm="این رکورد از فایل لاگ حذف شود؟"
                                        class="rounded-lg p-1.5 text-ink-3 opacity-0 transition-all hover:bg-red-50 hover:text-red-600 focus-visible:opacity-100 group-hover/row:opacity-100 dark:text-night-ink-2 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                                        aria-label="حذف این رکورد">
                                    <x-admin-panel::icon name="trash-2" class="size-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center gap-2 p-14 text-center">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-2 text-ink-3 dark:bg-night-3">
                    <x-admin-panel::icon name="scroll-text" class="size-6" />
                </span>
                <p class="text-sm font-bold">رکوردی برای نمایش وجود ندارد</p>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">فایل خالی است یا هیچ رکوردی با سطح/جستجوی انتخابی وجود ندارد.</p>
            </div>
        @endif
    </div>
</div>
