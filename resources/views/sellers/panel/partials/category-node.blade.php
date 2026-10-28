{{-- فاز ۱۱۹ — گرهٔ درخت دسته‌بندی (رندر بازگشتی)
     ورودی: $node (Category با childrenTree)، $depth
     چک‌باکس‌ها wire:model.live="category_id" — همان پراپرتی فرم‌های قدیمی --}}
@php($nodeChildren = $node->childrenTree ?? collect())
<div x-data="{ open: {{ $depth < 1 ? 'true' : 'false' }} }">
    <div class="flex items-center gap-1 rounded-lg px-1.5 py-1 transition-colors hover:bg-surface-2 dark:hover:bg-night-2">
        @if ($nodeChildren->count())
            <button type="button" x-on:click="open = !open" class="shrink-0 rounded p-0.5 text-ink-3 transition-colors hover:text-brand-600 dark:text-night-ink-3" :aria-expanded="open ? 'true' : 'false'" aria-label="باز و بستن زیرشاخه‌ها">
                <x-admin-panel::icon name="chevron-left" class="size-3.5 transition-transform duration-200" x-bind:class="open ? '-rotate-90' : 'rotate-0'" />
            </button>
        @else
            <span class="size-[18px] shrink-0"></span>
        @endif

        <label class="flex min-w-0 flex-1 cursor-pointer items-center gap-2 py-0.5">
            <input type="checkbox" wire:model.live="category_id" value="{{ $node->id }}"
                   class="size-4 shrink-0 cursor-pointer rounded accent-brand-600"
                   aria-label="دستهٔ {{ $node->title }}">
            <span class="min-w-0 truncate text-xs font-semibold text-ink dark:text-night-ink">{{ $node->title }}</span>
        </label>
    </div>

    @if ($nodeChildren->count())
        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="ms-[13px] space-y-0.5 border-s border-line ps-2.5 dark:border-night-line">
            @foreach ($nodeChildren as $childNode)
                @include('sellers.panel.partials.category-node', ['node' => $childNode, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
