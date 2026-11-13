@php
    $variables = get_widget($widget);
    $faqs = $variables['faqs'];

    // دریافت تنظیمات با مقادیر پیش‌فرض
    $header_title = $widget->option('title');
    $header_text = $widget->option('text');
    $block_color = $widget->option('block_color') ?? '#4f46e5';
    $text_color = $widget->option('text_color') ?? '#ffffff';
    $width = $widget->option('width') ?? '90%';
    $layout = $widget->option('layout') ?? 'top';

    // تبدیل اعداد فارسی به انگلیسی برای درصد
    $width = str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $width);
@endphp

@if(count($faqs))
    <!-- Start FAQ Widget -->

    <div class="col-lg-12 col-md-12 col-xs-12 pull-right">
        <div class="faq-widget-container layout-{{ $layout }}" style="width: {{ $width }}; --primary-color: {{ $block_color }}; --text-on-primary: {{ $text_color }};">

            <div class="faq-widget-header">
                <div class="faq-header-icon">
                    <i class="fa fa-question-circle"></i>
                </div>
                <div class="faq-header-titles">
                    <h3>{{ $header_title }}</h3>
                    <p>{{$header_text}}</p>
                </div>
            </div>

            <div class="faq-widget-body">
                @foreach($faqs as $index => $faq)
                    <div class="faq-accordion-item ">
                        <button class="faq-accordion-btn" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}">
                            <span class="faq-question-text">{{ $faq->question }}</span>
                            <span class="faq-icon-wrapper">
                            <i class="fa fa-plus"></i>
                        </span>
                        </button>
                        <div class="faq-accordion-content" {{ $index === 0 ? 'style="max-height: 500px; padding: 0 24px 20px 24px;"' : '' }}>
                            <p>{{ $faq->answer }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>


    @push('scripts')
        <script>

        </script>
    @endpush
@endif
<!-- End FAQ Widget -->
