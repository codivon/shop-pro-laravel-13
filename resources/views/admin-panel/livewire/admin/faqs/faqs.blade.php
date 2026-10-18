{{-- سوالات متداول — پنل جدید (فاز ۶) — پورت ۱:۱ back/faqs/index.blade.php (جدول + مودال ایجاد/ویرایش + حذف تکی/گروهی) --}}
<div class="mx-auto max-w-6xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-base font-extrabold">سوالات متداول</h1>
            <p class="mt-1 text-xs text-ink-3 dark:text-night-ink-2">مدیریت پرسش‌هایی که در صفحات سایت به کاربر نمایش داده می‌شود.</p>
        </div>
        @can('faqs.create')
            <button type="button" wire:click="openCreateModal" class="ap-btn !py-2.5">
                <x-admin-panel::icon name="plus" class="size-4" />
                افزودن سوال جدید
            </button>
        @endcan
    </div>

    {{-- نوار عملیات گروهی (پورت multiple-actions-bar قدیمی) --}}
    <div class="ap-card flex flex-wrap items-center justify-between gap-3 border-s-4 border-s-red-500 p-3.5"
         @class(['hidden' => count($selected) === 0])>
        <p class="text-[13px] font-bold text-red-600">
            <span class="fa-digits">{{ number_format(count($selected)) }}</span> مورد انتخاب شده:
        </p>
        @can('faqs.delete')
            <button type="button" wire:click="confirmBulkDelete" class="ap-btn bg-red-600 !py-2 text-white hover:bg-red-700">
                <x-admin-panel::icon name="trash-2" class="size-4" />
                حذف همه
            </button>
        @endcan
    </div>

    {{-- جدول سوالات (پورت faqAccordion قدیمی به جدول طبق اسپک) --}}
    <div class="ap-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-3xl text-start text-[13px]">
                <thead>
                    <tr class="border-b border-line bg-surface-2 text-ink-2 dark:border-night-line dark:bg-night-3">
                        @can('faqs.delete')
                            <th class="w-12 px-3 py-3 text-start font-bold">
                                <input type="checkbox" wire:model.live="selectAll"
                                       class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                       aria-label="انتخاب همه">
                            </th>
                        @endcan
                        <th class="w-16 px-3 py-3 text-start font-bold">ترتیب</th>
                        <th class="px-3 py-3 text-start font-bold">سوال</th>
                        <th class="px-3 py-3 text-start font-bold">جواب</th>
                        <th class="w-24 px-3 py-3 text-start font-bold">وضعیت</th>
                        <th class="w-28 px-3 py-3 text-start font-bold">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->faqs as $faq)
                        <tr wire:key="faq-{{ $faq->id }}"
                            class="border-b border-line/60 transition-colors last:border-0 hover:bg-surface-2 dark:border-night-line/60 dark:hover:bg-night-3/60">
                            @can('faqs.delete')
                                <td class="px-3 py-3">
                                    <input type="checkbox" wire:model.live="selected" value="{{ $faq->id }}"
                                           class="size-4 rounded-md border-line text-brand-600 focus:ring-brand-500/30 dark:border-night-line"
                                           aria-label="انتخاب سوال {{ $faq->id }}">
                                </td>
                            @endcan
                            <td class="px-3 py-3">
                                <span class="fa-digits inline-flex min-w-7 justify-center rounded-lg bg-surface-2 px-2 py-1 font-bold dark:bg-night-3">{{ number_format($faq->order) }}</span>
                            </td>
                            <td class="max-w-56 px-3 py-3">
                                <p class="truncate font-bold">{{ $faq->question }}</p>
                            </td>
                            <td class="max-w-72 px-3 py-3">
                                <p class="truncate text-ink-2 dark:text-night-ink-2" title="{{ $faq->answer }}">{{ \Illuminate\Support\Str::limit(strip_tags((string) $faq->answer), 90) }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <span class="ap-badge {{ $faq->published
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400'
                                    : 'bg-slate-100 text-slate-600 dark:bg-night-3 dark:text-night-ink-2' }}">
                                    {{ $faq->published ? 'فعال' : 'غیرفعال' }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-0.5">
                                    @can('faqs.update')
                                        <button type="button" wire:click="moveUp({{ $faq->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 hover:text-ink-2 dark:hover:bg-night-3"
                                                title="انتقال به بالا" aria-label="انتقال به بالا">
                                            <x-admin-panel::icon name="chevron-up" class="size-4" />
                                        </button>
                                        <button type="button" wire:click="moveDown({{ $faq->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-surface-3 hover:text-ink-2 dark:hover:bg-night-3"
                                                title="انتقال به پایین" aria-label="انتقال به پایین">
                                            <x-admin-panel::icon name="chevron-down" class="size-4" />
                                        </button>
                                        <button type="button" wire:click="openEditModal({{ $faq->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10"
                                                title="ویرایش سوال" aria-label="ویرایش">
                                            <x-admin-panel::icon name="pencil" class="size-4" />
                                        </button>
                                    @endcan
                                    @can('faqs.delete')
                                        <button type="button" wire:click="confirmDelete({{ $faq->id }})"
                                                class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                                                title="حذف سوال" aria-label="حذف">
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
                                    <x-admin-panel::icon name="message-circle-question" class="size-10 opacity-40" />
                                    <p class="text-sm font-medium">چیزی برای نمایش وجود ندارد!</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- صفحه‌بندی --}}
        <div class="border-t border-line px-4 py-3 dark:border-night-line">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-ink-3 dark:text-night-ink-2">
                    نمایش <span class="fa-digits">{{ number_format($this->faqs->firstItem() ?? 0) }}</span> تا
                    <span class="fa-digits">{{ number_format($this->faqs->lastItem() ?? 0) }}</span> از
                    <span class="fa-digits">{{ number_format($this->faqs->total()) }}</span> سوال
                </p>
                <nav class="flex items-center gap-1" aria-label="صفحه‌بندی">
                    @if ($this->faqs->onFirstPage())
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-right" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه قبل">
                            <x-admin-panel::icon name="chevron-right" class="size-4" />
                        </button>
                    @endif

                    @foreach ($this->faqs->onEachSide(1)->toArray()['links'] as $link)
                        @if ($link['label'] === '...')
                            <span class="px-2 text-xs text-ink-3">…</span>
                        @elseif (! is_numeric(trim($link['label'])))
                            {{-- دکمه‌های قبلی/بعدی بالاتر مدیریت شده‌اند --}}
                        @elseif ($link['active'])
                            <span class="fa-digits flex size-8 items-center justify-center rounded-lg bg-brand-600 text-[13px] font-bold text-white">{{ $link['label'] }}</span>
                        @else
                            <button wire:click="gotoPage({{ trim($link['label']) }})"
                                    class="fa-digits flex size-8 items-center justify-center rounded-lg text-[13px] font-semibold text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3">
                                {{ $link['label'] }}
                            </button>
                        @endif
                    @endforeach

                    @if ($this->faqs->hasMorePages())
                        <button wire:click="nextPage" class="rounded-lg p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:hover:bg-night-3" aria-label="صفحه بعد">
                            <x-admin-panel::icon name="chevron-left" class="size-4" />
                        </button>
                    @else
                        <span class="rounded-lg p-2 text-ink-3 opacity-40"><x-admin-panel::icon name="chevron-left" class="size-4" /></span>
                    @endif
                </nav>
            </div>
        </div>
    </div>

    {{-- مودال ایجاد/ویرایش (پورت faq-form-modal قدیمی) --}}
    <div x-data="{ open: @entangle('showFormModal').live }"
         x-show="open" x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" x-show="open" x-on:click="closeFormModal" x-transition.opacity aria-hidden="true"></div>
        <div x-show="open" x-on:click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="relative w-full max-w-lg rounded-2xl border border-line bg-surface p-6 shadow-pop dark:border-night-line dark:bg-night-2"
             role="dialog" aria-modal="true">
            <h3 class="text-base font-bold">{{ $faqId ? 'ویرایش سوال متداول' : 'افزودن سوال جدید' }}</h3>

            <form wire:submit="save" class="mt-4 space-y-4">
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="faq-question">سوال <span class="text-red-500">*</span></label>
                    <input id="faq-question" type="text" wire:model="question" class="ap-input" placeholder="مثلاً: چطور سفارشم را پیگیری کنم؟">
                    @error('question') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-[13px] font-semibold" for="faq-answer">جواب <span class="text-red-500">*</span></label>
                    <textarea id="faq-answer" wire:model="answer" rows="5" class="ap-input" placeholder="پاسخ کامل سوال…"></textarea>
                    @error('answer') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="faq-order">ترتیب نمایش</label>
                        <input id="faq-order" type="number" wire:model="order" min="0" class="ap-input" placeholder="خالی = انتهای لیست">
                        @error('order') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[13px] font-semibold" for="faq-published">وضعیت</label>
                        <select id="faq-published" wire:model="published" class="ap-input">
                            <option value="1">فعال</option>
                            <option value="0">غیرفعال</option>
                        </select>
                        @error('published') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex gap-2.5 pt-1">
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="ap-btn flex-1 !py-2.5">
                        <x-admin-panel::icon name="check" class="size-4" />
                        ذخیره
                    </button>
                    <button type="button" x-on:click="closeFormModal" class="ap-btn-ghost flex-1">انصراف</button>
                </div>
            </form>
        </div>
    </div>

    {{-- مودال تایید حذف تکی --}}
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
                با حذف سوال دیگر قادر به بازیابی آن نخواهید بود.
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

    {{-- مودال تایید حذف گروهی (پورت multiple-delete-modal قدیمی) --}}
    <div x-data="{ open: @entangle('confirmingBulkDelete').live }"
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
                با حذف سوالات دیگر قادر به بازیابی آنها نخواهید بود.
            </p>
            <div class="mt-5 flex gap-2.5">
                <button type="button" wire:click="bulkDelete" wire:loading.attr="disabled"
                        class="ap-btn flex-1 bg-red-600 text-white hover:bg-red-700">
                    بله حذف شود
                </button>
                <button type="button" x-on:click="open=false" class="ap-btn-ghost flex-1">خیر</button>
            </div>
        </div>
    </div>

    {{-- لودینگ --}}
    <div wire:loading wire:target="save, moveUp, moveDown, delete, bulkDelete, previousPage, nextPage, gotoPage"
         class="pointer-events-none fixed bottom-6 start-6 z-[65] flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 shadow-pop dark:border-night-line dark:bg-night-3">
        <svg class="size-4 animate-spin text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round" />
        </svg>
        <span class="text-[13px] font-semibold">در حال بارگذاری…</span>
    </div>
</div>
