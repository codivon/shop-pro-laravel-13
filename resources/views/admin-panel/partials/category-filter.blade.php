{{-- فاز ۱۲۰ — فیلتر مدرن دسته‌بندی پنل مدیریت (هم‌تجربه با پنل فروشنده)
     • پیکر درختی بازشو + جستجوی زندهٔ تخت (وقتی عبارت جستجو غیرخالی است)
     • چیپ‌های دسته‌های انتخاب‌شده با حذف تکی
     ورودی‌ها:
       $wireProp        — نام پراپرتی آرایه‌ای لایووایر (مثلاً categoryId)
       $categoriesTree  — درخت (childrenTree روی هر گره)
       $categoriesFlatJs— لیست تخت {id, title, path} برای جستجو
       $categoryTitles  — نقشهٔ id → عنوانِ انتخاب‌شده‌ها (چیپ‌ها)
     متد موردنیاز در کامپوننت: removeCategory(int $id) --}}
@php($wireProp = $wireProp ?? 'categoryId')
<div x-data="apCatFilter(@js($categoriesFlatJs))" class="relative">

    <span class="mb-1.5 block text-xs font-semibold text-ink-2 dark:text-night-ink-2">دسته‌بندی‌ها</span>

    {{-- دکمهٔ بازکردن پیکر --}}
    <button type="button" x-on:click="open = !open"
            class="ap-input flex w-full items-center justify-between gap-2 text-start"
            :aria-expanded="open ? 'true' : 'false'" aria-haspopup="listbox">
        <span class="min-w-0 truncate text-xs" x-text="selectedCount ? (ApFormat.faDigits(String(selectedCount)) + ' دسته انتخاب شده') : 'همهٔ دسته‌ها'"></span>
        <span class="flex shrink-0 items-center gap-1.5">
            <span x-show="selectedCount" x-cloak
                  class="fa-digits flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1.5 text-[10px] font-bold text-white"
                  x-text="ApFormat.faDigits(String(selectedCount))"></span>
            <x-admin-panel::icon name="chevron-down" class="size-4 text-ink-3 transition-transform duration-200"
                                 x-bind:class="open ? 'rotate-180' : ''" />
        </span>
    </button>

    {{-- پنل پیکر --}}
    <div x-show="open" x-on:click.outside="open = false" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 -translate-y-1"
         class="absolute inset-x-0 z-30 mt-2 rounded-2xl border border-line bg-surface p-3 shadow-pop dark:border-night-line dark:bg-night-2"
         role="listbox" aria-label="انتخاب دسته‌بندی‌ها">

        {{-- جستجو --}}
        <div class="relative">
            <input type="text" x-model="q" placeholder="جستجوی دسته…" autocomplete="off"
                   class="ap-input !py-2 pe-9 text-xs" aria-label="جستجوی دسته‌بندی">
            <span class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-ink-3">
                <x-admin-panel::icon name="search" class="size-4" />
            </span>
        </div>

        {{-- حالت جستجو: لیست تخت نتایج (با مسیر کامل) --}}
        <div x-show="q.trim()" x-cloak class="mt-2 max-h-64 space-y-0.5 overflow-y-auto">
            <template x-for="c in flatMatches" :key="c.id">
                <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                    <input type="checkbox" wire:model.live="{{ $wireProp }}" x-bind:value="c.id"
                           class="size-4 shrink-0 cursor-pointer rounded accent-brand-600">
                    <span class="min-w-0 flex-1 truncate text-xs text-ink dark:text-night-ink" x-text="c.path"></span>
                </label>
            </template>
            <p x-show="!flatMatches.length" class="py-3 text-center text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">
                دسته‌ای یافت نشد
            </p>
        </div>

        {{-- حالت عادی: درخت دسته‌ها --}}
        <div x-show="!q.trim()" class="mt-2 max-h-64 overflow-y-auto pe-1">
            <div class="space-y-0.5">
                @foreach ($categoriesTree as $treeNode)
                    @include('admin-panel::partials.category-node', ['node' => $treeNode, 'depth' => 0])
                @endforeach
            </div>
            @if (! $categoriesTree->count())
                <p class="py-3 text-center text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">دسته‌ای ثبت نشده است</p>
            @endif
        </div>

        {{-- پانوشت پیکر --}}
        <div class="mt-2 flex items-center justify-between border-t border-line pt-2.5 dark:border-night-line">
            <span class="fa-digits text-[11px] font-semibold text-ink-3 dark:text-night-ink-3">
                <b x-text="ApFormat.faDigits(String(selectedCount))"></b> مورد انتخاب شده
            </span>
            <button type="button" x-on:click="clearAll()" x-show="selectedCount" x-cloak
                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-bold text-ink-3 transition-colors hover:text-red-500 dark:text-night-ink-2">
                <x-admin-panel::icon name="rotate-ccw" class="size-3.5" />
                پاک‌کردن انتخاب‌ها
            </button>
        </div>
    </div>

    {{-- چیپ‌های انتخاب‌شده (سرور-رندر با هر آپدیت لایووایر به‌روز می‌شود) --}}
    @if (count($categoryTitles))
        <div class="mt-2 flex flex-wrap gap-1.5">
            @foreach ($categoryTitles as $chipId => $chipTitle)
                <span class="ap-badge gap-1 bg-brand-50 py-1 ps-2.5 text-brand-700 dark:bg-brand-500/12 dark:text-brand-300">
                    {{ $chipTitle }}
                    <button type="button" wire:click="removeCategory({{ $chipId }})"
                            class="rounded-full p-0.5 transition-colors hover:bg-brand-100 hover:text-red-500 dark:hover:bg-brand-500/20"
                            aria-label="حذف دستهٔ {{ $chipTitle }}">
                        <x-admin-panel::icon name="x" class="size-3" />
                    </button>
                </span>
            @endforeach
        </div>
    @endif
</div>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('apCatFilter', (flat = []) => ({
            open: false,
            q: '',
            flat: flat,

            get selectedCount() {
                return (this.$wire.{{ $wireProp }} || []).length;
            },

            get flatMatches() {
                const q = this.q.trim();
                if (! q) return [];
                return this.flat.filter((c) => (c.title + ' ' + c.path).includes(q));
            },

            clearAll() {
                this.q = '';
                this.$wire.set('{{ $wireProp }}', []);
            },
        }));
    });
</script>
