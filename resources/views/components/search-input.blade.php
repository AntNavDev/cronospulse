{{--
    Text input with a leading search icon.

    Passes all attributes through to the underlying <input> element.
    Common usage: wire:model, placeholder, wire:keydown.enter, etc.
--}}
<div class="relative">
    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0Z" />
        </svg>
    </span>
    <input
        type="search"
        {{ $attributes->merge([
            'class' => 'block w-full rounded-md border border-border bg-surface py-2 pl-9 pr-3 text-base text-text placeholder:text-[var(--color-text-placeholder)] focus:border-[var(--color-border-focus)] focus:ring-1 focus:ring-[var(--color-border-focus)] focus:outline-none transition-colors',
        ]) }}
    />
</div>