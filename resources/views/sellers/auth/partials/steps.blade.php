{{-- نوار پیشرفت ۵ گامی ثبت‌نام فروشنده (فاز ۱۰۸) — استفاده: @include('sellers.auth.partials.steps', ['active' => N]) --}}
@php
    $steps = [
        1 => ['label' => 'اطلاعات پایه', 'icon' => 'user-round'],
        2 => ['label' => 'تأیید موبایل', 'icon' => 'smartphone'],
        3 => ['label' => 'کسب‌وکار', 'icon' => 'briefcase'],
        4 => ['label' => 'مدارک', 'icon' => 'file-badge'],
        5 => ['label' => 'پرداخت طرح', 'icon' => 'rocket'],
    ];
@endphp
<ol class="flex items-center justify-between gap-1 rounded-2xl border border-line bg-surface/70 p-3 dark:border-night-line dark:bg-night-2/70 sm:gap-2"
    aria-label="مراحل ثبت‌نام فروشنده">
    @foreach ($steps as $i => $step)
        <li class="flex min-w-0 flex-1 items-center {{ $i < 5 ? 'gap-1.5' : '' }}">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl text-[12px] font-extrabold transition-colors
                        {{ $i < $active
                            ? 'bg-emerald-500 text-white'
                            : ($i === $active
                                ? 'bg-brand-600 text-white shadow-sm'
                                : 'bg-surface-2 text-ink-3 dark:bg-night-3 dark:text-night-ink-2') }}"
                  aria-current="{{ $i === $active ? 'step' : 'false' }}">
                @if ($i < $active)
                    <x-admin-panel::icon name="check" class="size-4" />
                @else
                    <span class="fa-digits">{{ $i }}</span>
                @endif
            </span>
            @if ($i < 5)
                <span class="hidden h-0.5 flex-1 rounded-full bg-line dark:bg-night-line sm:block
                            {{ $i < $active ? 'bg-emerald-400' : '' }}" aria-hidden="true"></span>
            @endif
        </li>
    @endforeach
</ol>
<p class="text-center text-[11px] font-bold text-ink-3 dark:text-night-ink-2">
    گام <span class="fa-digits">{{ $active }}</span> از <span class="fa-digits">۵</span>: {{ $steps[$active]['label'] }}
</p>
