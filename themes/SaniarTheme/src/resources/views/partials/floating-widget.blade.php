@php
    $fw_enabled = option('fw_enabled', '1');
    if (!$fw_enabled || $fw_enabled === '0') return;

    $channels = [
        'whatsapp'  => ['label' => option('fw_whatsapp_label', 'پشتیبانی واتساپ'),  'url' => option('fw_whatsapp_url', ''), 'icon' => 'whatsapp',  'color' => '#25D366', 'prefix' => 'fab'],
        'telegram'  => ['label' => option('fw_telegram_label', 'پشتیبانی تلگرام'),  'url' => option('fw_telegram_url', ''), 'icon' => 'telegram',  'color' => '#2AABEE', 'prefix' => 'fab'],
        'instagram' => ['label' => option('fw_instagram_label', 'اینستاگرام'),       'url' => option('fw_instagram_url', ''), 'icon' => 'instagram', 'color' => '#E1306C', 'prefix' => 'fab'],
        'twitter'   => ['label' => option('fw_twitter_label', 'توییتر/ایکس'),        'url' => option('fw_twitter_url', ''), 'icon' => 'x-twitter', 'color' => '#000000', 'prefix' => 'fab'],
        'youtube'   => ['label' => option('fw_youtube_label', 'یوتیوب'),             'url' => option('fw_youtube_url', ''), 'icon' => 'youtube',   'color' => '#FF0000', 'prefix' => 'fab'],
        'linkedin'  => ['label' => option('fw_linkedin_label', 'لینکدین'),           'url' => option('fw_linkedin_url', ''), 'icon' => 'linkedin', 'color' => '#0077B5', 'prefix' => 'fab'],
    ];

    $chat_channels    = array_filter(['whatsapp' => $channels['whatsapp'], 'telegram' => $channels['telegram']], fn($c) => !empty($c['url']));
    $social_channels  = array_filter(array_diff_key($channels, ['whatsapp' => null, 'telegram' => null]), fn($c) => !empty($c['url']));

    $phone   = option('fw_phone', '');
    $email   = option('fw_email', '');
    $address = option('fw_address', '');
    $working_hours = option('fw_working_hours', '');

    $btn_label = option('fw_button_label');
    $main_color = option('fw_main_color', '#5b6af7');
@endphp

<style>
    /* ─── Floating Widget ──────────────────────────────────────── */
    :root {
        --fw-primary: {{ $main_color }};
        --fw-primary-dark: color-mix(in srgb, {{ $main_color }} 80%, #000);
        --fw-radius: 20px;
        --fw-shadow: 0 20px 60px rgba(0,0,0,.22), 0 4px 12px rgba(0,0,0,.12);
    }

    .fw-wrapper {
        position: fixed;
        bottom: 28px;
        left: 28px;
        z-index: 9999;
        direction: rtl;
        font-family: inherit;
    }

    /* FAB button */
    .fw-fab {
        width: 58px;
        height: 58px;
        border-radius: 50%;
        background: var(--fw-primary);
        color: #fff;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 24px color-mix(in srgb, var(--fw-primary) 50%, transparent);
        transition: transform .3s cubic-bezier(.34,1.56,.64,1), box-shadow .3s;
        position: relative;
    }
    .fw-fab:hover { transform: scale(1.08); }
    .fw-fab.fw-open { transform: rotate(45deg) scale(1.05); }
    .fw-fab svg { width: 26px; height: 26px; fill: none; stroke: #fff; stroke-width: 2.2; stroke-linecap: round; transition: opacity .2s; }
    .fw-fab .fw-fab-close { position: absolute; font-size: 22px; opacity: 0; transition: opacity .2s; }
    .fw-fab.fw-open svg { opacity: 0; }
    .fw-fab.fw-open .fw-fab-close { opacity: 1; }

    /* Pulse ring */
    .fw-fab::before {
        content: '';
        position: absolute;
        inset: -5px;
        border-radius: 50%;
        border: 2px solid var(--fw-primary);
        opacity: 0;
        animation: fw-pulse 2.4s ease-out infinite;
    }
    @keyframes fw-pulse {
        0%   { transform: scale(1);    opacity: .6; }
        70%  { transform: scale(1.5);  opacity: 0; }
        100% { opacity: 0; }
    }

    /* FAB label */
    .fw-fab-label {
        position: absolute;
        left: calc(100% + 12px);
        top: 50%;
        transform: translateY(-50%);
        background: #fff;
        color: #333;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 13px;
        white-space: nowrap;
        box-shadow: 0 3px 12px rgba(0,0,0,.15);
        pointer-events: none;
        opacity: 1;
        transition: opacity .2s;
    }
    .fw-open ~ .fw-fab-label, .fw-open .fw-fab-label { opacity: 0; pointer-events: none; }
    .fw-fab-label::after {
        content: '';
        position: absolute;
        left: -7px;
        top: 50%;
        transform: translateY(-50%);
        border: 7px solid transparent;
        border-right-color: #fff;
        border-left: 0;
    }

    /* Popup panel */
    .fw-panel {
        position: absolute;
        bottom: calc(100% + 16px);
        left: 0;
        width: 320px;
        background: #fff;
        border-radius: var(--fw-radius);
        box-shadow: var(--fw-shadow);
        overflow: hidden;
        transform-origin: bottom left;
        transform: scale(.85) translateY(16px);
        opacity: 0;
        pointer-events: none;
        transition: transform .32s cubic-bezier(.34,1.3,.64,1), opacity .22s ease;
    }
    .fw-panel.fw-panel-open {
        transform: scale(1) translateY(0);
        opacity: 1;
        pointer-events: auto;
    }

    /* Panel header */
    .fw-header {
        background: linear-gradient(135deg, var(--fw-primary), color-mix(in srgb, var(--fw-primary) 70%, #8b5cf6));
        padding: 22px 20px 56px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .fw-header::before {
        content: '';
        position: absolute;
        width: 140px; height: 140px;
        background: rgba(255,255,255,.08);
        border-radius: 50%;
        top: -50px; right: -30px;
    }
    .fw-header::after {
        content: '';
        position: absolute;
        width: 80px; height: 80px;
        background: rgba(255,255,255,.06);
        border-radius: 50%;
        bottom: 10px; left: 20px;
    }
    .fw-header-title { font-size: 16px; font-weight: 700; margin: 0 0 4px; position: relative; z-index: 1; }
    .fw-header-sub   { font-size: 12px; opacity: .85; position: relative; z-index: 1; }
    .fw-status-dot   { display: inline-block; width: 8px; height: 8px; background: #4ade80; border-radius: 50%; margin-left: 6px; animation: fw-blink 1.6s infinite; }
    @keyframes fw-blink { 0%,100%{opacity:1} 50%{opacity:.3} }

    /* Tabs */
    .fw-tabs {
        display: flex;
        background: #f8f9fc;
        border-radius: 14px;
        margin: -26px 16px 0;
        padding: 5px;
        position: relative;
        z-index: 2;
        gap: 2px;
    }
    .fw-tab-btn {
        flex: 1;
        border: none;
        background: transparent;
        border-radius: 10px;
        padding: 9px 4px;
        font-size: 11px;
        cursor: pointer;
        color: #888;
        transition: all .22s;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }
    .fw-tab-btn i { font-size: 15px; }
    .fw-tab-btn.fw-tab-active {
        background: #fff;
        color: var(--fw-primary);
        box-shadow: 0 2px 10px rgba(0,0,0,.08);
        font-weight: 600;
    }

    /* Tab content */
    .fw-body { padding: 16px; }
    .fw-tab-pane { display: none; animation: fw-fadein .2s ease; }
    .fw-tab-pane.fw-active { display: block; }
    @keyframes fw-fadein { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:none} }

    /* Channel cards */
    .fw-channel {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 13px 14px;
        border-radius: 14px;
        background: #f8f9fc;
        margin-bottom: 10px;
        text-decoration: none;
        color: inherit;
        transition: background .18s, transform .18s;
        border: 1.5px solid transparent;
    }
    .fw-channel:last-child { margin-bottom: 0; }
    .fw-channel:hover {
        background: #fff;
        border-color: #e8eaf0;
        transform: translateX(-3px);
        box-shadow: 0 4px 14px rgba(0,0,0,.07);
    }
    .fw-ch-icon {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-size: 20px;
        color: #fff;
    }
    .fw-ch-info { flex: 1; }
    .fw-ch-name { font-size: 13px; font-weight: 700; color: #222; margin: 0 0 2px; }
    .fw-ch-sub  { font-size: 11px; color: #999; margin: 0; }
    .fw-ch-arrow { color: #bbb; font-size: 13px; transform: rotate(180deg); }

    /* Contact tab */
    .fw-contact-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 14px;
        border-radius: 12px;
        background: #f8f9fc;
        margin-bottom: 8px;
        font-size: 13px;
        color: #333;
        text-decoration: none;
        transition: background .18s;
        border: 1.5px solid transparent;
    }
    .fw-contact-item:hover { background: #fff; border-color: #e8eaf0; }
    .fw-contact-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        background: color-mix(in srgb, var(--fw-primary) 12%, #fff);
        color: var(--fw-primary);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-size: 15px;
    }
    .fw-contact-text { flex: 1; direction: ltr; text-align: right; line-height: 1.5; }

    /* Working hours badge */
    .fw-hours-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f0fdf4;
        color: #16a34a;
        border-radius: 8px;
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 600;
        margin-bottom: 12px;
    }

    /* Footer */
    .fw-footer {
        padding: 10px 16px 16px;
        text-align: center;
        font-size: 10px;
        color: #ccc;
        border-top: 1px solid #f0f0f0;
    }
</style>

<div class="fw-wrapper">
    <div class="fw-panel" id="fw-panel">
        <!-- Header -->
        <div class="fw-header">
            <p class="fw-header-title">
                <span class="fw-status-dot"></span>
                {{ option('fw_greeting', 'سلام، چطور می‌تونم کمکتون کنم؟') }}
            </p>
            <p class="fw-header-sub">{{ option('fw_sub_greeting', 'تیم پشتیبانی ما آماده‌ی پاسخ‌گویی است') }}</p>
        </div>

        <!-- Tabs -->
        <div class="fw-tabs">
            @if(count($chat_channels))
                <button class="fw-tab-btn fw-tab-active" data-tab="fw-chat">
                    <i class="fa-solid fa-comments"></i>
                    گفتگو
                </button>
            @endif
            @if(count($social_channels))
                <button class="fw-tab-btn" data-tab="fw-social">
                    <i class="fa-solid fa-share-nodes"></i>
                    شبکه‌ها
                </button>
            @endif
            @if($phone || $email || $address)
                <button class="fw-tab-btn" data-tab="fw-contact">
                    <i class="fa-solid fa-phone"></i>
                    تماس
                </button>
            @endif
        </div>

        <div class="fw-body">

            {{-- Chat Tab --}}
            @if(count($chat_channels))
                <div class="fw-tab-pane fw-active" id="fw-chat">
                    @if($working_hours)
                        <div class="fw-hours-badge">
                            <i class="fa-regular fa-clock"></i>
                            {{ $working_hours }}
                        </div>
                    @endif
                    @foreach($chat_channels as $key => $ch)
                        <a href="{{ $ch['url'] }}" target="_blank" rel="noopener" class="fw-channel">
                            <div class="fw-ch-icon" style="background: {{ $ch['color'] }};">
                                <i class="{{ $ch['prefix'] }} fa-{{ $ch['icon'] }}"></i>
                            </div>
                            <div class="fw-ch-info">
                                <p class="fw-ch-name">{{ $ch['label'] }}</p>
                                <p class="fw-ch-sub">پاسخ سریع</p>
                            </div>
                            <i class="fa-solid fa-chevron-right fw-ch-arrow"></i>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Social Tab --}}
            @if(count($social_channels))
                <div class="fw-tab-pane" id="fw-social">
                    @foreach($social_channels as $key => $ch)
                        <a href="{{ $ch['url'] }}" target="_blank" rel="noopener" class="fw-channel">
                            <div class="fw-ch-icon" style="background: {{ $ch['color'] }};">
                                <i class="{{ $ch['prefix'] }} fa-{{ $ch['icon'] }}"></i>
                            </div>
                            <div class="fw-ch-info">
                                <p class="fw-ch-name">{{ $ch['label'] }}</p>
                            </div>
                            <i class="fa-solid fa-chevron-right fw-ch-arrow"></i>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Contact Tab --}}
            @if($phone || $email || $address)
                <div class="fw-tab-pane" id="fw-contact">
                    @if($phone)
                        <a href="tel:{{ $phone }}" class="fw-contact-item">
                            <div class="fw-contact-icon"><i class="fa-solid fa-phone"></i></div>
                            <div class="fw-contact-text">{{ $phone }}</div>
                        </a>
                    @endif
                    @if($email)
                        <a href="mailto:{{ $email }}" class="fw-contact-item">
                            <div class="fw-contact-icon"><i class="fa-solid fa-envelope"></i></div>
                            <div class="fw-contact-text">{{ $email }}</div>
                        </a>
                    @endif
                    @if($address)
                        <div class="fw-contact-item">
                            <div class="fw-contact-icon"><i class="fa-solid fa-location-dot"></i></div>
                            <div class="fw-contact-text" style="direction:rtl;">{{ $address }}</div>
                        </div>
                    @endif
                </div>
            @endif

        </div>

        <div class="fw-footer">پشتیبانی آنلاین &middot; {{ option('info_site_title', 'فروشگاه') }}</div>
    </div>

    <!-- FAB -->
    <button class="fw-fab" id="fw-fab" aria-label="پشتیبانی آنلاین">
        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <span class="fw-fab-close">&times;</span>
    </button>

    @if($btn_label)
        <div class="fw-fab-label">{{ $btn_label }}</div>
    @endif

</div>

<script>
    (function () {
        var fab   = document.getElementById('fw-fab');
        var panel = document.getElementById('fw-panel');
        var tabs  = document.querySelectorAll('.fw-tab-btn');
        var panes = document.querySelectorAll('.fw-tab-pane');

        fab.addEventListener('click', function () {
            fab.classList.toggle('fw-open');
            panel.classList.toggle('fw-panel-open');
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.fw-wrapper')) {
                fab.classList.remove('fw-open');
                panel.classList.remove('fw-panel-open');
            }
        });

        tabs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                tabs.forEach(function (b) { b.classList.remove('fw-tab-active'); });
                panes.forEach(function (p) { p.classList.remove('fw-active'); });
                btn.classList.add('fw-tab-active');
                var target = document.getElementById(btn.getAttribute('data-tab'));
                if (target) target.classList.add('fw-active');
            });
        });
    })();
</script>
