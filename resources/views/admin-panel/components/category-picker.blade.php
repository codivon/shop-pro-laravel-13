{{-- انتخابگر درختی دسته‌بندی — فاز ۱۲ (تک/چند انتخابی + جستجو + پیشنهاد پرکاربرد) --}}
{{-- پارامترها:
     wireProp   نام پراپرتی لایووایر (اجباری) مثل category_id یا extraCategories
     :tree      درخت دسته‌ها: [['id'=>1,'title'=>'..','children'=>[...]], ...]
     :popular   پرکاربردها: [['id'=>..,'title'=>'..','count'=>..]] (اختیاری)
     multiple   انتخاب چندگانه (اختیاری)
     placeholder عنوان دکمه وقتی چیزی انتخاب نشده
     required   ستاره اجباری روی لیبل (اختیاری)
     label      متن لیبل (اختیاری — اگر خالی باشد لیبل رندر نمی‌شود)
--}}
@props([
    'wireProp' => 'category_id',
    'tree' => [],
    'popular' => [],
    'multiple' => false,
    'placeholder' => 'انتخاب دسته‌بندی…',
    'label' => null,
    'required' => false,
])

<div x-data="apCategoryPicker({ prop: @js($wireProp), multiple: @js($multiple), tree: {{ \Illuminate\Support\Js::from($tree) }}, popular: {{ \Illuminate\Support\Js::from($popular) }} })"
     class="ap-cat-picker relative" x-on:click.outside="close()">
    @if ($label)
        <label class="mb-1.5 block text-[13px] font-semibold">
            {{ $label }}
            @if ($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif

    {{-- دکمه بازکننده --}}
    <button type="button" class="ap-input flex w-full items-center gap-2 text-start" x-on:click="toggleOpen()"
            :aria-expanded="open.toString()" aria-haspopup="listbox">
        <span class="flex min-h-6 flex-1 flex-wrap items-center gap-1.5">
            {{-- چیپ‌های انتخاب‌شده --}}
            <template x-for="id in selectedIds()" :key="id">
                <span class="ap-cat-chip">
                    <span class="max-w-52 truncate" x-text="titles[id] || ('#' + id)"></span>
                    <button type="button" class="ap-cat-chip-x" x-on:click.stop="removeAt(id)" aria-label="حذف">
                        <x-admin-panel::icon name="x" class="size-3" />
                    </button>
                </span>
            </template>
            {{-- وقتی چیزی انتخاب نشده --}}
            <span x-show="selectedIds().length === 0" class="text-ink-3 dark:text-night-ink-2">{{ $placeholder }}</span>
        </span>
        {{-- پاک‌کردن همه --}}
        <span x-show="selectedIds().length > 0" x-on:click.stop="clear()"
              class="ap-cat-clear" role="button" tabindex="0" title="حذف انتخاب‌ها"
              x-on:keydown.enter.prevent="clear()">
            <x-admin-panel::icon name="x" class="size-3.5" />
        </span>
        <span class="ap-chevron ms-auto inline-flex" :class="open ? 'is-open' : ''">
            <x-admin-panel::icon name="chevron-down" class="size-4 opacity-60" />
        </span>
    </button>

    {{-- پنل درخت --}}
    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
         class="ap-cat-panel absolute inset-x-0 top-full z-30 mt-2">
        <div class="ap-cat-panel-inner">
            {{-- جستجو --}}
            <div class="ap-cat-search">
                <x-admin-panel::icon name="search" class="size-4 text-ink-3" />
                <input type="text" x-model="query" placeholder="جستجوی دسته‌بندی…"
                       class="w-full bg-transparent text-[13px] outline-none placeholder:text-ink-3"
                       x-ref="search" x-effect="$nextTick(() => open && $refs.search.focus())"
                       aria-label="جستجوی دسته‌بندی">
            </div>

            {{-- پرکاربردها --}}
            <div x-show="query.trim() === '' && popular.length > 0" class="ap-cat-popular">
                <span class="ap-cat-popular-title">
                    <x-admin-panel::icon name="flame" class="size-3.5" />
                    پرکاربرد
                </span>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($popular as $p)
                        <button type="button" class="ap-cat-pop-chip" x-on:click="choose({{ $p['id'] }})"
                                :class="isSelected({{ $p['id'] }}) ? 'is-selected' : ''">
                            <x-admin-panel::icon name="star" class="size-3" />
                            <span class="max-w-36 truncate">{{ $p['title'] }}</span>
                            <span class="ap-cat-pop-count">{{ number_format($p['count'] ?? 0) }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- درخت --}}
            <div class="ap-cat-tree max-h-72 overflow-y-auto" role="listbox" :aria-multiselectable="multiple ? 'true' : 'false'">
                @if (count($tree))
                    <ul class="ap-cat-list" role="presentation">
                        @foreach ($tree as $node)
                            @include('admin-panel::components.category-picker-node', ['node' => $node, 'depth' => 0])
                        @endforeach
                    </ul>
                @else
                    <p class="px-3 py-6 text-center text-xs text-ink-3">دسته‌بندی‌ای ثبت نشده است.</p>
                @endif
                <p x-show="query.trim() !== '' && tree.length > 0" class="hidden"></p>
            </div>

            {{-- فوتر حالت چندگانه --}}
            <div x-show="multiple" class="ap-cat-footer">
                <span class="text-[12px] font-semibold text-ink-2 dark:text-night-ink-2">
                    <span class="fa-digits" x-text="ApFormat.faDigits(selectedIds().length)"></span> مورد انتخاب شده
                </span>
                <button type="button" class="ap-btn-primary !px-4 !py-1.5 text-[12px]" x-on:click="close()">تایید</button>
            </div>
        </div>
    </div>
</div>
