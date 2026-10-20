@extends('vendor.installer.layouts.master')

@section('template_title', 'ساخت دیتابیس')

@section('title', 'ساخت جداول دیتابیس')
@section('title_icon')
    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/></svg>
@endsection
@section('title_desc', 'جداول یکی‌یکی و مرحله‌به‌مرحله ساخته می‌شوند — نیازی به رفرش نیست')

@section('container')
    {{-- فاز ۲۱ — صفحه‌ی پیشرفت مرحله‌به‌مرحله (رفع Timeout/صفحه‌ی سیاه روی هاست) --}}

    <div id="ap-db-running">
        <div class="ap-progress-head">
            <div class="ap-ring">
                <svg width="74" height="74" viewBox="0 0 74 74">
                    <circle class="bg" cx="37" cy="37" r="31"></circle>
                    <circle class="fg" id="ap-ring-fg" cx="37" cy="37" r="31" stroke-dasharray="194.8" stroke-dashoffset="194.8"></circle>
                </svg>
                <span class="ap-ring-num" id="ap-ring-num">۰٪</span>
            </div>
            <div class="ap-progress-info">
                <p class="ap-progress-title" id="ap-status">در حال آماده‌سازی…</p>
                <p class="ap-progress-sub" id="ap-status-sub">این کار خودکار انجام می‌شود؛ لطفا این صفحه را نبندید.</p>
            </div>
        </div>

        <div class="ap-alert ap-alert-ok">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>
            <span>اگر وسط کار اینترنت شما قطع شد یا صفحه بسته شد، نگران نباشید — با باز کردن مجدد همین آدرس، کار از همان‌جا ادامه پیدا می‌کند.</span>
        </div>

        <div class="ap-log" id="ap-log">در انتظار شروع…</div>
    </div>

    <div id="ap-db-error" style="display:none">
        <div class="ap-alert ap-alert-error">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
            <span id="ap-error-text">خطایی رخ داد.</span>
        </div>
        <div class="ap-actions">
            <button type="button" id="ap-retry" class="ap-btn ap-btn-primary">تلاش مجدد</button>
            <a href="{{ route('LaravelInstaller::environmentWizard') }}" class="ap-btn ap-btn-ghost">بازگشت به تنظیمات محیط</a>
        </div>
    </div>

    <div id="ap-db-done" style="display:none">
        <div class="ap-alert ap-alert-ok">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            <span><strong>دیتابیس با موفقیت ساخته شد!</strong> همه‌ی جداول و داده‌های اولیه نصب شدند.</span>
        </div>
        <div class="ap-actions">
            <a href="{{ route('LaravelInstaller::final') }}" class="ap-btn ap-btn-primary">
                مرحله بعد: اتمام نصب
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            var stepUrl = '{{ route('install.migrate-step') }}';
            var logEl = document.getElementById('ap-log');
            var statusEl = document.getElementById('ap-status');
            var statusSubEl = document.getElementById('ap-status-sub');
            var ringFg = document.getElementById('ap-ring-fg');
            var ringNum = document.getElementById('ap-ring-num');
            var runningEl = document.getElementById('ap-db-running');
            var errorEl = document.getElementById('ap-db-error');
            var doneEl = document.getElementById('ap-db-done');
            var errorText = document.getElementById('ap-error-text');
            var circ = 2 * Math.PI * 31;
            var percent = 0;
            var stopped = false;

            function setRing(p) {
                ringFg.setAttribute('stroke-dashoffset', String(circ * (1 - p / 100)));
                var fa = String(Math.round(p)).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
                ringNum.textContent = fa + '٪';
            }

            function addLog(text) {
                if (!text) return;
                var stamp = new Date().toLocaleTimeString('fa-IR');
                logEl.textContent = '[' + stamp + ']  ' + text + '\n' + logEl.textContent;
            }

            function faNum(n) {
                return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
            }

            function showError(msg) {
                stopped = true;
                runningEl.style.display = 'none';
                doneEl.style.display = 'none';
                errorEl.style.display = 'block';
                errorText.textContent = msg || 'خطای نامشخص در اجرای مایگریشن.';
            }

            function nextStep() {
                if (stopped) return;

                fetch(stepUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) {
                        return r.json().catch(function () { throw new Error('پاسخ نامعتبر از سرور (احتمالا خطای ۵۰۰).'); });
                    })
                    .then(function (data) {
                        if (data.log) addLog(data.log);

                        if (data.error) {
                            showError(data.error);
                            return;
                        }

                        if (data.total > 0) {
                            percent = Math.min(99, (data.done / data.total) * 100);
                            setRing(percent);
                        }

                        if (data.phase === 'migrate') {
                            statusEl.textContent = 'در حال ساخت جدول ' + (data.current || '');
                            statusSubEl.textContent = faNum(data.done) + ' از ' + faNum(data.total) + ' جدول ساخته شد — ' + faNum(data.remaining) + ' مورد باقی مانده';
                            setTimeout(nextStep, 150);
                            return;
                        }

                        if (data.phase === 'seed') {
                            statusEl.textContent = 'در حال ثبت داده‌های اولیه…';
                            statusSubEl.textContent = 'سیدهای اولیه (تنظیمات، واحدها و…) در حال ثبت هستند';
                            setTimeout(nextStep, 150);
                            return;
                        }

                        if (data.phase === 'done') {
                            setRing(100);
                            statusEl.textContent = 'نصب دیتابیس کامل شد';
                            statusSubEl.textContent = 'همه‌ی جداول ساخته شدند';
                            setTimeout(function () {
                                runningEl.style.display = 'none';
                                doneEl.style.display = 'block';
                            }, 500);
                            return;
                        }
                    })
                    .catch(function (e) {
                        showError(e.message);
                    });
            }

            document.getElementById('ap-retry').addEventListener('click', function () {
                errorEl.style.display = 'none';
                runningEl.style.display = 'block';
                stopped = false;
                nextStep();
            });

            nextStep();
        })();
    </script>
@endsection
