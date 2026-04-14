{{--
    Badge / status pill for USGS event types and semantic states.

    Props:
        variant — 'eq' | 'flood' | 'vol' | 'geo' | 'neutral'
                  | 'success' | 'danger' | 'warning' | 'info'
                  (default: 'neutral')

    Usage:
        <x-label variant="eq">Earthquake</x-label>
        <x-label variant="success">Active</x-label>
--}}
@props(['variant' => 'neutral'])

@php
    $base = 'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium';

    $variants = [
        /* USGS domain types */
        'eq'      => 'bg-[var(--color-badge-eq-bg)] text-[var(--color-badge-eq-text)] border-[var(--color-badge-eq-border)]',
        'flood'   => 'bg-[var(--color-badge-flood-bg)] text-[var(--color-badge-flood-text)] border-[var(--color-badge-flood-border)]',
        'vol'     => 'bg-[var(--color-badge-vol-bg)] text-[var(--color-badge-vol-text)] border-[var(--color-badge-vol-border)]',
        'geo'     => 'bg-[var(--color-badge-geo-bg)] text-[var(--color-badge-geo-text)] border-[var(--color-badge-geo-border)]',
        'neutral' => 'bg-[var(--color-badge-neutral-bg)] text-[var(--color-badge-neutral-text)] border-border',
        /* Semantic states */
        'success' => 'bg-accent-subtle text-[var(--color-success)] border-[var(--color-success)]',
        'danger'  => 'bg-[var(--color-badge-eq-bg)] text-[var(--color-danger)] border-[var(--color-danger)]',
        'warning' => 'bg-[var(--color-badge-vol-bg)] text-[var(--color-warning)] border-[var(--color-warning)]',
        'info'    => 'bg-[var(--color-badge-flood-bg)] text-[var(--color-info)] border-[var(--color-info)]',
    ];

    $classes = $base . ' ' . ($variants[$variant] ?? $variants['neutral']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>