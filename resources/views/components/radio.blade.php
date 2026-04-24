{{--
    Themed radio button with an inline label.

    The <label> wraps the <input> so clicking the label text selects the radio.
    All extra attributes (x-bind:checked, @change, wire:model, name, value, etc.)
    are passed through to the underlying <input> element.

    Props:
        disabled — disables the input (default: false)

    Usage:
        <x-radio name="unit" x-bind:checked="unit === 'km'" @change="switchUnit('km')">Kilometers</x-radio>

    NOTE: Use x-bind:checked (not the :checked shorthand) for Alpine bindings.
    Blade evaluates :attr as PHP on component attributes, causing "Undefined constant"
    errors for Alpine JS variables. x-bind:checked bypasses this.
--}}
@props(['disabled' => false])

<label class="flex cursor-pointer items-center gap-2 text-sm text-text">
    <input
        type="radio"
        @disabled($disabled)
        {{ $attributes->merge(['class' => 'accent-[var(--color-accent)]']) }}
    />
    {{ $slot }}
</label>