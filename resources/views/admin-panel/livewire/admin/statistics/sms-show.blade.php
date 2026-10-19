{{-- نمایش تکی پیامک — پورت sms/sms-show قدیمی (فاز ۷-ج۲) --}}
@php
    $raw     = (string) $sms->response;
    $decoded = json_decode($raw);
    $isJson  = $decoded !== null;
    $pretty  = $isJson
        ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : $raw;

    // هایلایت JSON — اول امن‌سازی، بعد رنگ‌آمیزی کلید/رشته/عدد (پورت دقیق قدیمی)
    $highlighted = htmlspecialchars($pretty, ENT_NOQUOTES);
    if ($isJson) {
        $highlighted = preg_replace('/"([^"]+)"(\s*:)/', '<span class="text-sky-600 dark:text-sky-400">"$1"</span>$2', $highlighted);
        $highlighted = preg_replace('/(:\s*)"([^"]*)"/', '$1<span class="text-brand-600 dark:text-brand-400">"$2"</span>', $highlighted);
        $highlighted = preg_replace('/(:\s*)(-?\d+(?:\.\d+)?)/', '$1<span class="text-amber-600 dark:text-amber-400">$2</span>', $highlighted);
        $highlighted = preg_replace('/: (true|false|null)\b/', ': <span class="text-indigo-600 dark:text-indigo-400">$1</span>', $highlighted);
    }
@endphp

<div class="mx-auto max-w-4xl space-y-5">

    {{-- سربرگ: شماره + بج‌ها --}}
    <section class="ap-card flex flex-wrap items-center gap-4 p-5">
        <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
            <x-admin-panel::icon name="smartphone" class="size-7" />
        </div>
        <div class="min-w-0">
            <h1 class="font-mono text-lg font-extrabold" dir="ltr">{{ $sms->mobile }}</h1>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <span class="ap-badge bg-brand-50 text-brand-700 dark:bg-brand-500/12 dark:text-brand-400">
                    {{ $sms->type() }}
                </span>
                @if ($sms->provider)
                    <span class="ap-badge bg-surface-2 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="server" class="size-3.5" />
                        {{ $sms->provider }}
                    </span>
                @endif
                @if (Route::has('np.sms-log'))
                    <a href="{{ route('np.sms-log') }}" wire:navigate
                       class="flex items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-bold text-brand-700 transition-colors hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-500/10">
                        <x-admin-panel::icon name="arrow-right" class="size-3.5" />
                        بازگشت به لاگ
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- اطلاعات --}}
    <section class="ap-card grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
        @if ($sms->user)
            <div>
                <p class="flex items-center gap-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
                    <x-admin-panel::icon name="user" class="size-3.5" />
                    کاربر
                </p>
                <a href="{{ route('np.users.show', ['user' => $sms->user]) }}" wire:navigate
                   class="mt-1 block truncate text-[13px] font-bold text-brand-700 hover:underline dark:text-brand-400">
                    {{ $sms->user->fullname }}
                </a>
            </div>
        @endif
        <div>
            <p class="flex items-center gap-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="clock" class="size-3.5" />
                تاریخ ارسال
            </p>
            <p class="fa-digits mt-1 text-[13px] font-bold">{{ jdate($sms->created_at)->format('Y/m/d - H:i') }}</p>
            <p class="fa-digits mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">({{ jdate($sms->created_at)->ago() }})</p>
        </div>
        <div>
            <p class="flex items-center gap-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="globe" class="size-3.5" />
                IP
            </p>
            <p class="mt-1 font-mono text-[13px] font-bold" dir="ltr">{{ $sms->ip }}</p>
        </div>
        <div>
            <p class="flex items-center gap-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="hash" class="size-3.5" />
                شناسه رکورد
            </p>
            <p class="fa-digits mt-1 font-mono text-[13px] font-bold" dir="ltr">#{{ $this->fa($sms->id) }}</p>
        </div>
    </section>

    {{-- پاسخ پنل پیامکی --}}
    <section class="ap-card overflow-hidden"
             x-data="{ copied: false, copy() { const pre = this.$refs.pre; const text = pre.textContent; if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(text).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1800) }) } else { const ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta); this.copied = true; setTimeout(() => this.copied = false, 1800) } } }">
        <header class="flex items-center justify-between border-b border-line px-5 py-4 dark:border-night-line">
            <div class="flex items-center gap-2.5">
                <x-admin-panel::icon name="code" class="size-4.5 text-brand-600 dark:text-brand-400" />
                <h3 class="text-sm font-bold">پاسخ پنل پیامکی</h3>
            </div>
            <button type="button" x-on:click="copy"
                    class="flex items-center gap-1.5 rounded-lg border border-line px-2.5 py-1.5 text-[11px] font-bold text-ink-2 transition-colors hover:bg-surface-2 dark:border-night-line dark:text-night-ink-2 dark:hover:bg-night-3">
                <span x-show="!copied" class="flex items-center gap-1.5">
                    <x-admin-panel::icon name="copy" class="size-3.5" />
                    کپی
                </span>
                <span x-show="copied" x-cloak class="flex items-center gap-1.5 text-brand-600 dark:text-brand-400">
                    <x-admin-panel::icon name="check" class="size-3.5" />
                    کپی شد
                </span>
            </button>
        </header>
        <pre x-ref="pre" dir="ltr"
             class="max-h-96 overflow-auto bg-surface-2 p-5 font-mono text-[12px] leading-6 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">@if(trim($pretty) === '')<span class="text-ink-3 dark:text-night-ink-2">— بدون پاسخ —</span>@else{!! $highlighted !!}@endif</pre>
    </section>

    {{-- متن پیامک (در صورت وجود) --}}
    @if ($sms->message)
        <section class="ap-card p-5">
            <p class="flex items-center gap-1.5 text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">
                <x-admin-panel::icon name="message-square" class="size-3.5" />
                متن پیامک
            </p>
            <p class="mt-2 whitespace-pre-wrap text-[13px] leading-6">{{ $sms->message }}</p>
        </section>
    @endif
</div>
