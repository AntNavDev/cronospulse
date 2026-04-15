{{--
    Link or button rendered inside an <x-dropdown> panel.

    When an href is provided it renders as an <a>. When used inside a <form>
    (e.g. for logout), omit the href and wrap in a form with a submit button instead.

    Props:
        href — destination URL (optional; omit to render as a <button>)
--}}
@props(['href' => null])

@if ($href)
    <a
        href="{{ $href }}"
        role="menuitem"
        {{ $attributes->merge([
            'class' => 'block w-full px-4 py-2 text-left text-sm text-text transition-colors hover:bg-surface-hover focus-visible:outline-none focus-visible:bg-surface-hover',
        ]) }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="button"
        role="menuitem"
        {{ $attributes->merge([
            'class' => 'block w-full px-4 py-2 text-left text-sm text-text transition-colors hover:bg-surface-hover focus-visible:outline-none focus-visible:bg-surface-hover',
        ]) }}
    >
        {{ $slot }}
    </button>
@endif