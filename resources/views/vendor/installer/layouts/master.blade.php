<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@if (trim($__env->yieldContent('template_title')))@yield('template_title') | @endif نصب فروشگاه‌ساز</title>
    <link rel="icon" type="image/png" href="{{ asset('vendor/installer/img/favicon/favicon-32x32.png') }}" sizes="32x32"/>

    {{-- فاز ۲۱ — بازطراحی کامل و مدرن (RTL، بدون وابستگی به FontAwesome/jQuery) --}}
    <style>
        :root {
            --brand: #059669;
            --brand-strong: #047857;
            --brand-soft: #d1fae5;
            --ink: #0f172a;
            --ink-2: #475569;
            --ink-3: #94a3b8;
            --line: #e2e8f0;
            --surface: #ffffff;
            --surface-2: #f8fafc;
            --red: #dc2626;
            --amber: #d97706;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html { -webkit-text-size-adjust: 100%; }

        body {
            font-family: Vazirmatn, "Segoe UI", Tahoma, Arial, sans-serif;
            direction: rtl;
            color: var(--ink);
            min-height: 100vh;
            background:
                radial-gradient(1100px 500px at 85% -10%, rgb(5 150 105 / .28), transparent 60%),
                radial-gradient(900px 500px at 10% 110%, rgb(4 120 87 / .22), transparent 55%),
                linear-gradient(160deg, #0f172a 0%, #111f36 55%, #0b2b22 100%);
            padding: 2rem 1rem;
            line-height: 1.8;
        }

        .ap-install { max-width: 860px; margin: 0 auto; }

        /* ---------- برند ---------- */
        .ap-brand {
            display: flex; align-items: center; justify-content: center; gap: .7rem;
            color: #fff; margin-bottom: 1.6rem;
        }
        .ap-brand-logo {
            width: 44px; height: 44px; border-radius: 14px; flex-shrink: 0;
            background: linear-gradient(135deg, #10b981, #047857);
            display: grid; place-items: center;
            box-shadow: 0 10px 24px -8px rgb(16 185 129 / .55);
        }
        .ap-brand-logo svg { width: 22px; height: 22px; stroke: #fff; }
        .ap-brand-title { font-size: 1.15rem; font-weight: 800; letter-spacing: -.2px; }
        .ap-brand-sub { font-size: .72rem; color: rgb(255 255 255 / .55); margin-top: 2px; }

        /* ---------- مراحل ---------- */
        .ap-steps {
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 1.6rem; flex-wrap: wrap; row-gap: .8rem;
        }
        .ap-step { display: flex; align-items: center; color: rgb(255 255 255 / .45); }
        .ap-step-dot {
            width: 34px; height: 34px; border-radius: 999px; display: grid; place-items: center;
            font-size: .8rem; font-weight: 800; flex-shrink: 0;
            background: rgb(255 255 255 / .08); border: 1.5px solid rgb(255 255 255 / .18);
            transition: all .25s ease;
        }
        .ap-step-dot svg { width: 15px; height: 15px; stroke: currentColor; }
        .ap-step-label { font-size: .74rem; font-weight: 700; margin: 0 .55rem; white-space: nowrap; }
        .ap-step-line { width: 34px; height: 1.5px; background: rgb(255 255 255 / .16); margin: 0 .3rem; flex-shrink: 0; }
        .ap-step.is-current { color: #34d399; }
        .ap-step.is-current .ap-step-dot {
            background: #10b981; border-color: #10b981; color: #06281f;
            box-shadow: 0 0 0 5px rgb(16 185 129 / .18);
        }
        .ap-step.is-done { color: #6ee7b7; }
        .ap-step.is-done .ap-step-dot { background: rgb(16 185 129 / .25); border-color: rgb(52 211 153 / .5); color: #6ee7b7; }
        .ap-step.is-done .ap-step-line { background: rgb(52 211 153 / .45); }

        /* ---------- کارت ---------- */
        .ap-card {
            background: var(--surface);
            border-radius: 22px;
            box-shadow: 0 30px 70px -25px rgb(2 6 23 / .55);
            overflow: hidden;
        }
        .ap-card-head {
            padding: 1.4rem 1.8rem 1.2rem;
            border-bottom: 1px solid var(--line);
            display: flex; align-items: center; gap: .8rem;
        }
        .ap-card-icon {
            width: 42px; height: 42px; border-radius: 13px; flex-shrink: 0;
            display: grid; place-items: center;
            background: var(--brand-soft); color: var(--brand-strong);
        }
        .ap-card-icon svg { width: 20px; height: 20px; stroke: currentColor; }
        .ap-card-title { font-size: 1.05rem; font-weight: 800; }
        .ap-card-desc { font-size: .78rem; color: var(--ink-3); margin-top: 2px; }
        .ap-card-body { padding: 1.6rem 1.8rem 1.8rem; }

        /* ---------- متن ---------- */
        .ap-lead { color: var(--ink-2); font-size: .92rem; }
        .ap-muted { color: var(--ink-3); font-size: .8rem; }

        /* ---------- لیست‌ها (نیازمندی/مجوز) ---------- */
        .ap-list { list-style: none; border: 1px solid var(--line); border-radius: 16px; overflow: hidden; }
        .ap-list + .ap-list { margin-top: 1rem; }
        .ap-list li {
            display: flex; align-items: center; justify-content: space-between; gap: .8rem;
            padding: .75rem 1.1rem; font-size: .85rem; font-weight: 600;
            border-bottom: 1px solid var(--line); background: var(--surface);
        }
        .ap-list li:last-child { border-bottom: 0; }
        .ap-list li.ap-list-title { background: var(--surface-2); font-weight: 800; }
        .ap-list li small { color: var(--ink-3); font-weight: 500; }
        .ap-state { display: inline-flex; align-items: center; gap: .4rem; font-size: .78rem; font-weight: 800; }
        .ap-state svg { width: 16px; height: 16px; stroke: currentColor; }
        .ap-state.ok { color: var(--brand-strong); }
        .ap-state.bad { color: var(--red); }

        /* ---------- فرم ---------- */
        .ap-field { margin-bottom: 1.05rem; }
        .ap-field label { display: block; font-size: .8rem; font-weight: 700; color: var(--ink-2); margin-bottom: .45rem; }
        .ap-field input, .ap-field select {
            width: 100%; padding: .72rem .95rem; font-size: .88rem; font-family: inherit;
            border: 1.5px solid var(--line); border-radius: 13px; background: var(--surface);
            color: var(--ink); transition: border-color .15s ease, box-shadow .15s ease;
        }
        .ap-field input:focus, .ap-field select:focus {
            outline: none; border-color: var(--brand);
            box-shadow: 0 0 0 4px rgb(5 150 105 / .12);
        }
        .ap-field .error-block {
            margin-top: .45rem; font-size: .75rem; color: var(--red); font-weight: 600;
        }
        .ap-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
        @media (max-width: 560px) { .ap-grid { grid-template-columns: 1fr; } }

        /* ---------- دکمه‌ها ---------- */
        .ap-actions { margin-top: 1.6rem; display: flex; align-items: center; gap: .8rem; flex-wrap: wrap; }
        .ap-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            padding: .8rem 1.5rem; border-radius: 14px; border: 0; cursor: pointer;
            font-family: inherit; font-size: .88rem; font-weight: 800; text-decoration: none;
            transition: all .18s ease;
        }
        .ap-btn svg { width: 16px; height: 16px; stroke: currentColor; }
        .ap-btn-primary { background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 10px 22px -10px rgb(5 150 105 / .6); }
        .ap-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 14px 26px -10px rgb(5 150 105 / .7); }
        .ap-btn-ghost { background: var(--surface-2); color: var(--ink-2); border: 1.5px solid var(--line); }
        .ap-btn-ghost:hover { background: var(--surface); color: var(--ink); }
        .ap-btn[disabled] { opacity: .55; cursor: not-allowed; transform: none !important; }
        .ap-btn-block { width: 100%; }

        /* ---------- هشدار/خطا ---------- */
        .ap-alert {
            display: flex; gap: .7rem; align-items: flex-start;
            border-radius: 14px; padding: .9rem 1.1rem; font-size: .82rem; font-weight: 600;
            margin-bottom: 1.1rem;
        }
        .ap-alert svg { width: 18px; height: 18px; flex-shrink: 0; stroke: currentColor; margin-top: 2px; }
        .ap-alert-warn { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .ap-alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .ap-alert-ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }

        /* ---------- پیش‌نمایش نصب (دیتابیس) ---------- */
        .ap-progress-head { display: flex; align-items: center; gap: 1.1rem; margin-bottom: 1.2rem; }
        .ap-ring { position: relative; width: 74px; height: 74px; flex-shrink: 0; }
        .ap-ring svg { transform: rotate(-90deg); }
        .ap-ring circle { fill: none; stroke-width: 7; stroke-linecap: round; }
        .ap-ring .bg { stroke: var(--line); }
        .ap-ring .fg { stroke: var(--brand); transition: stroke-dashoffset .4s ease; }
        .ap-ring-num {
            position: absolute; inset: 0; display: grid; place-items: center;
            font-size: .95rem; font-weight: 800; color: var(--brand-strong);
        }
        .ap-progress-info { min-width: 0; }
        .ap-progress-title { font-size: .95rem; font-weight: 800; }
        .ap-progress-sub { font-size: .78rem; color: var(--ink-3); margin-top: 2px; }

        .ap-log {
            background: #0f172a; color: #86efac; border-radius: 14px;
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .74rem; line-height: 2; direction: ltr; text-align: left;
            padding: 1rem 1.2rem; max-height: 220px; overflow-y: auto; white-space: pre-wrap;
            word-break: break-all;
        }

        /* ---------- نوار وضعیت مجوز/نیازمندی fail ---------- */
        .bar { width: 9px; border-radius: 6px; align-self: stretch; flex-shrink: 0; }
        .bar-success { background: var(--brand-soft); }
        .bar-danger { background: #fee2e2; }
        .list__item--permissions { gap: 1rem !important; }

        .ap-footer { text-align: center; margin-top: 1.6rem; font-size: .74rem; color: rgb(255 255 255 / .45); }

        [x-cloak] { display: none !important; }
    </style>
    @yield('style')
</head>
<body>
    <div class="ap-install">

        {{-- برند --}}
        <div class="ap-brand">
            <span class="ap-brand-logo">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9h18l-1.5 9.5a2 2 0 0 1-2 1.5h-11a2 2 0 0 1-2-1.5z"/><path d="M8 9V6a4 4 0 0 1 8 0v3"/></svg>
            </span>
            <span>
                <span class="ap-brand-title">نصب فروشگاه‌ساز</span>
                <span class="ap-brand-sub" style="display:block">راه‌اندازی اولیه فروشگاه اینترنتی شما</span>
            </span>
        </div>

        {{-- مراحل (RTL: خوش‌آمد → نیازمندی‌ها → مجوزها → محیط → دیتابیس → پایان) --}}
        @php
            $apSteps = [
                ['key' => 'welcome',      'label' => 'خوش‌آمد',      'match' => ['install', 'install/requirements', 'install/permissions', 'install/environment', 'install/environment/*', 'install/database', 'install/final']],
                ['key' => 'requirements', 'label' => 'نیازمندی‌ها',   'match' => ['install/requirements', 'install/permissions', 'install/environment', 'install/environment/*', 'install/database', 'install/final']],
                ['key' => 'permissions',  'label' => 'مجوزها',       'match' => ['install/permissions', 'install/environment', 'install/environment/*', 'install/database', 'install/final']],
                ['key' => 'environment',  'label' => 'تنظیمات محیط', 'match' => ['install/environment', 'install/environment/*', 'install/database', 'install/final']],
                ['key' => 'database',     'label' => 'دیتابیس',      'match' => ['install/database', 'install/final']],
                ['key' => 'final',        'label' => 'پایان',        'match' => ['install/final']],
            ];
            $apCurrent = request()->path();
            // آخرین مرحله‌ای که مسیر فعلی جزو «میزان طی‌شده» آن است
            $apActiveIndex = 0;
            foreach ($apSteps as $idx => $step) {
                if (request()->is(...$step['match'])) {
                    $apActiveIndex = $idx;
                }
            }
            // مسیر دقیق مرحله‌ی جاری برای هایلایت
            $apExact = ['welcome' => 'install', 'requirements' => 'install/requirements', 'permissions' => 'install/permissions', 'environment' => 'install/environment', 'database' => 'install/database', 'final' => 'install/final'];
            $faNum = fn ($n) => strtr((string) $n, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
        @endphp
        <div class="ap-steps">
            @foreach ($apSteps as $i => $step)
                @if ($i > 0)
                    <span class="ap-step-line"></span>
                @endif
                @php $isCurrent = $i === $apActiveIndex; @endphp
                <span class="ap-step {{ $isCurrent ? 'is-current' : ($i < $apActiveIndex ? 'is-done' : '') }}">
                    <span class="ap-step-dot">
                        @if ($i < $apActiveIndex)
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        @else
                            <span>{{ $faNum($i + 1) }}</span>
                        @endif
                    </span>
                    <span class="ap-step-label">{{ $step['label'] }}</span>
                </span>
            @endforeach
        </div>

        {{-- کارت محتوا --}}
        <div class="ap-card">
            @hasSection('title')
                <div class="ap-card-head">
                    <span class="ap-card-icon">@yield('title_icon')</span>
                    <span>
                        <span class="ap-card-title">@yield('title')</span>
                        @hasSection('title_desc')
                            <span class="ap-card-desc" style="display:block">@yield('title_desc')</span>
                        @endif
                    </span>
                </div>
            @endif
            <div class="ap-card-body">
                @yield('container')
            </div>
        </div>

        <p class="ap-footer">نسخه {{ app()->version() }} — قدرت گرفته از لاراول</p>
    </div>

    @yield('scripts')
</body>
</html>
