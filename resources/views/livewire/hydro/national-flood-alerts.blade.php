{{--
    NationalFloodAlerts component.

    Displays the most severe active NWS flood alerts (Severe + Extreme only)
    across the entire United States. Shown at the top of HydroWatch as an
    at-a-glance national picture before the user drills into a state.

    Polling: wire:poll.300s keeps the feed current.
    Hidden entirely when no qualifying alerts are active — no "all clear" noise.
--}}
<div wire:poll.300s="refreshAlerts">

    @if ($error)
        {{-- Silent fail — a banner error would be distracting above the main content --}}
    @elseif ($alerts === null)
        {{-- Loading skeleton --}}
        <div class="mb-8">
            <div class="skeleton mb-2 h-5 w-48 rounded"></div>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @for ($i = 0; $i < 3; $i++)
                    <div class="skeleton h-16 rounded-xl"></div>
                @endfor
            </div>
        </div>
    @elseif (! empty($alerts))
        <div class="mb-8">

            <div class="mb-3 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span
                        class="inline-block h-2 w-2 animate-pulse rounded-full bg-danger"
                        aria-hidden="true"
                    ></span>
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-text">
                        Major Flood Alerts — United States
                    </h2>
                </div>
                @if ($totalCount > count($alerts))
                    <span class="text-xs text-muted">
                        Showing {{ count($alerts) }} of {{ $totalCount }} active
                    </span>
                @else
                    <span class="text-xs text-muted">
                        {{ $totalCount }} active {{ $totalCount === 1 ? 'alert' : 'alerts' }}
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($alerts as $alert)
                    <div class="flex items-start gap-3 rounded-xl border border-border bg-surface p-3">

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
                            <p class="truncate text-xs font-semibold text-text">{{ $alert['event'] }}</p>
                            <p class="mt-0.5 truncate text-xs text-muted">{{ $alert['area_desc'] }}</p>
                            @if ($alert['formatted_expires'])
                                <p class="mt-0.5 text-xs text-muted">
                                    Expires {{ $alert['formatted_expires'] }}
                                </p>
                            @endif
                        </div>

                    </div>
                @endforeach
            </div>

        </div>
    @endif

</div>