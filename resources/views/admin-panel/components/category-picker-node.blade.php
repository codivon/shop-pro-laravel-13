{{-- یک گره درخت انتخابگر دسته‌بندی (بازگشتی) — پارامترها: node, depth --}}
{{-- node باید در اسکوپ Alpine هم در دسترس باشد (matches/choose/x-text از آن استفاده می‌کنند) --}}
<li class="ap-cat-node" x-data="{ node: @js($node) }" x-show="matches(node)" role="option" :aria-selected="isSelected(node.id).toString()">
    <div class="ap-cat-row @if (($depth ?? 0) > 0) ap-cat-row-child @endif"
         :class="isSelected(node.id) ? 'is-selected' : ''">
        {{-- دکمه باز/بسته فرزندان --}}
        @if (isset($node['children']) && count($node['children']))
            <button type="button" class="ap-cat-caret" x-on:click.stop="expanded[node.id] = !expanded[node.id]"
                    :class="shouldExpand(node) ? 'is-open' : ''"
                    :aria-expanded="shouldExpand(node).toString()" aria-label="نمایش زیرشاخه‌ها" tabindex="-1">
                <x-admin-panel::icon name="chevron-left" class="size-3.5" />
            </button>
        @else
            <span class="ap-cat-caret is-leaf" aria-hidden="true"></span>
        @endif

        {{-- ردیف انتخاب (کارت) --}}
        <button type="button" class="ap-cat-row-main" x-on:click="choose(node.id)">
            <span class="ap-cat-mark" :class="isSelected(node.id) ? 'is-checked' : ''" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5" />
                </svg>
            </span>
            <span class="flex-1 truncate text-start" x-text="node.title"></span>
            @if (isset($node['children']) && count($node['children']))
                <span class="ap-cat-badge" title="تعداد زیرشاخه‌ها">
                    <span class="fa-digits">{{ number_format(count($node['children'])) }}</span>
                    زیرشاخه
                </span>
            @endif
        </button>
    </div>

    {{-- فرزندان --}}
    @if (isset($node['children']) && count($node['children']))
        <ul class="ap-cat-children" x-show="shouldExpand(node)">
            @foreach ($node['children'] as $child)
                @include('admin-panel::components.category-picker-node', ['node' => $child, 'depth' => ($depth ?? 0) + 1])
            @endforeach
        </ul>
    @endif
</li>
