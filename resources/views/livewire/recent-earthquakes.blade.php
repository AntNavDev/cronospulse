<section wire:poll.300s class="space-y-6">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <x-label variant="eq">Seismic</x-label>
            <h2 class="text-2xl font-semibold text-text">Recent Seismic Activity</h2>
        </div>
        <a href="{{ route('quake-watch') }}" class="text-sm text-accent hover:underline">
            Search QuakeWatch →
        </a>
    </div>

    @if ($earthquakes->isEmpty())
        <div class="rounded-xl border border-border bg-surface px-6 py-10 text-center">
            <p class="text-sm text-muted">No events yet — data will appear after the first scheduled ingestion.</p>
        </div>
    @else
        <div class="divide-y divide-border overflow-hidden rounded-xl border border-border bg-surface">
            @foreach ($earthquakes as $eq)
                <div class="flex items-center gap-3 px-5 py-3.5 transition-colors hover:bg-surface-hover sm:gap-4">

                    {{-- Magnitude --}}
                    <span class="w-12 shrink-0 text-sm font-bold {{ $eq->magClass() }}">
                        M{{ number_format($eq->magnitude, 1) }}
                    </span>

                    {{-- Place --}}
                    <span class="min-w-0 flex-1 truncate text-sm text-text">
                        {{ $eq->place }}
                    </span>

                    {{-- Depth — hidden on small screens --}}
                    <span class="hidden shrink-0 text-xs text-muted sm:block">
                        {{ number_format($eq->depth_km, 0) }}&thinsp;km deep
                    </span>

                    {{-- PAGER alert badge --}}
                    @if ($eq->alert)
                        <x-label :variant="$eq->alertLabelVariant()">
                            PAGER: {{ ucfirst($eq->alert) }}
                        </x-label>
                    @endif

                    {{-- Time --}}
                    <span class="shrink-0 text-xs text-muted">
                        {{ $eq->occurred_at->diffForHumans() }}
                    </span>

                    {{-- USGS link --}}
                    @if ($eq->url)
                        <a href="{{ $eq->url }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="shrink-0 text-xs text-accent hover:underline">
                            USGS&thinsp;↗
                        </a>
                    @endif

                </div>
            @endforeach
        </div>

        @if ($lastIngestion)
            <p class="text-right text-xs text-muted">
                Last updated {{ $lastIngestion->diffForHumans() }}
            </p>
        @endif
    @endif

</section>