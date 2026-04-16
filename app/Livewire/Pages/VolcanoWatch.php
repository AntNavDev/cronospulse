<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Data\VolcanoData;
use App\Services\VolcanoService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('VolcanoWatch — US Volcano Monitoring | CronosPulse')]
class VolcanoWatch extends Component
{
    use WithPagination;

    protected VolcanoService $volcanoService;

    /**
     * Resolve the VolcanoService on every component lifecycle request.
     *
     * boot() is used instead of #[Inject] because it runs reliably on both
     * initial render and Livewire hydration, avoiding "typed property must not
     * be accessed before initialization" errors.
     */
    public function boot(VolcanoService $volcanoService): void
    {
        $this->volcanoService = $volcanoService;
    }

    /**
     * All volcano records returned by the API. Null until mount() completes.
     *
     * @var list<array<string, mixed>>|null
     */
    public ?array $volcanoes = null;

    /**
     * Error message shown when the API call fails.
     */
    public ?string $error = null;

    /**
     * Active region filter. Empty string means no filter applied.
     */
    public string $regionFilter = '';

    /**
     * Active alert level filter. Empty string means no filter applied.
     */
    public string $alertFilter = '';

    /**
     * Name search query. Filters by partial match on volcano name (case-insensitive).
     * Debounced via wire:model.live.debounce.300ms in the view.
     */
    public string $searchQuery = '';

    /**
     * Number of results to show per page. 0 means show all.
     */
    public int $perPage = 10;

    /**
     * Fetch all volcano data from the USGS API on initial page load.
     */
    public function mount(): void
    {
        $this->loadVolcanoes();
    }

    /**
     * Re-dispatch filtered volcanoes to the map when the region filter changes.
     * Also resets pagination to page 1.
     */
    public function updatedRegionFilter(): void
    {
        $this->resetPage();
        $this->dispatch('volcanoes-updated', volcanoes: $this->filteredVolcanoes());
    }

    /**
     * Re-dispatch filtered volcanoes to the map when the alert level filter changes.
     * Also resets pagination to page 1.
     */
    public function updatedAlertFilter(): void
    {
        $this->resetPage();
        $this->dispatch('volcanoes-updated', volcanoes: $this->filteredVolcanoes());
    }

    /**
     * Re-dispatch filtered volcanoes to the map when the name search query changes.
     * Also resets pagination to page 1.
     */
    public function updatedSearchQuery(): void
    {
        $this->resetPage();
        $this->dispatch('volcanoes-updated', volcanoes: $this->filteredVolcanoes());
    }

    /**
     * Clear all filters and dispatch the full volcano list to the map.
     *
     * Called by the Reset map button so all state resets in a single round-trip
     * rather than three separate property-set requests.
     */
    public function resetFilters(): void
    {
        $this->searchQuery  = '';
        $this->regionFilter = '';
        $this->alertFilter  = '';
        $this->resetPage();
        $this->dispatch('volcanoes-updated', volcanoes: $this->filteredVolcanoes());
    }

    /**
     * Update the per-page count and return to the first page.
     *
     * Called by the per-page selector component via wire:change.
     */
    public function setPerPage(int $value): void
    {
        $this->perPage = $value;
        $this->resetPage();
    }

    /**
     * Render the VolcanoWatch page.
     *
     * Builds a LengthAwarePaginator from the filtered volcano list so the
     * existing pagination-bar component can be reused without a database query.
     * Alert breakdown and elevated counts are derived from the full (unfiltered)
     * list so they always reflect the total dataset.
     */
    public function render(): View
    {
        $all      = collect($this->volcanoes ?? []);
        $filtered = collect($this->filteredVolcanoes());

        $regions = $all
            ->pluck('region')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $perPage   = $this->perPage > 0 ? $this->perPage : $filtered->count();
        $page      = $this->getPage();
        $paginator = new LengthAwarePaginator(
            items: $filtered->slice(($page - 1) * $perPage, $perPage)->values()->all(),
            total: $filtered->count(),
            perPage: $perPage,
            currentPage: $page,
        );

        // Alert level breakdown from the full list — drives the pie chart.
        $alertLevelOrder   = ['WARNING', 'WATCH', 'ADVISORY', 'NORMAL'];
        $alertColorVarMap  = [
            'WARNING'  => '--color-danger',
            'WATCH'    => '--color-warning',
            'ADVISORY' => '--color-info',
            'NORMAL'   => '--color-success',
        ];
        $alertCounts       = $all->countBy('alert_level');
        $chartLabels       = [];
        $chartData         = [];
        $chartColors       = [];

        foreach ($alertLevelOrder as $level) {
            $count = $alertCounts[$level] ?? 0;

            if ($count > 0) {
                $chartLabels[] = ucfirst(strtolower($level));
                $chartData[]   = $count;
                $chartColors[] = $alertColorVarMap[$level];
            }
        }

        // Elevated counts (Warning/Watch/Advisory) — drives the summary card.
        $elevatedCounts = [
            'WARNING'  => $alertCounts['WARNING']  ?? 0,
            'WATCH'    => $alertCounts['WATCH']     ?? 0,
            'ADVISORY' => $alertCounts['ADVISORY']  ?? 0,
        ];

        return view('livewire.pages.volcano-watch', [
            'filteredCount'  => $filtered->count(),
            'paginator'      => $paginator,
            'regions'        => $regions,
            'elevatedCounts' => $elevatedCounts,
            'chartLabels'    => $chartLabels,
            'chartData'      => $chartData,
            'chartColors'    => $chartColors,
        ]);
    }

    /**
     * Fetch all USGS volcano records via the service and store as arrays for Livewire state.
     */
    private function loadVolcanoes(): void
    {
        try {
            $this->volcanoes = $this->volcanoService->all()
                ->map(fn (VolcanoData $v) => $v->toArray())
                ->toArray();
        } catch (Throwable) {
            $this->error = 'Failed to reach the USGS Volcano API. Please check your connection and try again.';
        }
    }

    /**
     * Apply the active region, alert level, and name search filters to the full volcano list.
     *
     * @return list<array<string, mixed>>
     */
    private function filteredVolcanoes(): array
    {
        if ($this->volcanoes === null) {
            return [];
        }

        return collect($this->volcanoes)
            ->when($this->regionFilter !== '', fn ($c) => $c->where('region', $this->regionFilter))
            ->when($this->alertFilter !== '', fn ($c) => $c->where('alert_level', $this->alertFilter))
            ->when(
                $this->searchQuery !== '',
                fn ($c) => $c->filter(
                    fn ($v) => str_contains(strtolower($v['name']), strtolower($this->searchQuery)),
                ),
            )
            ->values()
            ->toArray();
    }

}
