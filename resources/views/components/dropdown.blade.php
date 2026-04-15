{{--
    Generic Alpine-powered dropdown.

    Slots:
        trigger — the button/element that opens the dropdown
        content — the menu items (rendered inside the panel)

    Props:
        align — horizontal alignment of the panel: 'left' | 'right' (default: 'right')
        width — panel width class (default: 'w-48')

    Usage:
        <x-dropdown>
            <x-slot:trigger>
                <x-button variant="secondary">Options</x-button>
            </x-slot:trigger>
            <x-slot:content>
                <x-dropdown-link href="#">Edit</x-dropdown-link>
                <x-dropdown-link href="#">Delete</x-dropdown-link>
            </x-slot:content>
        </x-dropdown>
--}}
@props(['align' => 'right', 'width' => 'w-48'])

@php
    $alignClass = $align === 'left' ? 'left-0' : 'right-0';
@endphp

<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">

    {{-- Trigger — the trigger slot button should ideally carry aria-haspopup="true" and :aria-expanded="open" --}}
    <div @click="open = !open" role="none">
        {{ $trigger }}
    </div>

    {{-- Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 mt-1 {{ $width }} {{ $alignClass }} rounded-md border border-border bg-surface py-1 shadow-lg"
        role="menu"
        @click="open = false"
    >
        {{ $content }}
    </div>
</div>