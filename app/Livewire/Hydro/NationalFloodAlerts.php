<?php

declare(strict_types=1);

namespace App\Livewire\Hydro;

use App\Api\Queries\NWSAlertsQuery;
use App\Data\FloodAlertData;
use App\Services\NWSAlertsService;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

/**
 * National flood alert banner for HydroWatch.
 *
 * Displays the most severe active flood alerts across the entire United States —
 * no state filter, Severe and Extreme only. Gives users an at-a-glance picture
 * of major flooding anywhere in the country before they drill into a state.
 *
 * Polling: wire:poll.300s keeps the feed current without a full page reload.
 *
 * Sorted by severity (Extreme first, then Severe), then by event type.
 * Capped at MAX_DISPLAY alerts to keep the banner concise.
 */
class NationalFloodAlerts extends Component
{
    /**
     * Maximum number of alerts to display in the banner.
     */
    private const MAX_DISPLAY = 12;

    protected NWSAlertsService $nwsAlertsService;

    /**
     * Resolve the service on every component lifecycle request.
     */
    public function boot(NWSAlertsService $nwsAlertsService): void
    {
        $this->nwsAlertsService = $nwsAlertsService;
    }

    /**
     * Severe and Extreme flood alerts for the entire US, sorted by severity.
     * Each entry is the result of FloodAlertData::toArray().
     *
     * @var list<array<string, mixed>>|null
     */
    public ?array $alerts = null;

    /**
     * Total number of Severe/Extreme flood alerts active nationally.
     * May exceed the displayed count when MAX_DISPLAY is reached.
     */
    public int $totalCount = 0;

    /**
     * Error message shown when the API call fails.
     */
    public ?string $error = null;

    /**
     * Load alerts on initial mount.
     */
    public function mount(): void
    {
        $this->loadAlerts();
    }

    /**
     * Called by wire:poll.300s to refresh the feed without a full page reload.
     */
    public function refreshAlerts(): void
    {
        $this->loadAlerts();
    }

    /**
     * Render the NationalFloodAlerts component.
     */
    public function render(): View
    {
        return view('livewire.hydro.national-flood-alerts');
    }

    /**
     * Fetch Severe/Extreme flood alerts for the entire US.
     *
     * Queries with no area filter and severity=Severe,Extreme. The service
     * further narrows to flood event types only. Results are sorted Extreme
     * first, then Severe, and capped at MAX_DISPLAY for display.
     */
    private function loadAlerts(): void
    {
        $this->error = null;

        try {
            $collection = $this->nwsAlertsService->activeFloodAlerts(
                NWSAlertsQuery::make()->severity(['Severe', 'Extreme']),
            );

            $this->totalCount = $collection->count();

            $severityOrder = ['Extreme' => 0, 'Severe' => 1];

            $this->alerts = $collection
                ->sortBy(fn (FloodAlertData $a): int => $severityOrder[$a->severity] ?? 2)
                ->take(self::MAX_DISPLAY)
                ->map(fn (FloodAlertData $alert): array => $alert->toArray())
                ->values()
                ->toArray();
        } catch (Throwable) {
            $this->error  = 'Failed to reach the NWS Alerts API.';
            $this->alerts = [];
        }
    }
}
