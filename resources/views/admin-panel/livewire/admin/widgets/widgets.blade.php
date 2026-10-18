{{-- ابزارک‌های صفحه اصلی — پنل جدید (فاز ۶) — پورت ۱:۱ back/widgets/index (بدون 404 اگر registry خالی — انحراف مستند) --}}
<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="layout-template" class="size-4" />
                {{ $this->pageTitle() }}
            </span>
            <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                قالب: {{ get_current_theme()['name'] ?? '—' }}
            </span>
        </div>
        <a href="{{ $this->createRoute() }}" wire:navigate class="ap-btn">
            <x-admin-panel::icon name="plus" class="size-4" />
            ابزارک جدید
        </a>
    </div>

    {{-- هشدار خالی بودن registry (هیچ نوع ابزارکی ثبت نشده) --}}
    @if ($this->isRegistryEmpty())
        <div class="ap-card flex flex-col items-start gap-3 border border-amber-200 bg-amber-50/70 p-5 dark:border-amber-500/20 dark:bg-amber-500/8 sm:flex-row sm:items-center">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                <x-admin-panel::icon name="layout-template" class="size-5" />
            </span>
            <div>
                <p class="text-sm font-bold text-amber-800 dark:text-amber-300">هیچ نوع ابزارکی ثبت نشده است</p>
                <p class="mt-1 text-xs leading-6 text-amber-700/90 dark:text-amber-400/80">
                    انواع ابزارک‌ها از ماژول‌های نصب‌شده (یا config قالب) خوانده می‌شوند؛ وقتی ماژولی ابزارکی ثبت کند،
                    همین صفحه به‌صورت کامل فعال می‌شود. ابزارک‌های قبلاً ذخیره‌شده در پایین لیست می‌مانند.
                </p>
            </div>
        </div>
    @endif

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-16 px-3 py-3 text-start font-bold">ترتیب</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="px-3 py-3 text-start font-bold">نوع ابزارک</th>
                        <th class="px-3 py-3 text-center font-bold">وضعیت</th>
                        <th class="w-32 px-3 py-3 text-center font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->widgets as $widget)
                        <tr wire:key="widget-{{ $widget->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-1">
                                    <span class="fa-digits min-w-6 text-center font-bold text-ink-2 dark:text-night-ink-2">{{ number_format($widget->ordering ?? 0) }}</span>
                                    <div class="flex flex-col">
                                        <button type="button" wire:click="moveUp({{ $widget->id }})"
                                                class="rounded p-0.5 text-ink-3 transition-colors hover:text-brand-600 disabled:opacity-30"
                                                @if ($loop->first) disabled @endif
                                                title="انتقال به بالا" aria-label="بالا">
                                            <x-admin-panel::icon name="chevron-up" class="size-3.5" />
                                        </button>
                                        <button type="button" wire:click="moveDown({{ $widget->id }})"
                                                class="rounded p-0.5 text-ink-3 transition-colors hover:text-brand-600 disabled:opacity-30"
                                                @if ($loop->last) disabled @endif
                                                title="انتقال به پایین" aria-label="پایین">
                                            <x-admin-panel::icon name="chevron-down" class="size-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="max-w-56 px-3 py-3">
                                <p class="truncate font-semibold">{{ $widget->title }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                                    {{ $this->typeTitle($widget->key) }}
                                </span>
                                <code class="mt-1 block text-[10px] text-ink-3 dark:text-night-ink-2" dir="ltr">{{ $widget->key }}</code>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <label class="inline-flex cursor-pointer items-center gap-2">
                                    <input type="checkbox" wire:click="toggleActive({{ $widget->id }})"
                                           {{ $widget->is_active ? 'checked' : '' }}
                                           aria-label="تغییر وضعیت {{ $widget->title }}"
                                           class="size-4 cursor-pointer rounded border-line text-brand-600">
                                    <span class="text-xs {{ $widget->is_active ? 'font-semibold text-emerald-600 dark:text-emerald-400' : 'text-ink-3 dark:text-night-ink-2' }}">
                                        {{ $widget->is_active ? 'فعال' : 'غیرفعال' }}
                                    </span>
                                </label>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center justify-center gap-0.5">
                                    <a href="{{ $this->editRoute($widget) }}" wire:navigate
                                       class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                       title="ویرایش ابزارک" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </a>
                                    <button type="button" wire:click="confirmDelete({{ $widget->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف ابزارک" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="layout-template" class="size-10 opacity-40" />
                                    @if ($this->isRegistryEmpty())
                                        <p class="text-sm font-medium">هیچ نوع ابزارکی ثبت نشده است</p>
                                        <p class="text-xs">با نصب ماژول‌ها یا ثبت ابزارک‌ها در قالب، این صفحه فعال می‌شود</p>
                                    @else
                                        <p class="text-sm font-medium">هنوز ابزارکی برای این صفحه ساخته نشده است</p>
                                        <a href="{{ $this->createRoute() }}" wire:navigate class="text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">
                                            ساخت اولین ابزارک
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- مودال تایید حذف --}}
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
            <h3 class="text-base font-bold">حذف ابزارک</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف ابزارک، تنظیمات و گزینه‌های آن هم حذف می‌شوند و قابل بازیابی نیستند.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>
</div>
