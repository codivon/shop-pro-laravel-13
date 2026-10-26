@extends('front::user.layouts.master')

@section('title', 'راهنما و آموزش باشگاه مشتریان')

@section('user-content')
    <div class="headline-profile">
        <div class="row">
            <div class="col-12">
                <h4 class="mb-4">راهنما و آموزش باشگاه مشتریان</h4>

                <style>
                    .ccg2-wrap{display:grid;gap:16px}
                    .ccg2-card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
                    .ccg2-card h5{font-size:15.5px;font-weight:800;margin:0 0 10px;display:flex;align-items:center;gap:8px}
                    .ccg2-card ul{margin:0;padding-right:18px;font-size:13.5px;line-height:2.1;color:#374151}
                    .ccg2-note{background:#f0fdfa;border:1px solid #99f6e4;border-radius:14px;padding:12px 16px;font-size:13px;color:#115e59;margin-top:4px}
                    .ccg2-badge{display:inline-block;background:#f0fdfa;color:#0f766e;border:1px solid #99f6e4;border-radius:999px;padding:2px 10px;font-size:12px;font-weight:700}
                </style>

                <div class="ccg2-wrap">
                    @if ($loyaltyOn)
                        <div class="ccg2-card">
                            <h5><i class="fas fa-star text-info"></i> امتیاز وفاداری چطور کار می‌کند؟</h5>
                            <ul>
                                <li>با هر خرید، به‌ازای مبلغ سفارش امتیاز می‌گیرید (طبق قانون پله‌ای فروشگاه).</li>
                                <li>ثبت‌نام موفق، تأیید نظر شما دربارهٔ محصولات و روز تولدتان هم امتیاز هدیه دارد.</li>
                                <li>امتیازها «لات‌دار» هستند: هر بستهٔ امتیاز زمان انقضای خودش را دارد و در تبدیل، همیشه قدیمی‌ترین (نزدیک‌ترین به انقضا) مصرف می‌شود.
                                    @if ($expiryDays > 0) — امتیازهای فعلی {{ $expiryDays }} روز اعتبار دارند. @endif
                                </li>
                                <li>حداقل {{ number_format($minPoints) }} امتیاز را می‌توانید با نرخ هر امتیاز {{ number_format($rate) }} تومان، مستقیم به کیف پول تبدیل کنید و در خرید بعدی استفاده کنید.</li>
                            </ul>
                        </div>
                    @endif

                    @if ($giftcardsOn)
                        <div class="ccg2-card">
                            <h5><i class="fas fa-gift text-warning"></i> کارت هدیه</h5>
                            <ul>
                                <li>کارت‌های هدیه با کدی شبیه <span class="ccg2-badge">CC-XXXX-XXXX</span> صادر می‌شوند.</li>
                                <li>کد را در صفحهٔ باشگاه مشتریان وارد کنید؛ مبلغ کارت بلافاصله به کیف پول شما شارژ می‌شود.</li>
                                <li>هر کارت فقط یک‌بار قابل استفاده است؛ اگر منقضی یا غیرفعال شده باشد، سیستم پیام دقیق نشان می‌دهد.</li>
                            </ul>
                        </div>
                    @endif

                    @if ($referralOn)
                        <div class="ccg2-card">
                            <h5><i class="fas fa-share-nodes text-success"></i> معرفی دوستان</h5>
                            <ul>
                                <li>لینک دعوت اختصاصی خود را از صفحهٔ باشگاه بردارید و برای دوستانتان بفرستید.</li>
                                <li>دوست شما با کلیک روی لینک، به صفحهٔ ثبت‌نام هدایت می‌شود و کد شما خودکار ثبت می‌شود.</li>
                                <li>با خرید موفق هر دعوت‌شده، شمارندهٔ شما بالا می‌رود؛ رسیدن به سطوح برنزی/نقره‌ای/طلایی پاداش یک‌بارمصرف دارد.</li>
                            </ul>
                        </div>
                    @endif

                    <div class="ccg2-note">
                        <i class="fas fa-circle-info ml-1"></i>
                        هر سؤالی دربارهٔ باشگاه مشتریان دارید، از طریق «تماس با ما» با پشتیبانی در تماس باشید — آمار دقیق امتیازها همیشه در «دفتر امتیازهای من» در دسترس شماست.
                    </div>

                    <a href="{{ route('front.club.index') }}" class="btn btn-primary">
                        <i class="fas fa-arrow-right ml-1"></i> بازگشت به باشگاه مشتریان
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
