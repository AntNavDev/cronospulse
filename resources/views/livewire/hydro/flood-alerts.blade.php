{{--
    FloodAlerts component.

    Polling: wire:poll.300s="refreshAlerts" keeps the NWS feed current every 5 minutes.
    The map is wire:ignore — Alpine's Leaflet instance survives Livewire re-renders.
    After each load, Livewire dispatches 'flood-alerts-updated' so the map redraws polygons.

    Alert selection: clicking a polygon dispatches 'flood-alert-selected'. The root div
    listens for it and calls $wire.selectAlert() to show the detail panel.
    Clicking a list row dispatches 'flood-alert-focus' via $wire.selectAlert(), which
    causes the map to fly to that polygon and open its popup.
--}}
<div
    wire:poll.300s="refreshAlerts"
    @flood-alert-selected.window="$wire.selectAlert($event.detail.alertId)"
>

    {{-- Section header + state selector --}}
    <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-text">Active Flood Alerts</h2>
            <p class="mt-0.5 text-sm text-muted">
                Current NWS flood watches, warnings, and advisories. Click a polygon or row for details.
            </p>
        </div>

        <div class="w-52">
            <label for="flood-state" class="mb-1.5 block text-xs font-medium uppercase tracking-wider text-muted">
                State / Territory
            </label>
            <select
                id="flood-state"
                wire:model.live="stateCd"
                class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-text focus:border-accent focus:outline-none focus:ring-2 focus:ring-[var(--color-accent)]/20"
            >
                @foreach (\App\Livewire\Hydro\FloodAlerts::US_STATES as $code => $name)
                    <option value="{{ $code }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Error --}}
    @if ($error)
        <div class="mb-4 rounded-lg border border-[var(--color-danger)]/30 bg-[var(--color-danger)]/10 px-4 py-3 text-sm text-danger">
            {{ $error }}
        </div>
    @endif

    {{-- Loading skeleton --}}
    @if ($alerts === null && ! $error)
        <div class="skeleton h-[500px] w-full rounded-xl"></div>
    @else

        @if (empty($alerts) && ! $error)
            {{-- No active flood alerts --}}
            <div class="flex h-48 items-center justify-center rounded-xl border border-border bg-surface">
                <div class="text-center">
                    <p class="text-sm font-medium text-text">No active flood alerts</p>
                    <p class="mt-1 text-xs text-muted">
                        No flood watches, warnings, or advisories for
                        {{ \App\Livewire\Hydro\FloodAlerts::US_STATES[$stateCd] ?? strtoupper($stateCd) }}.
                    </p>
                </div>
            </div>
        @else

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

                {{-- Map (spans 2 of 3 columns on xl) --}}
                <div class="xl:col-span-2">
                    <x-flood-alerts-map
                        id="flood-alerts-map"
                        height="520px"
                        :initial-alerts="$alerts ?? []"
                    />

                    <div class="mt-2 flex items-center justify-between text-xs text-muted">
                        <span>{{ count($alerts ?? []) }} active alert{{ count($alerts ?? []) !== 1 ? 's' : '' }}</span>
                        <button
                            type="button"
                            class="cursor-pointer text-accent hover:underline focus:outline-none"
                            @click="window.dispatchEvent(new CustomEvent('flood-alerts-map-reset'))"
                        >
                            Reset map
                        </button>
                    </div>
                </div>

                {{-- Alert list / detail panel --}}
                <div class="flex flex-col gap-3">

                    @if ($selectedAlert !== null)
                        {{-- Detail panel --}}
                        <div class="flex-1 overflow-y-auto rounded-xl border border-border bg-surface p-5">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <span
                                        class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                        style="
                                            background: {{ $selectedAlert['severity_badge']['bg'] }};
                                            color: {{ $selectedAlert['severity_badge']['text'] }};
                                            border: 1px solid {{ $selectedAlert['severity_badge']['border'] }};
                                        "
                                    >
                                        {{ $selectedAlert['severity'] }}
                                    </span>
                                    <h3 class="mt-1.5 text-sm font-bold text-text">{{ $selectedAlert['event'] }}</h3>
                                </div>
                                <button
                                    type="button"
                                    wire:click="$set('selectedAlertId', null)"
                                    class="shrink-0 text-xs text-muted hover:text-text focus:outline-none"
                                >
                                    ✕ Close
                                </button>
                            </div>

                            <p class="mb-3 text-xs font-medium text-muted">{{ $selectedAlert['area_desc'] }}</p>

                            @if ($selectedAlert['formatted_effective'] || $selectedAlert['formatted_expires'])
                                <div class="mb-4 flex gap-4 text-xs text-muted">
                                    @if ($selectedAlert['formatted_effective'])
                                        <div>
                                            <span class="font-medium text-text">Effective</span><br>
                                            {{ $selectedAlert['formatted_effective'] }}
                                        </div>
                                    @endif
                                    @if ($selectedAlert['formatted_expires'])
                                        <div>
                                            <span class="font-medium text-text">Expires</span><br>
                                            {{ $selectedAlert['formatted_expires'] }}
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="mb-4 rounded-lg bg-surface-sunken p-3">
                                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Headline</p>
                                <p class="mt-1 text-sm text-text">{{ $selectedAlert['headline'] }}</p>
                            </div>

                            @if ($selectedAlert['instruction'])
                                <div class="mb-4 rounded-lg border border-[var(--color-warning)]/30 bg-[var(--color-warning)]/10 p-3">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-warning">What to do</p>
                                    <p class="mt-1 whitespace-pre-line text-xs text-text">{{ $selectedAlert['instruction'] }}</p>
                                </div>
                            @endif

                            <div>
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-muted">Details</p>
                                <p class="whitespace-pre-line text-xs leading-relaxed text-muted">{{ $selectedAlert['description'] }}</p>
                            </div>
                        </div>

                    @else
                        {{-- Alert list --}}
                        <div class="h-[520px] overflow-y-auto rounded-xl border border-border bg-surface">
                            @foreach (($alerts ?? []) as $alert)
                                <button
                                    type="button"
                                    wire:click="selectAlert('{{ $alert['id'] }}')"
                                    class="w-full border-b border-border px-4 py-3 text-left last:border-0 hover:bg-surface-hover focus:outline-none"
                                >
                                    <div class="flex items-start gap-2">
                                        <span
                                            class="mt-0.5 shrink-0 inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                                            style="
                                                background: {{ $alert['severity_badge']['bg'] }};
                                                color: {{ $alert['severity_badge']['text'] }};
                                                border: 1px solid {{ $alert['severity_badge']['border'] }};
                                            "
                                        >
                                            {{ $alert['severity'] }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-text">{{ $alert['event'] }}</p>
                                            <p class="mt-0.5 truncate text-xs text-muted">{{ $alert['area_desc'] }}</p>
                                            @if ($alert['formatted_expires'])
                                                <p class="mt-0.5 text-xs text-muted">Expires {{ $alert['formatted_expires'] }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                </div>

            </div>

        @endif

    @endif

</div>