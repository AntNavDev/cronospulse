{{--
    Themed text input.

    Props:
        type     — input type (default: 'text')
        disabled — disables the input (default: false)

    Passes all other attributes through to the <input> element.
--}}
@props(['type' => 'text', 'disabled' => false])

<input
    type="{{ $type }}"
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'block w-full rounded-md border border-border bg-surface px-3 py-2 text-base text-text placeholder:text-[var(--color-text-placeholder)] focus:border-[var(--color-border-focus)] focus:ring-1 focus:ring-[var(--color-border-focus)] focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 transition-colors',
    ]) }}
/>