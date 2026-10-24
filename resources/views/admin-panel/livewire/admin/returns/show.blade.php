{{-- جزئیات مرجوعی — پنل جدید (فاز ۴) پورت back/returns/show.blade.php --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
@php
    $statusInfo = \App\Models\ReturnRequest::statusLabels()[$returnRequest->status] ?? ['label' => $returnRequest->status];
    $item = $returnRequest->orderItem;
    $timeline = $this->timeline();
@endphp
<div class="mx-auto max-w-7xl space-y-5">

    {{-- هدر --}}
    <div class="ap-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('np.returns.index') }}" wire:navigate
                   class="rounded-xl p-2 text-ink-2 transition-colors hover:bg-surface-3 dark:text-night-ink-2 dark:hover:bg-night-3"
                   aria-label="بازگشت به لیست مرجوعی‌ها">
                    <x-admin-panel::icon name="arrow-right" class="size-5" />
                </a>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-extrabold">جزئیات مرجوعی <span class="fa-digits">#{{ number_format($returnRequest->id) }}</span></h2>
                        <span class="ap-badge {{ \App\Livewire\Admin\Returns\ReturnIndex::paymentBadgeClass($returnRequest->payment_type) }}">
                            <x-admin-panel::icon name="{{ \App\Livewire\Admin\Returns\ReturnIndex::paymentIcon($returnRequest->payment_type) }}" class="size-3.5" />
                            {{ $returnRequest->paymentTypeLabel() }}
                        </span>
                        <span class="ap-badge {{ \App\Livewire\Admin\Returns\ReturnIndex::statusBadgeClass($returnRequest->status) }}">
                            <x-admin-panel::icon name="{{ \App\Livewire\Admin\Returns\ReturnIndex::statusIcon($returnRequest->status) }}" class="size-3.5" />
                            {{ $statusInfo['label'] }}
                        </span>
                    </div>
                    <p class="fa-digits mt-1 text-xs text-ink-3 dark:text-night-ink-2">
                        {{ $this->jdate($returnRequest->created_at, 'Y/m/d - H:i') }}
                        — سفارش <span class="fa-digits">#{{ number_format($returnRequest->order_id) }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- ستون اصلی: اطلاعات --}}
        <div class="space-y-5 xl:col-span-2">

            {{-- محصول مرجوعی --}}
            <section class="ap-card p-5">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="package" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    محصول مرجوعی
                </h3>
                <div class="flex flex-wrap items-center gap-4">
                    @if ($item?->product?->image)
                        <img src="{{ asset($item->product->image) }}" alt="{{ $item->title }}"
                             class="size-20 rounded-xl border border-line object-cover dark:border-night-line">
                    @else
                        <span class="flex size-20 items-center justify-center rounded-xl border border-line bg-surface-2 text-ink-3 dark:border-night-line dark:bg-night-3">
                            <x-admin-panel::icon name="image" class="size-7 opacity-40" />
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold" title="{{ $item?->title }}">{{ $item?->title ?? '—' }}</p>
                        <p class="fa-digits mt-1.5 text-xs text-ink-3 dark:text-night-ink-2">
                            سفارش:
                            <a href="{{ route('np.orders.show', $returnRequest->order_id) }}" wire:navigate
                               class="font-bold text-brand-600 transition-colors hover:text-brand-700 dark:text-brand-400">
                                #{{ number_format($returnRequest->order_id) }}
                            </a>
                            · تعداد: <span class="fa-digits">{{ number_format($item?->quantity ?? 0) }}</span>
                            · مبلغ محصول: <span class="fa-digits">{{ number_format(($item?->price ?? 0) * ($item?->quantity ?? 0)) }}</span> تومان
                        </p>
                    </div>
                </div>
            </section>

            {{-- اطلاعات مالی مرجوعی --}}
            <section class="ap-card overflow-hidden border-emerald-200 dark:border-emerald-500/20">
                <header class="flex items-center gap-2 border-b border-line bg-emerald-50 px-5 py-3.5 dark:border-night-line dark:bg-emerald-500/5">
                    <x-admin-panel::icon name="wallet" class="size-4.5 text-emerald-600 dark:text-emerald-400" />
                    <h3 class="text-sm font-bold text-emerald-800 dark:text-emerald-400">اطلاعات مالی مرجوعی</h3>
                </header>
                <div class="grid grid-cols-1 gap-x-6 gap-y-4 p-5 sm:grid-cols-2">
                    <div>
                        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">مبلغ محصول (با تخفیف)</p>
                        <p class="fa-digits mt-1 text-sm font-extrabold">{{ number_format($returnRequest->total_item_amount) }} تومان</p>
                    </div>
                    <div>
                        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">نوع پرداخت سفارش</p>
                        <p class="mt-1 flex items-center gap-1.5 text-sm font-extrabold">
                            <x-admin-panel::icon name="{{ \App\Livewire\Admin\Returns\ReturnIndex::paymentIcon($returnRequest->payment_type) }}" class="size-4 text-ink-2" />
                            {{ $returnRequest->paymentTypeLabel() }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">بازگشت به کیف پول</p>
                        <p class="mt-1 text-sm font-extrabold text-emerald-600 dark:text-emerald-400">
                            @if ($returnRequest->wallet_refund_amount > 0)
                                <span class="fa-digits">{{ number_format($returnRequest->wallet_refund_amount) }}</span> تومان
                                @if ($returnRequest->paid_to_wallet)
                                    <span class="ap-badge mr-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">واریز شد</span>
                                @endif
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">بازگشت به اعتبار</p>
                        <p class="mt-1 text-sm font-extrabold text-blue-600 dark:text-blue-400">
                            @if ($returnRequest->credit_restore_amount > 0)
                                <span class="fa-digits">{{ number_format($returnRequest->credit_restore_amount) }}</span> تومان
                                @if ($returnRequest->credit_restored)
                                    <span class="ap-badge mr-1 bg-blue-50 text-blue-700 dark:bg-blue-500/12 dark:text-blue-400">برگشت داده شد</span>
                                @endif
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">هزینه ارسال</p>
                        <p class="mt-1 text-sm font-bold text-ink-3 dark:text-night-ink-2">قابل بازگشت نیست</p>
                    </div>
                    <div class="border-t border-dashed border-emerald-200 pt-3 dark:border-emerald-500/20 sm:col-span-2">
                        <p class="text-[11px] text-ink-3 dark:text-night-ink-2">مبلغ کل قابل برگشت</p>
                        <p class="fa-digits mt-1 text-xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ number_format($returnRequest->refund_amount) }} تومان</p>
                    </div>
                </div>

                @if ($returnRequest->payment_type !== 'cash')
                    <div class="mx-5 mb-5 rounded-xl border border-line bg-surface-2 p-4 text-[13px] leading-6 dark:border-night-line dark:bg-night-3">
                        <p class="flex items-center gap-1.5 font-bold">
                            <x-admin-panel::icon name="circle-help" class="size-4 text-ink-2" />
                            نکته درباره سفارش {{ $returnRequest->paymentTypeLabel() }}:
                        </p>
                        <ul class="mt-1.5 list-disc space-y-1 pe-5 text-ink-2 dark:text-night-ink-2">
                            @if ($returnRequest->payment_type === 'credit')
                                <li>مبلغ پرداخت‌شده کاربر (قسط اول + اقساط پرداخت‌شده) × سهم این آیتم به کیف پول کاربر واریز شد.</li>
                                <li>اعتبار استفاده‌شده برای این آیتم به حساب اعتباری کاربر برگردانده شد.</li>
                            @elseif ($returnRequest->payment_type === 'installment')
                                <li>مبلغ پرداخت‌شده کاربر (پیش‌پرداخت + اقساط پرداخت‌شده) × سهم این آیتم به کیف پول کاربر واریز شد.</li>
                            @endif
                            <li>هزینه ارسال قابل بازگشت نیست.</li>
                        </ul>
                    </div>
                @endif
            </section>

            {{-- توضیحات کاربر --}}
            @if ($returnRequest->description)
                <section class="ap-card p-5">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="message-square" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        توضیحات کاربر
                    </h3>
                    <p class="text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">{{ $returnRequest->description }}</p>
                </section>
            @endif

            {{-- تصاویر ارسالی --}}
            @if ($returnRequest->images->count())
                <section class="ap-card p-5" x-data="{ open: false, src: '' }">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="images" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        تصاویر ارسالی (<span class="fa-digits">{{ number_format($returnRequest->images->count()) }}</span>)
                    </h3>
                    <div class="flex flex-wrap gap-2.5">
                        @foreach ($returnRequest->images as $image)
                            <button type="button" wire:key="return-image-{{ $image->id }}"
                                    x-on:click="src = '{{ \Storage::url($image->path) }}'; open = true"
                                    class="overflow-hidden rounded-xl border border-line transition-transform hover:scale-105 dark:border-night-line"
                                    aria-label="بزرگ‌نمایی تصویر">
                                <img src="{{ \Storage::url($image->path) }}" alt="{{ $image->original_name ?? 'تصویر مرجوعی' }}"
                                     class="size-24 object-cover">
                            </button>
                        @endforeach
                    </div>

                    {{-- لایت‌باکس ساده --}}
                    <div x-show="open" x-cloak
                         class="fixed inset-0 z-[70] flex items-center justify-center p-4"
                         x-on:click="open = false" x-on:keydown.escape.window="open = false">
                        <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" aria-hidden="true"></div>
                        <img x-show="open" :src="src" alt="نمایش تصویر"
                             class="relative max-h-[85vh] max-w-full rounded-2xl border border-line object-contain shadow-pop dark:border-night-line">
                    </div>
                </section>
            @endif

            {{-- نتیجه بررسی محصول --}}
            @if ($returnRequest->inspection_result)
                <section class="ap-card p-5">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="search" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        نتیجه بررسی محصول
                    </h3>
                    <p class="text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">{{ $returnRequest->inspection_result }}</p>
                </section>
            @endif

            {{-- دلیل رد --}}
            @if ($returnRequest->rejection_reason)
                <section class="ap-card border-red-200 p-5 dark:border-red-500/25">
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-red-600 dark:text-red-400">
                        <x-admin-panel::icon name="circle-x" class="size-4.5" />
                        دلیل رد
                    </h3>
                    <p class="text-[13px] leading-6 text-ink-2 dark:text-night-ink-2">{{ $returnRequest->rejection_reason }}</p>
                </section>
            @endif
        </div>

        {{-- ستون کنار: اطلاعات و عملیات --}}
        <div class="space-y-5">

            {{-- اطلاعات کلی --}}
            <section class="ap-card p-5">
                <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                    <x-admin-panel::icon name="user" class="size-4.5 text-brand-600 dark:text-brand-400" />
                    اطلاعات
                </h3>
                <dl class="space-y-3 text-[13px]">
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-ink-3 dark:text-night-ink-2">کاربر</dt>
                        <dd class="font-semibold">
                            @if ($returnRequest->user)
                                <a href="{{ route('np.users.show', $returnRequest->user->id) }}" wire:navigate
                                   class="text-brand-600 transition-colors hover:text-brand-700 dark:text-brand-400">
                                    {{ trim($returnRequest->user->first_name . ' ' . $returnRequest->user->last_name) ?: 'کاربر #' . $returnRequest->user->id }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-ink-3 dark:text-night-ink-2">موبایل</dt>
                        <dd class="fa-digits font-semibold">{{ $returnRequest->user?->mobile ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-ink-3 dark:text-night-ink-2">ایمیل</dt>
                        <dd class="max-w-40 truncate font-semibold" title="{{ $returnRequest->user?->email }}">{{ $returnRequest->user?->email ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-ink-3 dark:text-night-ink-2">دلیل</dt>
                        <dd class="font-semibold">{{ $returnRequest->reason?->title ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-ink-3 dark:text-night-ink-2">مبلغ کل برگشتی</dt>
                        <dd class="fa-digits font-extrabold text-emerald-600 dark:text-emerald-400">{{ number_format($returnRequest->refund_amount) }} تومان</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-ink-3 dark:text-night-ink-2">تاریخ ثبت</dt>
                        <dd class="fa-digits font-semibold">{{ $this->jdate($returnRequest->created_at, 'Y/m/d H:i') }}</dd>
                    </div>
                </dl>
                @if ($returnRequest->admin_notes)
                    <div class="mt-4 rounded-xl border border-line bg-surface-2 p-3 text-[13px] leading-6 dark:border-night-line dark:bg-night-3">
                        <p class="mb-1 text-[11px] font-bold text-ink-3 dark:text-night-ink-2">یادداشت ادمین</p>
                        {{ $returnRequest->admin_notes }}
                    </div>
                @endif
            </section>

            {{-- تایم‌لاین وضعیت --}}
            @if (count($timeline))
                <section class="ap-card p-5">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="history" class="size-4.5 text-brand-600 dark:text-brand-400" />
                        روند درخواست
                    </h3>
                    <ol class="relative space-y-4 border-s border-line ps-5 dark:border-night-line">
                        @foreach ($timeline as $step)
                            <li wire:key="timeline-{{ $step['label'] }}" class="relative">
                                <span class="absolute -start-[27px] flex size-5 items-center justify-center rounded-full bg-brand-50 text-brand-600 ring-4 ring-surface dark:bg-brand-500/12 dark:text-brand-400 dark:ring-night-2">
                                    <x-admin-panel::icon name="{{ $step['icon'] }}" class="size-3" />
                                </span>
                                <p class="text-[13px] font-bold">{{ $step['label'] }}</p>
                                <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $step['date'] }}</p>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            {{-- پنل عملیات بر اساس وضعیت (پورت کامل گاردهای legacy) --}}
            @can('returns.approve')
                @if ($returnRequest->isPending() && $returnRequest->canBeApproved())
                    <section class="ap-card overflow-hidden border-amber-200 dark:border-amber-500/25">
                        <header class="border-b border-line bg-amber-50 px-5 py-3.5 dark:border-night-line dark:bg-amber-500/5">
                            <h3 class="text-sm font-bold text-amber-700 dark:text-amber-400">بررسی اولیه</h3>
                        </header>
                        <div class="space-y-5 p-5">
                            <form wire:submit.prevent="approve">
                                <label class="mb-1.5 block text-xs font-semibold" for="approve-notes">یادداشت (اختیاری)</label>
                                <textarea id="approve-notes" wire:model="adminNotes" rows="2" class="ap-input"
                                          placeholder="یادداشت درباره بررسی اولیه…"></textarea>
                                @error('adminNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                <button type="submit" wire:loading.attr="disabled"
                                        class="ap-btn mt-3 w-full bg-amber-500 text-white hover:bg-amber-600">
                                    <x-admin-panel::icon name="check" class="size-4" />
                                    تایید اولیه
                                </button>
                            </form>

                            <div class="border-t border-line dark:border-night-line"></div>

                            <form wire:submit.prevent="reject">
                                <label class="mb-1.5 block text-xs font-semibold" for="reject-reason-pending">دلیل رد</label>
                                <textarea id="reject-reason-pending" wire:model="rejectionReason" rows="2" class="ap-input"
                                          placeholder="چرا این درخواست رد می‌شود؟"></textarea>
                                @error('rejectionReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                <button type="submit" wire:loading.attr="disabled" wire:confirm="این درخواست مرجوعی رد شود؟"
                                        class="ap-btn mt-3 w-full bg-red-600 text-white hover:bg-red-700">
                                    <x-admin-panel::icon name="circle-x" class="size-4" />
                                    رد درخواست
                                </button>
                            </form>
                        </div>
                    </section>
                @endif
            @endcan

            @can('returns.approve')
                @if ($returnRequest->isApproved())
                    <section class="ap-card overflow-hidden border-violet-200 dark:border-violet-500/25">
                        <header class="border-b border-line bg-violet-50 px-5 py-3.5 dark:border-night-line dark:bg-violet-500/5">
                            <h3 class="text-sm font-bold text-violet-700 dark:text-violet-400">ارسال محصول توسط مشتری</h3>
                        </header>
                        <div class="p-5">
                            <form wire:submit.prevent="markCustomerShipped">
                                <label class="mb-1.5 block text-xs font-semibold" for="shipped-notes">یادداشت (اختیاری)</label>
                                <textarea id="shipped-notes" wire:model="adminNotes" rows="2" class="ap-input"
                                          placeholder="مثلاً کد رهگیری ارسال مشتری…"></textarea>
                                @error('adminNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                <button type="submit" wire:loading.attr="disabled"
                                        class="ap-btn mt-3 w-full bg-violet-600 text-white hover:bg-violet-700">
                                    <x-admin-panel::icon name="truck" class="size-4" />
                                    ثبت ارسال محصول توسط مشتری
                                </button>
                            </form>
                        </div>
                    </section>
                @endif
            @endcan

            @can('returns.received')
                @if ($returnRequest->canBeReceived())
                    <section class="ap-card overflow-hidden border-cyan-200 dark:border-cyan-500/25">
                        <header class="border-b border-line bg-cyan-50 px-5 py-3.5 dark:border-night-line dark:bg-cyan-500/5">
                            <h3 class="text-sm font-bold text-cyan-700 dark:text-cyan-400">دریافت محصول</h3>
                        </header>
                        <div class="p-5">
                            <form wire:submit.prevent="markReceived">
                                <label class="mb-1.5 block text-xs font-semibold" for="received-notes">یادداشت (اختیاری)</label>
                                <textarea id="received-notes" wire:model="adminNotes" rows="2" class="ap-input"
                                          placeholder="یادداشت هنگام دریافت کالا…"></textarea>
                                @error('adminNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                <button type="submit" wire:loading.attr="disabled"
                                        class="ap-btn mt-3 w-full bg-cyan-600 text-white hover:bg-cyan-700">
                                    <x-admin-panel::icon name="box" class="size-4" />
                                    محصول دریافت شد
                                </button>
                            </form>
                        </div>
                    </section>
                @endif
            @endcan

            @can('returns.complete')
                @if ($returnRequest->canComplete())
                    <section class="ap-card overflow-hidden border-emerald-200 dark:border-emerald-500/25">
                        <header class="border-b border-line bg-emerald-50 px-5 py-3.5 dark:border-night-line dark:bg-emerald-500/5">
                            <h3 class="text-sm font-bold text-emerald-700 dark:text-emerald-400">تایید نهایی</h3>
                        </header>
                        <div class="space-y-5 p-5">
                            {{-- تفکیک مبالغ --}}
                            <div class="rounded-xl border border-line bg-surface-2 p-3.5 text-[13px] dark:border-night-line dark:bg-night-3">
                                @if ($returnRequest->wallet_refund_amount > 0)
                                    <div class="flex items-center justify-between py-0.5">
                                        <span class="text-ink-3 dark:text-night-ink-2">بازگشت به کیف پول:</span>
                                        <strong class="fa-digits text-emerald-600 dark:text-emerald-400">{{ number_format($returnRequest->wallet_refund_amount) }} ت</strong>
                                    </div>
                                @endif
                                @if ($returnRequest->credit_restore_amount > 0)
                                    <div class="flex items-center justify-between py-0.5">
                                        <span class="text-ink-3 dark:text-night-ink-2">بازگشت به اعتبار:</span>
                                        <strong class="fa-digits text-blue-600 dark:text-blue-400">{{ number_format($returnRequest->credit_restore_amount) }} ت</strong>
                                    </div>
                                @endif
                                <div class="mt-1.5 flex items-center justify-between border-t border-dashed border-line pt-1.5 dark:border-night-line">
                                    <span class="text-ink-3 dark:text-night-ink-2">کل بازگشتی:</span>
                                    <strong class="fa-digits">{{ number_format($returnRequest->refund_amount) }} ت</strong>
                                </div>
                            </div>

                            <form wire:submit.prevent="complete">
                                <label class="mb-1.5 block text-xs font-semibold" for="complete-notes">یادداشت (اختیاری)</label>
                                <textarea id="complete-notes" wire:model="adminNotes" rows="2" class="ap-input"
                                          placeholder="یادداشت ادمین…"></textarea>
                                @error('adminNotes') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                <label class="mt-3 mb-1.5 block text-xs font-semibold" for="complete-inspection">نتیجه بررسی (اختیاری)</label>
                                <textarea id="complete-inspection" wire:model="inspectionResult" rows="2" class="ap-input"
                                          placeholder="مشکل محصول چه بود؟…"></textarea>
                                @error('inspectionResult') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                <button type="submit" wire:loading.attr="disabled" wire:confirm="وجه به کیف پول کاربر برگشت داده شود؟"
                                        class="ap-btn mt-3 w-full bg-emerald-600 text-white hover:bg-emerald-700">
                                    <x-admin-panel::icon name="check-check" class="size-4" />
                                    تایید نهایی و بازگشت وجه
                                </button>
                            </form>
                        </div>
                    </section>
                @endif
            @endcan

            @can('returns.received')
                @if ($returnRequest->canReship())
                    <section class="ap-card overflow-hidden border-indigo-200 dark:border-indigo-500/25">
                        <header class="border-b border-line bg-indigo-50 px-5 py-3.5 dark:border-night-line dark:bg-indigo-500/5">
                            <h3 class="text-sm font-bold text-indigo-700 dark:text-indigo-400">ارسال مجدد</h3>
                        </header>
                        <div class="p-5">
                            <form wire:submit.prevent="reship">
                                <label class="mb-1.5 block text-xs font-semibold" for="reship-inspection">یادداشت بررسی (اختیاری)</label>
                                <textarea id="reship-inspection" wire:model="inspectionResult" rows="2" class="ap-input"
                                          placeholder="نتیجه بررسی محصول…"></textarea>
                                @error('inspectionResult') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                <button type="submit" wire:loading.attr="disabled" wire:confirm="محصول دوباره ارسال شود؟"
                                        class="ap-btn mt-3 w-full bg-indigo-600 text-white hover:bg-indigo-700">
                                    <x-admin-panel::icon name="truck" class="size-4" />
                                    محصول مشکلی نداشت — ارسال مجدد
                                </button>
                            </form>
                        </div>
                    </section>
                @endif
            @endcan

            @can('returns.reject')
                @if ($returnRequest->isReceived() || $returnRequest->isReshipped())
                    <section class="ap-card p-5">
                        <form wire:submit.prevent="reject">
                            <label class="mb-1.5 block text-xs font-semibold" for="reject-reason">دلیل رد</label>
                            <textarea id="reject-reason" wire:model="rejectionReason" rows="2" class="ap-input"
                                      placeholder="چرا این درخواست رد می‌شود؟"></textarea>
                            @error('rejectionReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            <button type="submit" wire:loading.attr="disabled" wire:confirm="این درخواست مرجوعی رد شود؟"
                                    class="ap-btn-outline mt-3 w-full !border-red-200 !text-red-600 hover:!bg-red-50 dark:!border-red-500/25 dark:hover:!bg-red-500/10">
                                <x-admin-panel::icon name="circle-x" class="size-4" />
                                رد درخواست
                            </button>
                        </form>
                    </section>
                @endif
            @endcan

            {{-- کارت تکمیل‌شده --}}
            @if ($returnRequest->isCompleted())
                <section class="ap-card overflow-hidden border-emerald-200 dark:border-emerald-500/25">
                    <header class="flex items-center gap-2 border-b border-line bg-emerald-50 px-5 py-3.5 dark:border-night-line dark:bg-emerald-500/5">
                        <x-admin-panel::icon name="check-check" class="size-4.5 text-emerald-600 dark:text-emerald-400" />
                        <h3 class="text-sm font-bold text-emerald-700 dark:text-emerald-400">تکمیل شده</h3>
                    </header>
                    <div class="space-y-3 p-5 text-[13px]">
                        @if ($returnRequest->paid_to_wallet)
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-ink-3 dark:text-night-ink-2">واریز به کیف پول:</span>
                                <span class="font-extrabold text-emerald-600 dark:text-emerald-400">
                                    <span class="fa-digits">{{ number_format($returnRequest->wallet_refund_amount) }}</span> تومان
                                    <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/12 dark:text-emerald-400">✓</span>
                                </span>
                            </div>
                        @endif
                        @if ($returnRequest->credit_restored)
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-ink-3 dark:text-night-ink-2">برگشت به اعتبار:</span>
                                <span class="font-extrabold text-blue-600 dark:text-blue-400">
                                    <span class="fa-digits">{{ number_format($returnRequest->credit_restore_amount) }}</span> تومان
                                    <span class="ap-badge bg-blue-50 text-blue-700 dark:bg-blue-500/12 dark:text-blue-400">✓</span>
                                </span>
                            </div>
                        @endif
                        <div class="border-t border-line pt-3 dark:border-night-line">
                            <p class="text-[11px] text-ink-3 dark:text-night-ink-2">تاریخ تکمیل</p>
                            <p class="fa-digits mt-0.5 font-semibold">{{ $this->jdate($returnRequest->completed_at, 'Y/m/d H:i') }}</p>
                        </div>
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
