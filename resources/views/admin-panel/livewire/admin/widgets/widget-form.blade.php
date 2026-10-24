{{-- فرم ابزارک — پنل جدید (فاز ۶) — پورت ۱:۱ back/widgets/{create,edit} + template.blade.php --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="{{ $this->indexRoute() }}" wire:navigate
               class="rounded-lg p-2 text-ink-3 transition-colors hover:bg-surface-3 dark:hover:bg-night-3"
               aria-label="بازگشت به ابزارک‌ها" title="بازگشت">
                <x-admin-panel::icon name="arrow-right" class="size-4.5" />
            </a>
            <h1 class="text-base font-bold sm:text-lg">
                {{ $this->isEditing ? 'ویرایش ابزارک: ' . $widget->title : 'ابزارک جدید' }}
            </h1>
        </div>
    </div>

    @if ($this->isRegistryEmpty())
        {{-- حالت خالی دوستانه — هیچ نوع ابزارکی ثبت نشده (انحراف مستند از 404 قدیمی) --}}
        <div class="ap-card flex flex-col items-center gap-3 p-10 text-center">
            <span class="flex size-14 items-center justify-center rounded-3xl bg-amber-50 text-amber-500 dark:bg-amber-500/10 dark:text-amber-400">
                <x-admin-panel::icon name="layout-template" class="size-7" />
            </span>
            <h2 class="text-sm font-bold">هیچ نوع ابزارکی ثبت نشده است</h2>
            <p class="max-w-md text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                انواع ابزارک‌ها وقتی در دسترس قرار می‌گیرند که ماژولی آن‌ها را ثبت کند یا قالب فعال، پیکربندی ابزارک‌ها را داشته باشد.
                پس از ثبت، همین صفحه به‌صورت خودکار فعال می‌شود.
            </p>
            <a href="{{ $this->indexRoute() }}" wire:navigate class="ap-btn-outline">
                <x-admin-panel::icon name="arrow-right" class="size-4" />
                بازگشت به لیست ابزارک‌ها
            </a>
        </div>
    @else
        <form wire:submit="save" class="grid grid-cols-1 gap-5 lg:grid-cols-3">

            {{-- ستون تنظیمات options (پورت template.blade.php) --}}
            <div class="space-y-5 lg:col-span-2">
                <div class="ap-card p-4 sm:p-5" x-data="{ tab: 'type' }">
                    <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="sliders-horizontal" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        تنظیمات ابزارک
                    </h2>

                    {{-- انتخاب نوع ابزارک --}}
                    {{-- فاز ۱۱: انتخاب نوع به‌صورت کارت‌های کلیک‌شدنی (به‌جای select) --}}
                    <div class="mb-5">
                        <label class="mb-1.5 block text-xs font-semibold" for="widget-key">نوع ابزارک <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                            @foreach ($this->templates as $templateKey => $templateWidget)
                                @php
                                    $tImg = $templateWidget['image_url'] ?? (isset($templateWidget['image']) ? theme_asset($templateWidget['image']) : null);
                                @endphp
                                <button type="button"
                                        wire:click="$set('key', {{ \Illuminate\Support\Js::from($templateKey)->toHtml() }})"
                                        class="group relative flex items-center gap-2.5 rounded-2xl border p-2.5 text-start transition-all duration-200 {{ $key === $templateKey
                                            ? 'border-brand-500 bg-brand-50/60 shadow-[0_2px_10px_-4px_rgb(5_150_105/.35)] dark:bg-brand-500/12'
                                            : 'border-line bg-surface hover:border-brand-400/70 hover:bg-brand-50/30 dark:border-night-line dark:bg-night-3/60 dark:hover:bg-brand-500/8' }}"
                                        :aria-pressed="{{ $key === $templateKey ? 'true' : 'false' }}">
                                    @if ($tImg)
                                        <img src="{{ $tImg }}" alt="{{ $templateWidget['title'] ?? $templateKey }}"
                                             class="size-11 shrink-0 rounded-xl border border-line object-cover dark:border-night-line"
                                             onerror="this.classList.add('hidden');">
                                    @else
                                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-line bg-surface-2 text-ink-3 dark:border-night-line dark:bg-night-3">
                                            <x-admin-panel::icon name="layout-template" class="size-5" />
                                        </span>
                                    @endif
                                    <span class="min-w-0">
                                        <span class="block truncate text-xs font-bold">{{ $templateWidget['title'] ?? $templateKey }}</span>
                                        @if (isset($templateWidget['module']))
                                            <span class="mt-0.5 block truncate text-[9px] font-semibold text-brand-700 dark:text-brand-400">{{ $templateWidget['module'] }}</span>
                                        @endif
                                    </span>
                                    <span class="absolute top-2 end-2 flex size-4.5 items-center justify-center rounded-full transition-all {{ $key === $templateKey ? 'bg-brand-600 text-white' : 'bg-surface-3 text-transparent dark:bg-night-3' }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="size-3"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        @error('key') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- پیش‌نمایش نوع انتخاب‌شده (تصویر/ماژول) --}}
                    @if ($this->template)
                        <div class="mb-5 flex items-center gap-3 rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                            @if ($this->template['image_url'] ?? null)
                                <img src="{{ $this->template['image_url'] }}" alt="{{ $this->template['title'] ?? $key }}"
                                     class="size-14 rounded-xl border border-line object-cover dark:border-night-line">
                            @elseif ($this->template['image'] ?? null)
                                <img src="{{ theme_asset($this->template['image']) }}" alt="{{ $this->template['title'] ?? $key }}"
                                     class="size-14 rounded-xl border border-line object-cover dark:border-night-line">
                            @else
                                <span class="flex size-14 items-center justify-center rounded-xl border border-line text-ink-3 dark:border-night-line dark:text-night-ink-2">
                                    <x-admin-panel::icon name="layout-template" class="size-6" />
                                </span>
                            @endif
                            <div>
                                <p class="text-[13px] font-bold">{{ $this->template['title'] ?? $key }}</p>
                                @if (isset($this->template['module']))
                                    <span class="ap-badge mt-1 bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">ماژول: {{ $this->template['module'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- رندر داینامیک options بر اساس template config (پورت template.blade.php) --}}
                    @if ($this->template && ! empty($this->template['options']))
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @include('admin-panel::livewire.admin.widgets.partials.template-options', [
                                'options' => $this->template['options'],
                                'widget'  => $this->widget,
                            ])
                        </div>
                    @elseif ($this->template)
                        <p class="rounded-xl border border-line bg-surface-2 p-3 text-xs text-ink-3 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">
                            این نوع ابزارک تنظیمات اضافه‌ای ندارد
                        </p>
                    @endif
                </div>
            </div>

            {{-- ستون اصلی --}}
            <div class="space-y-5">
                <div class="ap-card p-4 sm:p-5">
                    <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="clipboard-list" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        اطلاعات ابزارک
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold" for="widget-title">عنوان ابزارک <span class="text-red-500">*</span></label>
                            <input id="widget-title" type="text" wire:model="title" class="ap-input" placeholder="مثال: اسلایدر اصلی">
                            @error('title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <label class="flex cursor-pointer items-center justify-between gap-2.5 rounded-xl border border-line bg-surface-2 p-3 dark:border-night-line dark:bg-night-3">
                            <span>
                                <span class="block text-[13px] font-semibold">فعال بودن ابزارک</span>
                                <span class="block text-[11px] text-ink-3 dark:text-night-ink-2">ابزارک غیرفعال در سایت نمایش داده نمی‌شود</span>
                            </span>
                            <input type="checkbox" wire:model="is_active"
                                   class="size-4 cursor-pointer rounded border-line text-brand-600">
                        </label>

                        <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn w-full">
                            <x-admin-panel::icon name="check" class="size-4" />
                            {{ $this->isEditing ? 'ذخیره تغییرات' : 'ایجاد ابزارک' }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>
