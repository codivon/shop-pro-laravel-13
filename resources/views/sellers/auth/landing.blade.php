{{-- لندینگ تک‌صفحه‌ای «فروشنده شوید» (فاز ۱۰۹) — الهام از digikala.landings/seller-introduction
     محتوا از پنل مدیریت شخصی‌سازی می‌شود (فروشندگان ← تنظیمات فروشندگان ← صفحهٔ فرود فروشندگان).
     «چرا ما» / «سوالات متداول» / «مزایا» از مدل‌های SellerHero / SellerQuestion / SellerCommission.
     ⚠️ هندلرهای Alpine همیشه به‌صورت متد تعریف می‌شوند (عبارت var-دار در evaluator جدید می‌شکند) --}}
@php
    $hero = $landingContent['hero'];
    $steps = $landingContent['steps'];
    $plans = array_values(array_filter($landingContent['plans'], fn ($p) => trim((string) ($p['title'] ?? '')) !== ''));
    $testimonials = array_values(array_filter($landingContent['testimonials'], fn ($t) => trim((string) ($t['name'] ?? '')) !== '' || trim((string) ($t['quote'] ?? '')) !== ''));
    $brands = array_values(array_filter($landingContent['brands'], fn ($b) => trim((string) ($b['name'] ?? '')) !== ''));
    $app = $landingContent['app'];
    $stats = $stats ?? [];
@endphp
<div class="space-y-14 pb-6">

    {{-- Toast اسکریپت مشترک — فاز ۱۱۱: میزبان/شنوندهٔ toast به لایوت مهمان منتقل شد --}}
    @include('sellers.auth.partials.sp-fetch')

    {{-- ===== ناوبری چسبان ===== --}}
    <nav class="ap-card sticky top-2 z-40 flex items-center justify-between gap-3 px-4 py-2.5" aria-label="ناوبری لندینگ فروشندگان">
        <a href="#sp-hero" class="flex shrink-0 items-center gap-2">
            <span class="flex size-8 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-emerald-600 text-white">
                <x-admin-panel::icon name="store" class="size-4" />
            </span>
            <span class="text-[13px] font-extrabold text-ink dark:text-night-ink">{{ option('info_site_title', 'او پی شاپ') }}</span>
        </a>
        <div class="hidden items-center gap-1 lg:flex">
            <a href="#sp-why" class="rounded-lg px-3 py-1.5 text-[12px] font-bold text-ink-2 transition-colors hover:bg-surface-2 hover:text-brand-600 dark:text-night-ink-2 dark:hover:bg-night-3">چرا ما؟</a>
            <a href="#sp-benefits" class="rounded-lg px-3 py-1.5 text-[12px] font-bold text-ink-2 transition-colors hover:bg-surface-2 hover:text-brand-600 dark:text-night-ink-2 dark:hover:bg-night-3">مزایا</a>
            <a href="#sp-steps" class="rounded-lg px-3 py-1.5 text-[12px] font-bold text-ink-2 transition-colors hover:bg-surface-2 hover:text-brand-600 dark:text-night-ink-2 dark:hover:bg-night-3">مسیر شروع فروش</a>
            <a href="#sp-testimonials" class="rounded-lg px-3 py-1.5 text-[12px] font-bold text-ink-2 transition-colors hover:bg-surface-2 hover:text-brand-600 dark:text-night-ink-2 dark:hover:bg-night-3">تجربهٔ فروشندگان</a>
            <a href="#sp-faq" class="rounded-lg px-3 py-1.5 text-[12px] font-bold text-ink-2 transition-colors hover:bg-surface-2 hover:text-brand-600 dark:text-night-ink-2 dark:hover:bg-night-3">سوالات متداول</a>
        </div>
        <a href="{{ route('seller.registration') }}" wire:navigate
           class="ap-btn-primary px-4 py-2! text-[12px]">
            <x-admin-panel::icon name="rocket" class="size-4" />
            ثبت‌نام
        </a>
    </nav>

    {{-- ===== هیرو: فرم ثبت‌نام سریع + تصویر ===== --}}
    <section id="sp-hero" class="ap-card overflow-hidden scroll-mt-24">
        <div class="grid items-stretch lg:grid-cols-2">
            <div class="order-2 p-6 sm:p-10 lg:order-1">
                <h1 class="text-2xl font-black leading-[1.7] text-ink dark:text-night-ink sm:text-3xl">{{ $hero['title'] }}</h1>
                <p class="mt-2 text-[13px] font-semibold leading-7 text-ink-3 dark:text-night-ink-2">{{ $hero['subtitle'] }}</p>

                {{-- فرم ثبت‌نام سریع — فاز ۱۱۰: فقط شمارهٔ موبایل؛ کد ۶ رقمی مستقیم ارسال می‌شود
                     فاز ۱۱۱: پاسخ status=exists مودال «فروشنده از قبل هست» (ورود/تغییر شماره) را باز می‌کند --}}
                <div class="mt-7 max-w-md" x-data="{
                        busy: false,
                        mobile: '',
                        async go() {
                            let m = (this.mobile || '').replace(/[^0-9]/g, '');
                            if (! /^09[0-9]{9}$/.test(m)) {
                                spToast('شمارهٔ موبایل را با فرمت 09xxxxxxxxx وارد کنید', 'error');
                                return;
                            }
                            this.busy = true;
                            let fd = new FormData();
                            fd.append('mobile', m);
                            try {
                                let data = await spPost('{{ route('seller.registration_phone_send') }}', fd);
                                if (data.status === 'exists') {
                                    document.dispatchEvent(new CustomEvent('sp-registration-exists', { detail: { login_url: data.login_url || '{{ route('seller.login') }}' } }));
                                    this.busy = false;
                                    return;
                                }
                                if (data.redirect) { window.location.href = data.redirect; }
                                else { spToast(data.message || 'ارسال کد ناموفق بود', 'error'); this.busy = false; }
                            } catch (e) { spToast('خطا در برقراری ارتباط', 'error'); this.busy = false; }
                        }
                    }">
                    <p class="mb-2 flex items-center gap-1.5 text-[12px] font-extrabold text-ink dark:text-night-ink">
                        <x-admin-panel::icon name="rocket" class="size-4 text-brand-600 dark:text-brand-400" />
                        ثبت‌نام سریع
                    </p>
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-bold text-ink-3 dark:text-night-ink-2" for="sp-hero-mobile">{{ $hero['mobile_label'] }}</label>
                            <div class="relative">
                                <x-admin-panel::icon name="smartphone" class="pointer-events-none absolute end-3 top-1/2 size-4 -translate-y-1/2 text-ink-3" />
                                <input id="sp-hero-mobile" type="tel" dir="ltr" inputmode="numeric" x-model="mobile" maxlength="11"
                                       class="ap-input py-3! ps-10! text-start fa-digits" placeholder="09xxxxxxxxx" autocomplete="tel">
                            </div>
                        </div>
                        <button type="button" x-on:click="go()" :disabled="busy"
                                x-bind:class="busy ? 'opacity-60' : ''"
                                class="ap-btn-primary w-full py-3!">
                            <span x-show="!busy" class="flex items-center justify-center gap-2">
                                {{ $hero['button'] }}
                                <x-admin-panel::icon name="chevron-left" class="size-4" />
                            </span>
                            <span x-show="busy" x-cloak class="flex items-center justify-center gap-2">
                                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                                در حال ارسال کد…
                            </span>
                        </button>
                        <p class="flex items-center justify-center gap-1.5 rounded-xl bg-brand-50/70 py-2.5 text-[11px] font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                            <x-admin-panel::icon name="clock" class="size-3.5" />
                            {{ $hero['response_time'] }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="relative order-1 min-h-56 lg:order-2 lg:min-h-full">
                <img src="{{ asset($hero['image'] ?: 'images/seller-landing/hero.png') }}"
                     alt="فروشنده در حال بسته‌بندی سفارش‌ها"
                     class="absolute inset-0 size-full object-cover">
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-l from-transparent via-transparent to-surface/90 dark:to-night-1/90"></div>
            </div>
        </div>
    </section>

    {{-- ===== پلن‌های همکاری ===== --}}
    @if (count($plans))
        <section class="scroll-mt-24">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                @foreach ($plans as $i => $plan)
                    <div class="ap-card relative overflow-hidden p-6 @if ($i === 1) border-brand-300 dark:border-brand-500/40 @endif">
                        @if ($i === 1)
                            <span class="absolute end-4 top-4 rounded-lg bg-brand-500/12 px-2 py-1 text-[10px] font-extrabold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">محبوب‌ترین</span>
                        @endif
                        <h3 class="text-[15px] font-black text-ink dark:text-night-ink">{{ $plan['title'] }}</h3>
                        <ul class="mt-4 space-y-2.5">
                            @foreach (array_filter((array) ($plan['features'] ?? [])) as $feature)
                                <li class="flex items-start gap-2 rounded-xl border border-line px-3 py-2 text-[12px] font-semibold leading-6 text-ink-2 dark:border-night-line dark:text-night-ink-2">
                                    <x-admin-panel::icon name="circle-check" class="mt-1 size-4 shrink-0 text-brand-600 dark:text-brand-400" />
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ===== نوار آمار (تیره) ===== --}}
    @if (count($stats))
        <section class="rounded-3xl bg-zinc-950 px-4 py-8 text-white sm:px-8 dark:bg-night-2 dark:border dark:border-night-line" aria-label="آمار فروشندگان">
            <div class="grid grid-cols-2 gap-6 lg:grid-cols-4">
                @foreach ($stats as $stat)
                    <div class="text-center">
                        <p class="fa-digits text-2xl font-black tracking-tight sm:text-3xl">{{ $stat['value'] }}</p>
                        <p class="mt-1 text-[12px] font-bold text-white/70">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ===== چرا ما (از مدل SellerHero) ===== --}}
    @if (count($sellersHeroes))
        <section id="sp-why" class="scroll-mt-24">
            <h2 class="text-center text-xl font-black text-ink dark:text-night-ink">چرا فروش در اینجا؟</h2>
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($sellersHeroes as $heroItem)
                    <div class="ap-card p-5">
                        <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400 [&>svg]:size-5 [&>i]:text-[20px]">{!! \App\Support\IconRegistry::render($heroItem->icon) !!}</span>
                        <p class="mt-3 text-[13px] font-extrabold text-ink dark:text-night-ink">{{ $heroItem->title }}</p>
                        <p class="mt-1.5 text-[12px] font-semibold leading-6 text-ink-3 dark:text-night-ink-2">{{ $heroItem->description }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ===== مزایای فروشندگان (از مدل SellerCommission) ===== --}}
    @if (count($sellersCommissions))
        <section id="sp-benefits" class="scroll-mt-24">
            <h2 class="text-center text-xl font-black text-ink dark:text-night-ink">مزایای فروشندگان</h2>
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($sellersCommissions as $commission)
                    <div class="ap-card flex items-start gap-3 p-5">
                        <span class="shrink-0 text-brand-600 dark:text-brand-400 [&>svg]:size-6 [&>i]:text-[24px]">{!! \App\Support\IconRegistry::render($commission->icon) !!}</span>
                        <div class="min-w-0">
                            <p class="text-[13px] font-extrabold text-ink dark:text-night-ink">{{ $commission->title }}</p>
                            <p class="mt-1 text-[12px] font-semibold leading-6 text-ink-3 dark:text-night-ink-2">{{ $commission->description }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ===== مسیر شروع فروش ===== --}}
    @if (count($steps))
        <section id="sp-steps" class="scroll-mt-24">
            <h2 class="text-center text-xl font-black text-ink dark:text-night-ink">مسیر شروع فروش</h2>
            <p class="mt-1.5 text-center text-[12px] font-semibold text-ink-3 dark:text-night-ink-2">در سه قدم فروش خود را آغاز کنید</p>
            <div class="mt-8 grid grid-cols-1 items-stretch gap-4 md:grid-cols-3">
                @foreach ($steps as $i => $step)
                    <div class="ap-card relative flex flex-col items-center p-6 text-center">
                        @if ($i < count($steps) - 1)
                            <x-admin-panel::icon name="arrow-left" class="pointer-events-none absolute -start-3.5 top-1/2 z-10 hidden size-6 -translate-y-1/2 rounded-full border border-line bg-surface p-1 text-brand-600 md:block dark:border-night-line dark:bg-night-2 dark:text-brand-400" />
                        @endif
                        <span class="fa-digits flex size-9 items-center justify-center rounded-xl bg-brand-500/12 text-[14px] font-black text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">{{ $i + 1 }}</span>
                        <p class="mt-3 text-[14px] font-extrabold text-ink dark:text-night-ink">{{ $step['title'] }}</p>
                        <p class="mt-1.5 text-[12px] font-semibold leading-6 text-ink-3 dark:text-night-ink-2">{{ $step['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ===== تجربهٔ فروشندگان (کاروسل) ===== --}}
    @if (count($testimonials))
        <section id="sp-testimonials" class="scroll-mt-24"
                 x-data="{ i: 0, n: {{ count($testimonials) }}, next() { this.i = (this.i + 1) % this.n; }, prev() { this.i = (this.i - 1 + this.n) % this.n; } }">
            <h2 class="text-center text-xl font-black text-ink dark:text-night-ink">تجربهٔ فروشندگان</h2>
            <div class="relative mt-6">
                <div class="overflow-hidden rounded-3xl">
                    <div class="flex transition-transform duration-500 ease-out" x-bind:style="'transform: translateX(' + (i * 100) + '%)'">
                        @foreach ($testimonials as $testimonial)
                            <figure class="w-full shrink-0 p-2" wire:key="sp-testimonial-{{ $loop->index }}">
                                <div class="ap-card p-6 sm:p-8">
                                    <div class="flex items-start gap-3">
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-brand-500/12 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                            <x-admin-panel::icon name="message-square" class="size-5" />
                                        </span>
                                        <blockquote class="flex-1 text-[13px] font-semibold leading-8 text-ink-2 dark:text-night-ink-2">
                                            {{ $testimonial['quote'] }}
                                        </blockquote>
                                    </div>
                                    <figcaption class="mt-4 text-start">
                                        <p class="text-[13px] font-extrabold text-ink dark:text-night-ink">{{ $testimonial['name'] }}</p>
                                        <p class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">{{ $testimonial['business'] }}</p>
                                    </figcaption>
                                </div>
                            </figure>
                        @endforeach
                    </div>
                </div>
                @if (count($testimonials) > 1)
                    <button type="button" x-on:click="prev()" aria-label="نظر قبلی"
                            class="absolute -start-2 top-1/2 z-10 flex size-9 -translate-y-1/2 items-center justify-center rounded-full border border-line bg-surface shadow-md transition-colors hover:text-brand-600 dark:border-night-line dark:bg-night-2">
                        <x-admin-panel::icon name="chevron-right" class="size-4" />
                    </button>
                    <button type="button" x-on:click="next()" aria-label="نظر بعدی"
                            class="absolute -end-2 top-1/2 z-10 flex size-9 -translate-y-1/2 items-center justify-center rounded-full border border-line bg-surface shadow-md transition-colors hover:text-brand-600 dark:border-night-line dark:bg-night-2">
                        <x-admin-panel::icon name="chevron-left" class="size-4" />
                    </button>
                    <div class="mt-4 flex items-center justify-center gap-1.5">
                        @foreach ($testimonials as $t)
                            <button type="button" x-on:click="i = {{ $loop->index }}" aria-label="نظر {{ $loop->iteration }}"
                                    class="h-2 rounded-full transition-all"
                                    x-bind:class="i === {{ $loop->index }} ? 'w-6 bg-brand-500' : 'w-2 bg-line dark:bg-night-line'"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ===== CTA پایین ===== --}}
    <section class="ap-card relative overflow-hidden p-6 sm:p-10">
        <div class="pointer-events-none absolute -end-16 -top-16 size-56 rounded-full bg-brand-500/10 blur-3xl"></div>
        <div class="relative flex flex-col items-center gap-6 text-center md:flex-row md:items-center md:justify-between md:text-start">
            <div>
                <h2 class="text-xl font-black text-ink dark:text-night-ink">{{ $app['title'] }}</h2>
                <div class="mt-3 flex flex-wrap justify-center gap-2 md:justify-start">
                    @foreach (array_filter((array) ($app['pills'] ?? [])) as $pill)
                        <span class="rounded-xl border border-line bg-surface-2/70 px-3 py-1.5 text-[11px] font-bold text-ink-2 dark:border-night-line dark:bg-night-3 dark:text-night-ink-2">{{ $pill }}</span>
                    @endforeach
                </div>
            </div>
            <a href="{{ $app['link'] ?: route('seller.registration') }}" @if (preg_match('~^https?://~', (string) $app['link'])) target="_blank" rel="noopener" @endif
               class="ap-btn-primary px-8 py-3!">
                <x-admin-panel::icon name="rocket" class="size-4.5" />
                {{ $app['button'] }}
            </a>
        </div>
    </section>

    {{-- ===== سوالات متداول (از مدل SellerQuestion) ===== --}}
    @if (count($sellersQuestions))
        <section id="sp-faq" class="scroll-mt-24">
            <h2 class="text-center text-xl font-black text-ink dark:text-night-ink">سوالات متداول</h2>
            <div class="mt-6 space-y-2.5">
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

    {{-- ===== فروشندگان برتر (پیش‌فرض خالی — از ادیتور ادمین) ===== --}}
    @if (count($brands))
        <section class="scroll-mt-24">
            <h2 class="text-center text-xl font-black text-ink dark:text-night-ink">فروشندگان برتر</h2>
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($brands as $brand)
                    <div class="ap-card p-4 text-center">
                        <span class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-brand-500/12 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                            <x-admin-panel::icon name="store" class="size-5" />
                        </span>
                        <p class="mt-2.5 text-[13px] font-extrabold text-ink dark:text-night-ink">{{ $brand['name'] }}</p>
                        @if (trim((string) ($brand['tag'] ?? '')) !== '')
                            <span class="mt-1.5 inline-block rounded-lg bg-surface-2 px-2 py-0.5 text-[10px] font-bold text-ink-3 dark:bg-night-3 dark:text-night-ink-2">{{ $brand['tag'] }}</span>
                        @endif
                        @if (trim((string) ($brand['metric'] ?? '')) !== '')
                            <p class="fa-digits mt-2 text-[12px] font-extrabold text-brand-600 dark:text-brand-400">{{ $brand['metric'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <p class="pb-2 text-center text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
        <a href="{{ route('seller.login') }}" wire:navigate class="transition-colors hover:text-brand-600 dark:hover:text-brand-400">ورود به پنل فروشندگان</a>
        <span class="mx-2 text-line dark:text-night-line">|</span>
        <a href="{{ route('front.index') }}" class="transition-colors hover:text-brand-600 dark:hover:text-brand-400">بازگشت به فروشگاه</a>
    </p>

    {{-- فاز ۱۱۱ — مودال «فروشنده با این شماره از قبل ثبت‌نام کرده است» (ورود / تغییر شماره)
         ⚠️ Livewire فقط یک المنت ریشه می‌پذیرد → مودال باید داخل ریشه باشد (فاز ۱۱۴) --}}
    @include('sellers.auth.partials.exists-modal')
</div>
