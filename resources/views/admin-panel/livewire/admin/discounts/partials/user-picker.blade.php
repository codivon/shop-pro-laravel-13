{{-- چیپ‌یاب کاربران — قابل استفاده مجدد (فاز ۴ تخفیف‌ها) --}}
@props([
    'label' => 'انتخاب مشتریان',
    'placeholder' => 'جستجو بر اساس نام، نام کاربری یا ایمیل…',
    'searchField' => 'usersSearch',
    'resultsField' => 'usersResults',
    'results' => [],
    'selected' => [],
    'addAction' => 'addUser',
    'removeAction' => 'removeUser',
    'errorField' => 'users',
])

<div x-data="{ hidden: false }" x-on:click.outside="hidden = true" class="relative">
    <label class="mb-1.5 block text-xs font-semibold">{{ $label }}</label>
    <div class="relative">
        <x-admin-panel::icon name="search" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
        <input type="text" wire:model.live.debounce.350ms="{{ $searchField }}" x-on:focus="hidden = false"
               placeholder="{{ $placeholder }}" autocomplete="off"
               class="ap-input !py-2 pe-9" aria-label="{{ $label }}">
    </div>
    @error($errorField) <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

    {{-- نتایج جستجو --}}
    <div x-show="!hidden && ($wire.{{ $resultsField }}.length > 0 || $wire.{{ $searchField }}.length > 1)" x-cloak
         class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-line bg-surface p-1 shadow-pop dark:border-night-line dark:bg-night-2">
        @forelse ($results as $result)
            <button type="button" wire:click="{{ $addAction }}({{ $result['id'] }})" x-on:click="hidden = true"
                    wire:key="{{ $searchField }}-result-{{ $result['id'] }}"
                    class="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-start text-[13px] transition-colors hover:bg-brand-50 dark:hover:bg-brand-500/10">
                <span class="truncate font-semibold">{{ $result['label'] }}</span>
                <span dir="ltr" class="fa-digits shrink-0 text-[11px] text-ink-3">{{ $result['username'] }}</span>
            </button>
        @empty
            <p class="px-3 py-2.5 text-center text-xs text-ink-3 dark:text-night-ink-2">مشتری‌ای یافت نشد</p>
        @endforelse
    </div>

    {{-- چیپ‌های انتخاب‌شده --}}
    @if (count($selected))
        <div class="mt-2.5 flex flex-wrap gap-1.5">
            @foreach ($selected as $chip)
                <span wire:key="{{ $searchField }}-chip-{{ $chip['id'] }}"
                      class="inline-flex items-center gap-1 rounded-lg bg-brand-50 py-1 pe-1.5 ps-2.5 text-xs font-semibold text-brand-700 dark:bg-brand-500/12 dark:text-brand-300">
                    {{ $chip['label'] }}
                    <button type="button" wire:click="{{ $removeAction }}({{ $chip['id'] }})"
                            class="rounded-md p-0.5 transition-colors hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-500/15"
                            aria-label="حذف {{ $chip['label'] }}">
                        <x-admin-panel::icon name="x" class="size-3.5" />
                    </button>
                </span>
            @endforeach
        </div>
    @endif
</div>
