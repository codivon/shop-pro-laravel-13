@php
    // بارگذاری تنظیمات فرم
    $setting = $form->setting ?? new \App\Models\FormSetting();

    // کلاس‌های عرض فرم
    $widthClass = '';
    switch($setting->form_width ?? 'full') {
        case 'half':
            $widthClass = 'col-md-6 col-12';
            break;
        case 'third':
            $widthClass = 'col-md-4 col-12';
            break;
        default:
            $widthClass = 'col-12';
    }

    // کلاس‌های تراز فرم
    $alignmentClass = '';
    switch($setting->form_alignment ?? 'center') {
        case 'center':
            $alignmentClass = 'mx-auto';
            break;
        case 'right':
            $alignmentClass = 'me-auto';
            break;
        case 'left':
            $alignmentClass = 'ms-auto';
            break;
    }

    // موقعیت فرم (top = بالای توضیحات, bottom = زیر توضیحات)
    $formPosition = $setting->form_position ?? 'top';

    // کلاس سفارشی فرم
    $formCustomClass = $setting->form_class ?? '';

    // CSS سفارشی
    $customCss = $setting->custom_css ?? '';
@endphp
<link rel="stylesheet" href="{{ theme_asset('css/form-builder.css') }}">
<style>
    .shortcode-form .card label{
        margin-right: unset;
        margin-top: unset;
        float: unset;
        text-align: right;
    }

    .shortcode-form select{
        border: 1px solid #ced4da;
        border-radius: 5px;
        overflow: hidden;
        padding: 6px;
    }
    .has-diviter-remember-me{
        width: auto !important;
    }
</style>
@if($customCss)
    <style>

        /* فرم شماره {{ $form->id }} */
        .shortcode-form-{{ $form->id }} {!! $customCss !!}
    </style>
@endif

<div class="shortcode-form shortcode-form-{{ $form->id }} my-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ $form->title }}</h5>
        </div>
        <div class="card-body">
            <!-- توضیحات فرم -->
            @if($form->description)
                <div class="form-description mb-4" id="form-description-{{ $form->id }}">
                    {!! $form->description !!}
                </div>
            @endif

            <!-- فرم (با قابلیت جابجایی به بالا یا پایین توضیحات) -->
            <div class="form-wrapper
                        {{ $widthClass }}
                        {{ $alignmentClass }}
                        {{ $formCustomClass }}"
                 id="form-wrapper-{{ $form->id }}"
                 data-position="{{ $formPosition }}">

                <form method="POST" action="{{ route('front.form.submit', $form) }}" class="ajax-form" id="ajax-form-{{ $form->id }}">
                    @csrf

                    <div class="row" id="form-fields-container">
                        @foreach($form->fields->sortBy('order') as $field)
                            @php
                                // تعیین کلاس ستون - اولویت با column_class فیلد
                                $columnClass = $field->column_class ?? ($setting->default_column_class ?? 'col-md-6');

                                // برای textarea اگر ستون خاصی تنظیم نشده، col-12 بده
                                if ($field->type === 'textarea' && empty($field->column_class)) {
                                    $columnClass = 'col-12';
                                }

                                // کلاس والد فیلد
                                $wrapperClass = $field->wrapper_class ?? '';
                            @endphp

                            <div class="form-group-custom {{ $columnClass }} {{ $wrapperClass }} mb-3 " data-field-id="{{ $field->id }}">
                                <div class="form-group mb-0">
                                    @if($field->show_label)
                                        <label for="field_{{ $field->id }}" style="margin-right: unset" class="form-label fw-bold mb-2 {{ $field->label_class }}">
                                            {{ $field->label }}
                                            @if($field->required)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                    @endif

                                    @switch($field->type)
                                        @case('textarea')
                                            @case('textarea')
                                                <textarea name="{{ $field->name }}"
                                                          id="{{ $field->name }}"
                                                          class="form-control-custom {{ $field->class }}"
                                                          placeholder="{{ $field->placeholder ?? 'متن خود را وارد کنید...' }}"
                                                          rows="4">{{ old($field->name) }}</textarea>
                                                @break

                                            @case('select')
                                                <select name="{{ $field->name }}"
                                                        id="{{ $field->name }}"
                                                        class="select-custom {{ $field->class }}">
                                                    <option value="">انتخاب کنید...</option>
                                                    @foreach($field->options_array as $option)
                                                        <option value="{{ $option }}" {{ old($field->name) == $option ? 'selected' : '' }}>
                                                            {{ $option }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @break

                                            @case('checkbox')
                                                <div class="checkbox-group">
                                                    @foreach($field->options_array as $index => $option)
                                                        <div class="form-auth-row">
                                                            <label class="ui-checkbox has-diviter">
                                                                <input type="checkbox"
                                                                       id="checkbox-{{ $field->id }}-{{ $index }}"
                                                                       value="{{ $option }}"
                                                                       name="{{ $field->name }}[]"
                                                                       class="{{ $field->class }}"
                                                                    {{ in_array($option, (array)old($field->name, [])) ? 'checked' : '' }}>
                                                                <span class="ui-checkbox-check"></span>
                                                            </label>
                                                            <label for="checkbox-{{ $field->id }}-{{ $index }}" class="remember-me has-diviter-remember-me cursor-pointer">
                                                                {{ $option }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @break

                                            @case('radio')
                                                <div class="radio-group">
                                                    @foreach($field->options_array as $index => $option)
                                                        <label class="radio-item">
                                                            <input type="radio"
                                                                   name="{{ $field->name }}"
                                                                   value="{{ $option }}"
                                                                   class="{{ $field->class }}"
                                                                   id="radio-{{ $field->id }}-{{ $index }}"
                                                                {{ old($field->name) == $option ? 'checked' : '' }}>
                                                            <span>{{ $option }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                @break

                                            @case('file')
                                                <div class="form-group-custom">
                                                    {{--                                                            <label for="file-input-{{ $field->name }}">{{ $field->label }}</label>--}}
                                                    <label class="upload-label" for="file-input-{{ $field->name }}">
                                                        <i class="fa fa-upload"></i>
                                                        <span>برای آپلود کلیک کنید</span>
                                                    </label>
                                                    <input type="file"
                                                           name="{{ $field->name }}"
                                                           id="file-input-{{ $field->name }}"
                                                           class="file-input-hidden {{ $field->class }}"
                                                           style="display: none;">
                                                    <span id="file-name-{{ $field->name }}" class="file-name-display"></span>
                                                    <small class="d-block mt-2 text-muted">فرمت‌های مجاز: jpg, png, pdf</small>
                                                </div>
                                                @break

                                        @default
                                            <input type="{{ $field->type }}"
                                                   name="{{ $field->name }}"
                                                   id="field_{{ $field->id }}"
                                                   class="form-control {{ $field->class }}"
                                                   placeholder="{{ $field->placeholder }}"
                                                   value="{{ $field->default_value }}"
                                                {{ $field->required ? 'required' : '' }}>
                                    @endswitch

                                    @if($field->help_text)
                                        <small class="form-text text-muted mt-1">{{ $field->help_text }}</small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="fa fa-paper-plane"></i> {{ $form->button_text }}
                            </button>
                            <div class="form-result mt-3"></div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        var formId = {{ $form->id }};
        var formPosition = '{{ $formPosition }}';
        var formWrapper = document.getElementById('form-wrapper-' + formId);
        var formDescription = document.getElementById('form-description-' + formId);

        // تنظیم موقعیت فرم نسبت به توضیحات
        if (formPosition === 'bottom') {
            // فرم باید زیر توضیحات قرار بگیرد
            if (formDescription && formWrapper) {
                // اگر توضیحات وجود دارد، فرم را بعد از توضیحات قرار بده
                formDescription.parentNode.insertBefore(formWrapper, formDescription.nextSibling);
            }
        } else {
            // حالت top (پیش‌فرض) - فرم بالای توضیحات قرار می‌گیرد
            if (formDescription && formWrapper) {
                // اگر توضیحات وجود دارد، فرم را قبل از توضیحات قرار بده
                formDescription.parentNode.insertBefore(formWrapper, formDescription);
            }
        }

        // نمایش فرم بعد از جابجایی
        if (formWrapper) {
            formWrapper.style.display = 'block';
        }

        // ارسال فرم با AJAX
        var form = document.getElementById('ajax-form-' + formId);

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);
                var submitBtn = this.querySelector('[type="submit"]');
                var originalText = submitBtn.innerHTML;
                var resultDiv = this.querySelector('.form-result');

                submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> در حال ارسال...';
                submitBtn.disabled = true;

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function(response) {
                        if (!response.ok) {
                            return response.text().then(function(text) {
                                console.error('Server response:', text);
                                throw new Error('Server error: ' + response.status);
                            });
                        }
                        return response.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            resultDiv.innerHTML = `
                            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                                <i class="fa fa-check-circle"></i> ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `;
                            form.reset();

                            setTimeout(function() {
                                resultDiv.innerHTML = '';
                            }, 5000);
                        }
                    })
                    .catch(function(error) {
                        console.error('Error:', error);
                        resultDiv.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                            <i class="fa fa-exclamation-circle"></i> خطایی رخ داده است
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;

                        setTimeout(function() {
                            resultDiv.innerHTML = '';
                        }, 5000);
                    })
                    .finally(function() {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    });
            });
        }
    })();
</script>
