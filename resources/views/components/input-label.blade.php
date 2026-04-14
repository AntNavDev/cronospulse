{{--
    Form field label.

    Props:
        for   — the id of the associated input element
        value — label text (alternative to using the slot)
--}}
@props(['for' => null, 'value' => null])

<label
    @if ($for) for="{{ $for }}" @endif
    {{ $attributes->merge(['class' => 'block text-sm font-medium text-text']) }}
>
    {{ $value ?? $slot }}
</label>