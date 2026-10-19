{{-- کارت روشن/خاموش پیامک — پورت back/settings/partials/sms-toggle-card.blade.php --}}
@php($accent = $card['accent'] ?? 'primary')
<div class="flex items-center gap-3 rounded-xl border border-line bg-surface p-3.5 transition-colors hover:border-brand-300 dark:border-night-line dark:bg-night-2">
    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
        <x-admin-panel::icon name="{{ $card['icon'] ?? 'bell' }}" class="size-4" />
    </span>
    <span class="min-w-0 flex-1">
        <span class="block text-[13px] font-bold">{{ $card['title'] }}</span>
        @isset($card['desc'])
            <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">{{ $card['desc'] }}</span>
        @endisset
    </span>
    <input type="checkbox" wire:model="toggles.{{ $card['name'] }}"
           class="size-5 shrink-0 cursor-pointer rounded border-line text-brand-600">
</div>
