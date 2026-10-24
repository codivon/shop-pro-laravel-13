{{-- فرم ایجاد/ویرایش صفحه — پنل جدید (فاز ۶) — پورت ۱:۱ PageController@store/update --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
@php
    $isEdit = $editing;
@endphp
@php
    /*
    |--------------------------------------------------------------------------
    | گالری ویجت‌ها و فرم‌ها — پورت مودال «گالیــری ویجت‌ها و فرم‌ها» قدیمی
    | (مرجع: resources/views/back/pages/create.blade.php از خط ~۱۶۰ تا پایان)
    |--------------------------------------------------------------------------
    | داده‌ها عیناً مثل قدیمی: merge دو کانفیگ front.home-widgets و
    | front.posts-widgets + شورتکد [widget-KEY] + تصویر با theme_asset.
    | گرادیان‌های کارت‌های بدون تصویر با رنگ‌های برند پنل (سبز/کهربا/مزاله).
    */
    $wgGradients = [
        'linear-gradient(135deg, #10b981 0%, #14b8a6 100%)', // سبز زمردی → فیروزه‌ای
        'linear-gradient(135deg, #f59e0b 0%, #f97316 100%)', // کهربایی → نارنجی
        'linear-gradient(135deg, #d946ef 0%, #ec4899 100%)', // مزاله‌ای → صورتی
        'linear-gradient(135deg, #22c55e 0%, #84cc16 100%)', // سبز → لیمویی
        'linear-gradient(135deg, #ec4899 0%, #f43f5e 100%)', // صورتی → رز
        'linear-gradient(135deg, #14b8a6 0%, #10b981 100%)', // فیروزه‌ای → زمردی
    ];
    $wgIcons = ['package-2', 'package', 'layout-template', 'sliders-horizontal', 'layout-dashboard', 'share-2'];

    $wgIconSvg = static function (string $name): string {
        /* فاز ۱۹: رندر آیکون حالا کلوژور برمی‌گرداند (فاز ۱۷) — باید با data صدا زده شود */
        $fn = (new \App\View\Components\AdminPanel\Icon($name, 'size-9 text-white/90 drop-shadow'))->render();
        return (string) $fn(['attributes' => new \Illuminate\View\ComponentAttributeBag()]);
    };

    /* همان داده‌های قدیمی: کانفیگ ویجت‌های خانه + ویجت‌های نوشته‌ها */
    $wgAll = array_merge(config('front.home-widgets', []), config('front.posts-widgets', []));

    $wgItems = [];
    $wgGi = 0;
    foreach ($wgAll as $wgKey => $wgWidget) {
        $wgTitle = $wgWidget['title'] ?? $wgKey;
        $wgSc = '[widget-' . $wgKey . ']';

        if (isset($wgWidget['image'])) {
            /* مثل قدیمی: تصویر تم با پوشش گرادیانی برند */
            $wgHead = 'background-image: linear-gradient(135deg, rgb(5 150 105 / 0.55), rgb(217 70 239 / 0.35)), url(\'' . theme_asset($wgWidget['image']) . '\'); background-size: cover; background-position: center;';
            $wgSvg = null;
        } else {
            $wgHead = 'background: ' . $wgGradients[$wgGi % 6] . ';';
            $wgSvg = $wgIconSvg($wgIcons[$wgGi % 6]);
        }

        $wgItems[] = [
            'key' => 'w-' . $wgKey,
            'cat' => 'widget',
            'title' => $wgTitle,
            'sc' => $wgSc,
            'badge' => 'ویجت',
            'hint' => null,
            'svg' => $wgSvg,
            'head' => $wgHead,
            'search' => mb_strtolower($wgTitle . ' ' . $wgKey . ' ' . $wgSc . ' widget ویجت', 'UTF-8'),
        ];
        $wgGi++;
    }

    /* کارت فرم نمونه [form-1] — مثل قدیمی، با راهنمای «عدد را تغییر دهید» */
    $wgItems[] = [
        'key' => 'f-hero',
        'cat' => 'form',
        'title' => 'فرم‌های ورودی اطلاعاتی',
        'sc' => '[form-1]',
        'badge' => 'فرم',
        'hint' => 'برای فرم‌ها عدد را تغییر دهید',
        'svg' => $wgIconSvg('file-signature'),
        'head' => 'background: linear-gradient(135deg, #059669 0%, #14b8a6 100%);',
        'search' => 'فرم ورودی اطلاعاتی form [form-1] فرم‌ها',
    ];

    /* فرم‌های واقعی منتشرشده (حداکثر ۲۰ کارت [form-ID])
       نکته: مدل App\Models\Form اسکوپ published() ندارد؛ معادل دقیق همان
       «فرم‌های published» با where('published', true) گرفته می‌شود. */
    $wgForms = \App\Models\Form::query()->where('published', true)->orderByDesc('id')->limit(20)->get(['id', 'title']);
    foreach ($wgForms as $wgForm) {
        $wgSc = '[form-' . $wgForm->id . ']';
        $wgTitle = $wgForm->title ?: ('فرم ' . $wgForm->id);
        $wgItems[] = [
            'key' => 'f-' . $wgForm->id,
            'cat' => 'form',
            'title' => $wgTitle,
            'sc' => $wgSc,
            'badge' => 'فرم',
            'hint' => null,
            'svg' => $wgIconSvg('file-signature'),
            'head' => 'background: linear-gradient(135deg, #d946ef 0%, #ec4899 100%);',
            'search' => mb_strtolower($wgTitle . ' ' . $wgSc . ' form فرم', 'UTF-8'),
        ];
    }

    /* ارقام فارسی برای شمارنده ثابت سربرگ مودال */
    $wgFaDigits = static function ($value): string {
        return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    };
    $wgTotalFa = $wgFaDigits(count($wgItems));
@endphp

<div class="mx-auto max-w-5xl" x-data="{
    wgOpen: false,
    wgQuery: '',
    wgFilter: 'all',
    wgCopied: '',
    wgItems: @js($wgItems),
    get wgVisible() {
        const q = this.wgQuery.trim().toLowerCase();
        return this.wgItems.filter((it) => (this.wgFilter === 'all' || it.cat === this.wgFilter) && (!q || (it.search || '').includes(q)));
    },
    wgCopy(sc, key) {
        const done = () => {
            this.wgCopied = key;
            setTimeout(() => { if (this.wgCopied === key) this.wgCopied = ''; }, 2500);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(sc).then(done).catch(() => { done(); });
        } else {
            // پشتیبان برای مرورگرهای بدون Clipboard API
            const ta = document.createElement('textarea');
            ta.value = sc;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            done();
        }
        try {
            window.ApEditor?.instance?.model?.change(w => {
                const t = w.createText(sc);
                window.ApEditor.instance.model.insertContent(t, window.ApEditor.instance.model.document.selection);
            });
        } catch (e) {
            console.warn('insert shortcode failed:', e);
        }
        window.ApToast && window.ApToast.success('شورتکد کپی شد — در متن Paste کنید یا خودکار درج شد.');
    },
    wgFa(n) {
        return String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
    },
}">

    <form wire:submit="{{ $isEdit ? 'update' : 'store' }}" class="space-y-6"
          x-on:keydown.enter="if ($event.target.tagName !== 'TEXTAREA') { $event.preventDefault(); }">

        {{-- هدر --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <x-admin-panel::icon name="file-text" class="size-5" />
                </span>
                <div>
                    <h2 class="text-base font-extrabold">{{ $isEdit ? 'ویرایش صفحه: ' . $title : 'ایجاد صفحه جدید' }}</h2>
                    @if ($isEdit)
                        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
                            اسلاگ فعلی: <code class="text-[10px]" dir="ltr">{{ $page->slug }}</code>
                        </p>
                    @endif
                </div>
            </div>
            <a href="{{ route('np.pages.index') }}" wire:navigate class="ap-btn-ghost !py-2">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به صفحات
            </a>
        </div>

        {{-- فیلدها --}}
        <div class="ap-card space-y-5 p-5 sm:p-6">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="page-title">عنوان <span class="text-red-500">*</span></label>
                    <input id="page-title" type="text" wire:model="title" class="ap-input" placeholder="عنوان صفحه…">
                    @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="page-slug">آدرس (اسلاگ)</label>
                    <input id="page-slug" type="text" wire:model="slug" dir="ltr"
                           class="ap-input font-mono !text-xs" placeholder="خالی = از عنوان ساخته می‌شود">
                    @error('slug') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    @if ($isEdit)
                        <p class="mt-1.5 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                            با تغییر اسلاگ، لینک «/pages/…» در منوها و لینک‌های فوتر به‌صورت خودکار به‌روزرسانی می‌شود.
                        </p>
                    @endif
                </div>

                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="page-meta-title">عنوان سئو</label>
                    <input id="page-meta-title" type="text" wire:model="meta_title" class="ap-input"
                           placeholder="عنوان سئو…" @disabled($isEdit)>
                    @if ($isEdit)
                        <p class="mt-1.5 text-[11px] text-amber-600 dark:text-amber-400">
                            مثل پنل قدیمی، متا فقط هنگام ایجاد ذخیره می‌شود و در ویرایش به‌روزرسانی نمی‌شود.
                        </p>
                    @endif
                    @error('meta_title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="page-meta-description">توضیحات سئو</label>
                    <textarea id="page-meta-description" wire:model="meta_description" rows="3" class="ap-input"
                              placeholder="توضیحات سئو…" @disabled($isEdit)></textarea>
                    @error('meta_description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- محتوا (ویرایشگر CKEditor — پل ApEditor پنل) + دکمه گالری ویجت‌ها و فرم‌ها --}}
            <div>
                <div class="mb-1.5 flex flex-wrap items-center justify-between gap-2">
                    <label class="text-[13px] font-semibold">محتوا <span class="text-red-500">*</span></label>
                    <button type="button" x-on:click="wgOpen = true"
                            class="ap-btn !px-3 !py-1.5 bg-brand-50 text-[11.5px] text-brand-700 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-400 dark:hover:bg-brand-500/15"
                            aria-haspopup="dialog">
                        <x-admin-panel::icon name="layout-template" class="size-4" />
                        گالری ویجت‌ها و فرم‌ها
                    </button>
                </div>
                <div class="overflow-hidden rounded-xl border border-line dark:border-night-line" wire:ignore>
                    <div id="ap-ckeditor" data-editor-value="{{ $description }}"></div>
                </div>
                <input type="hidden" wire:model="description" id="ap-ckeditor-input">
                @error('description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- نوار ذخیره --}}
        <div class="ap-card sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 p-4 shadow-pop sm:p-5">
            <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                <input type="checkbox" wire:model="published"
                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                انتشار صفحه؟
            </label>
            <div class="flex items-center gap-2">
                <a href="{{ route('np.pages.index') }}" wire:navigate class="ap-btn-ghost !py-2">انصراف</a>
                <button type="submit" wire:loading.attr="disabled" wire:target="{{ $isEdit ? 'update' : 'store' }}"
                        class="ap-btn-primary !py-2.5">
                    <x-admin-panel::icon name="check" class="size-4" />
                    {{ $isEdit ? 'ذخیره تغییرات' : 'ایجاد صفحه' }}
                </button>
            </div>
        </div>
    </form>

    {{-- مودال «گالیــری ویجت‌ها و فرم‌ها» — پورت مودال قدیمی با استایل پنل جدید --}}
    <div x-show="wgOpen" x-cloak
         x-on:keydown.escape.window="wgOpen = false"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="wg-modal-title">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="wgOpen" x-on:click="wgOpen = false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="wgOpen" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
             class="relative flex max-h-[85vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2">

            {{-- سربرگ --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4 dark:border-night-line">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white">
                        <x-admin-panel::icon name="layout-template" class="size-5" />
                    </span>
                    <div>
                        <h3 id="wg-modal-title" class="text-sm font-extrabold">گالری ویجت‌ها و فرم‌ها</h3>
                        <p class="mt-0.5 flex items-center gap-1 text-[11px] text-ink-3 dark:text-night-ink-2">
                            <x-admin-panel::icon name="layout-panel-top" class="size-3.5" />
                            <span class="fa-digits">{{ $wgTotalFa }}</span> مورد در گالری
                        </p>
                    </div>
                </div>
                <button type="button" x-on:click="wgOpen = false" aria-label="بستن گالری"
                        class="rounded-xl p-2 text-ink-3 transition-colors hover:bg-surface-3 hover:text-ink dark:hover:bg-night-3 dark:hover:text-night-ink">
                    <x-admin-panel::icon name="x" class="size-4.5" />
                </button>
            </div>

            {{-- بدنه با اسکرول داخلی (اسکرول‌بار سفارشی سراسری پنل اعمال می‌شود) --}}
            <div class="max-h-[70vh] overflow-y-auto px-5 py-4">
                <p class="mb-4 flex items-start gap-2 rounded-xl bg-brand-50 px-3.5 py-2.5 text-[12px] leading-6 text-brand-800 dark:bg-brand-500/10 dark:text-brand-200">
                    <x-admin-panel::icon name="circle-help" class="mt-0.5 size-4 shrink-0" />
                    <span>برای درج ویجت یا فرم در محتوای صفحه، روی دکمه <strong>«کپی شورتکد»</strong> کلیک کنید؛ شورتکد در کلیپ‌بورد کپی و در صورت باز بودن ویرایشگر، در محل کرسر درج می‌شود.</span>
                </p>

                {{-- ابزار: جستجو + چیپ‌های فیلتر --}}
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative flex-1 sm:max-w-xs">
                        <x-admin-panel::icon name="search" class="pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                        <input type="text" x-model="wgQuery" aria-label="جستجو در ویجت‌ها و فرم‌ها"
                               placeholder="جستجو در ویجت‌ها و فرم‌ها…"
                               class="ap-input !py-2 ps-9 text-[12.5px]">
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5" role="group" aria-label="فیلتر گالری">
                        <button type="button" x-on:click="wgFilter = 'all'"
                                class="ap-btn !rounded-full !px-3.5 !py-2 text-[11.5px] font-bold"
                                :class="wgFilter === 'all' ? 'bg-brand-600 text-white' : 'border border-line bg-surface-2 text-ink-2 hover:border-brand-300 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2'">
                            <x-admin-panel::icon name="layout-dashboard" class="size-3.5" />
                            همه
                        </button>
                        <button type="button" x-on:click="wgFilter = 'widget'"
                                class="ap-btn !rounded-full !px-3.5 !py-2 text-[11.5px] font-bold"
                                :class="wgFilter === 'widget' ? 'bg-brand-600 text-white' : 'border border-line bg-surface-2 text-ink-2 hover:border-brand-300 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2'">
                            <x-admin-panel::icon name="package-2" class="size-3.5" />
                            ویجت‌ها
                        </button>
                        <button type="button" x-on:click="wgFilter = 'form'"
                                class="ap-btn !rounded-full !px-3.5 !py-2 text-[11.5px] font-bold"
                                :class="wgFilter === 'form' ? 'bg-brand-600 text-white' : 'border border-line bg-surface-2 text-ink-2 hover:border-brand-300 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2'">
                            <x-admin-panel::icon name="file-signature" class="size-3.5" />
                            فرم‌ها
                        </button>
                    </div>
                </div>

                {{-- گرید کارت‌ها (۱/۲/۴ ستونه) --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <template x-for="item in wgVisible" :key="item.key">
                        <div class="group flex flex-col overflow-hidden rounded-2xl border border-line bg-surface transition-all duration-200 hover:-translate-y-0.5 hover:shadow-pop dark:border-night-line dark:bg-night-3">
                            <div class="relative flex h-24 items-center justify-center" :style="item.head">
                                <template x-if="item.svg">
                                    <span x-html="item.svg" aria-hidden="true"></span>
                                </template>
                                <span class="absolute start-2.5 top-2.5 rounded-full bg-white/25 px-2 py-0.5 text-[10px] font-bold text-white backdrop-blur-sm" x-text="item.badge"></span>
                            </div>
                            <div class="flex flex-1 flex-col gap-2 p-3">
                                <h4 class="truncate text-[13px] font-bold" :title="item.title" x-text="item.title"></h4>
                                <template x-if="item.hint">
                                    <p class="flex items-center gap-1 text-[10.5px] font-semibold text-amber-600 dark:text-amber-400">
                                        <x-admin-panel::icon name="sparkles" class="size-3" />
                                        <span x-text="item.hint"></span>
                                    </p>
                                </template>
                                <code class="truncate rounded-lg bg-surface-2 px-2 py-1 text-center text-[11px] text-ink-2 dark:bg-night-2 dark:text-night-ink-2" dir="ltr" x-text="item.sc"></code>
                                <button type="button" x-on:click="wgCopy(item.sc, item.key)"
                                        class="ap-btn mt-auto !py-2 text-[11.5px] font-bold transition-colors"
                                        :class="wgCopied === item.key ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-brand-600 text-white hover:bg-brand-700'">
                                    <template x-if="wgCopied === item.key">
                                        <span class="inline-flex items-center gap-1.5">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="size-3.5"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            کپی شد ✓
                                        </span>
                                    </template>
                                    <template x-if="wgCopied !== item.key">
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-admin-panel::icon name="clipboard-list" class="size-3.5" />
                                            کپی شورتکد
                                        </span>
                                    </template>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- حالت خالی --}}
                <div x-show="wgVisible.length === 0" x-cloak
                     class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="search" class="size-10 opacity-40" />
                    <p class="text-sm font-medium">موردی مطابق با جستجوی شما یافت نشد.</p>
                    <span class="text-[11.5px]">لطفاً عبارت دیگری را امتحان کنید یا فیلتر را تغییر دهید.</span>
                </div>
            </div>

            {{-- فوتر --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3.5 dark:border-night-line">
                <span class="flex items-center gap-1.5 text-[11.5px] font-semibold text-ink-2 dark:text-night-ink-2">
                    <x-admin-panel::icon name="circle-check" class="size-4 text-emerald-500" />
                    <span class="fa-digits" x-text="wgFa(wgVisible.length)"></span>
                    مورد یافت شد
                </span>
                <button type="button" x-on:click="wgOpen = false" class="ap-btn-ghost !py-2">
                    <x-admin-panel::icon name="x" class="size-4" />
                    بستن پنجره
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // رندر مجدد راه‌اندازی CKEditor بعد از هر بار لود صفحه (wire:navigate سازگار)
    document.addEventListener('DOMContentLoaded', () => window.ApEditor?.init());
    document.addEventListener('livewire:navigated', () => window.ApEditor?.init());
    window.ApEditor?.init();
</script>
@endpush
