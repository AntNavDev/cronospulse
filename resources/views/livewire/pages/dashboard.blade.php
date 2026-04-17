<x-slot:seo>
    <x-seo
        title="Dashboard"
        description="Your saved earthquake searches and personal CronosPulse settings."
        :canonical="url('/dashboard')"
    />
</x-slot:seo>

<div class="space-y-10">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-semibold text-text">Welcome back, {{ auth()->user()->name }}</h1>
        <p class="mt-1 text-sm text-muted">Your saved searches and personal data.</p>
    </div>

    {{-- Saved earthquake searches --}}
    <section class="space-y-4">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-semibold text-text">Saved earthquake searches</h2>
            @if ($searches->isNotEmpty())
                <span class="rounded-full border border-border bg-surface-raised px-2 py-0.5 text-xs font-medium text-muted">
                    {{ $searches->count() }} / 20
                </span>
            @endif
        </div>

        @if ($searches->isEmpty())
            <div class="rounded-xl border border-border bg-surface p-8 text-center">
                <p class="text-sm text-muted">No saved searches yet.</p>
                <p class="mt-1 text-sm text-muted">
                    Run a search on
                    <a href="{{ route('quake-watch') }}" class="text-accent hover:underline">QuakeWatch</a>
                    and save it to see it here.
                </p>
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($searches as $search)
                    <div class="flex flex-col gap-4 rounded-xl border border-border bg-surface p-5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-medium text-text">{{ $search->name }}</p>
                                <p class="mt-0.5 text-xs text-muted">
                                    Saved {{ $search->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <button
                                type="button"
                                wire:click="deleteSearch({{ $search->id }})"
                                wire:confirm="Delete '{{ $search->name }}'?"
                                class="shrink-0 rounded-md p-1 text-muted transition hover:bg-surface-hover hover:text-danger focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-border-focus)]"
                                aria-label="Delete search"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <dl class="space-y-1.5 text-sm">
                            <div class="flex justify-between gap-2">
                                <dt class="text-muted">Radius</dt>
                                <dd class="font-mono text-text">{{ number_format($search->radius_km, 0) }} km</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-muted">Min magnitude</dt>
                                <dd class="font-mono text-text">
                                    {{ $search->min_magnitude > 0 ? 'M ' . number_format($search->min_magnitude, 1) . '+' : 'Any' }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-muted">Coordinates</dt>
                                <dd class="font-mono text-xs text-text">
                                    {{ number_format($search->latitude, 4) }}, {{ number_format($search->longitude, 4) }}
                                </dd>
                            </div>
                        </dl>

                        <a
                            href="{{ route('quake-watch', ['lat' => $search->latitude, 'lng' => $search->longitude, 'radius' => $search->radius_km, 'minMag' => $search->min_magnitude]) }}"
                            class="mt-auto inline-flex items-center justify-center rounded-lg border border-border bg-surface-raised px-3 py-2 text-sm font-medium text-text transition hover:bg-surface-hover"
                        >
                            Re-run search →
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

</div>