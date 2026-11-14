{{-- ویجت سوالات متداول — آکاردئون سانیار --}}
{{-- فاز ۱۶۴ — همهٔ تنظیمات خود ابزارک اعمال می‌شوند:
     title/text (هدر)، number (تعداد)، block_color/text_color (رنگ هدر)،
     width (عرض کادر) و layout (هدر بالا یا کنار) --}}
@php
    $variables = get_widget($widget);
    $faqs = $variables['faqs'];

    $faq_text       = $widget->option('text');
    $faq_block_color = $widget->option('block_color', '#4f46e5');
    $faq_text_color  = $widget->option('text_color', '#ffffff');
    $faq_width       = str_replace(
        ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
        ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
        (string) $widget->option('width', '90%')
    );
    $faq_layout = $widget->option('layout', 'top') === 'side' ? 'side' : 'top';
@endphp

@if(count($faqs))
    <div class="py-20">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <div class="faq-parent faq-layout-{{ $faq_layout }}"
                         style="max-width: {{ $faq_width }}; --faq-block-color: {{ $faq_block_color }}; --faq-text-color: {{ $faq_text_color }};">
                        <div class="faq-title">
                            <div class="text-center">
                                <i class="bi bi-patch-question font-40"></i>
                            </div>
                            <h4 class="text-center fw-semibold my-3">{{ $widget->title ?: 'سوالات پرتکرار شما' }}</h4>
                            @if($faq_text)
                                <p class="text-center font-14 mb-3">{{ $faq_text }}</p>
                            @endif
                        </div>

                        <div class="accordion-style" id="faqAccordion{{ $widget->id }}">
                            @foreach($faqs as $index => $faq)
                                <div class="card mb-3">
                                    <div class="card-header" id="faqHeading{{ $widget->id }}-{{ $index }}">
                                        <h5 class="mb-0">
                                            <button aria-controls="faqCollapse{{ $widget->id }}-{{ $index }}"
                                                    aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                                                    class="btn btn-link {{ $index === 0 ? '' : 'collapsed' }}"
                                                    data-bs-target="#faqCollapse{{ $widget->id }}-{{ $index }}"
                                                    data-bs-toggle="collapse">
                                                {{ $faq->question }}
                                            </button>
                                        </h5>
                                    </div>
                                    <div aria-labelledby="faqHeading{{ $widget->id }}-{{ $index }}"
                                         class="collapse {{ $index === 0 ? 'show' : '' }}"
                                         data-bs-parent="#faqAccordion{{ $widget->id }}"
                                         id="faqCollapse{{ $widget->id }}-{{ $index }}">
                                        <div class="card-body">
                                            {{ $faq->answer }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
