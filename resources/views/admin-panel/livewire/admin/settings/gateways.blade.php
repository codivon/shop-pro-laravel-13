{{-- درگاه‌های پرداخت — فاز ۱۸: بازطراحی کامل مدرن + تغییر تصویر لوگوی بانک‌ها
     پورت ۱:۱ منطق ذخیره (Gateways::save) — کارت‌های برنددار + لوگو از فایل‌منیجر سایت --}}
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

    <form wire:submit="save" class="space-y-5">

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            @foreach ($this->cards as $card)
                @php($brand = \App\Livewire\Admin\Settings\Gateways::gatewayBranding($card['key']))
                <div class="ap-card overflow-hidden transition-shadow hover:shadow-card"
                     x-data="{ open: {{ $card['is_active'] ? 'true' : 'false' }} }"
                     wire:key="gw-{{ $card['id'] }}">

                    {{-- نوار رنگی برند --}}
                    <div class="h-1 w-full" style="background: linear-gradient(90deg, {{ $brand['color'] }}, color-mix(in srgb, {{ $brand['color'] }} 55%, white));"></div>

                    {{-- سربرگ کارت --}}
                    <div class="flex cursor-pointer select-none items-center gap-3 px-4 py-3.5"
                         x-on:click="open = !open" role="button" tabindex="0"
                         x-on:keydown.enter.prevent="open = !open"
                         :aria-expanded="open ? 'true' : 'false'">

                        {{-- لوگو یا آواتار برند --}}
                        <span class="relative flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl text-white shadow-sm"
                              style="background-color: {{ $brand['color'] }}">
                            @if (! empty($card['configs']['logo']))
                                <img :src="window.ApMedia.url(@js($card['configs']['logo']))"
                                     alt="لوگوی {{ $card['label'] }}" loading="lazy"
                                     class="size-full object-contain p-1"
                                     x-on:error="$el.classList.add('hidden')">
                            @else
                                <span class="text-base font-extrabold">{{ mb_substr($card['label'], 0, 1) }}</span>
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-bold">{{ $card['label'] }}</p>
                                <span class="ltr rounded-md bg-surface-2 px-1.5 py-0.5 text-[9px] font-bold text-ink-3 dark:bg-night-3 dark:text-night-ink-2">{{ $card['key'] }}</span>
                            </div>
                            <div class="mt-0.5 flex items-center gap-2">
                                <span class="ap-badge"
                                      @class(['bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400' => ! $card['is_active'],
                                              'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400' => $card['is_active']])>
                                    <span class="size-1.5 rounded-full" @class(['bg-red-500' => ! $card['is_active'], 'bg-emerald-500' => $card['is_active']])></span>
                                    {{ $card['is_active'] ? 'فعال' : 'غیرفعال' }}
                                </span>
                                @if ($brand['bank'])
                                    <span class="text-[10px] text-ink-3 dark:text-night-ink-2">{{ $brand['bank'] }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- سوییچ فعال‌سازی --}}
                        <label class="flex cursor-pointer items-center" x-on:click.stop>
                            <input type="checkbox" wire:model="form.{{ $card['id'] }}.is_active"
                                   x-on:change="open = $event.target.checked"
                                   class="peer sr-only">
                            <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-ink-2/25 transition-colors duration-300 peer-checked:bg-emerald-500 dark:bg-night-line">
                                <span class="absolute right-1 size-4.5 rounded-full bg-white shadow transition-transform duration-300 peer-checked:-translate-x-5"></span>
                            </span>
                        </label>

                        <x-admin-panel::icon name="chevron-down" class="size-4 shrink-0 text-ink-3 transition-transform duration-200"
                                             x-bind:class="open ? 'rotate-180' : ''" />
                    </div>

                    {{-- بدنه کارت --}}
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-cloak
                         class="border-t border-line bg-surface-2/40 px-4 py-4 dark:border-night-line dark:bg-night-3/30">

                        {{-- لوگو — انتخاب تصویر از فایل‌منیجر سایت --}}
                        <div class="mb-4 rounded-2xl border border-dashed border-line p-3.5 dark:border-night-line">
                            <x-admin-panel::media-picker
                                wireModel="form.{{ $card['id'] }}.configs.logo"
                                :value="$card['configs']['logo'] ?? ''"
                                label="تصویر لوگوی بانک"
                                hint="لوگو در هنگام پرداخت و فاکتورها نمایش داده می‌شود — ترجیحاً مربعی و PNG شفاف"
                                height="h-32"
                                aspect="contain"
                            />
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" for="gw-name-{{ $card['id'] }}">عنوان نمایشی</label>
                                <input id="gw-name-{{ $card['id'] }}" type="text" wire:model="form.{{ $card['id'] }}.name" class="ap-input">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" for="gw-order-{{ $card['id'] }}">ترتیب نمایش</label>
                                <input id="gw-order-{{ $card['id'] }}" type="number" dir="ltr" wire:model="form.{{ $card['id'] }}.ordering" class="ap-input font-mono">
                            </div>

                            @foreach ($card['fields'] as $field)
                                @continue(($field['name'] ?? '') === 'logo')
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
                    </div>
                </div>
            @endforeach
        </div>

        {{-- نوار ذخیره --}}
        <div class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-lg backdrop-blur dark:border-night-line dark:bg-night-2/95">
            <span class="flex items-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="info" class="size-3.5" />
                درگاه‌های غیرفعال‌شده با سوییچ، پس از ذخیره از صفحه پرداخت حذف می‌شوند.
            </span>
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn-primary !py-2.5">
                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                    <x-admin-panel::icon name="check-circle" class="size-4" />
                    ذخیره تغییرات
                </span>
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                    <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
                    </svg>
                    در حال ذخیره…
                </span>
            </button>
        </div>
    </form>
</div>
