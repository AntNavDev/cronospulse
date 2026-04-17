{{--
    FloodAlerts component.

    Two panels:
      Left  — national paginated list of all active flood alerts, sorted by severity
              (Extreme → Severe → Moderate → Minor → Unknown). Clicking an alert
              switches the map to that alert's state and highlights its polygon.
              When an alert is selected the detail panel replaces the list; a
              "← Back" button returns to the list.
      Right — Leaflet map for the selected US state (wire:ignore).

    Polling: wire:poll.300s="refreshAlerts" keeps both feeds current every 5 minutes.
    After each map reload Livewire dispatches 'flood-alerts-updated' so the map
    redraws polygons. 'flood-alert-focus' is dispatched on selection to fly the
    map to and highlight the matching polygon.
--}}
<div
    wire:poll.300s="refreshAlerts"
    @flood-alert-selected.window="$wire.selectAlert($event.detail.alertId)"
>

    {{-- Section header --}}
    <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-text">Active Flood Alerts</h2>
            <p class="mt-0.5 text-sm text-muted">
                Current NWS flood watches, warnings, and advisories across the United States.
            </p>
        </div>

        {{-- State selector for the map panel --}}
        <div class="w-52 shrink-0">
            <label for="flood-state" class="mb-1.5 block text-xs font-medium uppercase tracking-wider text-muted">
                Map state
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

    {{-- Loading skeleton: wait for both feeds --}}
    @if ($allAlerts === null || $mapAlerts === null)
        <div class="skeleton h-[560px] w-full rounded-xl"></div>
    @else

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            {{-- Left panel: national alert list or detail --}}
            <div class="flex flex-col">

                @if ($selectedAlert !== null)

                    {{-- Detail panel --}}
                    <div class="flex-1 overflow-y-auto rounded-xl border border-border bg-surface p-5">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <button
                                type="button"
                                wire:click="$set('selectedAlertId', null)"
                                class="flex items-center gap-1 text-xs text-muted hover:text-text focus:outline-none"
                            >
                                ← Back to list
                            </button>
                        </div>

                        <div class="mb-4">
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

                    {{-- National alert list --}}
                    @if ($listError)
                        <div class="mb-3 rounded-lg border border-[var(--color-danger)]/30 bg-[var(--color-danger)]/10 px-4 py-3 text-sm text-danger">
                            {{ $listError }}
                        </div>
                    @endif

                    @if (empty($allAlerts) && ! $listError)
                        <div class="flex h-48 items-center justify-center rounded-xl border border-border bg-surface">
                            <div class="text-center">
                                <p class="text-sm font-medium text-text">No active flood alerts</p>
                                <p class="mt-1 text-xs text-muted">No flood watches, warnings, or advisories are currently active nationally.</p>
                            </div>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-xl border border-border bg-surface">
                            @foreach ($pagedAlerts as $alert)
                                <button
                                    type="button"
                                    wire:click="selectAlertFromList('{{ $alert['id'] }}', '{{ $alert['state_code'] ?? '' }}')"
                                    class="w-full border-b border-border px-4 py-3 text-left last:border-0 hover:bg-surface-hover focus:outline-none"
                                >
                                    <div class="flex items-start gap-2">
                                        <span
                                            class="mt-0.5 inline-block shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold"
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
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>

                        {{-- Pagination --}}
                        @if ($totalPages > 1)
                            <div class="mt-3 flex items-center justify-between text-xs text-muted">
                                <button
                                    type="button"
                                    wire:click="previousPage"
                                    @class(['text-accent hover:underline focus:outline-none' => $listPage > 1, 'cursor-not-allowed opacity-40' => $listPage <= 1])
                                    @disabled($listPage <= 1)
                                >
                                    ← Previous
                                </button>
                                <span>{{ $listPage }} / {{ $totalPages }} ({{ $totalAlerts }} alerts)</span>
                                <button
                                    type="button"
                                    wire:click="nextPage"
                                    @class(['text-accent hover:underline focus:outline-none' => $listPage < $totalPages, 'cursor-not-allowed opacity-40' => $listPage >= $totalPages])
                                    @disabled($listPage >= $totalPages)
                                >
                                    Next →
                                </button>
                            </div>
                        @else
                            <p class="mt-2 text-xs text-muted">{{ $totalAlerts }} alert{{ $totalAlerts !== 1 ? 's' : '' }} nationally</p>
                        @endif
                    @endif

                @endif

            </div>

            {{-- Right panel: state map --}}
            <div class="xl:col-span-2">

                @if ($error)
                    <div class="mb-3 rounded-lg border border-[var(--color-danger)]/30 bg-[var(--color-danger)]/10 px-4 py-3 text-sm text-danger">
                        {{ $error }}
                    </div>
                @endif

                <x-flood-alerts-map
                    id="flood-alerts-map"
                    height="520px"
                    :initial-alerts="$mapAlerts ?? []"
                />

                <div class="mt-2 flex items-center justify-between text-xs text-muted">
                    <span>
                        {{ count($mapAlerts ?? []) }} alert{{ count($mapAlerts ?? []) !== 1 ? 's' : '' }}
                        in {{ \App\Livewire\Hydro\FloodAlerts::US_STATES[$stateCd] ?? strtoupper($stateCd) }}
                    </span>
                    <button
                        type="button"
                        class="cursor-pointer text-accent hover:underline focus:outline-none"
                        @click="window.dispatchEvent(new CustomEvent('flood-alerts-map-reset'))"
                    >
                        Reset map
                    </button>
                </div>

            </div>

        </div>

    @endif

</div>