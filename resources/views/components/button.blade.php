{{--
    Themed button with multiple visual variants.

    Props:
        variant — 'primary' | 'secondary' | 'danger' | 'success' | 'ghost' (default: 'primary')
        type    — button type attribute (default: 'button')

    Passes all other attributes through to the <button> element.
--}}
@props(['variant' => 'primary', 'type' => 'button'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)] focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50';

    $variants = [
        'primary'   => 'bg-accent text-[var(--color-text-on-accent)] hover:bg-accent-hover',
        'secondary' => 'border border-border bg-surface-raised text-text hover:bg-surface-hover',
        'danger'    => 'bg-[var(--color-danger)] text-white hover:bg-[var(--color-danger-hover)]',
        'success'   => 'bg-[var(--color-success)] text-white hover:bg-[var(--color-success-hover)]',
        'ghost'     => 'text-muted hover:bg-surface-hover hover:text-text',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['primary']);
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>