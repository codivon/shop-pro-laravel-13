{{--
    بنر «راهنما و آموزش باشگاه مشتریان» — درون‌صفحه‌ای (درس فاز ۸۹).
    خودکفا: همهٔ استایل‌ها inline و با پیشوند ccg- (به هیچ CSS خارجی وابسته نیست).
--}}
@php
    $guideUrl = route('front.club.guide');
@endphp

<style>
    .ccg-guide-banner {
        position: relative; overflow: hidden;
        display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
        background: linear-gradient(135deg, #0d9488 0%, #0f766e 55%, #115e59 100%);
        border-radius: 18px; padding: 18px 22px; margin: 0 0 20px;
        color: #fff; box-shadow: 0 10px 26px rgba(13, 148, 136, .30);
    }
    .ccg-guide-banner::before {
        content: ''; position: absolute; top: -60px; left: -40px;
        width: 190px; height: 190px; background: rgba(255,255,255,.12);
        border-radius: 50%; pointer-events: none;
    }
    .ccg-guide-banner::after {
        content: ''; position: absolute; bottom: -80px; right: -30px;
        width: 150px; height: 150px; background: rgba(255,255,255,.08);
        border-radius: 50%; pointer-events: none;
    }
    .ccg-guide-icon {
        flex: 0 0 auto; width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.16); display: flex; align-items: center;
        justify-content: center; font-size: 22px; z-index: 1;
    }
    .ccg-guide-body { flex: 1 1 260px; z-index: 1; }
    .ccg-guide-title { margin: 0 0 4px; font-size: 16px; font-weight: 800; }
    .ccg-guide-text { margin: 0; font-size: 13px; opacity: .92; line-height: 1.9; }
    .ccg-guide-btn {
        flex: 0 0 auto; z-index: 1; display: inline-flex; align-items: center; gap: 8px;
        background: #fff; color: #0f766e; font-weight: 700; font-size: 14px;
        padding: 10px 18px; border-radius: 12px; text-decoration: none;
        transition: transform .15s ease, box-shadow .15s ease;
        box-shadow: 0 4px 14px rgba(0,0,0,.18);
    }
    .ccg-guide-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.22); color: #0d9488; }
    @media (max-width: 576px) { .ccg-guide-btn { width: 100%; justify-content: center; } }
</style>

<div class="ccg-guide-banner">
    <span class="ccg-guide-icon"><i class="fas fa-graduation-cap"></i></span>
    <div class="ccg-guide-body">
        <h5 class="ccg-guide-title">راهنما و آموزش باشگاه مشتریان</h5>
        <p class="ccg-guide-text">نحوهٔ کسب امتیاز، تبدیل امتیاز به اعتبار کیف پول، فعال‌سازی کارت هدیه و پاداش معرفی دوستان را قدم‌به‌قدم بیاموزید.</p>
    </div>
    <a href="{{ $guideUrl }}" class="ccg-guide-btn">
        <i class="fas fa-book-open"></i>
        مشاهدهٔ راهنما
    </a>
</div>
