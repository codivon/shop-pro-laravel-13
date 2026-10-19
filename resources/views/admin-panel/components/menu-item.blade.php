{{-- یک آیتم منو (برگ) — $item از والد --}}
@php
    $url = '#';
    $isActive = false;
    if (!empty($item['route'])) {
        try {
            $url = route($item['route'], $item['params'] ?? []);
            if (str_starts_with($item['route'], 'np.')) {
                $isActive = request()->routeIs($item['route']);
                // صفحات create/edit/show یک بخش، آیتم «لیست» همان بخش را هم فعال کنند
                if (!$isActive && str_ends_with($item['route'], '.index')) {
                    $base = substr($item['route'], 0, -6);
                    $isActive = request()->routeIs([$base.'.create', $base.'.edit', $base.'.show', $base.'.form']);
                }
            }
        } catch (\Throwable $e) {
            $url = '#';
        }
    }
@endphp

<a href="{{ $url }}"
   @if (($item['external'] ?? false)) target="_blank" rel="noopener" @else wire:navigate @endif
   class="ap-menu-link group/item {{ $isActive ? 'is-active' : '' }}"
   @if ($isActive) aria-current="page" @endif>
    <x-admin-panel::icon :name="$item['icon'] ?? 'circle'" class="size-4 shrink-0 opacity-70 transition-opacity group-hover/item:opacity-100" />
    <span class="truncate">{{ $item['label'] }}</span>
    @if ($isActive)
        <span class="ms-auto size-1.5 rounded-full bg-brand-500"></span>
    @endif
</a>
