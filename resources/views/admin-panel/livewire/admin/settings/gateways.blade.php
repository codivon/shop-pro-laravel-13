{{-- درگاه‌های پرداخت — فاز ۲۰: بازطراحی کامل (کارت‌بندی مدرن + مودال تغییر تنظیمات)
     فاز ۱۸: لوگو از فایل‌منیجر سایت + پورت ۱:۱ منطق ذخیره (Gateways::save)
     فاز ۲۰: معماری جدید — هر درگاه کارت مستقل دارد؛ دکمه «تغییر تنظیمات» مودالِ
     همان درگاه را باز می‌کند و ذخیره هم مستقیم همان درگاه است (saveGateway)؛
     سوییچ فعال/غیرفعال هم بدون مودال و فوری ذخیره می‌شود (toggleActive). --}}
<div class="mx-auto max-w-6xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="credit-card" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-bold">درگاه‌های پرداخت</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">فعال‌سازی، پیکربندی و لوگوی درگاه‌های بانکی فروشگاه</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                <span class="fa-digits">{{ $this->stats['active'] }} درگاه فعال</span>
            </span>
            <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                <span class="fa-digits">از {{ $this->stats['total'] }} درگاه</span>
            </span>
        </div>
    </div>

    {{-- کارت‌ها --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">

        @foreach ($this->cards as $card)
            {{-- فاز ۲۰: متغیرهای کارت با تگ خام PHP — بلوک @php داخل کامپوننت Livewire
                 توسط ExtendBlade به‌درستی کامپایل نمی‌شود (ParseError: unexpected token) --}}
            <?php
                $brand = \App\Livewire\Admin\Settings\Gateways::gatewayBranding($card['key']);
                $configFields = collect($card['fields'])->reject(fn ($f) => ($f['name'] ?? '') === 'logo')->values();
                $hasConfig = $configFields->contains(fn ($f) => filled($card['configs'][$f['name']] ?? ''));
                // resolve لوگو سمت سرور برای <img> ثابت کارت/مودال (معادل JS-side window.ApMedia.url)
                $logoValue = trim((string) ($card['configs']['logo'] ?? ''));
                $logoUrl = $logoValue === '' ? '' : (preg_match('#^(https?:)?//#i', $logoValue) ? $logoValue : asset($logoValue));
            ?>
            {{-- فاز ۲۰: روی ریشه‌ی کارت هیچ transform/transition-transform مجاز نیست —
                 hover-lift باعث می‌شود کارت محتویِ fixed مودال شود و مودال بریده/جابجا شود
                 (درسِ فاز ۱۸-ج۱). فقط سایه‌ی هاور مجاز است. --}}
            <div class="ap-card group relative flex flex-col overflow-hidden transition-shadow duration-200 hover:shadow-card"
                 x-data="{ open: false }"
                 x-on:gw-saved.window="open = false"
                 wire:key="gw-{{ $card['id'] }}">

                {{-- نوار رنگی برند --}}
                <div class="h-1.5 w-full" style="background: linear-gradient(90deg, {{ $brand['color'] }}, color-mix(in srgb, {{ $brand['color'] }} 55%, white));"></div>

                {{-- بدنه کارت --}}
                <div class="flex flex-1 flex-col p-4">

                    {{-- سربرگ: لوگو + نام + وضعیت --}}
                    <div class="flex items-start gap-3">
                        <span class="relative flex size-13 shrink-0 items-center justify-center overflow-hidden rounded-2xl text-white shadow-sm ring-1 ring-black/5"
                              style="background-color: {{ $brand['color'] }}">
                            @if ($logoUrl !== '')
                                <img src="{{ $logoUrl }}"
                                     alt="لوگوی {{ $card['label'] }}" loading="lazy"
                                     class="size-full object-contain p-1"
                                     x-on:error="$el.classList.add('hidden')">
                            @else
                                <span class="text-lg font-extrabold">{{ mb_substr($card['label'], 0, 1) }}</span>
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <p class="truncate text-sm font-bold">{{ $card['label'] }}</p>
                                <span class="ltr rounded-md bg-surface-2 px-1.5 py-0.5 text-[9px] font-bold text-ink-3 dark:bg-night-3 dark:text-night-ink-2">{{ $card['key'] }}</span>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                @if ($brand['bank'])
                                    <span class="text-[10px] text-ink-3 dark:text-night-ink-2">{{ $brand['bank'] }}</span>
                                @endif
                                @if ($hasConfig)
                                    <span class="ap-badge bg-emerald-50 text-[10px] text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                                        <x-admin-panel::icon name="check" class="size-3" />
                                        پیکربندی شده
                                    </span>
                                @else
                                    <span class="ap-badge bg-surface-2 text-[10px] text-ink-3 dark:bg-night-3 dark:text-night-ink-2">
                                        بدون پیکربندی
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- فاصله‌ساز برای هم‌ترازی کارت‌ها --}}
                    <div class="flex-1"></div>

                    {{-- پانوشت کارت: سوییچ فعال‌سازی + دکمه تغییر تنظیمات --}}
                    <div class="mt-4 flex items-center justify-between gap-3 border-t border-line pt-3.5 dark:border-night-line">
                        {{-- سوییچ فعال/غیرفعال — ذخیره‌ی فوری --}}
                        <button type="button" wire:click="toggleActive({{ $card['id'] }})"
                                wire:loading.attr="disabled" wire:target="toggleActive({{ $card['id'] }})"
                                class="flex items-center gap-2.5"
                                role="switch" aria-checked="{{ $card['is_active'] ? 'true' : 'false' }}"
                                aria-label="{{ $card['is_active'] ? 'غیرفعال‌سازی' : 'فعال‌سازی' }} {{ $card['label'] }}">
                            <span class="relative inline-flex shrink-0 items-center">
                                {{-- ریل --}}
                                <span class="block h-6 w-11 rounded-full transition-colors duration-200 {{ $card['is_active'] ? 'bg-emerald-500' : 'bg-surface-3 dark:bg-night-line' }}"></span>
                                {{-- دستگیره — inset منطقی (RTL/LTR) --}}
                                <span class="absolute start-0.5 top-0.5 size-5 rounded-full bg-white shadow transition-all duration-200 {{ $card['is_active'] ? 'start-[1.375rem]' : '' }}"></span>
                            </span>
                            <span class="text-[11px] font-bold {{ $card['is_active'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-ink-3 dark:text-night-ink-2' }}">
                                {{ $card['is_active'] ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </button>

                        {{-- دکمه تغییر تنظیمات (مودال) --}}
                        <button type="button" x-on:click="open = true"
                                class="ap-btn-outline !py-1.5 !text-xs"
                                :aria-expanded="open ? 'true' : 'false'">
                            <x-admin-panel::icon name="settings-2" class="size-3.5" />
                            تغییر تنظیمات
                        </button>
                    </div>
                </div>

                {{-- مودال تغییر تنظیمات — فاز ۲۰
                     z-[150]: بالای سایدبار/هدر، زیر مودال فایل‌منیجر (z-[200]) تا
                     انتخاب لوگو از داخل همین مودال قابل استفاده باشد --}}
                <div x-show="open" x-cloak
                     class="fixed inset-0 z-[150] flex items-center justify-center p-3 sm:p-6"
                     x-on:keydown.escape.window="if (open) open = false"
                     role="dialog" aria-modal="true" aria-label="تنظیمات {{ $card['label'] }}">

                    {{-- پس‌زمینه تار — کلیک بیرون می‌بندد --}}
                    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"
                         x-show="open" x-transition.opacity.duration.200ms
                         x-on:click="open = false" aria-hidden="true"></div>

                    {{-- پنل مودال --}}
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 scale-95"
                         class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-line bg-surface shadow-2xl dark:border-night-line dark:bg-night-2">

                        <form wire:submit="saveGateway({{ $card['id'] }})" class="flex max-h-[90vh] flex-col overflow-hidden">

                            {{-- سربرگ مودال — گرادیان برند --}}
                            <div class="relative shrink-0 overflow-hidden px-5 py-4"
                                 style="background: linear-gradient(135deg, {{ $brand['color'] }}, color-mix(in srgb, {{ $brand['color'] }} 65%, #0f172a));">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white/15 text-white ring-1 ring-white/25 backdrop-blur">
                                        @if ($logoUrl !== '')
                                            <img src="{{ $logoUrl }}"
                                                 alt="" loading="lazy" class="size-full object-contain p-1"
                                                 x-on:error="$el.classList.add('hidden')">
                                        @else
                                            <span class="text-base font-extrabold">{{ mb_substr($card['label'], 0, 1) }}</span>
                                        @endif
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-bold text-white">تنظیمات {{ $card['label'] }}</p>
                                        <p class="ltr mt-0.5 text-[10px] font-semibold text-white/70">{{ $card['key'] }}@if ($brand['bank']) · {{ $brand['bank'] }} @endif</p>
                                    </div>
                                    <button type="button" x-on:click="open = false"
                                            class="rounded-xl p-2 text-white/80 transition-colors hover:bg-white/15 hover:text-white"
                                            aria-label="بستن">
                                        <x-admin-panel::icon name="x" class="size-4.5" />
                                    </button>
                                </div>
                            </div>

                            {{-- بدنه مودال --}}
                            <div class="flex-1 space-y-4 overflow-y-auto p-5">

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold" for="gw-name-{{ $card['id'] }}">عنوان نمایشی</label>
                                        <input id="gw-name-{{ $card['id'] }}" type="text" wire:model="form.{{ $card['id'] }}.name" class="ap-input">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold" for="gw-order-{{ $card['id'] }}">ترتیب نمایش</label>
                                        <input id="gw-order-{{ $card['id'] }}" type="number" dir="ltr" wire:model="form.{{ $card['id'] }}.ordering" class="ap-input font-mono">
                                    </div>
                                </div>

                                {{-- لوگو — انتخاب تصویر از فایل‌منیجر سایت --}}
                                <div class="rounded-2xl border border-dashed border-line p-3.5 dark:border-night-line">
                                    <x-admin-panel::media-picker
                                        wireModel="form.{{ $card['id'] }}.configs.logo"
                                        :value="$card['configs']['logo'] ?? ''"
                                        label="تصویر لوگوی بانک"
                                        hint="لوگو در هنگام پرداخت و فاکتورها نمایش داده می‌شود — ترجیحاً مربعی و PNG شفاف"
                                        height="h-32"
                                        aspect="contain"
                                    />
                                </div>

                                {{-- فیلدهای اختصاصی درگاه --}}
                                @if ($configFields->isNotEmpty())
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        @foreach ($configFields as $field)
                                            <div @class(['sm:col-span-2' => ($field['col'] ?? 4) >= 8])>
                                                <label class="mb-1.5 block text-xs font-semibold" for="gw-{{ $card['id'] }}-{{ $field['name'] }}">
                                                    {{ $field['label'] }} <span class="text-red-500">*</span>
                                                </label>
                                                @if (($field['type'] ?? 'text') === 'textarea')
                                                    <textarea id="gw-{{ $card['id'] }}-{{ $field['name'] }}" rows="3" dir="ltr"
                                                              wire:model="form.{{ $card['id'] }}.configs.{{ $field['name'] }}"
                                                              class="ap-input font-mono text-xs"></textarea>
                                                @else
                                                    <input id="gw-{{ $card['id'] }}-{{ $field['name'] }}" type="text" dir="ltr"
                                                           wire:model="form.{{ $card['id'] }}.configs.{{ $field['name'] }}"
                                                           class="ap-input font-mono" autocomplete="off">
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <p class="flex items-center gap-1.5 rounded-xl bg-surface-2 px-3 py-2.5 text-[11px] text-ink-3 dark:bg-night-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="info" class="size-3.5 shrink-0" />
                                    تغییرات فقط همین درگاه را ذخیره می‌کند؛ فعال/غیرفعال‌سازی مستقل روی کارت انجام می‌شود.
                                </p>
                            </div>

                            {{-- پانوشت مودال --}}
                            <div class="flex shrink-0 items-center justify-between gap-3 border-t border-line bg-surface-2/50 px-5 py-3.5 dark:border-night-line dark:bg-night-3/40">
                                <button type="button" x-on:click="open = false" class="ap-btn-ghost !py-2">
                                    انصراف
                                </button>
                                <button type="submit" wire:loading.attr="disabled" wire:target="saveGateway({{ $card['id'] }})"
                                        class="ap-btn-primary !py-2">
                                    <span wire:loading.remove wire:target="saveGateway({{ $card['id'] }})" class="flex items-center gap-2">
                                        <x-admin-panel::icon name="check-circle" class="size-4" />
                                        ذخیره تنظیمات
                                    </span>
                                    <span wire:loading wire:target="saveGateway({{ $card['id'] }})" class="flex items-center gap-2">
                                        <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                                        </svg>
                                        در حال ذخیره…
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
