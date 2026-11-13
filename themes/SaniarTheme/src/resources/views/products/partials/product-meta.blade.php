{{-- Open Graph --}}
<meta property="og:title" content="{{ $product->meta_title ?: $product->title }}" />
<meta property="og:type" content="product" />
<meta property="og:url" content="{{ route('front.products.show', ['product' => $product]) }}" />
<meta property="og:description" content="{{ $product->meta_description ?: $product->short_description }}" />
<meta property="og:site_name" content="{{ option('info_site_title', 'او پی شاپ') }}" />

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $product->meta_title ?: $product->title }}">
<meta name="twitter:description" content="{{ $product->meta_description ?: $product->short_description }}">

{{-- Meta Tags --}}
<meta name="description" content="{{ $product->meta_description ?: $product->short_description }}">
<meta name="keywords" content="{{ $product->getTags }}">
<meta name="product_id" content="{{ $product->id }}">

{{-- Canonical --}}
<link rel="canonical" href="{{ route('front.products.show', ['product' => $product]) }}" />

{{-- Open Graph Images --}}
@if ($product->image)
    <meta property="og:image" content="{{ asset($product->image) }}">
    <meta property="og:image:width" content="600"/>
    <meta property="og:image:height" content="600"/>
    <meta name="twitter:image" content="{{ asset($product->image) }}">
@endif

{{-- Product Availability & Price --}}
@if ($product->addableToCart())
    <meta property="product:availability" content="in stock">
    <meta property="product:price:amount" content="{{ $product->getLowestPrice(true) }}">
    <meta property="product:price:currency" content="IRR">
@else
    <meta property="product:availability" content="out of stock">
@endif

{{-- Structured Data (Schema.org) --}}
@php
    $schema = [
        "@context" => "https://schema.org/",
        "@type" => "Product",
        "name" => $product->meta_title ?: $product->title,
        "description" => strip_tags($product->meta_description ?: $product->short_description),
        "sku" => $product->sku ?? $product->id,
        "image" => asset($product->image),
        "brand" => [
            "@type" => "Brand",
            "name" => $product->brand->name ?? 'برند'
        ],
        "offers" => [
            "@type" => "Offer",
            "priceCurrency" => "IRR",
            "price" => $product->getLowestPrice(true),
            "availability" => $product->addableToCart() ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
            "url" => route('front.products.show', ['product' => $product])
        ]
    ];
@endphp

<script type="application/ld+json">
    {!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
