{{-- کارت محصول صفحات محصولات (لیست/جستجو/تخفیف‌دار/ویژه) — دلیگیت به پارشال مشترک سانیار --}}
{{-- پارامترها: $product (اجباری)، $withTimer، $hoverBtns --}}
@include('front::partials.product-card', [
    'product' => $product,
    'withTimer' => $withTimer ?? false,
    'hoverBtns' => $hoverBtns ?? true,
])
