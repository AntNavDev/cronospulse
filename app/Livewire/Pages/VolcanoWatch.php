<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Services\VolcanoService;
use Illuminate\View\View;
use Livewire\Attributes\Inject;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('VolcanoWatch — US Volcano Monitoring | CronosPulse')]
class VolcanoWatch extends Component
{
    #[Inject]
    protected VolcanoService $volcanoService;

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
     * Fetch all volcano data from the USGS API on initial page load.
     */
    public function mount(): void
    {
        $this->loadVolcanoes();
    }

    /**
     * Re-dispatch filtered volcanoes to the map when the region filter changes.
     */
    public function updatedRegionFilter(): void
    {
        $this->dispatch('volcanoes-updated', volcanoes: $this->filteredVolcanoes());
    }

    /**
     * Re-dispatch filtered volcanoes to the map when the alert level filter changes.
     */
    public function updatedAlertFilter(): void
    {
        $this->dispatch('volcanoes-updated', volcanoes: $this->filteredVolcanoes());
    }

    /**
     * Render the VolcanoWatch page.
     *
     * Passes the filtered volcano list and distinct region names to the view.
     * Regions are derived from the full (unfiltered) list so the dropdown
     * always shows all available options regardless of active filters.
     */
    public function render(): View
    {
        $regions = collect($this->volcanoes ?? [])
            ->pluck('region')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return view('livewire.pages.volcano-watch', [
            'volcanoes' => $this->filteredVolcanoes(),
            'regions'   => $regions,
        ]);
    }

    /**
     * Fetch and normalise all USGS volcano records into $volcanoes via the service.
     */
    private function loadVolcanoes(): void
    {
        try {
            $this->volcanoes = $this->volcanoService->all();
        } catch (Throwable) {
            $this->error = 'Failed to reach the USGS Volcano API. Please check your connection and try again.';
        }
    }

    /**
     * Apply the active region and alert level filters to the full volcano list.
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
            ->values()
            ->toArray();
    }

}
