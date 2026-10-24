{{-- راه‌اندازی اولیه — ثبت اولین مدیر (بازطراحی مدرن مستقل) --}}
{{-- مستقل از پنل‌ها: CSS داخلی، بدون jQuery/Bootstrap — همان endpoint قبلی --}}
<!DOCTYPE html>
@php
    // پیشوند پیشنهادی — یک‌بار تولید می‌شود تا ورودی و پیش‌نمایش یکسان بمانند
    $adminPrefix = 'admin' . rand(11111, 99999);
@endphp
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>راه‌اندازی فروشگاه — {{ option('info_site_title', 'اُ شاپ') }}</title>
    <link rel="icon" href="{{ option('info_favicon', asset('favicon.ico')) }}">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --brand-50:#ecfdf5;--brand-100:#d1fae5;--brand-500:#10b981;--brand-600:#059669;--brand-700:#047857;
            --ink:#0f172a;--ink-2:#334155;--ink-3:#94a3b8;--line:#e2e8f0;--surface:#ffffff;--bg:#f1f5f9;
            --red:#dc2626;--radius:16px;
        }
        html{-webkit-text-size-adjust:100%}
        body{
            font-family:Vazirmatn,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Tahoma,sans-serif;
            background:
                radial-gradient(1200px 500px at 85% -10%, rgb(5 150 105/.14), transparent 60%),
                radial-gradient(900px 420px at 0% 110%, rgb(16 185 129/.12), transparent 60%),
                var(--bg);
            color:var(--ink);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;
            padding:24px 16px;line-height:1.7;
        }
        .lang-badge{position:fixed;top:16px;inset-inline-start:16px;font-size:11px;color:var(--ink-3)}
        .wrap{width:100%;max-width:1000px;display:grid;grid-template-columns:1fr 1.1fr;border-radius:28px;overflow:hidden;
            box-shadow:0 24px 70px -24px rgb(15 23 42/.25);border:1px solid var(--line);background:var(--surface)}
        .hero{position:relative;display:flex;flex-direction:column;justify-content:space-between;gap:28px;padding:44px 36px;color:#fff;
            background:linear-gradient(160deg,var(--brand-700),var(--brand-600) 55%,var(--brand-500))}
        .hero::before,.hero::after{content:"";position:absolute;border-radius:50%;background:rgb(255/255/.1);filter:blur(2px)}
        .hero::before{width:220px;height:220px;top:-70px;inset-inline-start:-60px}
        .hero::after{width:260px;height:260px;bottom:-90px;inset-inline-end:-70px}
        .hero .logo{max-width:150px;max-height:44px;filter:brightness(0) invert(1);object-fit:contain}
        .hero h1{font-size:24px;font-weight:800;position:relative;z-index:1}
        .hero p.sub{font-size:13.5px;opacity:.92;margin-top:8px;position:relative;z-index:1}
        .steps{position:relative;z-index:1;display:flex;flex-direction:column;gap:14px;margin-top:8px}
        .step{display:flex;align-items:center;gap:12px;font-size:13px;font-weight:600;opacity:.95}
        .step .n{flex:none;width:34px;height:34px;border-radius:12px;background:rgb(255/255/.16);display:grid;place-items:center;
            font-size:13px;font-weight:800;backdrop-filter:blur(4px)}
        .hero .hint{position:relative;z-index:1;font-size:11.5px;opacity:.85;border-top:1px solid rgb(255/255/.25);padding-top:14px}
        .form-side{padding:40px 38px}
        .form-side .head{margin-bottom:22px}
        .form-side .head h2{font-size:19px;font-weight:800}
        .form-side .head p{font-size:12.5px;color:var(--ink-3);margin-top:4px}
        .row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .field{margin-bottom:14px}
        .field label{display:block;font-size:12.5px;font-weight:700;color:var(--ink-2);margin-bottom:6px}
        .field label .req{color:var(--red)}
        .control{position:relative}
        .control .ic{position:absolute;inset-inline-start:12px;top:50%;transform:translateY(-50%);width:18px;height:18px;color:var(--ink-3);pointer-events:none}
        .control input{width:100%;border:1.5px solid var(--line);border-radius:12px;background:#f8fafc;padding:11px 40px;font:inherit;
            font-size:13.5px;color:var(--ink);transition:border-color .15s,box-shadow .15s,background .15s}
        .control input:focus{outline:none;border-color:var(--brand-500);background:#fff;box-shadow:0 0 0 4px rgb(16 185 129/.14)}
        .control input.err{border-color:var(--red)}
        .control .eye{position:absolute;inset-inline-end:8px;top:50%;transform:translateY(-50%);border:0;background:transparent;
            width:32px;height:32px;border-radius:9px;cursor:pointer;color:var(--ink-3);display:grid;place-items:center}
        .control .eye:hover{background:var(--brand-50);color:var(--brand-600)}
        .control .eye svg{width:17px;height:17px}
        .strength{display:flex;gap:5px;margin-top:7px}
        .strength i{height:4px;flex:1;border-radius:99px;background:var(--line);transition:background .2s}
        .strength i.on-1{background:#f87171}.strength i.on-2{background:#fbbf24}.strength i.on-3{background:var(--brand-500)}
        .strength-txt{font-size:11px;color:var(--ink-3);margin-top:4px;min-height:16px}
        .prefix-box{border:1.5px dashed var(--brand-500);background:var(--brand-50);border-radius:12px;padding:11px 14px;
            display:flex;align-items:center;gap:10px;margin-bottom:4px}
        .prefix-box .ic{flex:none;width:20px;height:20px;color:var(--brand-600)}
        .prefix-box input{border:0;background:transparent;padding:0;font:inherit;font-size:13.5px;font-weight:700;color:var(--brand-700);
            width:100%;direction:ltr;text-align:left;font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
        .prefix-box input:focus{outline:none}
        .prefix-box p{font-size:10.5px;color:var(--brand-700);opacity:.8;margin-top:2px}
        .alert{display:none;align-items:flex-start;gap:9px;border-radius:12px;padding:11px 13px;font-size:12.5px;font-weight:600;margin-bottom:14px}
        .alert svg{flex:none;width:17px;height:17px;margin-top:2px}
        .alert.show{display:flex}
        .alert.error{background:#fef2f2;color:var(--red);border:1px solid #fecaca}
        .btn{width:100%;border:0;border-radius:14px;padding:13px 18px;font:inherit;font-size:14.5px;font-weight:800;cursor:pointer;
            background:linear-gradient(135deg,var(--brand-600),var(--brand-500));color:#fff;margin-top:8px;
            display:inline-flex;align-items:center;justify-content:center;gap:9px;transition:transform .15s,box-shadow .15s,opacity .15s;
            box-shadow:0 12px 26px -10px rgb(5 150 105/.55)}
        .btn:hover{transform:translateY(-1px);box-shadow:0 16px 30px -10px rgb(5 150 105/.6)}
        .btn:active{transform:translateY(0) scale(.99)}
        .btn[disabled]{opacity:.65;cursor:not-allowed;transform:none}
        .btn .spin{display:none;width:18px;height:18px;animation:spin 1s linear infinite}
        .btn.loading .spin{display:block}
        .btn.loading .arr{display:none}
        @keyframes spin{to{transform:rotate(360deg)}}
        .foot{text-align:center;font-size:11.5px;color:var(--ink-3);margin-top:26px}
        .foot a{color:var(--brand-600);text-decoration:none;font-weight:600}
        @media(max-width:860px){
            .wrap{grid-template-columns:1fr;max-width:520px}
            .hero{padding:30px 26px;gap:20px}
            .hero .steps{display:none}
            .form-side{padding:28px 24px}
        }
        @media(max-width:520px){.row{grid-template-columns:1fr;gap:0}}
    </style>
</head>
<body>
    <span class="lang-badge">مرحله آخر نصب</span>

    <main class="wrap" role="main">
        {{-- ===== ستون معرفی ===== --}}
        <aside class="hero" aria-label="معرفی راه‌اندازی">
            <div>
                <img class="logo" src="{{ option('info_logo', theme_asset('img/logo.png')) }}"
                     alt="{{ option('info_site_title', 'اُ شاپ') }}" onerror="this.style.display='none'">
                <h1 style="margin-top:18px">به {{ option('info_site_title', 'اُ شاپ') }} خوش آمدید! 🎉</h1>
                <p class="sub">اطلاعات زیر را تکمیل کنید و ماجراجویی را شروع کنید.</p>
            </div>

            <div class="steps">
                <div class="step"><span class="n">۱</span> ساخت حساب مدیر کل</div>
                <div class="step"><span class="n">۲</span> انتخاب آدرس اختصاصی پنل مدیریت</div>
                <div class="step"><span class="n">۳</span> ورود مستقیم به پنل و شروع فروش</div>
            </div>

            <p class="hint">🔒 با همین اطلاعات از این پس به پنل مدیریت دسترسی خواهید داشت؛ گذرواژه را جایی امن نگه دارید.</p>
        </aside>

        {{-- ===== ستون فرم ===== --}}
        <section class="form-side">
            <div class="head">
                <h2>ساخت حساب مدیر</h2>
                <p>این فرم فقط یک‌بار و برای اولین مدیر نمایش داده می‌شود.</p>
            </div>

            <div id="js-alert" class="alert error" role="alert">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                <span id="js-alert-text"></span>
            </div>

            <form id="register-form" method="POST" action="{{ route('admin.register') }}" novalidate>
                @csrf
                <div class="row">
                    <div class="field">
                        <label for="first_name">نام <span class="req">*</span></label>
                        <div class="control">
                            <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <input type="text" id="first_name" name="first_name" placeholder="مثلاً مهدی" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="last_name">نام خانوادگی <span class="req">*</span></label>
                        <div class="control">
                            <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <input type="text" id="last_name" name="last_name" placeholder="مثلاً محمدی" required>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label for="username">نام کاربری <span class="req">*</span></label>
                    <div class="control">
                        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <input type="text" id="username" name="username" dir="ltr" style="text-align:left" placeholder="admin" autocomplete="username" required>
                    </div>
                </div>

                <div class="field">
                    <label for="admin_route_prefix">آدرس اختصاصی ورود به پنل <span class="req">*</span></label>
                    <div class="prefix-box">
                        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <div style="width:100%">
                            <input type="text" id="admin_route_prefix" name="admin_route_prefix" value="{{ $adminPrefix }}" required>
                            <p>آدرس پنل شما: <strong dir="ltr">{{ url('/') }}/<span id="prefix-preview">{{ $adminPrefix }}</span></strong> — آن را جایی یادداشت کنید.</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="field">
                        <label for="password">گذرواژه <span class="req">*</span></label>
                        <div class="control">
                            <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <input type="password" id="password" name="password" autocomplete="new-password" placeholder="حداقل ۶ کاراکتر" required>
                            <button type="button" class="eye" data-toggle="password" aria-label="نمایش گذرواژه">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <div class="strength" aria-hidden="true"><i></i><i></i><i></i></div>
                        <p class="strength-txt" id="strength-txt"></p>
                    </div>
                    <div class="field">
                        <label for="password_confirmation">تکرار گذرواژه <span class="req">*</span></label>
                        <div class="control">
                            <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" placeholder="تکرار گذرواژه" required>
                        </div>
                        <p class="strength-txt" id="match-txt"></p>
                    </div>
                </div>

                <button type="submit" id="submit-btn" class="btn">
                    <svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/></svg>
                    <span>ثبت‌نام و ورود به پنل مدیریت</span>
                    <svg class="arr" style="width:18px;height:18px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5m7-7-7 7 7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </form>
        </section>
    </main>

    <p class="foot">
        پس از ثبت‌نام، به <a href="{{ route('front.index') }}">فروشگاه</a> هم می‌توانید سر بزنید.
    </p>

    <script>
    (function () {
        'use strict';
        var form = document.getElementById('register-form');
        var btn = document.getElementById('submit-btn');
        var alertBox = document.getElementById('js-alert');
        var alertText = document.getElementById('js-alert-text');

        function showAlert(msg) {
            alertText.textContent = msg;
            alertBox.classList.add('show');
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        function hideAlert() { alertBox.classList.remove('show'); }

        // نمایش/مخفی گذرواژه
        document.querySelectorAll('.eye').forEach(function (eye) {
            eye.addEventListener('click', function () {
                var input = document.getElementById(eye.dataset.toggle);
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        });

        // پیش‌نمایش زنده آدرس پنل
        var prefixInput = document.getElementById('admin_route_prefix');
        var prefixPreview = document.getElementById('prefix-preview');
        prefixInput.addEventListener('input', function () { prefixPreview.textContent = prefixInput.value; });

        // سنجش قدرت گذرواژه
        var pw = document.getElementById('password');
        var bars = document.querySelectorAll('.strength i');
        var strengthTxt = document.getElementById('strength-txt');
        pw.addEventListener('input', function () {
            var v = pw.value, score = 0;
            if (v.length >= 6) score++;
            if (v.length >= 10 && /[A-Za-z]/.test(v) && /\d/.test(v)) score++;
            if (v.length >= 12 && /[^A-Za-z0-9]/.test(v)) score++;
            bars.forEach(function (b, i) { b.className = i < score ? 'on-' + score : ''; });
            strengthTxt.textContent = v === '' ? '' : (score <= 1 ? 'قدرت: ضعیف' : (score === 2 ? 'قدرت: متوسط' : 'قدرت: عالی'));
        });

        // تطابق تکرار گذرواژه
        var pw2 = document.getElementById('password_confirmation');
        var matchTxt = document.getElementById('match-txt');
        function checkMatch() {
            if (pw2.value === '') { matchTxt.textContent = ''; return pw2.value === pw.value; }
            var ok = pw2.value === pw.value;
            matchTxt.textContent = ok ? '✓ گذرواژه‌ها یکسان هستند' : '✗ گذرواژه‌ها یکسان نیستند';
            matchTxt.style.color = ok ? 'var(--brand-600)' : 'var(--red)';
            return ok;
        }
        pw2.addEventListener('input', checkMatch);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            hideAlert();

            var required = ['first_name', 'last_name', 'username', 'admin_route_prefix', 'password', 'password_confirmation'];
            for (var i = 0; i < required.length; i++) {
                var el = document.getElementById(required[i]);
                el.classList.remove('err');
                if (!el.value.trim()) {
                    el.classList.add('err');
                    el.focus();
                    showAlert('لطفاً همه فیلدهای ستاره‌دار را تکمیل کنید.');
                    return;
                }
            }
            if (pw.value.length < 6) { showAlert('گذرواژه باید حداقل ۶ کاراکتر باشد.'); pw.focus(); return; }
            if (!checkMatch()) { showAlert('گذرواژه و تکرار آن یکسان نیستند.'); pw2.focus(); return; }
            if (!/^[A-Za-z0-9\-_]+$/.test(prefixInput.value.trim())) {
                showAlert('آدرس پنل فقط می‌تواند شامل حروف انگلیسی، عدد و خط تیره باشد.');
                prefixInput.focus();
                return;
            }

            btn.classList.add('loading');
            btn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(Object.fromEntries(new FormData(form).entries()))
            })
            .then(function (res) {
                return res.json().then(function (data) { return { ok: res.ok, data: data }; });
            })
            .then(function (r) {
                if (r.ok && (r.data.redirect || r.data.admin_prefix)) {
                    btn.querySelector('span').textContent = 'خوش آمدید! در حال انتقال…';
                    window.location.href = r.data.redirect || (window.location.origin + '/admin/' + r.data.admin_prefix);
                    return;
                }
                throw new Error(r.data.message || 'خطا در ثبت‌نام. اطلاعات را بررسی کنید.');
            })
            .catch(function (err) {
                btn.classList.remove('loading');
                btn.disabled = false;
                showAlert(err.message === 'Failed to fetch'
                    ? 'ارتباط با سرور برقرار نشد؛ اتصال اینترنت را بررسی کنید.'
                    : err.message);
            });
        });
    })();
    </script>
</body>
</html>
