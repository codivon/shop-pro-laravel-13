{{-- گره درخت دسته‌بندی — فراخوانی بازگشتی با @include --}}
@php
    $category = $node['model'];
@endphp
<li wire:key="cat-{{ $category->id }}" class="list-none">
    <div class="flex flex-wrap items-center gap-x-2 gap-y-1.5 rounded-xl border border-line bg-surface px-3 py-2.5 transition-colors hover:border-brand-300 hover:bg-surface-2 dark:border-night-line dark:bg-night-2 dark:hover:border-brand-500/40 dark:hover:bg-night-3">

        <a href="{{ route('np.categories.edit', $category) }}" wire:navigate
           class="text-[13px] font-bold text-ink transition-colors hover:text-brand-600 dark:text-night-ink dark:hover:text-brand-400">
            {{ $category->title }}
        </a>

        {{-- نامک --}}
        <span class="ap-badge" dir="ltr" title="نامک دسته">
            <code class="text-[10px]">{{ $category->slug }}</code>
        </span>

        {{-- تعداد محصولات کل شاخه — فقط دسته‌بندی محصولات (پورت productsCount) --}}
        @if ($type === 'productcat')
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400"
                  title="تعداد محصولات این شاخه">
                <x-admin-panel::icon name="package" class="size-3" />
                <span class="fa-digits">{{ number_format($this->productCount($category)) }}</span>
            </span>
        @endif

        {{-- وضعیت انتشار --}}
        @if ($category->published)
            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400" title="منتشر شده">
                <x-admin-panel::icon name="eye" class="size-3" />
                منتشر شده
            </span>
        @else
            <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/12 dark:text-amber-400" title="پیش‌نویس">
                <x-admin-panel::icon name="eye-off" class="size-3" />
                پیش‌نویس
            </span>
        @endif

        <span class="flex-1"></span>

        {{-- مرتب‌سازی هم‌سطح‌ها (جایگزین درگ‌انددراپ قدیمی) --}}
        <button type="button" wire:click="moveUp({{ $category->id }})"
                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                title="انتقال به بالا" aria-label="انتقال به بالا">
            <x-admin-panel::icon name="chevron-up" class="size-4" />
        </button>
        <button type="button" wire:click="moveDown({{ $category->id }})"
                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                title="انتقال به پایین" aria-label="انتقال به پایین">
            <x-admin-panel::icon name="chevron-down" class="size-4" />
        </button>

        {{-- انتقال به سطح دیگر (والد جدید) — ضد حلقه در سرور --}}
        <select wire:model.live="parentIds.{{ $category->id }}"
                class="ap-input !w-40 !py-1.5 !text-xs"
                title="انتقال به سطح دیگر" aria-label="انتقال به سطح دیگر">
            <option value="0">— سطح ریشه —</option>
            @foreach ($node['parentOptions'] as $option)
                <option value="{{ $option->id }}">{{ $option->title }}</option>
            @endforeach
        </select>

        <a href="{{ route('np.categories.edit', $category) }}" wire:navigate
           class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
           title="ویرایش دسته‌بندی" aria-label="ویرایش">
            <x-admin-panel::icon name="pencil" class="size-4" />
        </a>

        <button type="button" wire:click="startCreate({{ $category->id }})"
                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                title="افزودن زیرشاخه" aria-label="افزودن زیرشاخه">
            <x-admin-panel::icon name="plus" class="size-4" />
        </button>

        @can($permission)
            <button type="button" wire:click="confirmDelete({{ $category->id }})"
                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                    title="حذف دسته‌بندی" aria-label="حذف">
                <x-admin-panel::icon name="trash-2" class="size-4" />
            </button>
        @endcan
    </div>

    @if (count($node['children']))
        <ul class="ms-4 mt-1.5 space-y-1.5 border-s border-line ps-3 sm:ms-6 dark:border-night-line">
            @foreach ($node['children'] as $childNode)
                @include('admin-panel::livewire.admin.taxonomy.partials.category-node', ['node' => $childNode])
            @endforeach
        </ul>
    @endif
</li>
