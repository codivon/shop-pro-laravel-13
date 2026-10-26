@extends(\Modules\CustomerClub\Support\ActivePanel::useNewPanel() ? 'admin-panel::layouts.module' : 'back.layouts.master')

@section('title', 'راهنما و آموزش — باشگاه مشتریان')

@push('styles')
    <link rel="stylesheet" href="{{ module_asset('CustomerClub', 'css/cc-admin.css') }}?v=1.0.0">
@endpush

@section('content')
<div class="app-content content">
    <div class="content-wrapper">
        <div class="content-body cc-scope">

            {{-- ═══════════ سربرگ صفحه ═══════════ --}}
            <div class="cc-page-head">
                <div class="cc-page-head-main">
                    <span class="cc-page-icon"><i class="fas fa-graduation-cap"></i></span>
                    <div>
                        <h4 class="cc-page-title">راهنما و آموزش</h4>
                        <p class="cc-page-desc">این پکیج چه‌کاری انجام می‌دهد و چطور از آن استفاده کنید</p>
                    </div>
                </div>
            </div>

            <div class="cc-grid cc-grid--main-side">
                {{-- ═══════════ این پکیج کارش چیه؟ ═══════════ --}}
                <div class="cc-card">
                    <div class="cc-card__head">
                        <span class="cc-card__chip cc-card__chip--brand"><i class="fas fa-circle-question"></i></span>
                        <div>
                            <h5 class="cc-card__title">«باشگاه مشتریان» کارش چیست؟</h5>
                            <p class="cc-card__desc">سه موتور وفاداری که به هم وصل‌اند و همه به کیف پول هسته ختم می‌شوند:</p>
                        </div>
                    </div>
                    <ul class="cc-doc-list">
                        <li><b>امتیاز وفاداری:</b> مشتری با خرید (پله‌ای از مبلغ سفارش)، ثبت‌نام، تأیید نظر و روز تولدش امتیاز می‌گیرد. امتیازها لات‌محورند (هر بسته تاریخ انقضای خودش) و کسر همیشه FIFO است. مشتری امتیاز را با نرخ دلخواه شما به اعتبار کیف پول تبدیل می‌کند.</li>
                        <li><b>کارت هدیه:</b> شما دسته‌ای کارت صادر می‌کنید (CSV کدها را می‌گیرید) و مشتری کد را در پنلش وارد می‌کند — مبلغ مستقیم به کیف پولش شارژ می‌شود. ضد مصرف دوباره با قفل تراکنشی.</li>
                        <li><b>معرفی دوستان:</b> توسعهٔ سیستم معرفی هسته — صفحهٔ فرود عمومی <code>referral/{code}</code> + سطوح برنزی/نقره‌ای/طلایی با پاداش یک‌بارمصرف وقتی دعوت‌شده خرید موفق دارد.</li>
                    </ul>
                    <div class="cc-note cc-note--info">
                        <i class="fas fa-circle-info"></i>
                        هیچ فایلی از هسته پچ نشده — اتصال‌ها همه با رویدادهای هسته (<code>OrderPaid</code>، <code>Registered</code>، Observer نظر) و کیف پول/تاریخچهٔ خود هسته است.
                    </div>
                </div>

                {{-- ═══════════ طرز استفاده ═══════════ --}}
                <div class="cc-card">
                    <div class="cc-card__head">
                        <span class="cc-card__chip"><i class="fas fa-rocket"></i></span>
                        <div>
                            <h5 class="cc-card__title">شروع سریع (۵ قدم)</h5>
                        </div>
                    </div>
                    <ol class="cc-doc-list cc-doc-list--num">
                        <li>از <a href="{{ route('admin.customerclub.settings.edit') }}" class="cc-link">تنظیمات</a> کلید امکانات را مطابق استراتژیتان روشن/خاموش کنید (حداقل «کلید اصلی» روشن باشد) و نرخ تبدیل را تعیین کنید.</li>
                        <li>در <a href="{{ route('admin.customerclub.rules.index') }}" class="cc-link">قوانین امتیاز</a> پلهٔ امتیاز خرید و امتیازهای ثابت را تنظیم کنید.</li>
                        <li>در <a href="{{ route('admin.customerclub.giftcards.index') }}" class="cc-link">کارت‌های هدیه</a> دستهٔ اول را صادر کنید و CSV کدها را برای کمپین بردارید.</li>
                        <li>در <a href="{{ route('admin.customerclub.referrals.index') }}" class="cc-link">معرفی دوستان</a> معرف‌های فعال را رصد کنید.</li>
                        <li>بعد از هر خرید/تأیید نظر، جریان امتیاز را در <a href="{{ route('admin.customerclub.points.index') }}" class="cc-link">دفتر امتیازها</a> ببینید.</li>
                    </ol>
                </div>
            </div>

            <div class="cc-grid cc-grid--main-side">
                {{-- ═══════════ تنظیمات و شخصی‌سازی ═══════════ --}}
                <div class="cc-card">
                    <div class="cc-card__head">
                        <span class="cc-card__chip"><i class="fas fa-sliders"></i></span>
                        <div>
                            <h5 class="cc-card__title">همهٔ امکانات از تنظیمات کنترل می‌شوند</h5>
                            <p class="cc-card__desc">الگوی این پکیج: هر قابلیت یک کلید فعال/غیرفعال مستقل دارد.</p>
                        </div>
                    </div>
                    <table class="cc-table">
                        <thead><tr><th>کلید</th><th>کنترل چه چیزی است؟</th></tr></thead>
                        <tbody>
                            <tr><td><code>club_enabled</code></td><td>کلید اصلی — خاموشی = پنهان‌شدن کل بخش مشتری</td></tr>
                            <tr><td><code>loyalty_enabled</code></td><td>موتور امتیاز (کسب + تبدیل)</td></tr>
                            <tr><td><code>giftcards_enabled</code></td><td>کارت هدیه</td></tr>
                            <tr><td><code>referral_program_enabled</code></td><td>بخش معرفی + صفحهٔ فرود دعوت</td></tr>
                            <tr><td><code>referral_bonus_enabled</code></td><td>پاداش سطوح</td></tr>
                            <tr><td><code>قوانین (is_active)</code></td><td>هر یک از ۴ رویداد امتیاز، جداگانه خاموش‌شدنی</td></tr>
                            <tr><td><code>redeem_rate_toman</code></td><td>نرخ تبدیل امتیاز به تومان</td></tr>
                            <tr><td><code>redeem_min_points</code></td><td>حداقل امتیاز تبدیل</td></tr>
                            <tr><td><code>points_expiry_days</code></td><td>انقضای امتیاز (۰ = بی‌انقضا)</td></tr>
                        </tbody>
                    </table>
                </div>

                {{-- ═══════════ سؤالات رایج ═══════════ --}}
                <div class="cc-card">
                    <div class="cc-card__head">
                        <span class="cc-card__chip"><i class="fas fa-comments-question-check"></i></span>
                        <div><h5 class="cc-card__title">سؤالات رایج</h5></div>
                    </div>

                    <details class="cc-faq">
                        <summary>امتیاز خرید چطور محاسبه می‌شود؟</summary>
                        <p>مبلغ کل سفارشِ پرداخت‌شده تقسیم بر «پلهٔ مبلغ» قانون خرید (پیش‌فرض هر ۱۰٬۰۰۰ تومان = ۱ امتیاز). سفارشِ زیرِ پله امتیازی نمی‌گیرد.</p>
                    </details>
                    <details class="cc-faq">
                        <summary>اگر رویداد دوباره dispatch شود امتیاز مضاعف می‌شود؟</summary>
                        <p>خیر — هر امتیاز با یگانهٔ منبع (مثلاً شمارهٔ سفارش) ثبت می‌شود؛ اجرای دوباره نادیده گرفته می‌شود.</p>
                    </details>
                    <details class="cc-faq">
                        <summary>کارت هدیهٔ صادرشده را اشتباه صادر کردم؟</summary>
                        <p>از جدول کارت‌ها روی آن «غیرفعال‌سازی» بزنید — دیگر هیچ‌کس نمی‌تواند آن را فعال کند. کارت مصرف‌شده قابل تغییر نیست.</p>
                    </details>
                    <details class="cc-faq">
                        <summary>پاداش معرفی دوباره داده می‌شود؟</summary>
                        <p>نه — هر سطح فقط یک‌بار در عمر معرف پاداش می‌دهد (یگانهٔ منبع = شناسهٔ سطح).</p>
                    </details>
                    <details class="cc-faq">
                        <summary>کامندهای خودکار چه‌اند؟</summary>
                        <p><code>customerclub:expire-points</code> (۰۳:۱۵ روزانه) و <code>customerclub:birthday-points</code> (۰۷:۰۰ روزانه) خودکار زمان‌بندی شده‌اند و نیازی به cron جداگانه ندارند (روی scheduler هسته سوارند).</p>
                    </details>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
