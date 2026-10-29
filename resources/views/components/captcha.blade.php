@php($name = $attributes->get('name', 'captcha'))

<div class="cap-field" data-captcha>
    <label class="cap-label">کد امنیتی <span class="cap-req">*</span></label>

    <div class="cap-row {{ $errors->has($name) ? 'cap-row--error' : '' }}">
        <div class="cap-input">
            <i class="mdi mdi-shield-key-outline"></i>
            <input type="text"
                   name="{{ $name }}"
                   class="cap-input__field"
                   placeholder="• • • • •"
                   autocomplete="off"
                   autocapitalize="off"
                   spellcheck="false"
                   maxlength="5"
                   dir="ltr">
        </div>

        <div class="cap-image">
            <img src="{{ route('captcha.image') }}" alt="کد امنیتی" class="cap-image__img">
            <button type="button" class="cap-image__refresh" title="تولید کد جدید" aria-label="تولید کد جدید">
                <i class="mdi mdi-refresh"></i>
            </button>
            <span class="cap-image__loading"><i class="mdi mdi-loading"></i></span>
        </div>
    </div>

    @if($errors->has($name))
        <p class="cap-error"><i class="mdi mdi-alert-circle-outline"></i> {{ $errors->first($name) }}</p>
    @endif
</div>

@once
    @push('styles')
        <style>
            .cap-field{
                display: flex;
                flex-direction: column;
            }
            .cap-label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px; }
            .cap-req { color: #ef4444; }

            .cap-row { display: flex; gap: 10px; align-items: stretch; }
            .cap-input { position: relative; flex: 1; min-width: 0; }
            .cap-input > i {
                position: absolute; inset-inline-start: 12px; top: 50%; transform: translateY(-50%);
                color: #94a3b8; font-size: 19px; pointer-events: none; transition: color .2s;
            }
            .cap-input__field {
                width: 100%; height: 48px; border: 1.5px solid #e2e8f0; border-radius: 13px;
                padding-inline: 14px 38px; font-size: 17px; font-weight: 800; letter-spacing: 5px;
                text-transform: uppercase; outline: none; background: #fff; direction: ltr;
                text-align: center; transition: border-color .2s, box-shadow .2s; font-family: inherit;
            }
            .cap-input__field:focus {
                border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124, 58, 237, .12);
            }
            .cap-input__field:focus + i, .cap-row:focus-within .cap-input > i { color: #7c3aed; }

            .cap-image {
                position: relative; width: 150px; height: 48px; flex-shrink: 0;
                border-radius: 13px; overflow: hidden; background: #0f172a;
                border: 1.5px solid #1e293b; box-shadow: inset 0 2px 8px rgba(0, 0, 0, .35);
            }
            .cap-image__img { width: 100%; height: 100%; display: block; object-fit: contain; }

            .cap-image__refresh {
                position: absolute; bottom: 4px; inset-inline-start: 4px;
                width: 24px; height: 24px; border-radius: 8px; border: none; cursor: pointer;
                background: rgba(255, 255, 255, .15); color: #fff; display: grid; place-items: center;
                backdrop-filter: blur(4px); transition: .2s; padding: 0;
            }
            .cap-image__refresh:hover { background: #fff; color: #0f172a; transform: rotate(90deg); }
            .cap-image__refresh i { font-size: 15px; }

            .cap-image__loading {
                position: absolute; inset: 0; display: grid; place-items: center;
                background: rgba(15, 23, 42, .78); color: #fff; font-size: 20px;
                opacity: 0; visibility: hidden; transition: .2s;
            }
            .cap-image__loading.is-visible { opacity: 1; visibility: visible; }
            @keyframes cap-spin { to { transform: rotate(360deg); } }
            .cap-image__loading i { animation: cap-spin 1s linear infinite; }

            .cap-row--error .cap-input__field { border-color: #fca5a5; }
            .cap-row--error .cap-input__field:focus { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, .12); }
            .cap-error { display: flex; align-items: center; gap: 5px; color: #ef4444; font-size: 12px; margin: 7px 2px 0; }

            @media (max-width: 480px) {
                .cap-row { flex-direction: column; }
                .cap-image { width: 100%; height: 56px; }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.querySelectorAll('[data-captcha]').forEach(function (field) {
                var img      = field.querySelector('.cap-image__img');
                var btn      = field.querySelector('.cap-image__refresh');
                var loading  = field.querySelector('.cap-image__loading');
                var input    = field.querySelector('.cap-input__field');
                var refreshed = false;

                function refresh() {
                    refreshed = true;
                    loading.classList.add('is-visible');
                    // cache-bust با پارامتر تصادفی
                    img.src = img.src.split('?')[0] + '?_=' + Date.now() + Math.random().toString(36).slice(2, 6);
                }

                img.addEventListener('load', function () {
                    loading.classList.remove('is-visible');
                    if (refreshed) {           // فقط بعد از رفرش دستی/خودکار
                        input.value = '';
                        input.focus();
                    }
                });
                img.addEventListener('error', function () {
                    loading.classList.remove('is-visible');
                });

                btn.addEventListener('click', refresh);

                // اگر فرم قبلاً خطا داده (کد مصرف/اشتباه شده)، خودکار کد تازه بیاور
                if (field.querySelector('.cap-row--error')) {
                    window.addEventListener('load', refresh);
                }
            });
        </script>
    @endpush
@endonce
