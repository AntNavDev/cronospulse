{{--
    Elevated Volcanoes summary card.

    Displays a count badge per elevated alert level (Warning / Watch / Advisory)
    so operators can assess volcano activity at a glance without scrolling the list.

    Props:
        counts — associative array of level => count, e.g.
                 ['WARNING' => 2, 'WATCH' => 5, 'ADVISORY' => 12]

    Usage:
        <x-volcano-elevated-card :counts="$elevatedCounts" />
--}}
@props(['counts'])

@php
    $levels = [
        'WARNING'  => ['label' => 'Warning',  'badgeClass' => 'bg-danger/15 text-danger'],
        'WATCH'    => ['label' => 'Watch',    'badgeClass' => 'bg-warning/15 text-warning'],
        'ADVISORY' => ['label' => 'Advisory', 'badgeClass' => 'bg-info/15 text-info'],
    ];

    $totalElevated = collect($counts)->only(array_keys($levels))->sum();
@endphp

<div class="rounded-xl border border-border bg-surface p-4">
    <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-muted">
            Elevated Volcanoes
        </h2>

        @if ($totalElevated === 0)
            <p class="text-sm text-success">All volcanoes at normal levels.</p>
        @else
            <div class="flex flex-wrap gap-4">
                @foreach ($levels as $level => $config)
                    @php $count = $counts[$level] ?? 0; @endphp
                    <div class="flex items-center gap-1.5">
                        <span class="min-w-[1.5rem] rounded-full px-2 py-0.5 text-center text-xs font-bold {{ $config['badgeClass'] }}">
                            {{ $count }}
                        </span>
                        <span class="text-sm text-muted">{{ $config['label'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>