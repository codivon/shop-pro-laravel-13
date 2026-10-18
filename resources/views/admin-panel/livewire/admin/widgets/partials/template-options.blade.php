{{-- رندر داینامیک options ابزارک بر اساس template config — پورت ۱:۱ back/widgets/template.blade.php + partials --}}
{{-- متغیرها: $options (لیست option های template)، $widget (Model|null)، $this → کامپوننت WidgetForm --}}
@foreach ($options as $option)
    @php $optionKey = $option['key']; $errorKey = 'optionValues.' . $optionKey; @endphp

    @switch($option['input-type'])

        @case('input')
            {{-- پورت back/widgets/partials/input.blade.php --}}
            <div class="{{ $option['class'] ?? '' }}">
                <label class="mb-1.5 block text-xs font-semibold" for="option-input-{{ $optionKey }}">
                    {{ $option['title'] }}
                </label>
                <input id="option-input-{{ $optionKey }}"
                       type="{{ $option['type'] ?? 'text' }}"
                       wire:model="optionValues.{{ $optionKey }}"
                       class="ap-input"
                       value="{{ $this->optionValue($optionKey) ?? $option['default'] ?? '' }}"
                       {!! $option['attributes'] ?? '' !!}>
                @isset($option['help'])
                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $option['help'] }}</p>
                @endisset
                @error($errorKey) <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            @break

        @case('select')
            {{-- پورت back/widgets/partials/select.blade.php --}}
            <div class="{{ $option['class'] ?? '' }}">
                <label class="mb-1.5 block text-xs font-semibold" for="option-select-{{ $optionKey }}">
                    {{ $option['title'] }}
                </label>
                <select id="option-select-{{ $optionKey }}"
                        wire:model="optionValues.{{ $optionKey }}"
                        class="ap-input"
                        {!! $option['attributes'] ?? '' !!}>
                    @foreach ($option['options'] as $item)
                        <option value="{{ $item['value'] }}" @selected($this->optionValue($optionKey) == $item['value'])>
                            {{ $item['title'] }}
                        </option>
                    @endforeach
                </select>
                @isset($option['help'])
                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $option['help'] }}</p>
                @endisset
                @error($errorKey) <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            @break

        @case('file')
            {{-- پورت back/widgets/partials/file.blade.php + آپلود WithFileUploads --}}
            <div class="{{ $option['class'] ?? '' }}">
                <label class="mb-1.5 block text-xs font-semibold" for="option-file-{{ $optionKey }}">
                    {{ $option['title'] }}
                </label>

                <input id="option-file-{{ $optionKey }}" type="file"
                       wire:model="fileUploads.{{ $optionKey }}"
                       class="block w-full cursor-pointer rounded-xl border border-line bg-transparent text-xs text-ink-2
                              file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5
                              file:text-xs file:font-semibold file:text-white hover:file:bg-brand-700 dark:border-night-line"
                       {!! $option['attributes'] ?? '' !!}>

                @if (isset($this->fileUploads[$optionKey]))
                    <p class="mt-1.5 flex items-center gap-1.5 text-[11px] font-semibold text-brand-600 dark:text-brand-400">
                        <x-admin-panel::icon name="upload" class="size-3.5" />
                        فایل جدید: {{ $this->fileUploads[$optionKey]->getClientOriginalName() }}
                    </p>
                @elseif ($this->optionValue($optionKey))
                    <p class="mt-1.5 flex items-center gap-2 text-[11px] text-ink-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="check-circle" class="size-3.5 text-emerald-500" />
                        فایل فعلی: <span dir="ltr">{{ $this->optionValue($optionKey) }}</span>
                        @if (preg_match('/\.(png|jpe?g|gif|webp|svg)$/i', (string) $this->optionValue($optionKey)))
                            <img src="{{ asset($this->optionValue($optionKey)) }}" alt="{{ $option['title'] }}"
                                 class="size-8 rounded-lg border border-line object-cover dark:border-night-line">
                        @endif
                    </p>
                @endif

                @isset($option['help'])
                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $option['help'] }}</p>
                @endisset
                @error($errorKey) <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            @break

        @case('post_categories')
        @case('product_categories')
            {{-- پورت back/widgets/partials/{post,product}-categories.blade.php — درخت چک‌باکس به‌جای select2ToTree --}}
            @php
                $categoriesList = $option['input-type'] === 'post_categories'
                    ? $this->postCategories
                    : $this->productCategories;
                $selectedIds = is_array($this->optionValues[$optionKey] ?? null) ? $this->optionValues[$optionKey] : [];
            @endphp
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-semibold">{{ $option['title'] }}</label>
                <div class="max-h-72 space-y-1 overflow-y-auto rounded-xl border border-line p-3 dark:border-night-line">
                    @forelse ($categoriesList as $category)
                        @php $depth = $category->parents()->count(); @endphp
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg px-1.5 py-1 transition-colors hover:bg-surface-2 dark:hover:bg-night-3">
                            <span class="flex items-center gap-2" dir="rtl">
                                @if ($depth > 0)
                                    <span class="text-line" aria-hidden="true">└</span>
                                @endif
                                <input type="checkbox" value="{{ $category->id }}"
                                       wire:model="optionValues.{{ $optionKey }}"
                                       class="size-4 cursor-pointer rounded border-line text-brand-600">
                                <span class="text-[13px] {{ $depth > 0 ? 'text-ink-2 dark:text-night-ink-2' : 'font-semibold' }}">
                                    {{ str_repeat('— ', max(0, $depth - 1)) }}{{ $category->title }}
                                </span>
                            </span>
                        </label>
                    @empty
                        <p class="py-3 text-center text-xs text-ink-3 dark:text-night-ink-2">دسته‌بندی‌ای یافت نشد</p>
                    @endforelse
                </div>
                @isset($option['help'])
                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $option['help'] }}</p>
                @endisset
                @error($errorKey) <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            @break

    @endswitch
@endforeach
