{{-- نوار پیشرفت ثبت‌نام مرحله‌ای فروشنده (فاز ۱۱۲ — ۸ گام؛ گام رمز عبور اضافه شد) — استفاده: @include('sellers.auth.partials.steps', ['active' => N]) --}}
@php
    $steps = [
        1 => ['label' => 'موبایل', 'icon' => 'smartphone'],
        2 => ['label' => 'تأیید کد', 'icon' => 'shield-check'],
        3 => ['label' => 'رمز عبور', 'icon' => 'lock-keyhole'],
        4 => ['label' => 'وضعیت', 'icon' => 'user'],
        5 => ['label' => 'اطلاعات تکمیلی', 'icon' => 'id-card'],
        6 => ['label' => 'کسب‌وکار', 'icon' => 'store'],
        7 => ['label' => 'مدارک', 'icon' => 'credit-card'],
        8 => ['label' => 'پایان', 'icon' => 'circle-check'],
    ];
@endphp
<!-- فاز ۱۱۳ — دکمهٔ اختیاری «بازگشت به مرحلهٔ قبل» (پارامتر back = route مرحلهٔ قبل) -->
@if (! empty($back))
    <div class="flex">
        <a href="{{ $back }}" wire:navigate
           class="inline-flex min-h-10 items-center gap-1.5 rounded-xl px-3 text-[12px] font-bold text-ink-3 transition-colors hover:bg-surface-2 hover:text-ink dark:text-night-ink-2 dark:hover:bg-night-3 dark:hover:text-night-ink">
            <x-admin-panel::icon name="chevron-right" class="size-4" />
            بازگشت به مرحلهٔ قبل
        </a>
    </div>
@endif
<ol class="flex items-center justify-between gap-1 rounded-2xl border border-line bg-surface/70 p-3 dark:border-night-line dark:bg-night-2/70 sm:gap-2"
    aria-label="مراحل ثبت‌نام فروشنده">
    @foreach ($steps as $i => $step)
        <li class="flex min-w-0 flex-1 items-center {{ $i < 8 ? 'gap-1' : '' }}">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-lg text-[11px] font-extrabold transition-colors sm:size-8 sm:rounded-xl sm:text-[12px]
                        {{ $i < $active
                            ? 'bg-emerald-500 text-white'
                            : ($i === $active
                                ? 'bg-brand-600 text-white shadow-sm'
                                : 'bg-surface-2 text-ink-3 dark:bg-night-3 dark:text-night-ink-2') }}"
                  aria-current="{{ $i === $active ? 'step' : 'false' }}">
                @if ($i < $active)
                    <x-admin-panel::icon name="check" class="size-3.5 sm:size-4" />
                @else
                    <span class="fa-digits">{{ $i }}</span>
                @endif
            </span>
            @if ($i < 8)
                <span class="hidden h-0.5 flex-1 rounded-full bg-line dark:bg-night-line sm:block
                            {{ $i < $active ? 'bg-emerald-400' : '' }}" aria-hidden="true"></span>
            @endif
        </li>
    @endforeach
</ol>
<p class="text-center text-[11px] font-bold text-ink-3 dark:text-night-ink-2">
    گام <span class="fa-digits">{{ $active }}</span> از <span class="fa-digits">۸</span>: {{ $steps[$active]['label'] }}
</p>
