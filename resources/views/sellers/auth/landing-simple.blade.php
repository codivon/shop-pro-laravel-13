{{-- نسخهٔ سادهٔ لندینگ فروشندگان (فاز ۱۰۸) — سرو می‌شود وقتی سوییچ seller_landing_page خاموش است (فاز ۱۰۹)
     داده‌ها: sellers_heroes / sellers_commissions / sellers_questions (همان منابع قدیمی) --}}
<div class="space-y-6" x-data>

    {{-- Toast اسکریپت مشترک --}}
    @include('sellers.auth.partials.sp-fetch')
    <div x-data
         x-on:sp-auth-toast.document="spToastShow($event.detail.message, $event.detail.type)"
         class="hidden"></div>
    <script>
        function spToastShow(message, type) {
            var host = document.getElementById('sp-auth-toasts');
            if (! host) { return; }
            var el = document.createElement('div');
            el.className = 'pointer-events-auto flex items-start gap-2.5 rounded-2xl border p-3.5 shadow-lg backdrop-blur-md text-[13px] font-bold w-full max-w-md '
                + (type === 'success'
                    ? 'border-emerald-200 bg-emerald-50/95 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-950/90 dark:text-emerald-200'
                    : 'border-red-200 bg-red-50/95 text-red-800 dark:border-red-500/30 dark:bg-red-950/90 dark:text-red-200');
            el.textContent = message;
            host.appendChild(el);
            setTimeout(function () { el.remove(); }, 6000);
        }
    </script>
    <div id="sp-auth-toasts" class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex flex-col items-center gap-2 px-4"></div>

    {{-- هیرو --}}
    <section class="ap-card overflow-hidden p-6 text-center sm:p-10">
        <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-emerald-600 text-white shadow-lg shadow-brand-500/25">
            <x-admin-panel::icon name="store" class="size-7" />
        </span>
        <h1 class="mt-4 text-2xl font-black leading-relaxed text-ink dark:text-night-ink">فروشنده شوید</h1>
        <p class="mx-auto mt-2 max-w-md text-[13px] font-semibold leading-7 text-ink-3 dark:text-night-ink-2">
            فروشگاه اختصاصی خود را بسازید؛ محصولات‌تان را مدیریت کنید، سفارش‌ها را ببینید
            و تسویهٔ شفاف داشته باشید.
        </p>

        @if (count($sellersHeroes))
            <div class="mt-6 grid grid-cols-1 gap-3 text-start sm:grid-cols-3">
                @foreach ($sellersHeroes as $hero)
                    <div class="rounded-2xl border border-line p-4 dark:border-night-line">
                        <div class="flex size-10 items-center justify-center overflow-hidden rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400 [&>svg]:size-5">{!! $hero->icon !!}</div>
                        <p class="mt-2.5 text-[13px] font-extrabold leading-6 text-ink dark:text-night-ink">{{ $hero->title }}</p>
                        <p class="mt-1 text-[11px] font-semibold leading-5 text-ink-3 dark:text-night-ink-2">{{ $hero->description }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ route('seller.registration') }}" wire:navigate class="ap-btn-primary w-full py-3! sm:w-auto sm:px-8!">
                <x-admin-panel::icon name="rocket" class="size-4.5" />
                ثبت‌نام فروشنده
            </a>
            <a href="{{ route('seller.login') }}" wire:navigate class="ap-btn-outline w-full py-3! sm:w-auto sm:px-8!">
                <x-admin-panel::icon name="log-in" class="size-4.5" />
                ورود به پنل
            </a>
        </div>
    </section>

    {{-- کمیسیون‌ها --}}
    @if (count($sellersCommissions))
        <section class="ap-card p-6">
            <h2 class="text-sm font-extrabold text-ink dark:text-night-ink">مزایای همکاری</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($sellersCommissions as $commission)
                    <div class="flex items-start gap-3 rounded-2xl border border-line p-4 dark:border-night-line">
                        <div class="shrink-0 [&>svg]:size-6">{!! $commission->icon !!}</div>
                        <div class="min-w-0">
                            <p class="text-[13px] font-extrabold text-ink dark:text-night-ink">{{ $commission->title }}</p>
                            <p class="mt-1 text-[11px] font-semibold leading-5 text-ink-3 dark:text-night-ink-2">{{ $commission->description }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- سوالات متداول --}}
    @if (count($sellersQuestions))
        <section class="ap-card p-6">
            <h2 class="text-sm font-extrabold text-ink dark:text-night-ink">سوالات متداول</h2>
            <div class="mt-4 space-y-2.5">
                @foreach ($sellersQuestions as $question)
                    <details class="group rounded-2xl border border-line open:border-brand-200 dark:border-night-line dark:open:border-brand-500/25">
                        <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 rounded-2xl px-4 py-2.5 text-[13px] font-bold text-ink dark:text-night-ink">
                            {{ $question->question }}
                            <x-admin-panel::icon name="chevron-down" class="size-4 shrink-0 text-ink-3 transition-transform group-open:rotate-180 dark:text-night-ink-2" />
                        </summary>
                        <p class="border-t border-line px-4 py-3 text-[12px] font-semibold leading-6 text-ink-2 dark:border-night-line dark:text-night-ink-2">
                            {{ $question->answer }}
                        </p>
                    </details>
                @endforeach
            </div>
        </section>
    @endif

    <p class="pb-4 text-center text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
        <a href="{{ route('front.index') }}" class="transition-colors hover:text-brand-600 dark:hover:text-brand-400">بازگشت به فروشگاه</a>
    </p>
</div>
