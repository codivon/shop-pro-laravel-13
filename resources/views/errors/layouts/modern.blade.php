{{-- ======================================================================
   فاز ۲۸ — لایه‌ی مدرن صفحات خطا (مستقل از پنل قدیمی — بدون بوت‌استرپ)
   نسخه‌های قبلی در resources/views/errors/old نگه‌داری شده‌اند.
   متغیرها:
     $code      کد خطا (۴۰۳/۴۰۴/۵۰۰/۵۰۳)
     $title     عنوان اصلی (فارسی)
     $subtitle  توضیح کوتاه
     $accent    رنگ برند (مثل #10b981 یا #ef4444)
     $icon      svg آیکون (inline)
     $dark      تم تاریک (اختیاری — از کوکی admin-theme تشخیص داده می‌شود)
====================================================================== --}}
@php
    $isDark = $dark ?? request()->cookie('admin-theme') === 'dark';
    try {
        $siteName = trim((string) \Illuminate\Support\Facades\DB::table('options')->where('key', 'info_site_title')->value('value') ?: '') ?: config('app.name', 'Shop Pro');
    } catch (\Throwable $e) {
        $siteName = config('app.name', 'Shop Pro');
    }
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl" class="{{ $isDark ? 'dark' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} | {{ $siteName }}</title>
    <style>
        @font-face {
            font-family: 'Vazir';
            src: url('{{ asset("back/app-assets/fonts/vazir/Vazir-Light-FD.woff2") }}') format('woff2'),
                 url('{{ asset("back/app-assets/fonts/vazir/Vazir-Light-FD.woff") }}') format('woff');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --accent: {{ $accent }};
            --bg: #f6f7f9;
            --card: rgba(255, 255, 255, 0.82);
            --text: #111827;
            --text-2: #6b7280;
            --line: rgba(17, 24, 39, 0.08);
            --ghost-hover: rgba(17, 24, 39, 0.05);
            --blob-opacity: 0.5;
            --grid-dot: rgba(17, 24, 39, 0.14);
        }
        html.dark {
            --bg: #0b1220;
            --card: rgba(17, 25, 40, 0.78);
            --text: #f3f4f6;
            --text-2: #9ca3af;
            --line: rgba(255, 255, 255, 0.09);
            --ghost-hover: rgba(255, 255, 255, 0.06);
            --blob-opacity: 0.35;
            --grid-dot: rgba(255, 255, 255, 0.12);
        }

        html, body { height: 100%; }

        body {
            font-family: 'Vazir', Vazirmatn, Tahoma, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px 16px;
            position: relative;
            overflow: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ---- پس‌زمینه: حباب‌های رنگی + شبکه نقطه‌ای ---- */
        .bg-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            opacity: var(--blob-opacity);
            pointer-events: none;
            z-index: 0;
            animation: blob-float 14s ease-in-out infinite alternate;
        }
        .bg-blob-1 { width: 420px; height: 420px; background: var(--accent); top: -140px; inset-inline-start: -120px; }
        .bg-blob-2 { width: 360px; height: 360px; background: #6366f1; bottom: -120px; inset-inline-end: -100px; animation-delay: -7s; }
        .bg-grid {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            background-image: radial-gradient(circle at 1px 1px, var(--grid-dot) 1px, transparent 0);
            background-size: 26px 26px;
            mask-image: radial-gradient(ellipse 70% 60% at 50% 45%, black 20%, transparent 75%);
            -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 45%, black 20%, transparent 75%);
        }
        @keyframes blob-float {
            0%   { transform: translate(0, 0) scale(1); }
            100% { transform: translate(40px, 30px) scale(1.12); }
        }
        @media (prefers-reduced-motion: reduce) {
            .bg-blob, .icon-badge::after { animation: none !important; }
        }

        /* ---- کارت اصلی ---- */
        .error-card {
            position: relative;
            z-index: 1;
            width: min(560px, 100%);
            background: var(--card);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid var(--line);
            border-radius: 28px;
            padding: 44px 36px 36px;
            text-align: center;
            box-shadow: 0 24px 70px -24px rgba(2, 8, 20, 0.35);
            animation: card-in 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @keyframes card-in {
            from { opacity: 0; transform: translateY(18px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* ---- آیکون ---- */
        .icon-badge {
            width: 108px;
            height: 108px;
            margin: 0 auto 22px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            background: color-mix(in srgb, var(--accent) 12%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
            position: relative;
        }
        .icon-badge svg { width: 52px; height: 52px; }
        .icon-badge::after {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: 30px;
            border: 1px solid color-mix(in srgb, var(--accent) 35%, transparent);
            animation: ring-pulse 2.6s ease-out infinite;
        }
        @keyframes ring-pulse {
            0%   { transform: scale(1); opacity: 0.9; }
            70%  { transform: scale(1.18); opacity: 0; }
            100% { transform: scale(1.18); opacity: 0; }
        }

        .error-code {
            font-size: 88px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: 0.04em;
            direction: ltr;
            background: linear-gradient(135deg, var(--accent) 20%, #94a3b8 140%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 10px;
        }

        .error-title { font-size: 24px; font-weight: 800; margin-bottom: 10px; }

        .error-subtitle {
            font-size: 15px;
            line-height: 2;
            color: var(--text-2);
            max-width: 40ch;
            margin: 0 auto 28px;
        }

        /* ---- دکمه‌ها ---- */
        .actions { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 26px;
            min-height: 46px;
            border-radius: 14px;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.2s ease, background 0.2s ease, filter 0.2s ease;
            border: 1px solid transparent;
        }
        .btn svg { width: 17px; height: 17px; }
        .btn:active { transform: scale(0.97); }
        .btn-primary {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 10px 24px -10px color-mix(in srgb, var(--accent) 70%, transparent);
        }
        .btn-primary:hover { filter: brightness(1.06); transform: translateY(-1px); }
        .btn-ghost { background: transparent; color: var(--text); border-color: var(--line); }
        .btn-ghost:hover { background: var(--ghost-hover); transform: translateY(-1px); }

        /* ---- پاورقی کارت ---- */
        .error-meta {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed var(--line);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 12px;
            color: var(--text-2);
        }
        .code-chip {
            direction: ltr;
            font-weight: 700;
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 999px;
            background: color-mix(in srgb, var(--accent) 12%, transparent);
            color: var(--accent);
            border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
        }

        @media (max-width: 480px) {
            .error-card { padding: 34px 22px 28px; border-radius: 22px; }
            .error-code { font-size: 64px; }
            .icon-badge { width: 88px; height: 88px; border-radius: 24px; }
            .icon-badge svg { width: 42px; height: 42px; }
            .actions .btn { flex: 1 1 100%; }
        }
    </style>
</head>
<body>
    <div class="bg-grid" aria-hidden="true"></div>
    <div class="bg-blob bg-blob-1" aria-hidden="true"></div>
    <div class="bg-blob bg-blob-2" aria-hidden="true"></div>

    <main class="error-card" role="main">
        <div class="icon-badge" aria-hidden="true">
            {!! $icon !!}
        </div>

        <p class="error-code" aria-hidden="true">{{ $code }}</p>

        <h1 class="error-title">{{ $title }}</h1>
        <p class="error-subtitle">{{ $subtitle }}</p>

        <div class="actions">
            @yield('actions')
        </div>

        <div class="error-meta">
            <span class="code-chip">{{ $code }}</span>
            <span>{{ $siteName }}</span>
        </div>
    </main>
</body>
</html>
