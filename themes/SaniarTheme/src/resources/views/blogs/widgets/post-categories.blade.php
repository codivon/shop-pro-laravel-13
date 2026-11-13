@php
    $variables  = get_widget($widget);
    $categories = $variables['categories'];
@endphp
@push('styles')
    <style>
        /* ===== Category Showcase ===== */
        .cat-showcase {
            /* رنگ اصلی — با رنگ برند خودتان عوض کنید */
            --cat-accent: #ef4056;
            --cat-accent-soft: #fdeef0;
            --cat-radius: 18px;
            margin: 24px 0;
        }

        /* --- هدر --- */
        .cat-showcase__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }
        .cat-showcase__title-wrap { display: flex; align-items: center; gap: 10px; }
        .cat-showcase__accent {
            width: 6px; height: 26px;
            border-radius: 99px;
            background: linear-gradient(180deg, var(--cat-accent), #ff9aa8);
        }
        .cat-showcase__title {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: #1f2937;
        }

        /* --- دکمه‌های اسکرول --- */
        .cat-showcase__nav { display: flex; gap: 8px; }
        .cat-nav-btn {
            width: 38px; height: 38px;
            border-radius: 50%;
            border: 1px solid #eceef2;
            background: #fff;
            color: #475569;
            cursor: pointer;
            display: grid; place-items: center;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .07);
            transition: .25s;
        }
        .cat-nav-btn:hover {
            background: var(--cat-accent);
            border-color: var(--cat-accent);
            color: #fff;
            transform: translateY(-2px);
        }
        .cat-nav-btn:disabled { opacity: .3; cursor: default; transform: none; }
        .cat-nav-btn svg { width: 18px; height: 18px; }

        /* --- ردیف اسکرولی --- */
        .cat-showcase__track {
            display: flex;
            gap: 14px;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            padding: 6px 2px 16px;
            scrollbar-width: none;           /* فایرفاکس */
        }
        .cat-showcase__track::-webkit-scrollbar { display: none; } /* کروم */

        /* --- کارت --- */
        .cat-card {
            flex: 0 0 152px;
            scroll-snap-align: start;
            position: relative;
            background: #fff;
            border: 1px solid #f1f2f6;
            border-radius: var(--cat-radius);
            text-decoration: none;
            overflow: hidden;
            transition: transform .3s cubic-bezier(.34, 1.56, .64, 1), box-shadow .3s, border-color .3s;
        }
        .cat-card:hover {
            transform: translateY(-6px);
            border-color: transparent;
            box-shadow: 0 16px 32px -12px rgba(17, 24, 39, .18);
        }
        /* حاشیه گرادیانی هنگام هاور */
        .cat-card::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 1.5px;
            background: linear-gradient(135deg, var(--cat-accent), #ffb3bd);
            -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: .3s;
            pointer-events: none;
            z-index: 1;
        }
        .cat-card:hover::before { opacity: 1; }

        /* --- تصویر --- */
        .cat-card__media {
            position: relative;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            border-radius: var(--cat-radius) var(--cat-radius) 0 0;
            background: linear-gradient(135deg, #f8fafc, #eef2f7);
        }
        .cat-card__media img {
            width: 100%; height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .45s ease;
        }
        .cat-card:hover .cat-card__media img { transform: scale(1.08); }

        /* حالت بدون تصویر — پس‌زمینه پاستلی تمیز */
        .cat-card__media--empty { background: linear-gradient(135deg, var(--cat-accent-soft), #fff); }

        /* --- بَج تعداد کالا --- */
        .cat-card__count {
            position: absolute;
            bottom: 8px;
            inset-inline-start: 8px;
            padding: 3px 10px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 600;
            color: #334155;
            background: rgba(255, 255, 255, .88);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, .7);
            box-shadow: 0 2px 6px rgba(0, 0, 0, .07);
            transition: .25s;
        }
        .cat-card:hover .cat-card__count {
            background: var(--cat-accent);
            color: #fff;
            border-color: transparent;
        }

        /* --- عنوان کارت --- */
        .cat-card__body { padding: 10px 10px 13px; text-align: center; }
        .cat-card__name {
            margin: 0;
            font-size: 13.5px;
            font-weight: 700;
            color: #1f2937;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: color .25s;
        }
        .cat-card:hover .cat-card__name { color: var(--cat-accent); }

        /* --- موبایل --- */
        @media (max-width: 768px) {
            .cat-card { flex-basis: 126px; }
            .cat-showcase__nav { display: none; }
            .cat-showcase__title { font-size: 17px; }
        }
        .cat-card__media{
            padding: 25px;
        }
    </style>
@endpush

<!-- Start Category-Section -->
@if ($categories->count())
    <div class="col-12">
        <section class="cat-showcase">
            {{-- هدر: عنوان + دکمه‌های اسکرول --}}
            <div class="cat-showcase__header">
                <div class="cat-showcase__title-wrap">
                    <span class="cat-showcase__accent"></span>
                    <h2 class="cat-showcase__title">{{ $widget->option('title') }}</h2>
                </div>
                <div class="cat-showcase__nav">
                    <button type="button" class="cat-nav-btn" data-dir="prev" aria-label="قبلی">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                    </button>
                    <button type="button" class="cat-nav-btn" data-dir="next" aria-label="بعدی">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                    </button>
                </div>
            </div>

            {{-- ردیف اسکرولی کارت‌ها --}}
            <div class="cat-showcase__track">
                @foreach ($categories as $category)
                    <a href="{{route('front.articles.index').'?cat='.$category->slug}}" class="image-data-src cat-card">
                        <div class="cat-card__media {{ $category->image ? '' : 'cat-card__media--empty' }}">
                            <img data-src="{{ $category->image ? asset($category->image) : asset('/no-image-product.svg') }}"
                                 src="{{ theme_asset('images/600-600.png') }}"
                                 alt="{{ $category->title }}">
                        </div>
                        <div class="cat-card__body">
                            <h3 class="cat-card__name">{{ $category->title }}</h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
@endif
<!-- End Category-Section -->
@push('scripts')
    <script>
        $(function () {
            $('.cat-showcase').each(function () {
                var track = $(this).find('.cat-showcase__track')[0];
                var $prev = $(this).find('[data-dir="prev"]');
                var $next = $(this).find('[data-dir="next"]');
                var rtl   = getComputedStyle(track).direction === 'rtl';

                function scroll(dir) {
                    var amount = Math.min(track.clientWidth * 0.8, 4 * 166);
                    // در RTL مقدار scrollLeft منفی است
                    track.scrollBy({ left: rtl ? -amount * dir : amount * dir, behavior: 'smooth' });
                }

                function update() {
                    var max = track.scrollWidth - track.clientWidth;
                    var pos = Math.abs(track.scrollLeft);
                    $prev.prop('disabled', pos <= 2);
                    $next.prop('disabled', pos >= max - 2);
                }

                $next.on('click', function () { scroll(1); });
                $prev.on('click', function () { scroll(-1); });
                track.addEventListener('scroll', update, { passive: true });
                $(window).on('resize', update);
                update();
            });
        });
    </script>
@endpush
