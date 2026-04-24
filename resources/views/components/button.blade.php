{{--
    Themed button with multiple visual variants and sizes.

    Props:
        variant — 'primary' | 'secondary' | 'danger' | 'success' | 'ghost'
                  | 'link' | 'muted-link'
                  (default: 'primary')
        size    — 'sm' | 'md' | 'lg'  (default: 'md')
                  Ignored by 'link' and 'muted-link' — those variants are
                  shape-less and inherit font size from context.
        type    — button type attribute (default: 'button')

    Passes all other attributes through to the <button> element.

    Variant guide:
        primary    — solid accent fill; primary call-to-action
        secondary  — bordered, surface-raised; secondary actions
        danger     — solid red fill; destructive actions
        success    — solid green fill; confirmations
        ghost      — transparent with hover bg; subtle actions inside panels
        link       — accent-coloured text, no shape; inline text links
        muted-link — muted-coloured text, no shape; dismiss / back / close links

    Documented exceptions where raw <button> is acceptable:
        - Table sort buttons inside <th> elements (structural table controls)
        - Full-width interactive card rows (styled list items, e.g. flood alert rows)
        - Icon-only action buttons (e.g. delete/close with p-1 padding and SVG icon)
          — these have specific sizing constraints that don't map to named sizes
        Use <a> (not <x-button>) for link-styled navigation that changes the URL.
--}}
@props(['variant' => 'primary', 'type' => 'button', 'size' => 'md'])

@php
    // Base: layout, typography weight, transitions, focus ring, disabled state.
    // No padding or border-radius here — those live in $sizes so link/muted-link
    // variants remain shape-less and flow inline with surrounding text.
    $base = 'inline-flex items-center justify-center gap-2 font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)] focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50';

    $sizes = [
        'sm' => 'rounded-md px-2.5 py-1 text-xs',
        'md' => 'rounded-md px-4 py-2 text-sm',
        'lg' => 'rounded-md px-5 py-2.5 text-base',
    ];

    $variants = [
        'primary'    => 'bg-accent text-[var(--color-text-on-accent)] hover:bg-accent-hover',
        'secondary'  => 'border border-border bg-surface-raised text-text hover:bg-surface-hover',
        'danger'     => 'bg-[var(--color-danger)] text-white hover:bg-[var(--color-danger-hover)]',
        'success'    => 'bg-[var(--color-success)] text-white hover:bg-[var(--color-success-hover)]',
        'ghost'      => 'text-muted hover:bg-surface-hover hover:text-text',
        'link'       => 'text-accent hover:underline',
        'muted-link' => 'text-muted hover:text-text',
    ];

    // link and muted-link are shape-less — they carry no padding or border-radius.
    $shapeless = in_array($variant, ['link', 'muted-link'], true);
    $sizeClasses    = $shapeless ? '' : ($sizes[$size] ?? $sizes['md']);
    $variantClasses = $variants[$variant] ?? $variants['primary'];

    $classes = trim($base . ' ' . $sizeClasses . ' ' . $variantClasses);
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>