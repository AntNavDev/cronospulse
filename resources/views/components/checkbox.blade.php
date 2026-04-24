{{--
    Themed checkbox with an inline label.

    The <label> wraps the <input> so clicking the label text toggles the checkbox.
    All extra attributes (name, value, wire:model, etc.)
    are passed through to the underlying <input> element.

    Props:
        disabled — disables the input (default: false)

    Usage:
        <x-checkbox name="remember">Remember me</x-checkbox>
        <x-checkbox wire:model="agreed">I agree to the terms</x-checkbox>
--}}
@props(['disabled' => false])

<label class="flex cursor-pointer items-center gap-2 text-sm text-muted">
    <input
        type="checkbox"
        @disabled($disabled)
        {{ $attributes->merge(['class' => 'rounded border-border text-accent focus-visible:ring-[var(--color-border-focus)]']) }}
    />
    {{ $slot }}
</label>