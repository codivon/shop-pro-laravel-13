{{-- درخت دسته‌بندی‌ها — پنل جدید (فاز ۵ + ۹-ج) — پورت ProductController::categories / PostController::categories --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
@php
    $isProduct  = $type === 'productcat';
    $permission = $isProduct ? 'products.category' : 'posts.category';
    $pageTitle  = $isProduct ? 'دسته‌بندی محصولات' : 'دسته‌بندی وبلاگ';
@endphp

<div class="mx-auto max-w-7xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                <x-admin-panel::icon name="folder-tree" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-extrabold">{{ $pageTitle }}</h2>
                <p class="text-[11px] text-ink-3 dark:text-night-ink-2">مدیریت ساختار درختی دسته‌بندی‌ها</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                کل: <span class="fa-digits">{{ number_format($stats['total']) }}</span>
            </span>
            <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">
                منتشر شده: <span class="fa-digits">{{ number_format($stats['published']) }}</span>
            </span>
            @can($permission)
                <button type="button" wire:click="startCreate" class="ap-btn !py-2">
                    <x-admin-panel::icon name="plus" class="size-4" />
                    افزودن دسته جدید
                </button>
            @endcan
        </div>
    </div>

    {{-- فرم ایجاد سریع (پورت فرم بالای صفحه قدیمی + «افزودن زیرشاخه») --}}
    @can($permission)
        <div x-show="$wire.creating" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="ap-card p-4 sm:p-5">
            <form wire:submit.prevent="createCategory" class="grid grid-cols-1 items-end gap-3.5 sm:grid-cols-12">

                <div class="sm:col-span-2">
                    <span class="mb-1.5 block text-xs font-semibold text-ink-3 dark:text-night-ink-2">ایجاد</span>
                    @if ($creatingParent)
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                                زیرشاخهٔ: {{ $creatingParentTitle ?? '—' }}
                            </span>
                            <button type="button" wire:click="startCreate"
                                    class="text-[11px] text-ink-3 underline transition-colors hover:text-brand-600 dark:text-night-ink-2">
                                سطح ریشه
                            </button>
                        </div>
                    @else
                        <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">دسته اصلی جدید</span>
                    @endif
                </div>

                <div class="sm:col-span-4">
                    <label class="mb-1.5 block text-xs font-semibold" for="cat-title">عنوان دسته</label>
                    <input id="cat-title" type="text" wire:model="title" class="ap-input !py-2" placeholder="عنوان دسته‌بندی…">
                    @error('title') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-4">
                    <label class="mb-1.5 block text-xs font-semibold" for="cat-slug">نامک (url)</label>
                    <div class="flex gap-2">
                        <input id="cat-slug" type="text" wire:model="slug" dir="ltr" class="ap-input !py-2 font-mono !text-xs" placeholder="category-slug">
                        <button type="button" wire:click="generateSlug" wire:loading.attr="disabled" wire:target="generateSlug"
                                class="ap-btn-outline whitespace-nowrap !px-3 !py-2 !text-xs">
                            ایجاد خودکار
                        </button>
                    </div>
                    @error('slug') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-2 sm:col-span-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="createCategory" class="ap-btn flex-1 !py-2">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        افزودن
                    </button>
                    <button type="button" wire:click="cancelCreate" class="ap-btn-ghost !py-2">انصراف</button>
                </div>
            </form>
        </div>
    @endcan

    {{-- درخت دسته‌بندی‌ها + وضعیت درگ‌انددراپ (Alpine خالص — فاز ۱۱ بازطراحی) --}}
    <div class="ap-card p-4 sm:p-5"
         x-data="{
            dragId: null,
            dragSubtree: [],
            overId: null,
            overMode: null,
            startDrag(event, id, subtree) {
                this.dragId = id
                this.dragSubtree = Array.isArray(subtree) && subtree.length ? subtree : [id]
                event.dataTransfer.effectAllowed = 'move'
                try { event.dataTransfer.setData('text/plain', String(id)) } catch (e) {}
            },
            overBand(event, id, mode) {
                if (this.dragId === null) return
                // فقط شاخه خودش را نمی‌توان داخل خودش برد
                this.overId = id
                this.overMode = this.dragSubtree.includes(id) ? 'forbidden' : mode
            },
            leaveBand(id) {
                if (this.overId === id) { this.overId = null; this.overMode = null }
            },
            dropClass(id) {
                if (this.dragId === null || this.overId !== id) return ''
                return {
                    before: 'ap-drop-before',
                    after: 'ap-drop-after',
                    inside: 'ap-drop-inside',
                    forbidden: 'ap-drop-forbidden',
                }[this.overMode] ?? ''
            },
            dropLabel(id) {
                if (this.dragId === null || this.overId !== id) return ''
                return {
                    before: 'هم‌سطح — قبل از این دسته',
                    after: 'هم‌سطح — بعد از این دسته',
                    inside: 'زیرشاخهٔ این دسته',
                    forbidden: 'انتقال به زیرشاخهٔ خودش مجاز نیست',
                }[this.overMode] ?? ''
            },
            resetDrag() {
                this.dragId = null
                this.dragSubtree = []
                this.overId = null
                this.overMode = null
            },
            drop(event, id) {
                const dragId = this.dragId
                const mode = this.overMode
                this.resetDrag()
                if (! dragId || dragId === id) return
                if (! mode || mode === 'forbidden') {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: 'انتقال دسته به داخل زیرشاخهٔ خودش مجاز نیست.', type: 'error' },
                    }))
                    return
                }
                $wire.moveNode(dragId, id, mode)
            },
            dropRoot(event) {
                const dragId = this.dragId
                this.resetDrag()
                if (! dragId) return
                $wire.moveNodeToRoot(dragId)
            },
         }"
         x-on:dragend.window="resetDrag()"
         {{-- dragover روی کل کارت: جلوگیری از رد شدن drop روی فاصله‌ها (به‌خصوص Firefox) --}}
         x-on:dragover.prevent=""
    >
        @if (count($nodes))
            <ul class="space-y-2">
                @foreach ($nodes as $node)
                    @include('admin-panel::livewire.admin.taxonomy.partials.category-node', ['node' => $node, 'level' => 1])
                @endforeach
            </ul>

            {{-- ناحیه رهاسازی ریشه — خروج از زیرمجموعه با درگ (فاز ۱۱) --}}
            <div x-show="dragId !== null" x-cloak x-transition.opacity.duration.150ms
                 x-on:dragover.prevent.stop="overId = null; overMode = 'root'"
                 x-on:dragleave.prevent="overMode = null"
                 x-on:drop.prevent.stop="dropRoot($event)"
                 class="mt-3 flex items-center justify-center gap-2 rounded-xl border-2 border-dashed p-4 text-[13px] font-bold transition-colors"
                 :class="overMode === 'root'
                     ? 'border-brand-500 bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300'
                     : 'border-line text-ink-3 dark:border-night-line dark:text-night-ink-2'"
                 role="region" aria-label="ناحیه انتقال به سطح ریشه">
                <x-admin-panel::icon name="corner-up-right" class="size-4.5 -scale-x-100" />
                <span>برای تبدیل به دسته اصلی (خروج از زیرمجموعه) اینجا رها کنید</span>
            </div>

            <p class="mt-4 flex items-start gap-1.5 text-xs leading-6 text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="info" class="mt-1 size-4 shrink-0" />
                <span>هندل ⋮⋮ را بکشید: <b class="text-ink-2 dark:text-night-ink">بالای ردیف</b> = هم‌سطح قبل، <b class="text-ink-2 dark:text-night-ink">وسط</b> = زیرشاخه، <b class="text-ink-2 dark:text-night-ink">پایین</b> = هم‌سطح بعد، یا رها کنید روی ناحیه «سطح ریشه». دکمه‌های فلش و سلکت «انتقال به سطح» هم برای مرتب‌سازی در دسترس‌اند.</span>
            </p>
        @else
            <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="folder-tree" class="size-10 opacity-40" />
                <p class="text-sm font-medium">دسته‌بندی‌ای ثبت نشده است</p>
                <p class="text-xs">با دکمه «افزودن دسته جدید» اولین دسته را بسازید.</p>
            </div>
        @endif
    </div>

    {{-- مودال تایید حذف (متن مثل پنل قدیمی) --}}
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
            <h3 class="text-base font-bold">حذف دسته‌بندی</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف این دسته بندی تمامی زیر دسته های آن حذف خواهند شد، آیا برای حذف مطمئن هستید؟
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="createCategory, generateSlug, moveUp, moveDown, moveNode, delete, parentIds, confirmDelete, startCreate"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
