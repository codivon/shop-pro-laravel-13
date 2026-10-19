{{-- درگاه‌های پرداخت — پنل جدید (فاز ۷-ج۳) — پورت back/settings/gateways.blade.php --}}
<div class="mx-auto max-w-5xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="credit-card" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-bold">درگاه‌های پرداخت</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">فعال‌سازی و پیکربندی درگاه‌های بانکی فروشگاه</p>
            </div>
        </div>
        <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
            <x-admin-panel::icon name="circle-check" class="size-3.5 text-emerald-500" />
            {{ $this->stats['active'] }} از {{ $this->stats['total'] }} درگاه فعال
        </span>
    </div>

    <form wire:submit="save" class="space-y-4">

        @foreach ($this->cards as $card)
            <div class="ap-card overflow-hidden" x-data="{ open: {{ $card['is_active'] ? 'true' : 'false' }} }">
                {{-- سربرگ کارت --}}
                <div class="flex cursor-pointer select-none items-center justify-between gap-3 bg-surface-2 px-4 py-3.5 dark:bg-night-3/60"
                     x-on:click="open = !open">
                    <div class="flex items-center gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-[13px] font-bold text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                            {{ mb_substr($card['label'], 0, 6) }}
                        </span>
                        <div>
                            <p class="text-sm font-bold">{{ $card['label'] }}</p>
                            <span class="ap-badge mt-0.5"
                                  @class(['bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400' => ! $card['is_active'],
                                          'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400' => $card['is_active']])>
                                <span class="size-1.5 rounded-full" @class(['bg-red-500' => ! $card['is_active'], 'bg-emerald-500' => $card['is_active']])></span>
                                {{ $card['is_active'] ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3" x-on:click.stop>
                        <label class="inline-flex cursor-pointer items-center gap-2 text-[11px] text-ink-3 dark:text-night-ink-2">
                            فعال‌سازی
                            <input type="checkbox" wire:model="form.{{ $card['id'] }}.is_active"
                                   x-on:change="open = $event.target.checked"
                                   class="size-5 cursor-pointer rounded border-line text-brand-600">
                        </label>
                        <x-admin-panel::icon name="chevron-down" class="size-4 text-ink-3 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                    </div>
                </div>

                {{-- بدنه کارت --}}
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="border-t border-line p-4 dark:border-night-line">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">ترتیب نمایش</label>
                            <input type="number" dir="ltr" wire:model="form.{{ $card['id'] }}.ordering" class="ap-input font-mono">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">عنوان</label>
                            <input type="text" wire:model="form.{{ $card['id'] }}.name" class="ap-input">
                        </div>

                        @foreach ($card['fields'] as $field)
                            <div @class(['sm:col-span-2 lg:col-span-2' => ($field['col'] ?? 4) >= 8])>
                                <label class="mb-1.5 block text-xs font-semibold">{{ $field['label'] }} <span class="text-red-500">*</span></label>
                                @if (($field['type'] ?? 'text') === 'textarea')
                                    <textarea rows="3" dir="ltr" wire:model="form.{{ $card['id'] }}.configs.{{ $field['name'] }}" class="ap-input font-mono text-xs"></textarea>
                                @else
                                    <input type="text" dir="ltr" wire:model="form.{{ $card['id'] }}.configs.{{ $field['name'] }}" class="ap-input font-mono">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        {{-- نوار ذخیره --}}
        <div class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-lg backdrop-blur dark:border-night-line dark:bg-night-2/95">
            <span class="flex items-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="info" class="size-3.5" />
                برای فعال نمودن هر یک از درگاه‌ها، پس از انتخاب درگاه اطلاعات مربوط به آن را پر کنید.
            </span>
            <button type="submit" class="ap-btn-primary !py-2.5">
                <x-admin-panel::icon name="check-circle" class="size-4" />
                ذخیره تغییرات
            </button>
        </div>
    </form>
</div>
