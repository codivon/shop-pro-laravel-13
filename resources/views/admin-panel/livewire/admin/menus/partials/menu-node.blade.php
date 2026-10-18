{{-- گره درخت منوها — فراخوانی بازگشتی با @include (پورت back/menus/partials/child_menu) --}}
@php
    $menu = $node['model'];
@endphp
<li wire:key="menu-{{ $menu->id }}" class="list-none">
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1.5 rounded-xl border border-line bg-surface px-3 py-2.5 transition-colors hover:border-brand-300 hover:bg-surface-2 dark:border-night-line dark:bg-night-2 dark:hover:border-brand-500/40 dark:hover:bg-night-3">

        {{-- full_title مثل قدیمی (عنوان + نوع) --}}
        <span class="text-[13px] font-bold text-ink dark:text-night-ink">{{ $this->displayTitle($menu) }}</span>

        {{-- نوع منو --}}
        <span class="ap-badge @if ($menu->type === 'category') bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400
                              @elseif ($menu->type === 'static') bg-violet-50 text-violet-700 dark:bg-violet-500/12 dark:text-violet-400
                              @elseif ($menu->type === 'megamenu') bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400
                              @else bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2 @endif"
              title="{{ \App\Livewire\Admin\Menus\MenuManager::typeLabel($menu->type) }}">
            {{ \App\Livewire\Admin\Menus\MenuManager::typeLabel($menu->type) }}
        </span>

        {{-- لینک (فقط normal/megamenu) --}}
        @if (in_array($menu->type, ['normal', 'megamenu']) && $menu->link)
            <code class="max-w-48 truncate text-[11px] text-ink-2 dark:text-night-ink-2" dir="ltr" title="{{ $menu->link }}">{{ $menu->link }}</code>
        @endif

        @if ($menu->type === 'category' && $menu->children)
            <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2" title="با زیرشاخه‌ها">
                <x-admin-panel::icon name="folder-tree" class="size-3" />
                با زیرشاخه‌ها
            </span>
        @endif

        <span class="flex-1"></span>

        {{-- مرتب‌سازی خواهرها (جایگزین درگ‌انددراپ قدیمی) --}}
        <button type="button" wire:click="moveUp({{ $menu->id }})"
                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                title="انتقال به بالا" aria-label="انتقال به بالا">
            <x-admin-panel::icon name="chevron-up" class="size-4" />
        </button>
        <button type="button" wire:click="moveDown({{ $menu->id }})"
                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                title="انتقال به پایین" aria-label="انتقال به پایین">
            <x-admin-panel::icon name="chevron-down" class="size-4" />
        </button>

        {{-- ویرایش (پورت دکمه edit-menu با فرم پرشده) --}}
        @can('menus.update')
            <button type="button" wire:click="editMenu({{ $menu->id }})"
                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                    title="ویرایش منو" aria-label="ویرایش">
                <x-admin-panel::icon name="pencil" class="size-4" />
            </button>
        @endcan

        {{-- افزودن زیرمنو (جایگزین کشیدن منو به داخل منوی دیگر) --}}
        @can('menus.create')
            <button type="button" wire:click="startCreate({{ $menu->id }})"
                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                    title="افزودن زیر منو" aria-label="افزودن زیر منو">
                <x-admin-panel::icon name="plus" class="size-4" />
            </button>
        @endcan

        @can('menus.delete')
            <button type="button" wire:click="confirmDelete({{ $menu->id }})"
                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                    title="حذف منو" aria-label="حذف">
                <x-admin-panel::icon name="trash-2" class="size-4" />
            </button>
        @endcan
    </div>

    @if (count($node['children']))
        <ul class="ms-4 mt-1.5 space-y-1.5 border-s border-line ps-3 sm:ms-6 dark:border-night-line">
            @foreach ($node['children'] as $childNode)
                @include('admin-panel::livewire.admin.menus.partials.menu-node', ['node' => $childNode])
            @endforeach
        </ul>
    @endif
</li>
