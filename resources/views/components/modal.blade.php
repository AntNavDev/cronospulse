{{--
    Base modal powered by Alpine.js.

    Open / close via custom window events:
        $dispatch('open-modal', 'modal-id')
        $dispatch('close-modal', 'modal-id')

    Or wire up the `show` prop directly from a parent Alpine component.

    Props:
        id       — unique identifier used with the open/close-modal events
        maxWidth — Tailwind max-width class for the panel (default: 'max-w-lg')

    Slots:
        title   — modal heading
        default — modal body content
        footer  — action buttons (right-aligned by default)

    Usage:
        <x-modal id="confirm-delete" max-width="max-w-md">
            <x-slot:title>Delete station</x-slot:title>
            <p>Are you sure?</p>
            <x-slot:footer>
                <x-button variant="secondary" @click="$dispatch('close-modal', 'confirm-delete')">Cancel</x-button>
                <x-button variant="danger">Delete</x-button>
            </x-slot:footer>
        </x-modal>
--}}
@props(['id', 'maxWidth' => 'max-w-lg'])

<div
    x-data="{ show: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $id }}') { show = true; $nextTick(() => $refs.panel.focus()); }"
    x-on:close-modal.window="$event.detail === '{{ $id }}' && (show = false)"
    x-show="show"
    class="fixed inset-0 z-50 flex items-center justify-center"
    role="dialog"
    aria-modal="true"
    @if($title ?? false) aria-labelledby="{{ $id }}-title" @endif
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/60"
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="show = false"
        aria-hidden="true"
    ></div>

    {{-- Panel --}}
    <div
        x-ref="panel"
        tabindex="-1"
        class="relative w-full {{ $maxWidth }} mx-4 rounded-xl border border-border bg-surface shadow-xl focus:outline-none"
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        @keydown.escape.window="show = false"
    >
        {{-- Header --}}
        @if ($title ?? false)
            <div class="flex items-center justify-between border-b border-border px-6 py-4">
                <h2 id="{{ $id }}-title" class="text-base font-semibold text-text">{{ $title }}</h2>
                <button
                    type="button"
                    @click="show = false"
                    class="rounded-md p-1 text-muted transition-colors hover:bg-surface-hover hover:text-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)]"
                    aria-label="Close"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        {{-- Body --}}
        <div class="px-6 py-5 text-sm text-text">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        @if ($footer ?? false)
            <div class="flex justify-end gap-3 border-t border-border px-6 py-4">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>