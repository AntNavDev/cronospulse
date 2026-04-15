{{--
    SEO meta block — renders <title>, description, canonical, Open Graph, and Twitter card tags.

    Props:
        title       — page title; appended with " | CronosPulse". Null → just "CronosPulse".
        description — meta description; truncated to 160 characters. Null → tag omitted.
        canonical   — canonical URL; defaults to the current request URL.
        image       — absolute URL for og:image; optional. Omitted when not provided.

    Usage:
        <x-seo
            title="About"
            description="An open data visualisation tool built on USGS public APIs."
            :canonical="url('/about')"
        />

    Wire into the layout via <x-slot:seo> from any Livewire page view:
        <x-slot:seo>
            <x-seo title="About" description="..." :canonical="url('/about')" />
        </x-slot:seo>
--}}
@props([
    'title'       => null,
    'description' => null,
    'canonical'   => null,
    'image'       => null,
])

@php
    $fullTitle   = $title ? $title . ' | CronosPulse' : 'CronosPulse';
    $canonical ??= url()->current();
    $description = $description
        ? \Illuminate\Support\Str::limit(strip_tags($description), 160, '')
        : null;
@endphp

<title>{{ $fullTitle }}</title>

@if ($description)
    <meta name="description" content="{{ $description }}">
@endif

<link rel="canonical" href="{{ $canonical }}">

<meta property="og:type"        content="website">
<meta property="og:site_name"   content="CronosPulse">
<meta property="og:title"       content="{{ $fullTitle }}">
<meta property="og:url"         content="{{ $canonical }}">
@if ($description)
    <meta property="og:description" content="{{ $description }}">
@endif
@if ($image)
    <meta property="og:image"       content="{{ $image }}">
@endif

<meta name="twitter:card"  content="{{ $image ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $fullTitle }}">
@if ($description)
    <meta name="twitter:description" content="{{ $description }}">
@endif
@if ($image)
    <meta name="twitter:image" content="{{ $image }}">
@endif