{{--
    Desktop navigation link.

    Props:
        href   — destination URL
        active — whether this link is currently active (default: false)
--}}
@props(['href', 'active' => false])

<a
    href="{{ $href }}"
    {{ $attributes->merge([
        'class' => $active
            ? 'rounded-md px-3 py-1.5 text-sm font-medium text-text bg-surface-hover'
            : 'rounded-md px-3 py-1.5 text-sm text-muted transition-colors hover:bg-surface-hover hover:text-text',
    ]) }}
>
    {{ $slot }}
</a>