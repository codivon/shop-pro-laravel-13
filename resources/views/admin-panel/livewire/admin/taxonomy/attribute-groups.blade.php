{{-- لیست گروه‌های ویژگی + ایجاد سریع + مودال ویرایش + ترتیب — پنل جدید (فاز ۵) --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-5xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
            کل گروه‌ها: <span class="fa-digits">{{ number_format($this->groups->count()) }}</span>
        </span>
        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">
            ترتیب نمایش در فرانت محصولات با دکمه‌های ▲▼ تنظیم می‌شود.
        </p>
    </div>

    {{-- کارت ایجاد گروه جدید (پورت create قدیمی به‌صورت کارت درون‌صفحه‌ای) --}}
    @can('attributes.groups.create')
        <div class="ap-card p-4 sm:p-5">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-bold">
                <x-admin-panel::icon name="plus" class="size-4.5 text-brand-600 dark:text-brand-400" />
                گروه ویژگی جدید
            </h2>
            <form wire:submit="createGroup" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="group-create-name">نام <span class="text-red-500">*</span></label>
                    <input id="group-create-name" type="text" wire:model="groupName" class="ap-input !py-2" placeholder="مثال: رنگ">
                    @error('groupName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold" for="group-create-type">نوع گروه <span class="text-red-500">*</span></label>
                    <select id="group-create-type" wire:model="groupType" class="ap-input !py-2">
                        <option value="checkbox">دکمه (چندانتخابی)</option>
                        <option value="color">رنگ</option>
                    </select>
                    @error('groupType') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end">
                    <button type="submit" wire:loading.attr="disabled" wire:target="createGroup" class="ap-btn w-full !py-2">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        ایجاد گروه
                    </button>
                </div>
            </form>
        </div>
    @endcan

    {{-- جدول --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        <th class="w-14 px-3 py-3 text-start font-bold">ردیف</th>
                        <th class="px-3 py-3 text-start font-bold">عنوان</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">نوع</th>
                        <th class="w-24 px-3 py-3 text-center font-bold">تعداد ویژگی</th>
                        <th class="w-44 px-3 py-3 text-center font-bold">ترتیب</th>
                        <th class="w-20 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->groups as $group)
                        <tr wire:key="attribute-group-{{ $group->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            <td class="px-3 py-3 fa-digits font-bold">{{ number_format($loop->iteration) }}</td>
                            <td class="max-w-48 px-3 py-3">
                                <p class="truncate font-semibold">{{ $group->name }}</p>
                            </td>
                            <td class="px-3 py-3">
                                @if ($group->type === 'color')
                                    <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">رنگ</span>
                                @else
                                    <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">دکمه</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="fa-digits inline-flex rounded-lg bg-surface-2 px-2.5 py-1 font-bold dark:bg-night-3">{{ number_format($group->get_attributes_count) }}</span>
                            </td>
                            <td class="px-3 py-3">
                                @can('attributes.groups.update')
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" wire:click="moveUp({{ $group->id }})"
                                                @class(['rounded-lg p-1.5 transition-colors hover:bg-surface-3 dark:hover:bg-night-3', 'pointer-events-none opacity-30' => $loop->first])
                                                title="یک پله بالا" aria-label="بالا بردن ترتیب">
                                            <x-admin-panel::icon name="chevron-up" class="size-4 text-ink-2 dark:text-night-ink-2" />
                                        </button>
                                        <button type="button" wire:click="moveDown({{ $group->id }})"
                                                @class(['rounded-lg p-1.5 transition-colors hover:bg-surface-3 dark:hover:bg-night-3', 'pointer-events-none opacity-30' => $loop->last])
                                                title="یک پله پایین" aria-label="پایین بردن ترتیب">
                                            <x-admin-panel::icon name="chevron-down" class="size-4 text-ink-2 dark:text-night-ink-2" />
                                        </button>
                                    </div>
                                @endcan
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    @can('attributes.index')
                                        <a href="{{ route('np.attribute-groups.attributes', ['attributeGroup' => $group]) }}" wire:navigate
                                           class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                           title="مشاهده ویژگی‌ها" aria-label="ویژگی‌ها">
                                            <x-admin-panel::icon name="list" class="size-4" />
                                        </a>
                                    @endcan
                                    @can('attributes.groups.update')
                                        <button type="button" wire:click="editGroup({{ $group->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                                title="ویرایش گروه" aria-label="ویرایش">
                                            <x-admin-panel::icon name="pencil" class="size-4" />
                                        </button>
                                    @endcan
                                    @can('attributes.groups.delete')
                                        <button type="button" wire:click="confirmDelete({{ $group->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف گروه" aria-label="حذف">
                                            <x-admin-panel::icon name="trash-2" class="size-4" />
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="flex flex-col items-center gap-2 py-14 text-ink-3 dark:text-night-ink-2">
                                    <x-admin-panel::icon name="layers" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">گروه ویژگی‌ای برای نمایش وجود ندارد</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- مودال ویرایش گروه (پورت edit قدیمی به‌صورت مودال درون‌صفحه‌ای) --}}
    @can('attributes.groups.update')
        <div x-data="{ open: @entangle('editingGroupId').live }"
             x-show="open" x-cloak
             class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="open = null" x-transition.opacity aria-hidden="true"></div>
            <div x-show="open" x-on:click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 class="relative w-full max-w-md rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
                 role="dialog" aria-modal="true">
                <h3 class="text-base font-bold">ویرایش گروه ویژگی</h3>
                <form wire:submit="updateGroup" class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="group-edit-name">نام <span class="text-red-500">*</span></label>
                        <input id="group-edit-name" type="text" wire:model="editName" class="ap-input" placeholder="مثال: رنگ">
                        @error('editName') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold" for="group-edit-type">نوع گروه <span class="text-red-500">*</span></label>
                        <select id="group-edit-type" wire:model="editType" class="ap-input">
                            <option value="checkbox">دکمه (چندانتخابی)</option>
                            <option value="color">رنگ</option>
                        </select>
                        @error('editType') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2.5 pt-1">
                        <button type="submit" wire:loading.attr="disabled" wire:target="updateGroup" class="ap-btn flex-1">
                            <x-admin-panel::icon name="check" class="size-4" />
                            ذخیره تغییرات
                        </button>
                        <button type="button" x-on:click="open = null" class="ap-btn-ghost flex-1">انصراف</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

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
            <h3 class="text-base font-bold">حذف گروه ویژگی</h3>
            <p class="mt-1.5 text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">
                با حذف گروه ویژگی، تمام ویژگی‌های داخل آن هم حذف می‌شوند و دیگر قادر به بازیابی آنها نخواهید بود.
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

    {{-- لودینگ --}}
    <div wire:loading wire:target="createGroup, updateGroup, moveUp, moveDown, delete"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
