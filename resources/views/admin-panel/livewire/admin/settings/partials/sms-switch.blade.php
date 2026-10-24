{{-- سوییچ روشن/خاموش پیامک — فاز ۱۷: بازطراحی مدرن (سوییچ به‌جای چک‌باکس) --}}
@php($accent = $card['accent'] ?? 'primary')
<label class="flex cursor-pointer items-center gap-3 rounded-xl border border-line bg-surface p-3.5 transition-all hover:border-brand-300 has-[:checked]:border-brand-400 has-[:checked]:bg-brand-50/40 dark:border-night-line dark:bg-night-2 dark:hover:border-brand-500/40 dark:has-[:checked]:bg-brand-500/5">
    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-surface-2 text-ink-2 transition-colors dark:bg-night-3 dark:text-night-ink-2">
        <x-admin-panel::icon name="{{ $card['icon'] ?? 'bell' }}" class="size-4" />
    </span>
    <span class="min-w-0 flex-1">
        <span class="block text-[13px] font-bold">{{ $card['title'] }}</span>
        @isset($card['desc'])
            <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">{{ $card['desc'] }}</span>
        @endisset
    </span>
    <span class="relative inline-flex shrink-0 items-center">
        <input type="checkbox" wire:model="toggles.{{ $card['name'] }}" class="peer sr-only">
        {{-- ریل سوییچ --}}
        <span class="block h-6 w-11 rounded-full bg-surface-3 transition-colors duration-200 peer-checked:bg-brand-600 dark:bg-night-line dark:peer-checked:bg-brand-600"></span>
        {{-- دستگیره — با inset منطقی (RTL/LTR هر دو درست) --}}
        <span class="absolute start-0.5 top-0.5 size-5 rounded-full bg-white shadow transition-all duration-200 peer-checked:start-[1.375rem]"></span>
    </span>
</label>
