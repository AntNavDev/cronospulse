{{--
    Form field label.

    Props:
        for     — the id of the associated input element
        value   — label text (alternative to using the slot)
        compact — renders as xs/uppercase/muted; use for panel section headers
                  above compact controls (e.g. state selectors in map panels)

    Usage:
        <x-input-label for="email" value="Email address" />
        <x-input-label for="email">Email address</x-input-label>
        <x-input-label for="state" compact class="mb-1.5">State</x-input-label>
--}}
@props(['for' => null, 'value' => null, 'compact' => false])

@php
    $classes = $compact
        ? 'block text-xs font-medium uppercase tracking-wider text-muted'
        : 'block text-sm font-medium text-text';
@endphp

<label
    @if ($for) for="{{ $for }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $value ?? $slot }}
</label>