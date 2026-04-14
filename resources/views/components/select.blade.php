{{--
    Themed select element.

    Props:
        disabled — disables the select (default: false)

    Passes all other attributes through to the <select> element.
    Used by <x-per-page-selector> and any other dropdowns backed by <select>.
--}}
@props(['disabled' => false])

<select
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => 'block rounded-md border border-border bg-surface px-3 py-2 text-sm text-text focus:border-[var(--color-border-focus)] focus:ring-1 focus:ring-[var(--color-border-focus)] focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 transition-colors',
    ]) }}
>
    {{ $slot }}
</select>