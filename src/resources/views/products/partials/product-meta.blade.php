{{-- SEO متای محصول سانیار — $product، $selected_price (اختیاری) --}}
<meta property="og:title" content="{{ $product->meta_title ?: $product->title }}">
<meta property="og:type" content="product">
<meta property="og:url" content="{{ route('front.products.show', ['product' => $product]) }}">
<meta property="og:description" content="{{ $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: $product->title), 160) }}">
<meta property="og:site_name" content="{{ option('info_site_title', config('front.demo.name')) }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $product->meta_title ?: $product->title }}">
<meta name="twitter:description" content="{{ $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: $product->title), 160) }}">

<meta name="description" content="{{ $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: $product->title), 160) }}">
<meta name="keywords" content="{{ $product->getTags }}">

<link rel="canonical" href="{{ route('front.products.show', ['product' => $product]) }}">

@if ($product->image)
    <meta property="og:image" content="{{ asset($product->image) }}">
    <meta property="og:image:width" content="600">
    <meta property="og:image:height" content="600">
    <meta name="twitter:image" content="{{ asset($product->image) }}">
@endif

@if ($product->addableToCart())
    <meta property="product:availability" content="in stock">
    <meta property="product:price:amount" content="{{ $product->getLowestPrice(true) }}">
    <meta property="product:price:currency" content="IRR">
@else
    <meta property="product:availability" content="out of stock">
@endif

{{-- Structured Data (Schema.org) — محصول + امتیاز و موجودی --}}
@php
    $saniar_schema = [
        "@context" => "https://schema.org/",
        "@type" => "Product",
        "name" => $product->meta_title ?: $product->title,
        "description" => strip_tags($product->meta_description ?: $product->short_description ?: $product->title),
        "sku" => $product->sku ?? $product->product_id ?? $product->id,
        "image" => [asset($product->image ?: 'no-image-product.svg')],
        "url" => route('front.products.show', ['product' => $product]),
        "brand" => [
            "@type" => "Brand",
            "name" => $product->brand->name ?? option('info_site_title', 'سانیار'),
        ],
        "offers" => [
            "@type" => "Offer",
            "priceCurrency" => "IRR",
            "price" => $product->getLowestPrice(true),
            "availability" => $product->addableToCart() ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
            "url" => route('front.products.show', ['product' => $product]),
            "seller" => [
                "@type" => "Organization",
                "name" => option('info_site_title', config('front.demo.name')),
            ],
        ],
    ];

    if ($product->rating && $product->reviews_count) {
        $saniar_schema["aggregateRating"] = [
            "@type" => "AggregateRating",
            "ratingValue" => (float) $product->rating,
            "reviewCount" => (int) $product->reviews_count,
            "bestRating" => 5,
            "worstRating" => 1,
        ];
    }
@endphp
<script type="application/ld+json">
    {!! json_encode($saniar_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
