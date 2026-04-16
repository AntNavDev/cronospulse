<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Api\Queries\VolcanoQuery;
use App\Api\USGSVolcano;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('VolcanoWatch — US Volcano Monitoring | CronosPulse')]
class VolcanoWatch extends Component
{
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
     * Fetch and normalise all USGS volcano records into $volcanoes.
     *
     * Maps vhpstatus response fields (camelCase) to snake_case for consistency.
     * Alert and color classes are computed here so the Blade view stays logic-free.
     * UNASSIGNED volcanoes (no active monitoring notice) are included and labelled.
     */
    private function loadVolcanoes(): void
    {
        try {
            $response = (new USGSVolcano())->vhpStatus(VolcanoQuery::make());

            if (! $response->successful()) {
                $this->error = 'The USGS Volcano API returned an error. Please try again.';

                return;
            }

            $this->volcanoes = collect($response->json() ?? [])
                ->map(function (array $v): array {
                    $alertLevel = $v['alertLevel'] ?? 'UNASSIGNED';
                    $colorCode  = $v['colorCode'] ?? 'UNASSIGNED';

                    return [
                        'vnum'        => (string) ($v['vnum'] ?? ''),
                        'name'        => $v['vName'] ?? '',
                        'region'      => $v['region'] ?? '',
                        'latitude'    => (float) ($v['lat'] ?? 0),
                        'longitude'   => (float) ($v['long'] ?? 0),
                        'alert_level' => $alertLevel,
                        'alert_class' => $this->alertLevelClass($alertLevel),
                        'color_code'  => $colorCode,
                        'color_class' => $this->aviationColorClass($colorCode),
                        'synopsis'    => $v['noticeSynopsis'] ?? null,
                        'url'         => $v['vUrl'] ?? null,
                    ];
                })
                ->values()
                ->toArray();
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

    /**
     * Return Tailwind badge classes for the given USGS ground alert level.
     *
     * Levels in ascending severity: NORMAL → ADVISORY → WATCH → WARNING.
     * UNASSIGNED indicates no active monitoring notice for the volcano.
     */
    private function alertLevelClass(string $alertLevel): string
    {
        return match ($alertLevel) {
            'WARNING'  => 'bg-danger/15 text-danger',
            'WATCH'    => 'bg-warning/15 text-warning',
            'ADVISORY' => 'bg-info/15 text-info',
            'NORMAL'   => 'bg-success/15 text-success',
            default    => 'bg-surface-raised text-muted',
        };
    }

    /**
     * Return Tailwind badge classes for the given USGS aviation color code.
     *
     * Codes in ascending severity: GREEN → YELLOW → ORANGE → RED.
     * UNASSIGNED indicates no active aviation notice.
     */
    private function aviationColorClass(string $colorCode): string
    {
        return match ($colorCode) {
            'RED'    => 'bg-danger/15 text-danger',
            'ORANGE' => 'bg-warning/15 text-warning',
            'YELLOW' => 'bg-info/15 text-info',
            'GREEN'  => 'bg-success/15 text-success',
            default  => 'bg-surface-raised text-muted',
        };
    }
}
