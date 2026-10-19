{{-- گره درخت دسته‌بندی — فراخوانی بازگشتی با @include (پارامتر level: ریشه = ۱) --}}
@php
    $level = (int) ($level ?? 1);
    $category = $node['model'];
    $isSub = $level >= 2;
    $isDeep = $level >= 3;

    // تمایز بصری سطح‌ها: پس‌زمینه ردیف + وزن/اندازه فونت عنوان (ریشه پررنگ‌ترین)
    $rowBg = $isDeep
        ? 'bg-surface-3/60 hover:bg-surface-3 dark:bg-night-3 dark:hover:bg-night-3/80'
        : ($isSub
            ? 'bg-surface-2/70 hover:bg-surface-3/70 dark:bg-night-3/60 dark:hover:bg-night-3'
            : 'bg-surface hover:bg-surface-2 dark:bg-night-2 dark:hover:bg-night-3');

    $titleClass = $isDeep
        ? 'text-xs font-medium'
        : ($isSub
            ? 'text-[13px] font-semibold'
            : 'text-[13px] font-extrabold');
@endphp
<li wire:key="cat-{{ $category->id }}" class="list-none">
    <div class="cat-row relative flex flex-wrap items-center gap-x-2 gap-y-1.5 rounded-xl border border-line px-3 py-2.5 transition-all {{ $rowBg }} hover:border-brand-300 dark:border-night-line dark:hover:border-brand-500/40"
         :class="[dropClass({{ $category->id }}), dragId === {{ $category->id }} ? 'opacity-40 saturate-50' : '']">

        {{-- هندل درگ‌انددراپ (آیکون grip-vertical) --}}
        <span draggable="true"
              x-on:dragstart="startDrag($event, {{ $category->id }}, {{ json_encode($node['subtreeIds']) }})"
              class="cursor-grab touch-none select-none rounded-md p-1 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 active:cursor-grabbing dark:text-night-ink-2 dark:hover:bg-brand-500/10 dark:hover:text-brand-400"
              title="کشیدن برای جابه‌جایی" aria-label="کشیدن برای جابه‌جایی دسته">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
                <circle cx="9" cy="5" r="1" /><circle cx="9" cy="12" r="1" /><circle cx="9" cy="19" r="1" />
                <circle cx="15" cy="5" r="1" /><circle cx="15" cy="12" r="1" /><circle cx="15" cy="19" r="1" />
            </svg>
        </span>

        {{-- آیکون گره کنار عنوان زیرشاخه‌ها --}}
        @if ($isSub)
            <x-admin-panel::icon name="circle-dot" class="size-3 shrink-0 text-brand-500/70 dark:text-brand-500/50" />
        @endif

        <a href="{{ route('np.categories.edit', $category) }}" wire:navigate draggable="false"
           class="{{ $titleClass }} text-ink transition-colors hover:text-brand-600 dark:text-night-ink dark:hover:text-brand-400">
            {{ $category->title }}
        </a>

        {{-- نامک --}}
        <span class="ap-badge" dir="ltr" title="نامک دسته">
            <code class="text-[10px]">{{ $category->slug }}</code>
        </span>

        {{-- تعداد محصولات کل شاخه — فقط دسته‌بندی محصولات (پورت productsCount) — برای زیرشاخه‌ها کوچک‌تر --}}
        @if ($type === 'productcat')
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400 {{ $isSub ? '!text-[10px]' : '' }}"
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

        {{-- مرتب‌سازی هم‌سطح‌ها (مکمل درگ‌انددراپ) --}}
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

        <a href="{{ route('np.categories.edit', $category) }}" wire:navigate draggable="false"
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

        {{-- ===== نوارهای رهاسازی (فاز ۱۱) — سه ناحیه واقعی و درشت ===== --}}
        {{-- فقط حین درگ فعال‌اند؛ بقیه وقت pointer-events-none تا کلیک/لینک/سلکت مختل نشود --}}
        <span class="ap-dropband absolute inset-x-0 top-0 z-20 h-[30%] rounded-t-xl"
              :class="dragId === null ? '!pointer-events-none' : ''"
              x-on:dragover.prevent.stop="overBand($event, {{ $category->id }}, 'before')"
              x-on:dragleave.prevent.stop="leaveBand({{ $category->id }})"
              x-on:drop.prevent.stop="drop($event, {{ $category->id }})"
              aria-hidden="true"></span>
        <span class="ap-dropband absolute inset-x-0 top-[30%] z-20 h-[40%]"
              :class="dragId === null ? '!pointer-events-none' : ''"
              x-on:dragover.prevent.stop="overBand($event, {{ $category->id }}, 'inside')"
              x-on:dragleave.prevent.stop="leaveBand({{ $category->id }})"
              x-on:drop.prevent.stop="drop($event, {{ $category->id }})"
              aria-hidden="true"></span>
        <span class="ap-dropband absolute inset-x-0 bottom-0 z-20 h-[30%] rounded-b-xl"
              :class="dragId === null ? '!pointer-events-none' : ''"
              x-on:dragover.prevent.stop="overBand($event, {{ $category->id }}, 'after')"
              x-on:dragleave.prevent.stop="leaveBand({{ $category->id }})"
              x-on:drop.prevent.stop="drop($event, {{ $category->id }})"
              aria-hidden="true"></span>

        {{-- برچسب راهنمای شناور حین درگ --}}
        <span x-show="dragId !== null && overId === {{ $category->id }}" x-cloak
              class="pointer-events-none absolute -top-3.5 start-6 z-30 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10.5px] font-bold shadow-lg"
              :class="overMode === 'forbidden'
                  ? 'bg-red-600 text-white'
                  : 'bg-brand-600 text-white'"
              aria-hidden="true">
            <span x-text="dropLabel({{ $category->id }})"></span>
        </span>
    </div>

    @if (count($node['children']))
        {{-- خط اتصال عمودی والد→فرزند (نقطه‌چین برند) --}}
        <ul class="ms-4 mt-1.5 space-y-1.5 border-s border-dashed ps-3 sm:ms-6 border-brand-300/60 dark:border-brand-500/30">
            @foreach ($node['children'] as $childNode)
                @include('admin-panel::livewire.admin.taxonomy.partials.category-node', ['node' => $childNode, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li>
