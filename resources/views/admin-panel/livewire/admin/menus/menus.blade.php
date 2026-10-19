{{-- مدیریت منوها — صفحه تک‌صفحه‌ای با درخت + فرم نوع‌محور — پورت back/menus/index --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-5xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                کل منوها: <span class="fa-digits">{{ number_format($this->totalCount) }}</span>
            </span>
        </div>
        @can('menus.create')
            <button type="button" wire:click="startCreate" class="ap-btn-primary !py-2">
                <x-admin-panel::icon name="plus" class="size-4" />
                افزودن منو
            </button>
        @endcan
    </div>

    {{-- فرم افزودن/ویرایش (آفلاین در صفحه — پورت مودال‌های قدیمی با سوییچ نوع) --}}
    @can('menus.create')
        <div class="ap-card p-4 sm:p-5" x-show="$wire.formOpen" x-cloak id="menu-form-card">
            <div class="mb-4 flex items-center justify-between gap-2">
                <h2 class="flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="{{ $editingId ? 'pencil' : 'plus' }}" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    {{ $editingId ? 'ویرایش منو' : 'ایجاد منوی جدید' }}
                </h2>
                <button type="button" wire:click="cancelForm"
                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 dark:hover:bg-night-3"
                        title="بستن فرم" aria-label="بستن فرم">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            <form wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="menu-form-type">نوع منو</label>
                        <select id="menu-form-type" wire:model.live="formType" class="ap-input !py-2">
                            <option value="normal">منوی معمولی</option>
                            <option value="category">منوی دسته بندی</option>
                            <option value="static">منوی ثابت</option>
                            <option value="megamenu">مگامنو</option>
                        </select>
                        @error('formType') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="menu-form-parent">منوی والد (اختیاری)</label>
                        <select id="menu-form-parent" wire:model="formParentId" class="ap-input !py-2">
                            <option value="">— منوی اصلی (سطح ریشه) —</option>
                            @foreach ($this->parentOptions as $option)
                                <option value="{{ $option->id }}">{{ $this->displayTitle($option) }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                            برای تبدیل منو به زیرمجموعه یا خروج از حالت زیرمجموعه، والد را تغییر دهید.
                        </p>
                        @error('formParentId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- نوع normal / megamenu: عنوان + لینک + آیکون --}}
                @if (in_array($formType, ['normal', 'megamenu']))
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="menu-form-title">عنوان <span class="text-red-500">*</span></label>
                            <input id="menu-form-title" type="text" wire:model="formTitle" class="ap-input !py-2" placeholder="عنوان منو…">
                            @error('formTitle') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="menu-form-link">لینک</label>
                            <input id="menu-form-link" type="text" wire:model="formLink" dir="ltr"
                                   class="ap-input !py-2 font-mono !text-xs" placeholder="/products">
                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">اختیاری — مثل فرم قدیمی.</p>
                            @error('formLink') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="menu-form-icon">آیکون (کلاس آیکون)</label>
                            <input id="menu-form-icon" type="text" wire:model="formIcon" dir="ltr"
                                   class="ap-input !py-2 font-mono !text-xs" placeholder="مثال: mdi mdi-menu">
                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">این فیلد اختیاری است</p>
                            @error('formIcon') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @elseif ($formType === 'category')
                    {{-- نوع category: عنوان + آیکون + انتخاب دسته + زیرشاخه‌ها --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="menu-form-category-title">عنوان</label>
                            <input id="menu-form-category-title" type="text" wire:model="formCategoryTitle" class="ap-input !py-2"
                                   placeholder="خالی = عنوان خود دسته در فرانت…">
                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">این فیلد اختیاری است</p>
                            @error('formCategoryTitle') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="menu-form-category-icon">آیکون (کلاس آیکون)</label>
                            <input id="menu-form-category-icon" type="text" wire:model="formCategoryIcon" dir="ltr"
                                   class="ap-input !py-2 font-mono !text-xs" placeholder="مثال: mdi mdi-menu">
                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">این فیلد اختیاری است</p>
                            @error('formCategoryIcon') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold" for="menu-form-category">انتخاب دسته بندی <span class="text-red-500">*</span></label>
                            <select id="menu-form-category" wire:model="formCategoryId" class="ap-input !py-2">
                                <option value="">انتخاب دسته‌بندی…</option>
                                @foreach ($this->categories as $type => $categoryGroup)
                                    <optgroup label="{{ category_group($type) }}">
                                        @foreach ($categoryGroup as $category)
                                            <option value="{{ $category->id }}">{{ $category->full_title }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('formCategoryId') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                            <input type="checkbox" wire:model="formChildren"
                                   class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line">
                            اضافه کردن زیر دسته ها
                        </label>
                    </div>
                @elseif ($formType === 'static')
                    {{-- نوع static: عنوان + آیکون + انتخاب منوی ثابت (کلیدهای دقیق config('general.static_menus')) --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="menu-form-static-title">عنوان <span class="text-red-500">*</span></label>
                            <input id="menu-form-static-title" type="text" wire:model="formTitle" class="ap-input !py-2" placeholder="عنوان منو…">
                            @error('formTitle') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="menu-form-static">انتخاب منو <span class="text-red-500">*</span></label>
                            <select id="menu-form-static" wire:model="formStatic" class="ap-input !py-2">
                                <option value="">انتخاب منوی ثابت…</option>
                                @foreach (config('general.static_menus', []) as $staticKey => $staticMenu)
                                    <option value="{{ $staticKey }}">{{ $staticMenu['title'] }}</option>
                                @endforeach
                            </select>
                            @error('formStatic') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="menu-form-static-icon">آیکون (کلاس آیکون)</label>
                            <input id="menu-form-static-icon" type="text" wire:model="formIcon" dir="ltr"
                                   class="ap-input !py-2 font-mono !text-xs" placeholder="مثال: mdi mdi-menu">
                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">این فیلد اختیاری است</p>
                            @error('formIcon') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                <div class="flex items-center gap-2 border-t border-line pt-4 dark:border-night-line">
                    <button type="submit" wire:loading.attr="disabled" wire:target="save"
                            class="ap-btn-primary !py-2.5">
                        <x-admin-panel::icon name="check" class="size-4" />
                        {{ $editingId ? 'ذخیره تغییرات' : 'ذخیره' }}
                    </button>
                    <button type="button" wire:click="cancelForm" class="ap-btn-ghost !py-2.5">انصراف</button>
                </div>
            </form>
        </div>
    @endcan

    {{-- درخت منوها --}}
    <div class="ap-card overflow-hidden">
        <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-3 dark:border-night-line">
            <h2 class="flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="list" class="size-4.5 text-brand-600 dark:text-brand-400" />
                درخت منوها
            </h2>
            <p class="hidden text-[11px] text-ink-3 dark:text-night-ink-2 sm:block">
                ترتیب با دکمه‌های ▲▼ و جایگاه با «ویرایش» و انتخاب منوی والد تنظیم می‌شود.
            </p>
        </div>

        <div class="p-4 sm:p-5">
            @if (count($this->nodes))
                <ul class="space-y-2">
                    @foreach ($this->nodes as $node)
                        @include('admin-panel::livewire.admin.menus.partials.menu-node', ['node' => $node])
                    @endforeach
                </ul>
            @else
                <div class="flex flex-col items-center gap-2 py-12 text-ink-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="menu" class="size-10 opacity-40" />
                    <p class="text-sm font-medium">منویی برای نمایش وجود ندارد</p>
                </div>
            @endif
        </div>
    </div>

    {{-- مودال تایید حذف (متن قدیمی حفظ شد) --}}
    <div x-data="{ open: @entangle('confirmingDelete').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-sm rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                <x-admin-panel::icon name="trash-2" class="size-6" />
            </div>
            <h3 class="text-base font-bold">حذف منو</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف این منو تمامی زیر منو های آن حذف خواهند شد، آیا برای حذف مطمئن هستید؟
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">خیر</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="save, editMenu, delete, moveUp, moveDown, startCreate"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
