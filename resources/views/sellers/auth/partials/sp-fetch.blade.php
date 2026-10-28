{{-- فاز ۱۰۸ — هلپر fetch پنل فروشنده: ارسال فرم به اندپوینت‌های JSON قدیمی
     (همان کنترلرهای منتقل‌شدهٔ App\Http\Controllers\Seller) با هندل خطا/ری‌دایرکت یکسان --}}
<script>
    window.spPost = async function (url, formData) {
        formData = formData || new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        var res = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        var ct = res.headers.get('content-type') || '';
        var data = ct.includes('json') ? await res.json() : { status: res.ok ? 'success' : 'error', message: 'پاسخ نامعتبر از سرور' };

        // اعتبارسنجی Laravel (بازگشت 422 با errors)
        if (res.status === 422 && data.errors) {
            var first = Object.values(data.errors)[0];
            return { status: 'error', message: Array.isArray(first) ? first[0] : String(first), errors: data.errors };
        }

        return data;
    };

    {{-- فاز ۱۱۱ — رویداد روی document dispatch می‌شود (شنوندهٔ ‎.document آلپاین
         رویدادهای dispatch‌شده روی window را نمی‌شنود — علت واقعی دیده‌نشدن پیام‌ها) --}}
    window.spToast = function (message, type) {
        document.dispatchEvent(new CustomEvent('sp-auth-toast', { detail: { message: message, type: type || 'error' } }));
    };

    {{-- فاز ۱۱۳ — بررسی سمت کلاینتِ فیلدهای required با پیام فارسی یکدست.
         فرم‌ها novalidate هستند (حبابهٔ انگلیسی مرورگر نداریم)؛ خالی‌ها با کلیدِ
         name فیلد در errors (همان آبجکت آلپاین) می‌نشینند و زیر فیلد رندر می‌شوند.
         customMessages: map اختیاری name → پیام فارسی (مثل تیک‌باکس‌های تأیید). --}}
    window.spRequire = function (form, errors, customMessages) {
        customMessages = customMessages || {};
        var missing = false;

        form.querySelectorAll('[required]').forEach(function (el) {
            var filled = el.type === 'checkbox' ? el.checked : String(el.value || '').trim() !== '';

            if (! filled) {
                var name = el.name || el.id || '_';
                if (! errors[name] || ! errors[name].length) {
                    errors[name] = [customMessages[name] || 'این فیلد الزامی است'];
                }
                missing = true;
            }
        });

        return missing;
    };

    {{-- فاز ۱۱۷ — تبدیل ارقام فارسی/عربی به انگلیسی + اعتبارسنجی کد ملی ایرانی
         (الگوریتم رسمی کنترل رقم) سمت کلاینت — قبل از ارسال به سرور --}}
    window.spEnDigits = function (value) {
        var map = { '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
                    '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4', '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9' };
        return String(value || '').replace(/[۰-۹٠-٩]/g, function (d) { return map[d]; });
    };

    window.spValidNationalCode = function (code) {
        code = spEnDigits(code).replace(/\D/g, '');
        if (!/^\d{10}$/.test(code)) return false;
        if (/^(\d)\1{9}$/.test(code)) return false; // ارقام یکسان معتبر نیستند
        var sum = 0;
        for (var i = 0; i < 9; i++) { sum += parseInt(code[i], 10) * (10 - i); }
        var r = sum % 11;
        return parseInt(code[9], 10) === (r < 2 ? r : 11 - r);
    };

    {{-- فاز ۱۱۳ — پاک‌کردن پیام «الزامی» کلاینت وقتی کاربر شروع به پرکردن کرد.
         scope = خود کامپوننت آلپاین (this در init) تا همیشه errors فعلی خوانده شود --}}
    window.spClearErrorOnInput = function (form, scope) {
        form.addEventListener('input', function (e) {
            var errs = scope.errors || {};
            if (e.target && e.target.name && errs[e.target.name] && errs[e.target.name][0] === 'این فیلد الزامی است') {
                delete errs[e.target.name];
            }
        });
        form.addEventListener('change', function (e) {
            var errs = scope.errors || {};
            if (e.target && e.target.type === 'checkbox' && e.target.name && errs[e.target.name]) {
                delete errs[e.target.name];
            }
        });
    };
</script>
