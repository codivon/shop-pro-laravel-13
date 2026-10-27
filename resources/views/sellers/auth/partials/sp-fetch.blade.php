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

    window.spToast = function (message, type) {
        window.dispatchEvent(new CustomEvent('sp-auth-toast', { detail: { message: message, type: type || 'error' } }));
    };
</script>
