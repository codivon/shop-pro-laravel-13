{{-- فاز ۸۳ — شخصی‌سازی کامل «هشدار کاهش قیمت» با پیش‌نمایش زنده --}}
{{-- الگوی ظاهری: صفحهٔ تنظیمات CheckoutTurbo (سکشن با آیکون، فیلدها با راهنما،
     کارت پیش‌نمایش زندهٔ خط‌چین «همان چیزی که مشتری می‌بیند») --}}
@php
    $paPalette = \App\Livewire\Admin\Settings\Others::PALETTE;
    $paPlacements = \App\Livewire\Admin\Settings\Others::PLACEMENT_LABELS;
    $paFloatLabels = \App\Livewire\Admin\Settings\Others::FLOAT_POSITION_LABELS;
    [$paPreviewBg, $paPreviewBg2] = $paPalette[$dt_price_alert_button_color] ?? $paPalette['red'];

    // فاز ۸۴ — استایل مطلقِ پیش‌نمایش دکمهٔ شناور داخل ماکِت صفحه (فیزیکی، هم‌قرارداد با CSS تم)
    $paFloatPreviewStyle = match ($dt_price_alert_float_pos) {
        'top-left'      => 'top:10px;left:10px',
        'top-center'    => 'top:10px;left:50%;transform:translateX(-50%)',
        'top-right'     => 'top:10px;right:10px',
        'middle-left'   => 'top:50%;left:10px;transform:translateY(-50%)',
        'middle-center' => 'top:50%;left:50%;transform:translate(-50%,-50%)',
        'middle-right'  => 'top:50%;right:10px;transform:translateY(-50%)',
        'bottom-left'   => 'bottom:10px;left:10px',
        'bottom-center' => 'bottom:10px;left:50%;transform:translateX(-50%)',
        default         => 'bottom:10px;right:10px',
    };
@endphp

<section>
    <div class="mb-4 flex items-center justify-between gap-3">
        <h3 class="flex items-center gap-2 text-sm font-bold">
            <span class="flex size-8 items-center justify-center rounded-xl bg-gradient-to-br from-rose-500 to-red-600 text-white shadow-sm">
                <x-admin-panel::icon name="trending-down" class="size-4" />
            </span>
            هشدار کاهش قیمت
        </h3>
        <span class="rounded-full px-2.5 py-1 text-[10.5px] font-bold {{ $dt_price_alert_status ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-surface-2 text-ink-3 dark:bg-night-3 dark:text-night-ink-2' }}">
            {{ $dt_price_alert_status ? 'فعال' : 'غیرفعال' }}
        </span>
    </div>

    @include('admin-panel::livewire.admin.settings.partials.setting-switch', [
        'name'  => 'dt_price_alert_status',
        'live'  => true,
        'icon'  => 'bell-ring',
        'title' => 'قابلیت هشدار کاهش قیمت',
        'desc'  => 'مشتری روی دکمهٔ هشدار در صفحهٔ محصول کلیک می‌کند و به‌محض افت قیمت، از طریق پیامک یا اعلان پروفایل (انتخاب خودش) باخبر می‌شود.',
    ])

    <div @class([
        'mt-4 space-y-5 transition-opacity',
        'opacity-50 pointer-events-none select-none' => ! $dt_price_alert_status,
    ])>

        {{-- ============ پیش‌نمایش زنده ============ --}}
        <div class="rounded-2xl border-2 border-dashed border-brand-300/70 bg-brand-50/40 p-4 dark:border-brand-500/25 dark:bg-brand-500/5">
            <div class="mb-3 flex items-center gap-2 text-[11.5px] font-bold text-brand-700 dark:text-brand-400">
                <x-admin-panel::icon name="eye" class="size-4" />
                پیش‌نمایش زنده — همان چیزی که مشتری می‌بیند
            </div>

            {{-- ماکِت کارت فروش محصول تم (RTL مثل صفحهٔ واقعی) --}}
            @if ($dt_price_alert_button_placement === 'float')
                {{-- فاز ۸۴ — ماکِت صفحهٔ کامل با دکمهٔ شناور در گوشهٔ انتخابی --}}
                <div dir="rtl" class="relative mx-auto h-48 w-full max-w-sm overflow-hidden rounded-xl border border-line bg-white shadow-sm dark:border-night-line dark:bg-night-2">
                    {{-- کروم مرورگر --}}
                    <div class="flex items-center gap-1 border-b border-line bg-surface-2 px-2.5 py-1.5 dark:border-night-line dark:bg-night-3">
                        <span class="size-1.5 rounded-full bg-rose-400"></span>
                        <span class="size-1.5 rounded-full bg-amber-400"></span>
                        <span class="size-1.5 rounded-full bg-emerald-400"></span>
                        <span class="ms-2 h-2 flex-1 rounded-full bg-surface dark:bg-night-2"></span>
                    </div>
                    {{-- محتوای صفحه --}}
                    <div class="space-y-2 p-3">
                        <div class="h-2.5 w-1/2 rounded-full bg-surface-3 dark:bg-night-line"></div>
                        <div class="h-2 w-2/3 rounded-full bg-surface-2 dark:bg-night-3"></div>
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <div class="h-10 rounded-lg bg-surface-2 dark:bg-night-3"></div>
                            <div class="h-10 rounded-lg bg-surface-2 dark:bg-night-3"></div>
                            <div class="h-10 rounded-lg bg-surface-2 dark:bg-night-3"></div>
                        </div>
                        <div class="h-2 w-1/3 rounded-full bg-surface-2 dark:bg-night-3"></div>
                    </div>
                    {{-- دکمهٔ شناور در گوشهٔ انتخابی (انتقال نرم هنگام تغییر) --}}
                    <div class="absolute transition-all duration-300" style="{{ $paFloatPreviewStyle }}">
                        <button type="button" tabindex="-1" class="dt-preview-btn dt-preview-btn--float dt-preview-btn--{{ $dt_price_alert_button_size }}"
                                style="--dt-pa-radius: {{ $dt_price_alert_button_radius }}px; --dt-pa-bg: {{ $paPreviewBg }}; --dt-pa-bg2: {{ $paPreviewBg2 }}">
                            <i class="{{ $dt_price_alert_button_icon }}"></i>
                            <span>{{ $dt_price_alert_button_text }}</span>
                        </button>
                    </div>
                </div>
            @else
                <div dir="rtl" class="mx-auto max-w-xs rounded-xl bg-white p-3 shadow-sm dark:bg-night-2">
                    <div class="mb-2 flex items-center gap-2">
                        <span class="flex size-11 items-center justify-center rounded-lg bg-amber-100 text-amber-500 dark:bg-amber-500/15">
                            <x-admin-panel::icon name="package" class="size-5" />
                        </span>
                        <div class="flex-1 leading-tight">
                            <p class="truncate text-[12px] font-bold text-slate-800 dark:text-night-ink">نمونهٔ کارت محصول فروشگاه</p>
                            <p class="text-[11px] text-rose-600 dark:text-rose-400">۲۴۵,۰۰۰ <span class="text-[9.5px]">تومان</span></p>
                        </div>
                    </div>

                    @if ($dt_price_alert_button_placement === 'above-cart')
                        <div class="mb-2 flex items-center justify-center" style="--dt-pa-bg: {{ $paPreviewBg }}; --dt-pa-bg2: {{ $paPreviewBg2 }}">
                            <button type="button" tabindex="-1" class="dt-preview-btn dt-preview-btn--{{ $dt_price_alert_button_size }}"
                                    style="--dt-pa-radius: {{ $dt_price_alert_button_radius }}px">
                                <i class="{{ $dt_price_alert_button_icon }}"></i>
                                <span>{{ $dt_price_alert_button_text }}</span>
                            </button>
                        </div>
                    @endif

                    <button type="button" tabindex="-1" class="flex h-11 w-full items-center justify-center gap-1.5 rounded-lg bg-rose-500 text-[13.5px] font-bold text-white opacity-90">
                        <x-admin-panel::icon name="shopping-cart" class="size-4" />
                        افزودن به سبد خرید
                    </button>

                    @if ($dt_price_alert_button_placement !== 'above-cart')
                        <div class="flex items-center justify-center" style="--dt-pa-bg: {{ $paPreviewBg }}; --dt-pa-bg2: {{ $paPreviewBg2 }}">
                            <button type="button" tabindex="-1" class="dt-preview-btn dt-preview-btn--{{ $dt_price_alert_button_size }}"
                                    style="--dt-pa-radius: {{ $dt_price_alert_button_radius }}px">
                                <i class="{{ $dt_price_alert_button_icon }}"></i>
                                <span>{{ $dt_price_alert_button_text }}</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            <p class="mt-2.5 text-center text-[10.5px] text-ink-3 dark:text-night-ink-2">
                @if ($dt_price_alert_button_placement === 'float')
                    موقعیت فعلی: <b class="text-ink-2 dark:text-night-ink">شناور ({{ $paFloatLabels[$dt_price_alert_float_pos] ?? 'پایین-راست' }})</b>
                @elseif ($dt_price_alert_button_placement === 'custom-selector')
                    موقعیت فعلی: <b class="text-ink-2 dark:text-night-ink">تزریق با سلکتور{{ count($dt_price_alert_selectors) ? ' (' . count($dt_price_alert_selectors) . ' سلکتور)' : ' — بدون سلکتور (زیر دکمهٔ خرید)' }}</b>
                @else
                    موقعیت فعلی دکمه: <b class="text-ink-2 dark:text-night-ink">{{ $paPlacements[$dt_price_alert_button_placement] ?? 'زیر دکمهٔ خرید' }}</b>
                @endif
            </p>
        </div>

        {{-- ============ ظاهر دکمه ============ --}}
        <div class="rounded-xl border border-line p-4 dark:border-night-line">
            <p class="mb-3 flex items-center gap-1.5 text-xs font-bold text-ink-2 dark:text-night-ink">
                <x-admin-panel::icon name="palette" class="size-4 text-brand-600 dark:text-brand-400" />
                ظاهر دکمه در صفحهٔ محصول
            </p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">متن دکمه</label>
                    <input type="text" wire:model.live.debounce.400ms="dt_price_alert_button_text" class="ap-input" placeholder="هشدار کاهش قیمت">
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">خالی بماند = «هشدار کاهش قیمت»</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">آیکون دکمه (کلاس MDI)</label>
                    <div class="flex items-center gap-2">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink">
                            <i class="{{ $dt_price_alert_button_icon ?: 'mdi mdi-bell-ring-outline' }}"></i>
                        </span>
                        <input id="dt-price-alert-button-icon" type="text" dir="ltr" wire:model.live.debounce.400ms="dt_price_alert_button_icon" class="ap-input font-mono" placeholder="mdi mdi-bell-ring-outline">
                        <x-admin-panel::icon-picker for="dt-price-alert-button-icon" mode="class" />
                    </div>
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">پیش‌نمایش آیکون در کادر کناری — خالی = زنگوله</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">رنگ دکمه</label>
                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="رنگ دکمه">
                        @foreach ($paPalette as $paKey => $paHexes)
                            <button type="button" wire:click="$set('dt_price_alert_button_color', '{{ $paKey }}')"
                                    wire:key="pa-color-{{ $paKey }}"
                                    class="relative size-9 rounded-xl transition-transform hover:scale-105 {{ $dt_price_alert_button_color === $paKey ? 'ring-2 ring-offset-2 ring-ink-400 dark:ring-night-ink dark:ring-offset-night-2' : '' }}"
                                    style="background: linear-gradient(135deg, {{ $paHexes[0] }}, {{ $paHexes[1] }})"
                                    role="radio" aria-checked="{{ $dt_price_alert_button_color === $paKey ? 'true' : 'false' }}"
                                    aria-label="رنگ {{ $paKey }}" title="{{ $paKey }}">
                                    @if ($dt_price_alert_button_color === $paKey)
                                        <span class="absolute inset-0 flex items-center justify-center text-white">
                                            <x-admin-panel::icon name="check" class="size-4" />
                                        </span>
                                    @endif
                            </button>
                        @endforeach
                    </div>
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">رنگ گرادیانی دکمه و مودال</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">اندازه دکمه</label>
                        <select wire:model.live="dt_price_alert_button_size" class="ap-input">
                            <option value="sm">کوچک</option>
                            <option value="md">متوسط</option>
                            <option value="lg">بزرگ</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 flex items-center justify-between text-xs font-semibold">
                            گردی گوشه‌ها
                            <span class="fa-digits rounded-md bg-surface-2 px-1.5 py-0.5 text-[10px] font-bold text-ink-2 dark:bg-night-3 dark:text-night-ink">{{ $dt_price_alert_button_radius }}px</span>
                        </label>
                        <input type="range" min="0" max="30" step="1" wire:model.live="dt_price_alert_button_radius"
                               class="mt-2 w-full accent-brand-600" aria-label="گردی گوشه‌ها (پیکسل)">
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold">موقعیت دکمه</label>
                    {{-- فاز ۸۴ — سلکت (مثل نمونهٔ CheckoutTurbo) با دو گزینهٔ جدید: شناور در گوشهٔ صفحه + تگ دلخواه --}}
                    <select wire:model.live="dt_price_alert_button_placement" class="ap-input" aria-label="موقعیت دکمه هشدار قیمت">
                        @foreach ($paPlacements as $paKey => $paLabel)
                            <option value="{{ $paKey }}">{{ $paLabel }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                        @if ($dt_price_alert_button_placement === 'custom-selector')
                            دکمه با سلکتورهای زیر در صفحهٔ محصول تزریق می‌شود؛ اگر هیچ سلکتوری پیدا نشود، خودکار زیر دکمهٔ خرید می‌نشیند.
                        @elseif ($dt_price_alert_button_placement === 'float')
                            دکمه به‌صورت شناور (fixed) و همیشه‌در-دسترس در گوشهٔ انتخابی صفحه نمایش داده می‌شود.
                        @else
                            دکمه داخل باکس فروش صفحهٔ محصول، {{ $paPlacements[$dt_price_alert_button_placement] }} قرار می‌گیرد.
                        @endif
                    </p>
                </div>

                {{-- فاز ۸۴ — موقعیت دکمهٔ شناور (۹گانه با تیک/بوردر انتخاب) --}}
                @if ($dt_price_alert_button_placement === 'float')
                    <div class="sm:col-span-2" wire:key="pa-float-picker">
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-bold text-ink-2 dark:text-night-ink">
                            <x-admin-panel::icon name="monitor" class="size-4 text-brand-600 dark:text-brand-400" />
                            موقعیت دکمهٔ شناور در صفحه
                        </p>
                        <div class="mx-auto grid max-w-md grid-cols-3 gap-2" role="radiogroup" aria-label="موقعیت دکمهٔ شناور">
                            @foreach (\App\Livewire\Admin\Settings\Others::FLOAT_POSITIONS as $paFp)
                                <button type="button" wire:click="$set('dt_price_alert_float_pos', '{{ $paFp }}')"
                                        wire:key="pa-float-{{ $paFp }}"
                                        @class([
                                            'relative flex flex-col items-center gap-1 rounded-xl border-2 px-2 py-2.5 text-[10px] font-bold transition-all duration-200',
                                            'border-brand-600 bg-brand-50 text-brand-700 dark:border-brand-500 dark:bg-brand-500/10 dark:text-brand-400' => $dt_price_alert_float_pos === $paFp,
                                            'border-line text-ink-3 hover:border-brand-300 dark:border-night-line dark:text-night-ink-2 dark:hover:border-brand-500/40' => $dt_price_alert_float_pos !== $paFp,
                                        ])
                                        role="radio" aria-checked="{{ $dt_price_alert_float_pos === $paFp ? 'true' : 'false' }}">
                                    <span @class([
                                        'size-2.5 rounded-full transition-colors',
                                        'bg-brand-600 dark:bg-brand-400' => $dt_price_alert_float_pos === $paFp,
                                        'bg-ink-300/60 dark:bg-night-line' => $dt_price_alert_float_pos !== $paFp,
                                    ]) aria-hidden="true"></span>
                                    {{ $paFloatLabels[$paFp] }}
                                    @if ($dt_price_alert_float_pos === $paFp)
                                        <span class="absolute -top-2 -start-2 flex size-4.5 items-center justify-center rounded-full bg-brand-600 text-white shadow">
                                            <x-admin-panel::icon name="check" class="size-2.5" />
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- فاز ۸۴ — تزریق در تگ دلخواه (سلکتورهای کلاس/آیدی) --}}
                @if ($dt_price_alert_button_placement === 'custom-selector')
                    <div class="sm:col-span-2" wire:key="pa-selector-repeater">
                        <p class="mb-2 flex items-center gap-1.5 text-xs font-bold text-ink-2 dark:text-night-ink">
                            <x-admin-panel::icon name="code-2" class="size-4 text-brand-600 dark:text-brand-400" />
                            تزریق در دکمه‌ها (سلکتور)
                        </p>

                        <div class="space-y-2">
                            @forelse ($dt_price_alert_selectors as $paSelIndex => $paSel)
                                <div class="flex items-center gap-2" wire:key="pa-sel-{{ $paSelIndex }}-{{ md5($paSel) }}">
                                    <button type="button" wire:click="paRemoveSelector({{ $paSelIndex }})"
                                            class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-red-200 text-red-500 transition-colors hover:bg-red-50 dark:border-red-500/25 dark:hover:bg-red-500/10"
                                            aria-label="حذف سلکتور {{ $paSel }}" title="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                    <span class="ap-input flex-1 truncate font-mono" dir="ltr">{{ $paSel }}</span>
                                </div>
                            @empty
                                <p class="rounded-lg bg-surface-2 px-3 py-2 text-[11px] text-ink-3 dark:bg-night-3 dark:text-night-ink-2">
                                    هنوز سلکتوری اضافه نشده — تا زمان افزودن، دکمه زیر دکمهٔ خرید نمایش داده می‌شود.
                                </p>
                            @endforelse
                        </div>

                        <div class="mt-2.5 flex items-center gap-2">
                            <input type="text" dir="ltr" wire:model="pa_selector_input" wire:keydown.enter="paAddSelector"
                                   class="ap-input flex-1 font-mono" placeholder="مثلاً: .product-seller-row>.product-seller-row-price"
                                   aria-label="سلکتور جدید (کلاس یا آیدی)">
                            <button type="button" wire:click="paAddSelector" class="ap-btn-primary shrink-0 !px-3 !py-2 text-xs">
                                <x-admin-panel::icon name="plus" class="size-4" />
                                افزودن سلکتور
                            </button>
                        </div>

                        <p class="mt-2 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                            هر خط یک سلکتور CSS — از هر سلکتور فقط <b class="text-ink-2 dark:text-night-ink">اولین عنصرِ منطبق</b> دکمه را می‌سازد
                            (کلاس با <code dir="ltr">.</code> و آیدی با <code dir="ltr">#</code>). اگر هیچ‌کدام در صفحه پیدا نشود، دکمه به‌صورت خودکار زیر دکمهٔ خرید نمایش داده می‌شود.
                        </p>

                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span class="text-[10.5px] font-bold text-ink-3 dark:text-night-ink-2">سلکتورهای پرکاربرد:</span>
                            @foreach (\App\Livewire\Admin\Settings\Others::SELECTOR_EXAMPLES as $paExSel => $paExLabel)
                                <button type="button" wire:click="paAddExampleSelector('{{ $paExSel }}')"
                                        wire:key="pa-ex-{{ md5($paExSel) }}"
                                        class="group inline-flex items-center gap-1 rounded-lg border border-line bg-surface px-2 py-1 transition-colors hover:border-brand-400 hover:bg-brand-50/50 dark:border-night-line dark:bg-night-3 dark:hover:border-brand-500/40"
                                        title="{{ $paExLabel }} — برای افزودن کلیک کنید">
                                    <code dir="ltr" class="font-mono text-[10.5px] text-brand-700 dark:text-brand-400">{{ $paExSel }}</code>
                                    <span class="text-[9.5px] text-ink-3 dark:text-night-ink-2">({{ $paExLabel }})</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ============ مودال و اطلاع‌رسانی ============ --}}
        <div class="rounded-xl border border-line p-4 dark:border-night-line">
            <p class="mb-3 flex items-center gap-1.5 text-xs font-bold text-ink-2 dark:text-night-ink">
                <x-admin-panel::icon name="message-square-text" class="size-4 text-brand-600 dark:text-brand-400" />
                مودال و راه‌های اطلاع‌رسانی
            </p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">عنوان مودال</label>
                    <input type="text" wire:model.live.debounce.400ms="dt_price_alert_modal_title" class="ap-input" placeholder="هشدار کاهش قیمت">
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">خالی بماند = «هشدار کاهش قیمت»</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold">راه‌های اطلاع‌رسانی مجاز</label>
                    <div class="space-y-2">
                        <label class="flex cursor-pointer items-center justify-between gap-2 rounded-lg border border-line px-3 py-2 text-xs dark:border-night-line">
                            <span class="flex items-center gap-1.5 font-semibold text-ink-2 dark:text-night-ink">
                                <x-admin-panel::icon name="smartphone" class="size-3.5" />
                                پیامک موبایل
                            </span>
                            <input type="checkbox" wire:model.live="dt_price_alert_channel_sms" class="size-4 accent-brand-600" aria-label="پیامک موبایل">
                        </label>
                        <label class="flex cursor-pointer items-center justify-between gap-2 rounded-lg border border-line px-3 py-2 text-xs dark:border-night-line">
                            <span class="flex items-center gap-1.5 font-semibold text-ink-2 dark:text-night-ink">
                                <x-admin-panel::icon name="user-check" class="size-3.5" />
                                اعلان در پروفایل کاربر
                            </span>
                            <input type="checkbox" wire:model.live="dt_price_alert_channel_profile" class="size-4 accent-brand-600" aria-label="اعلان پروفایل">
                        </label>
                    </div>
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">اگر هیچ‌کدام تیک نخورد، «هر دو» اعمال می‌شود. مهمان‌ها فقط پیامک می‌گیرند.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-semibold">متن توضیح مودال</label>
                    <textarea wire:model.live.debounce.400ms="dt_price_alert_modal_desc" rows="2" class="ap-input" placeholder="به‌محض اینکه قیمت این محصول کمتر شد، همان لحظه به شما خبر می‌دهیم؛ دیگر لازم نیست صفحه را دنبال کنید."></textarea>
                    <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">خالی بماند = متن پیش‌فرض توضیح قابلیت</p>
                </div>
            </div>
        </div>

        {{-- ============ پیش‌نمایش مودال ============ --}}
        <div x-data="{ paOpen: false }" class="flex items-center justify-between gap-3 rounded-xl border border-line bg-surface-2/60 px-4 py-3 dark:border-night-line dark:bg-night-3/40">
            <p class="text-[11.5px] leading-6 text-ink-3 dark:text-night-ink-2">
                <b class="text-ink-2 dark:text-night-ink">پیش‌نمایش مودال:</b>
                ظاهر مودال با عنوان/توضیح/کانال‌هایی که همین حالا تنظیم کرده‌اید.
            </p>
            <button type="button" x-on:click="paOpen = true" class="ap-btn shrink-0 border border-line bg-surface !py-2 text-xs dark:border-night-line dark:bg-night-3">
                <x-admin-panel::icon name="eye" class="size-4" />
                نمایش
            </button>

            {{-- مودال پیش‌نمایش (فقط ظاهر) --}}
            <div x-show="paOpen" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" dir="rtl">
                <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-on:click="paOpen = false" x-show="paOpen" x-transition.opacity aria-hidden="true"></div>
                <div x-show="paOpen" x-on:click.stop x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                     class="relative w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl dark:bg-night-2"
                     role="dialog" aria-modal="true" aria-label="پیش‌نمایش مودال هشدار قیمت">
                    <button type="button" x-on:click="paOpen = false" class="absolute end-4 top-4 rounded-full p-1.5 text-ink-3 hover:bg-surface-2 dark:hover:bg-night-3" aria-label="بستن">
                        <x-admin-panel::icon name="x" class="size-4" />
                    </button>

                    <div class="mx-auto mb-3 flex size-16 items-center justify-center rounded-2xl text-3xl text-white"
                         style="background: linear-gradient(135deg, {{ $paPreviewBg }}, {{ $paPreviewBg2 }})">
                        <i class="mdi mdi-bell-ring-outline"></i>
                    </div>
                    <h4 class="mb-2 text-center text-base font-extrabold text-slate-900 dark:text-white">
                        {{ $dt_price_alert_modal_title ?: 'هشدار کاهش قیمت' }}
                    </h4>
                    <p class="mb-4 text-center text-[11.5px] leading-6 text-slate-500 dark:text-night-ink-2">
                        {{ $dt_price_alert_modal_desc ?: 'به‌محض اینکه قیمت این محصول کمتر شد، همان لحظه به شما خبر می‌دهیم؛ دیگر لازم نیست صفحه را دنبال کنید.' }}
                    </p>

                    <div class="mb-3 space-y-2">
                        @if ($dt_price_alert_channel_sms || (! $dt_price_alert_channel_sms && ! $dt_price_alert_channel_profile))
                            <div class="flex items-center gap-2.5 rounded-2xl border-2 px-3 py-2.5" style="border-color: {{ $paPreviewBg }}">
                                <span class="flex size-9 items-center justify-center rounded-xl text-lg" style="color: {{ $paPreviewBg }}; background: {{ $paPreviewBg }}1f">
                                    <i class="mdi mdi-cellphone-message"></i>
                                </span>
                                <span class="flex-1 text-[12px] font-bold text-slate-800 dark:text-white">پیامک موبایل
                                    <small class="block text-[10.5px] font-normal text-slate-400">به‌محض کاهش قیمت، پیامک اطلاع‌رسانی می‌گیرید</small>
                                </span>
                                <span class="flex size-5 items-center justify-center rounded-full text-[11px] text-white" style="background: {{ $paPreviewBg }}">
                                    <x-admin-panel::icon name="check" class="size-3" />
                                </span>
                            </div>
                        @endif
                        @if ($dt_price_alert_channel_profile || (! $dt_price_alert_channel_sms && ! $dt_price_alert_channel_profile))
                            <div class="flex items-center gap-2.5 rounded-2xl border-2 px-3 py-2.5" style="border-color: {{ $paPreviewBg }}">
                                <span class="flex size-9 items-center justify-center rounded-xl text-lg" style="color: {{ $paPreviewBg }}; background: {{ $paPreviewBg }}1f">
                                    <i class="mdi mdi-account-box-outline"></i>
                                </span>
                                <span class="flex-1 text-[12px] font-bold text-slate-800 dark:text-white">اعلان در پروفایل
                                    <small class="block text-[10.5px] font-normal text-slate-400">در صفحهٔ «اعلان‌ها»ی پروفایل کاربری ذخیره می‌شود</small>
                                </span>
                                <span class="flex size-5 items-center justify-center rounded-full text-[11px] text-white" style="background: {{ $paPreviewBg }}">
                                    <x-admin-panel::icon name="check" class="size-3" />
                                </span>
                            </div>
                        @endif
                    </div>

                    <button type="button" tabindex="-1" class="flex h-12 w-full items-center justify-center gap-2 rounded-2xl text-[13.5px] font-bold text-white"
                            style="background: linear-gradient(135deg, {{ $paPreviewBg }}, {{ $paPreviewBg2 }})">
                        <i class="mdi mdi-bell-check"></i>
                        فعال‌سازی هشدار
                    </button>
                </div>
            </div>
        </div>

        {{-- استایل دکمهٔ پیش‌نمایش (کپی هم‌قرارداد از تم) --}}
        <style>
            .dt-preview-btn{--dt-pa-radius:10px;--dt-pa-h:48px;--dt-pa-fs:13.5px;
                display:flex;align-items:center;justify-content:center;gap:8px;width:100%;height:var(--dt-pa-h);
                margin:8px 0 0;padding:0 14px;border:0;border-radius:var(--dt-pa-radius);
                background:linear-gradient(135deg,var(--dt-pa-bg,#ef394e),var(--dt-pa-bg2,#c8102e));
                color:#fff;font-size:var(--dt-pa-fs);font-weight:600;cursor:default;box-shadow:0 8px 20px -10px var(--dt-pa-bg,#ef394e)}
            .dt-preview-btn i{font-size:1.3em}
            .dt-preview-btn--sm{--dt-pa-h:40px;--dt-pa-fs:12.5px}
            .dt-preview-btn--lg{--dt-pa-h:54px;--dt-pa-fs:14.5px}
            /* فاز ۸۴ — حالت شناور داخل ماکِت: عرض خودکار، بدون مارجین */
            .dt-preview-btn--float{width:auto;display:inline-flex;margin:0;padding:0 16px;box-shadow:0 10px 24px -8px rgba(15,23,42,.35)}
        </style>
    </div>
</section>
