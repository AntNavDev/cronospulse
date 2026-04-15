{{--
    Mobile navigation link (inside the collapsed menu).

    Props:
        href   — destination URL
        active — whether this link is currently active (default: false)
--}}
@props(['href', 'active' => false])

<a
    href="{{ $href }}"
    @if($active) aria-current="page" @endif
    {{ $attributes->merge([
        'class' => $active
            ? 'block rounded-md px-3 py-2 text-sm font-medium text-text bg-surface-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)]'
            : 'block rounded-md px-3 py-2 text-sm text-muted transition-colors hover:bg-surface-hover hover:text-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)]',
    ]) }}
>
    {{ $slot }}
</a>