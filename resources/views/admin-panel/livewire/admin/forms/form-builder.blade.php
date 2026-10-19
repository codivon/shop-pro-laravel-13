{{-- فرم‌ساز — پنل جدید (فاز ۶) — پورت ۱:۱ back/forms/{create,edit} + سازنده‌ی فیلد Alpine --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5" x-data="fbBuilder({
        isEditing: @js($isEditing),
        existingNames: @js($this->existingFields->pluck('name')->all()),
        typeNames: @js(\App\Livewire\Admin\Forms\FormBuilder::TYPE_NAMES),
        typeIcons: @js(\App\Livewire\Admin\Forms\FormBuilder::TYPE_ICONS),
        choiceTypes: @js(['select', 'radio', 'checkbox'])
     })">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="{{ route('np.forms.index') }}" wire:navigate
               class="rounded-lg p-2 text-ink-3 transition-colors hover:bg-surface-3 dark:hover:bg-night-3"
               aria-label="بازگشت به فرم‌ها" title="بازگشت">
                <x-admin-panel::icon name="arrow-right" class="size-4.5" />
            </a>
            <h1 class="text-base font-bold sm:text-lg">
                {{ $isEditing ? 'ویرایش فرم: ' . $form->title : 'ایجاد فرم جدید' }}
            </h1>
        </div>
        <button type="button" x-on:click="submitForm" wire:loading.attr="disabled" wire:target="save"
                class="ap-btn">
            <x-admin-panel::icon name="check" class="size-4" />
            ذخیره فرم
        </button>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">

        {{-- ستون فیلدهای فرم (تمام عرض در موبایل، ۲/۳ عرض در دسکتاپ) --}}
        <div class="space-y-5 xl:col-span-2">

            {{-- کارت فیلدهای فرم --}}
            <div class="ap-card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line p-4 dark:border-night-line sm:p-5">
                    <div>
                        <h2 class="flex items-center gap-2 text-sm font-bold">
                            <x-admin-panel::icon name="list" class="size-4.5 text-brand-600 dark:text-brand-400" />
                            فیلدهای فرم
                        </h2>
                        <p class="mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">
                            @if ($isEditing)
                                فیلدهای ذخیره‌شده با فلش‌های بالا/پایین جابجا می‌شوند؛ فقط فیلدهای جدید پس از ذخیره به فرم اضافه می‌شوند
                            @else
                                برای شروع، روی «افزودن فیلد جدید» بزنید
                            @endif
                        </p>
                    </div>
                    <button type="button" class="ap-btn-outline" x-on:click="openModal()">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        افزودن فیلد جدید
                    </button>
                </div>

                <div class="p-4 sm:p-5">
                    @error('fieldsData')
                        <div class="mb-4 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-[13px] font-medium text-red-700 dark:border-red-500/25 dark:bg-red-500/10 dark:text-red-300">
                            <x-admin-panel::icon name="triangle-alert" class="mt-0.5 size-4 shrink-0" />
                            <span>{{ $message }}</span>
                        </div>
                    @enderror

                    {{-- فیلدهای موجود (حالت ویرایش) --}}
                    @if ($isEditing)
                        <ul class="mb-4 space-y-2.5">
                            @forelse ($this->existingFields as $existing)
                                <li wire:key="existing-field-{{ $existing->id }}"
                                    class="rounded-xl border border-line bg-surface-2 p-3.5 transition-colors hover:border-brand-300 dark:border-night-line dark:bg-night-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-ink-3"><x-admin-panel::icon name="ellipsis" class="size-4" /></span>
                                                <strong class="text-[13px]">{{ $existing->label }}</strong>
                                                <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                                    {{ \App\Livewire\Admin\Forms\FormBuilder::typeName($existing->type) }}
                                                </span>
                                                @if ($existing->required)
                                                    <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400">ضروری</span>
                                                @endif
                                            </div>
                                            <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-ink-3 dark:text-night-ink-2">
                                                <span>نام: <code dir="ltr">{{ $existing->name }}</code></span>
                                                @if ($existing->placeholder)
                                                    <span>| متن نمایشی: {{ $existing->placeholder }}</span>
                                                @endif
                                                @if ($existing->help_text)
                                                    <span>| راهنما: {{ $existing->help_text }}</span>
                                                @endif
                                                @if ($existing->rules_validation)
                                                    <span dir="ltr">| اعتبارسنجی: {{ $existing->rules_validation }}</span>
                                                @endif
                                                @if ($existing->default_value)
                                                    <span>| مقدار پیش‌فرض: {{ $existing->default_value }}</span>
                                                @endif
                                                @if ($existing->class)
                                                    <span dir="ltr">| کلاس: {{ $existing->class }}</span>
                                                @endif
                                            </div>
                                            @if ($existing->options && is_array($existing->options) && count($existing->options))
                                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                                    <span class="text-[11px] text-ink-3">گزینه‌ها:</span>
                                                    @foreach ($existing->options as $option)
                                                        <span class="rounded-md border border-line bg-surface px-1.5 py-0.5 text-[11px] dark:border-night-line dark:bg-night-2">{{ $option }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex shrink-0 items-center gap-0.5">
                                            <button type="button" wire:click="moveExistingField({{ $existing->id }}, 'up')"
                                                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                                    title="جابجایی به بالا" aria-label="بالا">
                                                <x-admin-panel::icon name="chevron-up" class="size-4" />
                                            </button>
                                            <button type="button" wire:click="moveExistingField({{ $existing->id }}, 'down')"
                                                    class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                                    title="جابجایی به پایین" aria-label="پایین">
                                                <x-admin-panel::icon name="chevron-down" class="size-4" />
                                            </button>
                                            @can('forms.delete')
                                                <button type="button" wire:click="confirmDeleteField({{ $existing->id }})"
                                                        class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                        title="حذف فیلد" aria-label="حذف">
                                                    <x-admin-panel::icon name="trash-2" class="size-4" />
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="flex flex-col items-center gap-1.5 py-6 text-center text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="list" class="size-8 opacity-40" />
                                    <p class="text-[13px] font-medium">این فرم هنوز فیلد ذخیره‌شده ندارد</p>
                                </li>
                            @endforelse
                        </ul>
                    @endif

                    {{-- فیلدهای جدید هنوز ذخیره‌نشده (سازنده‌ی Alpine) --}}
                    <ul class="space-y-2.5" x-show="fields.length > 0">
                        <template x-for="(field, index) in fields" :key="field.id">
                            <li class="rounded-xl border border-brand-200 bg-brand-50/50 p-3.5 dark:border-brand-500/25 dark:bg-brand-500/8">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="ap-badge bg-brand-600 text-white">جدید</span>
                                            <strong class="text-[13px]" x-text="field.label"></strong>
                                            <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2" x-text="typeNames[field.type] ?? field.type"></span>
                                            <span class="ap-badge bg-red-50 text-red-600 dark:bg-red-500/12 dark:text-red-400" x-show="field.required">ضروری</span>
                                        </div>
                                        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-ink-3 dark:text-night-ink-2">
                                            <span>نام: <code dir="ltr" x-text="field.name"></code></span>
                                            <span x-show="field.placeholder">| متن نمایشی: <span x-text="field.placeholder"></span></span>
                                            <span x-show="field.help_text">| راهنما: <span x-text="field.help_text"></span></span>
                                            <span x-show="field.validation" dir="ltr">| اعتبارسنجی: <span x-text="field.validation"></span></span>
                                            <span x-show="field.default_value">| مقدار پیش‌فرض: <span x-text="field.default_value"></span></span>
                                            <span x-show="field.class" dir="ltr">| کلاس: <span x-text="field.class"></span></span>
                                        </div>
                                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5" x-show="field.options && field.options.length > 0">
                                            <span class="text-[11px] text-ink-3">گزینه‌ها:</span>
                                            <template x-for="(option, optionIndex) in field.options" :key="optionIndex">
                                                <span class="rounded-md border border-line bg-surface px-1.5 py-0.5 text-[11px] dark:border-night-line dark:bg-night-2" x-text="option"></span>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-0.5">
                                        <button type="button" x-on:click="moveField(index, -1)"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                                title="جابجایی به بالا" aria-label="بالا">
                                            <x-admin-panel::icon name="chevron-up" class="size-4" />
                                        </button>
                                        <button type="button" x-on:click="moveField(index, 1)"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                                title="جابجایی به پایین" aria-label="پایین">
                                            <x-admin-panel::icon name="chevron-down" class="size-4" />
                                        </button>
                                        <button type="button" x-on:click="removeField(index)"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف فیلد جدید" aria-label="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    </div>
                                </div>
                            </li>
                        </template>
                    </ul>

                    {{-- حالت خالی (create بدون فیلد) --}}
                    <div x-show="fields.length === 0" class="flex flex-col items-center gap-1.5 py-8 text-center text-ink-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="list" class="size-10 opacity-40" />
                        <p class="text-sm font-semibold">هنوز فیلدی اضافه نشده است</p>
                        <p class="text-xs">برای شروع، روی «افزودن فیلد جدید» بزنید</p>
                    </div>
                </div>
            </div>

            {{-- دکمه‌های اقدام --}}
            <div class="ap-card flex items-center justify-between gap-3 p-4 sm:p-5">
                <p class="text-xs text-ink-3 dark:text-night-ink-2">
                    نامک (اسلاگ) در صورت خالی بودن از عنوان ساخته می‌شود
                </p>
                <button type="button" x-on:click="submitForm" wire:loading.attr="disabled" wire:target="save"
                        class="ap-btn">
                    <x-admin-panel::icon name="check" class="size-4" />
                    ذخیره فرم
                </button>
            </div>
        </div>

        {{-- ستون متادیتای فرم --}}
        <div class="space-y-5">
            <div class="ap-card p-4 sm:p-5">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="clipboard-list" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    اطلاعات فرم
                </h2>

                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="fb-title">عنوان فرم <span class="text-red-500">*</span></label>
                        <input id="fb-title" type="text" wire:model="title" class="ap-input" placeholder="مثال: فرم تماس با ما">
                        @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="fb-slug">نامک (آدرس)</label>
                        <input id="fb-slug" type="text" wire:model="slug" dir="ltr" class="ap-input font-mono" placeholder="contact-us">
                        <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">بهتر است خالی بماند و خودکار ایجاد شود</p>
                        @error('slug') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="fb-success-message">پیام موفقیت</label>
                        <input id="fb-success-message" type="text" wire:model="success_message" class="ap-input" placeholder="فرم با موفقیت ارسال شد">
                        @error('success_message') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="fb-button-text">متن دکمه ارسال</label>
                        <input id="fb-button-text" type="text" wire:model="button_text" class="ap-input" placeholder="ارسال">
                        @error('button_text') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="fb-email-notify">ایمیل اعلان</label>
                        <input id="fb-email-notify" type="email" wire:model="email_notify" dir="ltr" class="ap-input" placeholder="info@example.com">
                        <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">در صورت پر شدن، اعلان پاسخ جدید ایمیل می‌شود</p>
                        @error('email_notify') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                        <input type="checkbox" wire:model="published"
                               class="size-4 cursor-pointer rounded border-line text-brand-600">
                        <span class="text-[13px] font-semibold">انتشار صفحه؟</span>
                    </label>

                    {{-- توضیحات فرم (CKEditor با پل ApEditor) --}}
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">توضیحات فرم</label>
                        <div class="overflow-hidden rounded-xl border border-line dark:border-night-line" wire:ignore>
                            <div id="ap-ckeditor" data-editor-value="{{ $description }}"></div>
                        </div>
                        <input type="hidden" wire:model="description" id="ap-ckeditor-input">
                        @error('description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </form>
            </div>

            {{-- تنظیمات سئو --}}
            <div class="ap-card p-4 sm:p-5">
                <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="search" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    تنظیمات سئو
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="fb-meta-title">عنوان سئو</label>
                        <input id="fb-meta-title" type="text" wire:model="meta_title" class="ap-input">
                        @error('meta_title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="fb-meta-description">توضیحات سئو</label>
                        <textarea id="fb-meta-description" rows="3" wire:model="meta_description" class="ap-input"></textarea>
                        @error('meta_description') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2 rounded-xl border border-line bg-surface-2 p-3 text-[11px] text-ink-3 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                        <p class="flex items-center gap-1.5"><x-admin-panel::icon name="check" class="size-3.5 text-brand-500" /> ایجاد Google Snippet برای موتور جستجو (خودکار)</p>
                        <p class="flex items-center gap-1.5"><x-admin-panel::icon name="check" class="size-3.5 text-brand-500" /> ایجاد پیشنمایش برای شبکه‌های اجتماعی (خودکار)</p>
                        <p class="flex items-center gap-1.5"><x-admin-panel::icon name="check" class="size-3.5 text-brand-500" /> افزودن به sitemap.xml سایت (خودکار)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= مودال افزودن فیلد (انتخاب نوع + پیکربندی) ================= --}}
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="modalOpen" x-on:click="closeModal" x-transition.opacity aria-hidden="true"></div>

        <div x-show="modalOpen" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">

            <div class="flex items-center justify-between gap-2 border-b border-line p-4 dark:border-night-line">
                <div>
                    <h3 class="flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="layers" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        <span x-show="step === 'pick'">انتخاب نوع فیلد</span>
                        <span x-show="step === 'config'">پیکربندی فیلد</span>
                    </h3>
                    <p class="mt-0.5 text-xs text-ink-3 dark:text-night-ink-2">
                        <span x-show="step === 'pick'">نوع فیلد مورد نظر را انتخاب کنید</span>
                        <span x-show="step === 'config'">اطلاعات فیلد را تکمیل کنید</span>
                    </p>
                </div>
                <button type="button" x-on:click="closeModal"
                        class="rounded-lg p-2 text-ink-3 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="بستن">
                    <x-admin-panel::icon name="x" class="size-4" />
                </button>
            </div>

            <div class="flex items-center gap-3 border-b border-line bg-surface-2 px-4 py-2.5 dark:border-night-line dark:bg-night-3">
                <span class="flex items-center gap-1.5 text-xs font-bold" :class="step === 'pick' ? 'text-brand-600 dark:text-brand-400' : 'text-ink-3 dark:text-night-ink-2'">
                    <span class="fa-digits flex size-5 items-center justify-center rounded-full" :class="step === 'pick' ? 'bg-brand-600 text-white' : 'bg-surface-3 text-ink-3'">۱</span>
                    انتخاب نوع
                </span>
                <span class="h-px flex-1 bg-line dark:bg-night-line"></span>
                <span class="flex items-center gap-1.5 text-xs font-bold" :class="step === 'config' ? 'text-brand-600 dark:text-brand-400' : 'text-ink-3 dark:text-night-ink-2'">
                    <span class="fa-digits flex size-5 items-center justify-center rounded-full" :class="step === 'config' ? 'bg-brand-600 text-white' : 'bg-surface-3 text-ink-3'">۲</span>
                    پیکربندی
                </span>
            </div>

            <div class="flex-1 overflow-y-auto p-4 sm:p-5">
                {{-- مرحله ۱: انتخاب نوع (۱۲ نوع — پورت fb-type-cards قدیمی) --}}
                <div x-show="step === 'pick'" class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                    @foreach (\App\Livewire\Admin\Forms\FormBuilder::TYPE_NAMES as $type => $typeName)
                        <button type="button" x-on:click="pickType('{{ $type }}')"
                                class="flex flex-col items-center gap-2 rounded-xl border p-4 transition-colors"
                                :class="selectedType === '{{ $type }}'
                                    ? 'border-brand-500 bg-brand-50 dark:border-brand-400 dark:bg-brand-500/10'
                                    : 'border-line bg-surface hover:border-brand-300 dark:border-night-line dark:bg-night-3'">
                            <x-admin-panel::icon name="{{ \App\Livewire\Admin\Forms\FormBuilder::TYPE_ICONS[$type] }}" class="size-5 text-brand-600 dark:text-brand-400" />
                            <span class="text-[12px] font-semibold">{{ $typeName }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- مرحله ۲: پیکربندی --}}
                <div x-show="step === 'config'" class="space-y-4">
                    <div class="flex items-center justify-between gap-2 rounded-xl border border-brand-200 bg-brand-50/60 p-3 dark:border-brand-500/25 dark:bg-brand-500/10">
                        <div class="flex items-center gap-2">
                            <x-admin-panel::icon name="sliders-horizontal" class="size-4 text-brand-600 dark:text-brand-400" />
                            <div>
                                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">نوع فیلد انتخاب شده</p>
                                <p class="text-[13px] font-bold" x-text="typeNames[selectedType] ?? selectedType"></p>
                            </div>
                        </div>
                        <button type="button" x-on:click="step = 'pick'" class="ap-btn-ghost !py-1.5 text-xs">
                            <x-admin-panel::icon name="rotate-ccw" class="size-3.5" />
                            تغییر
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">عنوان فیلد <span class="text-red-500">*</span></label>
                            <input type="text" x-model="cfg.label" class="ap-input" placeholder="مثال: نام و نام خانوادگی">
                            <p class="mt-1 text-[11px] font-medium text-red-600" x-show="errors.label" x-text="errors.label"></p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">نام فیلد (name) <span class="text-red-500">*</span></label>
                            <input type="text" x-model="cfg.name" dir="ltr" class="ap-input font-mono" placeholder="full_name">
                            <p class="mt-1 text-[11px] font-medium text-red-600" x-show="errors.name" x-text="errors.name"></p>
                        </div>
                    </div>
                    <p class="text-[11px] text-ink-3 dark:text-night-ink-2">نام فیلد فقط شامل حروف انگلیسی، اعداد و زیرخط باشد</p>

                    {{-- گزینه‌ها (برای select/radio/checkbox) --}}
                    <div x-show="choiceTypes.includes(selectedType)" class="rounded-xl border border-line p-3.5 dark:border-night-line">
                        <label class="mb-2 block text-xs font-semibold">گزینه‌ها <span class="text-red-500">*</span></label>
                        <div class="space-y-2">
                            <template x-for="(option, optionIndex) in cfg.options" :key="optionIndex">
                                <div class="flex gap-2">
                                    <input type="text" x-model="cfg.options[optionIndex]" class="ap-input" :placeholder="'گزینه ' + (optionIndex + 1)">
                                    <button type="button" x-on:click="cfg.options.splice(optionIndex, 1)"
                                            class="ap-btn-ghost shrink-0 !p-2 text-red-600" aria-label="حذف گزینه">
                                        <x-admin-panel::icon name="x" class="size-4" />
                                    </button>
                                </div>
                            </template>
                        </div>
                        <button type="button" x-on:click="cfg.options.push('')" class="ap-btn-outline mt-2.5 !py-1.5 text-xs">
                            <x-admin-panel::icon name="plus" class="size-3.5" />
                            افزودن گزینه
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">متن نمایشی (Placeholder)</label>
                            <input type="text" x-model="cfg.placeholder" class="ap-input" placeholder="مثال: نام را وارد کنید">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">متن راهنما</label>
                            <input type="text" x-model="cfg.help_text" class="ap-input" placeholder="متن راهنمای فیلد">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">مقدار پیش‌فرض</label>
                            <input type="text" x-model="cfg.default_value" class="ap-input" placeholder="مقدار پیش‌فرض">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">کلاس CSS</label>
                            <input type="text" x-model="cfg.class" dir="ltr" class="ap-input font-mono" placeholder="form-control-lg">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">قوانین اعتبارسنجی (اختیاری)</label>
                        <input type="text" x-model="cfg.validation" dir="ltr" class="ap-input font-mono" placeholder="min:3|max:255">
                        <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">مثال: min:3|max:255|regex:/[a-z]/</p>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                        <input type="checkbox" x-model="cfg.required" class="size-4 cursor-pointer rounded border-line text-brand-600">
                        <span>
                            <span class="block text-[13px] font-semibold">فیلد اجباری</span>
                            <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">کاربر باید این فیلد را پر کند</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-between gap-2.5 border-t border-line p-4 dark:border-night-line">
                <button type="button" class="ap-btn-ghost" x-show="step === 'config'" x-on:click="step = 'pick'">
                    <x-admin-panel::icon name="arrow-right" class="size-4" />
                    بازگشت
                </button>
                <span x-show="step === 'pick'"></span>
                <button type="button" class="ap-btn" x-show="step === 'config'" x-on:click="addField()">
                    <x-admin-panel::icon name="check" class="size-4" />
                    افزودن فیلد
                </button>
            </div>
        </div>
    </div>

    {{-- مودال تایید حذف فیلد موجود --}}
    @if ($isEditing)
        <div x-data="{ open: @entangle('confirmingDeleteField').live }"
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
                <h3 class="text-base font-bold">حذف فیلد</h3>
                <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                    با حذف فیلد دیگر قادر به بازیابی آن نخواهید بود.
                </p>
                <div class="mt-5 flex gap-2.5">
                    <button type="button" wire:click="deleteField" wire:loading.attr="disabled"
                            class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                        بله حذف شود
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </div>
        </div>
    @endif
</div>

@script
<script>
    function fbBuilder(config) {
        return {
            /* ---------- state ---------- */
            modalOpen: false,
            step: 'pick',
            selectedType: 'text',
            fields: [],
            errors: {},
            choiceTypes: config.choiceTypes,
            typeNames: config.typeNames,
            typeIcons: config.typeIcons,
            existingNames: config.existingNames || [],
            cfg: {},

            emptyCfg() {
                return {
                    label: '',
                    name: '',
                    placeholder: '',
                    required: false,
                    help_text: '',
                    default_value: '',
                    class: '',
                    validation: '',
                    options: [''],
                };
            },

            /* ---------- مودال ---------- */
            openModal() {
                this.cfg = this.emptyCfg();
                this.selectedType = 'text';
                this.step = 'pick';
                this.errors = {};
                this.modalOpen = true;
            },

            closeModal() {
                this.modalOpen = false;
            },

            pickType(type) {
                this.selectedType = type;
                this.step = 'config';
            },

            /* ---------- اعتبارسنجی فیلد (پورت validateField قدیمی) ---------- */
            validateField() {
                this.errors = {};
                let isValid = true;

                if (!this.cfg.label) {
                    this.errors.label = 'عنوان فیلد الزامی است';
                    isValid = false;
                }

                if (!this.cfg.name) {
                    this.errors.name = 'نام فیلد الزامی است';
                    isValid = false;
                } else if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(this.cfg.name)) {
                    this.errors.name = 'نام فیلد باید با حرف انگلیسی یا زیرخط شروع شود و فقط شامل حروف، اعداد و زیرخط باشد';
                    isValid = false;
                } else if (this.fields.some((f) => f.name === this.cfg.name) || this.existingNames.includes(this.cfg.name)) {
                    this.errors.name = 'این نام فیلد قبلاً استفاده شده است';
                    isValid = false;
                }

                if (this.choiceTypes.includes(this.selectedType)) {
                    const hasOption = this.cfg.options.some((option) => option && option.trim() !== '');
                    if (!hasOption) {
                        this.$wire.dispatch('toast', { message: 'حداقل یک گزینه وارد کنید', type: 'error' });
                        isValid = false;
                    }
                }

                return isValid;
            },

            /* ---------- افزودن فیلد جدید (پورت add-field-btn قدیمی) ---------- */
            addField() {
                if (!this.validateField()) return;

                const options = this.choiceTypes.includes(this.selectedType)
                    ? this.cfg.options.filter((option) => option && option.trim() !== '')
                    : [];

                this.fields.push({
                    id: Date.now(),
                    type: this.selectedType,
                    label: this.cfg.label,
                    name: this.cfg.name,
                    placeholder: this.cfg.placeholder,
                    required: this.cfg.required,
                    help_text: this.cfg.help_text,
                    default_value: this.cfg.default_value,
                    class: this.cfg.class,
                    validation: this.cfg.validation,
                    options: options,
                });

                this.modalOpen = false;
                this.$wire.dispatch('toast', { message: 'فیلد با موفقیت اضافه شد', type: 'success' });
            },

            removeField(index) {
                this.fields.splice(index, 1);
            },

            moveField(index, direction) {
                const target = index + direction;
                if (target < 0 || target >= this.fields.length) return;
                [this.fields[index], this.fields[target]] = [this.fields[target], this.fields[index]];
            },

            /* ---------- ذخیره (fields_data JSON → Livewire) ---------- */
            async submitForm() {
                await this.$wire.set('fieldsData', JSON.stringify(this.fields));
                await this.$wire.call('save');
            },
        };
    }

    // بلوک اسکریپت لایوایر محدوده خودش را دارد؛ سازنده را global می‌کنیم تا Alpine بتواند x-data را resolve کند
    window.fbBuilder = fbBuilder;
</script>
@endscript
