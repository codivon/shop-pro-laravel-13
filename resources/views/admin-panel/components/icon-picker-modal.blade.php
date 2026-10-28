{{--
    فاز ۱۱۴ — مودال انتخابگر آیکون (یک‌بار به‌ازای هر صفحه — گارد IconPickerModal::$rendered)
    تب‌ها: Font Awesome / Material Design (MDI) / Lucide — سرچ + رنگ/اندازه
    درایور Alpine: window.apIconPicker (تعریف در admin.js)
    داده‌ها: JSON اینلاین (fa-icons-map / mdi-icons-map / icons-map)
--}}
@php
    $faList = array_keys(\App\Support\IconRegistry::fa());
    $mdiList = array_keys(\App\Support\IconRegistry::mdi());
    $lucide = \App\Support\IconRegistry::lucide();
    $lucideList = [];
    foreach ($lucide as $name => $inner) {
        $lucideList[] = ['name' => $name, 'inner' => $inner];
    }
@endphp
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/MaterialDesign-Webfont/7.4.47/css/materialdesignicons.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

<script type="application/json" id="ap-icon-picker-data">{!! json_encode([
    'fa' => $faList,
    'mdi' => $mdiList,
    'lucide' => $lucideList,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

<div x-data="apIconPicker" x-cloak
     x-on:open-ap-icon-picker.window="openWith($event.detail.target, $event.detail.mode)"
     x-on:keydown.escape.window="open = false"
     class="ap-icon-picker-modal" aria-hidden="true">
    {{-- پوشش پس‌زمینه --}}
    <div x-show="open" x-transition.opacity class="fixed inset-0 z-[70] bg-black/50 backdrop-blur-sm"
         x-on:click="open = false" aria-hidden="true"></div>

    {{-- پنجرهٔ اصلی --}}
    <div x-show="open" x-transition.scale.origin.center
         class="fixed inset-4 z-[71] mx-auto flex max-h-[92vh] w-auto max-w-4xl flex-col overflow-hidden rounded-3xl border border-line bg-surface shadow-2xl sm:inset-x-auto sm:left-1/2 sm:w-[min(56rem,calc(100vw-2rem))] sm:-translate-x-1/2 dark:border-night-line dark:bg-night-1"
         role="dialog" aria-modal="true" aria-label="انتخاب آیکون">

        {{-- سربرگ: عنوان + سرچ + تب‌ها --}}
        <header class="shrink-0 border-b border-line bg-surface-2/60 px-4 py-3 dark:border-night-line dark:bg-night-2/60">
            <div class="flex items-center justify-between gap-3">
                <h3 class="flex items-center gap-2 text-sm font-extrabold">
                    <x-admin-panel::icon name="shapes" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    انتخاب آیکون
                </h3>
                <button type="button" x-on:click="open = false"
                        class="min-h-11 rounded-xl p-1.5 text-ink-3 transition-colors hover:bg-surface-3 hover:text-red-500 dark:text-night-ink-2 dark:hover:bg-night-3"
                        aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4.5" />
                </button>
            </div>

            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                {{-- تب مجموعه‌ها --}}
                <div class="grid shrink-0 grid-cols-3 gap-1 rounded-2xl bg-surface-3 p-1 dark:bg-night-3/80" role="tablist" aria-label="مجموعه آیکون">
                    <button type="button" role="tab" x-on:click="tab = 'fa'; selected = null"
                            x-bind:aria-selected="String(tab === 'fa')"
                            class="flex min-h-9 items-center justify-center gap-1.5 rounded-xl px-3 text-[12px] font-extrabold transition-all"
                            x-bind:class="tab === 'fa' ? 'bg-surface text-brand-700 shadow-sm dark:bg-night-2 dark:text-brand-300' : 'text-ink-3 dark:text-night-ink-2'">
                        <i class="fa-solid fa-flag text-[13px]" aria-hidden="true"></i> Font Awesome
                    </button>
                    <button type="button" role="tab" x-on:click="tab = 'mdi'; selected = null"
                            x-bind:aria-selected="String(tab === 'mdi')"
                            class="flex min-h-9 items-center justify-center gap-1.5 rounded-xl px-3 text-[12px] font-extrabold transition-all"
                            x-bind:class="tab === 'mdi' ? 'bg-surface text-brand-700 shadow-sm dark:bg-night-2 dark:text-brand-300' : 'text-ink-3 dark:text-night-ink-2'">
                        <i class="mdi mdi-material-design text-[15px]" aria-hidden="true"></i> Material
                    </button>
                    <button type="button" role="tab" x-on:click="tab = 'lucide'; selected = null"
                            x-bind:aria-selected="String(tab === 'lucide')"
                            class="flex min-h-9 items-center justify-center gap-1.5 rounded-xl px-3 text-[12px] font-extrabold transition-all"
                            x-bind:class="tab === 'lucide' ? 'bg-surface text-brand-700 shadow-sm dark:bg-night-2 dark:text-brand-300' : 'text-ink-3 dark:text-night-ink-2'">
                        <x-admin-panel::icon name="pen-tool" class="size-3.5" /> Lucide
                    </button>
                </div>

                {{-- جستجو --}}
                <div class="relative flex-1">
                    <x-admin-panel::icon name="search" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                    <input type="text" x-model="q" x-ref="q" autocomplete="off"
                           class="ap-input ps-4! pe-10! min-h-10! py-2! text-[13px]" placeholder="جستجوی آیکون…">
                </div>
            </div>
        </header>

        {{-- بدنه: شبکهٔ آیکون‌ها --}}
        <div class="min-h-0 flex-1 overflow-y-auto p-4" x-ref="grid">
            <div class="grid grid-cols-6 gap-2 sm:grid-cols-10 md:grid-cols-12">
                {{-- فاز ۱۱۷ — رنگ‌های حالت انتخاب/عادی داخل ternary آمده‌اند؛ اگر مثل قبل
                     فقط کلاسِ انتخاب به class اضافه می‌شد، ترتیب CSS تیلویند (border-line
                     بعد از border-brand-500 ساخته می‌شود) حالت انتخاب را می‌پوشاند --}}
                <template x-for="icon in filtered" :key="tab + '-' + icon.key">
                    <button type="button"
                            x-on:click="pick(icon)"
                            class="group flex min-h-14 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border p-1.5 transition-all"
                            x-bind:class="selected && selected.key === icon.key ? 'border-brand-500 bg-brand-50/50 ring-2 ring-brand-500/25 dark:border-brand-500 dark:bg-brand-500/10' : 'border-line bg-surface hover:border-brand-400 hover:bg-brand-50/50 dark:border-night-line dark:bg-night-2 dark:hover:border-brand-500/50 dark:hover:bg-brand-500/10'"
                            x-bind:title="icon.name">
                        <span class="ap-ip-icon text-ink-2 group-hover:text-brand-600 dark:text-night-ink dark:group-hover:text-brand-300" x-html="preview(icon)"></span>
                        <span class="w-full truncate text-center font-mono text-[8px] leading-3 text-ink-3 dark:text-night-ink-2" x-text="icon.name"></span>
                    </button>
                </template>
            </div>

            <div class="py-6 text-center text-[12px] font-semibold text-ink-3 dark:text-night-ink-2"
                 x-show="filtered.length === 0">
                <x-admin-panel::icon name="search-x" class="mx-auto mb-2 size-8 text-ink-3" />
                آیکونی با این نام پیدا نشد
            </div>
        </div>

        {{-- بخش دوم: رنگ و اندازه (بعد از انتخاب آیکون) --}}
        <div x-show="selected" x-cloak
             class="shrink-0 border-t-2 border-brand-200 bg-brand-50/50 px-4 py-3 dark:border-brand-500/30 dark:bg-brand-500/5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                {{-- پیش‌نمایش زنده --}}
                <div class="flex items-center gap-3">
                    <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl border border-line bg-surface ap-ip-preview dark:border-night-line dark:bg-night-2" x-html="selectedPreview"></span>
                    <div class="min-w-0">
                        <p class="font-mono text-[11px] font-bold text-ink-2 dark:text-night-ink" x-text="selected ? selected.name : ''"></p>
                        <p class="text-[10px] text-ink-3 dark:text-night-ink-2" x-text="selected ? collections[selected.tab].label : ''"></p>
                    </div>
                </div>

                {{-- رنگ --}}
                <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5">
                    <span class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2">رنگ:</span>
                    <template x-for="c in swatches" :key="'c-' + (c || 'none')">
                        <button type="button" x-on:click="color = c"
                                class="size-6 rounded-lg border border-line transition-transform hover:scale-110 dark:border-night-line"
                                x-bind:style="c ? 'background:' + c : ''"
                                x-bind:class="color === c ? 'ring-2 ring-brand-500 ring-offset-1 ring-offset-surface dark:ring-offset-night-1' : ''"
                                x-bind:aria-label="c ? 'رنگ ' + c : 'رنگ پیش‌فرض'">
                            <span x-show="!c" class="block text-[9px] font-black text-ink-3">✕</span>
                        </button>
                    </template>
                    <label class="ms-1 flex cursor-pointer items-center gap-1 rounded-lg border border-line bg-surface px-1.5 py-1 dark:border-night-line dark:bg-night-2">
                        <input type="color" x-model="color" class="size-5 cursor-pointer border-0 bg-transparent p-0" aria-label="رنگ دلخواه">
                        <span class="text-[10px] font-bold text-ink-3 dark:text-night-ink-2">دلخواه</span>
                    </label>
                </div>

                {{-- اندازه — فاز ۱۱۷: کلاس‌های رنگی انتخاب در ternary (مشکل ترتیب CSS تیلویند) --}}
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2">اندازه:</span>
                    <template x-for="s in sizes" :key="'s-' + s">
                        <button type="button" x-on:click="size = s"
                                class="min-w-10 rounded-lg border px-2 py-1 font-mono text-[11px] font-bold transition-colors hover:border-brand-400"
                                x-bind:class="size === s ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-500 dark:bg-brand-500/15 dark:text-brand-300' : 'border-line bg-surface text-ink-2 dark:border-night-line dark:bg-night-2 dark:text-night-ink'"
                                x-text="s"></button>
                    </template>
                    <button type="button" x-on:click="size = 0"
                            class="rounded-lg border px-2 py-1 text-[10px] font-bold transition-colors hover:border-brand-400"
                            x-bind:class="size === 0 ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-500 dark:bg-brand-500/15 dark:text-brand-300' : 'border-line bg-surface text-ink-3 dark:border-night-line dark:bg-night-2 dark:text-night-ink-2'">پیش‌فرض</button>
                </div>

                {{-- عملیات --}}
                <div class="flex shrink-0 items-center gap-2">
                    <button type="button" x-on:click="apply()"
                            class="ap-btn-primary inline-flex min-h-10 items-center gap-1.5 rounded-xl px-4! text-xs font-bold">
                        <x-admin-panel::icon name="check" class="size-4" />
                        تایید
                    </button>
                    <button type="button" x-on:click="resetSelection()"
                            class="ap-btn-outline inline-flex min-h-10 items-center rounded-xl px-3! text-xs font-bold">انصراف</button>
                </div>
            </div>
        </div>

        {{-- فوتر: شمارش نتایج --}}
        <footer class="shrink-0 border-t border-line px-4 py-2 text-[11px] font-semibold text-ink-3 dark:border-night-line dark:text-night-ink-2">
            <span class="fa-digits" x-text="filtered.length"></span> آیکون
            <span x-show="totalOfTab > filtered.length"> — نمایش
                <span class="fa-digits">240</span> مورد اول (برای دیدن بیشتر جستجو کنید)</span>
        </footer>
    </div>
</div>
