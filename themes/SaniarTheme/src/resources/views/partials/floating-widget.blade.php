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

<link rel="stylesheet" href="{{ theme_asset('css/floating-widget.css') }}">


<div class="fw-wrapper" data-main-color="{{ $main_color }}">
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

<script src="{{ theme_asset('js/floating-widget.js') }}"></script>
