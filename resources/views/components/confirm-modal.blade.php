{{--
    Confirmation modal — wraps <x-modal> with a standard cancel + confirm pattern.

    Open via:  $dispatch('open-modal', 'modal-id')
    Close via: $dispatch('close-modal', 'modal-id')

    Props:
        id             — passed to <x-modal>
        maxWidth       — passed to <x-modal> (default: 'max-w-md')
        confirmVariant — button variant for the confirm action (default: 'danger')
        confirmText    — confirm button label (default: 'Confirm')
        cancelText     — cancel button label (default: 'Cancel')
        confirmAction  — Alpine expression or Livewire call run on confirm click

    Slots:
        title   — modal heading
        default — body content / warning message

    Usage:
        <x-confirm-modal
            id="delete-station"
            confirm-text="Delete"
            confirm-action="$wire.deleteStation(stationId)"
        >
            <x-slot:title>Remove station</x-slot:title>
            <p>This will permanently remove the station from your saved list.</p>
        </x-confirm-modal>
--}}
@props([
    'id',
    'maxWidth'       => 'max-w-md',
    'confirmVariant' => 'danger',
    'confirmText'    => 'Confirm',
    'cancelText'     => 'Cancel',
    'confirmAction'  => '',
])

<x-modal :id="$id" :max-width="$maxWidth">
    <x-slot:title>{{ $title ?? 'Are you sure?' }}</x-slot:title>

    {{ $slot }}

    <x-slot:footer>
        <x-button
            variant="secondary"
            @click="$dispatch('close-modal', '{{ $id }}')"
        >{{ $cancelText }}</x-button>
        <x-button
            variant="{{ $confirmVariant }}"
            @click="{{ $confirmAction }}; $dispatch('close-modal', '{{ $id }}')"
        >{{ $confirmText }}</x-button>
    </x-slot:footer>
</x-modal>