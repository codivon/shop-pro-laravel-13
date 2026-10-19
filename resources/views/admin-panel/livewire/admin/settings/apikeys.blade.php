{{-- کلیدهای وب سرویس — پورت back/apikeys (فاز ۷-ب۲) --}}
<div class="mx-auto max-w-7xl space-y-5" x-data="{ copied: null }">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="key-round" class="size-3.5" />
                کل کلیدها: <span class="fa-digits">{{ number_format(count($this->rows)) }}</span>
            </span>
        </div>
        <button type="button" wire:click="openCreateModal" class="ap-btn !py-2">
            <x-admin-panel::icon name="plus" class="size-4" />
            ایجاد کلید جدید
        </button>
    </div>

    {{-- جدول (پورت back/apikeys/index — بدون صفحه‌بندی مثل قدیمی) --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-12 px-3 py-3 text-start font-bold">ردیف</th>
                        <th class="px-3 py-3 text-start font-bold">توضیحات</th>
                        <th class="px-3 py-3 text-start font-bold">کلید</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">تاریخ</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->rows as $apikey)
                        <tr wire:key="apikey-{{ $apikey->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits text-ink-3 dark:text-night-ink-2">{{ $loop->iteration }}</td>
                            <td class="max-w-52 px-3 py-3">
                                <p class="truncate font-semibold">{{ $apikey->description ?: '-' }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-1.5">
                                    <input type="text" value="{{ $apikey->key }}" readonly dir="ltr"
                                           class="w-56 cursor-pointer rounded-lg border border-line bg-surface-2 px-2 py-1.5 font-mono text-xs text-ink-2 dark:border-night-line dark:bg-night-3"
                                           x-on:click="$event.target.select()"
                                           aria-label="کلید وب سرویس {{ $loop->iteration }}">
                                    <button type="button"
                                            x-on:click="navigator.clipboard.writeText('{{ $apikey->key }}'); copied = {{ $apikey->id }}; setTimeout(() => copied = null, 1500)"
                                            class="shrink-0 rounded-lg p-2 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400"
                                            title="کپی کلید" aria-label="کپی کلید">
                                        <span x-show="copied === {{ $apikey->id }}" x-cloak class="block text-emerald-500">
                                            <x-admin-panel::icon name="check" class="size-4" />
                                        </span>
                                        <span x-show="copied !== {{ $apikey->id }}">
                                            <x-admin-panel::icon name="copy" class="size-4" />
                                        </span>
                                    </button>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                @if ($apikey->is_active)
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">فعال</span>
                                @else
                                    <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400">غیر فعال</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 fa-digits text-ink-3 dark:text-night-ink-2">{{ self::jdate($apikey->created_at) }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    <button type="button" wire:click="editApikey({{ $apikey->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                            title="ویرایش کلید" aria-label="ویرایش">
                                        <x-admin-panel::icon name="pencil" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $apikey->id }})"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                            title="حذف کلید" aria-label="حذف">
                                        <x-admin-panel::icon name="trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="key-round" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">چیزی برای نمایش وجود ندارد!</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- مودال ایجاد کلید (پورت back/apikeys/create) --}}
    <div x-data="{ open: @entangle('showCreateModal').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">ایجاد کلید وب سرویس جدید</h3>

            <form wire:submit="createApikey" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">کلید <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-1.5">
                        <input type="text" wire:model="createKey" dir="ltr" class="ap-input !py-2 font-mono text-start text-xs">
                        <button type="button" wire:click="regenerateCreateKey"
                                class="shrink-0 rounded-lg p-2 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                title="تولید کلید جدید" aria-label="تولید کلید جدید">
                            <x-admin-panel::icon name="wand-2" class="size-4" />
                        </button>
                    </div>
                    @error('createKey') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">توضیحات</label>
                    <textarea wire:model="createDescription" rows="3" class="ap-input !py-2"></textarea>
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                    <input type="checkbox" wire:model="createIsActive" class="size-4 accent-brand-600">
                    فعال
                </label>
                <div class="flex gap-2.5">
                    <button type="submit" wire:loading.attr="disabled" wire:target="createApikey" class="ap-btn flex-1">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        ایجاد کلید
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال ویرایش کلید (پورت back/apikeys/edit) --}}
    <div x-data="{ open: @entangle('editingId').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open=false" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">ویرایش کلید وب سرویس</h3>

            <form wire:submit="updateApikey" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">کلید <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="editKey" dir="ltr" class="ap-input !py-2 font-mono text-start text-xs">
                    @error('editKey') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold">توضیحات</label>
                    <textarea wire:model="editDescription" rows="3" class="ap-input !py-2"></textarea>
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-[13px] font-semibold">
                    <input type="checkbox" wire:model="editIsActive" class="size-4 accent-brand-600">
                    فعال
                </label>
                <div class="flex gap-2.5">
                    <button type="submit" wire:loading.attr="disabled" wire:target="updateApikey" class="ap-btn flex-1">
                        <x-admin-panel::icon name="check" class="size-4" />
                        ویرایش کلید
                    </button>
                    <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال تایید حذف (متن پورت back/apikeys/index) --}}
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
            <h3 class="text-base font-bold">آیا مطمئن هستید؟</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف کلید وب سرویس دیگر قادر به بازیابی آن نخواهید بود و کاربرانی که از این کلید برای اتصال به وب سرویس استفاده میکنند دیگر قادر به اتصال به وب سرویس نخواهند بود
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
    <div wire:loading wire:target="openCreateModal, createApikey, updateApikey, delete"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
