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
 * Flood alerts map component for HydroWatch.
 *
 * Loads active NWS flood alerts for a selected US state and renders them as
 * GeoJSON polygon overlays on a Leaflet map, coloured by CAP severity.
 * Clicking a polygon or list row opens a detail panel for that alert.
 *
 * Polling: wire:poll.300s="refreshAlerts" keeps the feed current.
 * The map (wire:ignore) survives re-renders — updated alert data is pushed
 * via the 'flood-alerts-updated' browser event after each load.
 *
 * Alerts without geometry are shown in the list only (no map polygon).
 */
class FloodAlerts extends Component
{
    /**
     * US state and territory codes for the state selector dropdown.
     *
     * @var array<string, string>
     */
    public const US_STATES = [
        'al' => 'Alabama',          'ak' => 'Alaska',          'az' => 'Arizona',
        'ar' => 'Arkansas',         'ca' => 'California',      'co' => 'Colorado',
        'ct' => 'Connecticut',      'de' => 'Delaware',        'fl' => 'Florida',
        'ga' => 'Georgia',          'hi' => 'Hawaii',          'id' => 'Idaho',
        'il' => 'Illinois',         'in' => 'Indiana',         'ia' => 'Iowa',
        'ks' => 'Kansas',           'ky' => 'Kentucky',        'la' => 'Louisiana',
        'me' => 'Maine',            'md' => 'Maryland',        'ma' => 'Massachusetts',
        'mi' => 'Michigan',         'mn' => 'Minnesota',       'ms' => 'Mississippi',
        'mo' => 'Missouri',         'mt' => 'Montana',         'ne' => 'Nebraska',
        'nv' => 'Nevada',           'nh' => 'New Hampshire',   'nj' => 'New Jersey',
        'nm' => 'New Mexico',       'ny' => 'New York',        'nc' => 'North Carolina',
        'nd' => 'North Dakota',     'oh' => 'Ohio',            'ok' => 'Oklahoma',
        'or' => 'Oregon',           'pa' => 'Pennsylvania',    'ri' => 'Rhode Island',
        'sc' => 'South Carolina',   'sd' => 'South Dakota',    'tn' => 'Tennessee',
        'tx' => 'Texas',            'ut' => 'Utah',            'vt' => 'Vermont',
        'va' => 'Virginia',         'wa' => 'Washington',      'wv' => 'West Virginia',
        'wi' => 'Wisconsin',        'wy' => 'Wyoming',
        'pr' => 'Puerto Rico',      'vi' => 'U.S. Virgin Islands',
        'gu' => 'Guam',             'as' => 'American Samoa',
    ];

    protected NWSAlertsService $nwsAlertsService;

    /**
     * Resolve the service on every component lifecycle request.
     */
    public function boot(NWSAlertsService $nwsAlertsService): void
    {
        $this->nwsAlertsService = $nwsAlertsService;
    }

    /**
     * Two-letter US state or territory code used to scope the alerts query.
     */
    public string $stateCd = 'va';

    /**
     * Active flood alerts for the selected state, serialised for Livewire storage.
     * Each entry is the result of FloodAlertData::toArray().
     *
     * @var list<array<string, mixed>>|null
     */
    public ?array $alerts = null;

    /**
     * The NWS alert id of the currently selected alert (null = none).
     */
    public ?string $selectedAlertId = null;

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
     * Reload alerts and clear the selection whenever the state selector changes.
     */
    public function updatedStateCd(): void
    {
        $this->selectedAlertId = null;
        $this->loadAlerts();
    }

    /**
     * Called by wire:poll.300s to refresh the alert feed without a full page reload.
     */
    public function refreshAlerts(): void
    {
        $this->loadAlerts();
    }

    /**
     * Select an alert by its NWS id to show the detail panel.
     *
     * Called from Alpine via the 'flood-alert-selected' browser event:
     *   @flood-alert-selected.window="$wire.selectAlert($event.detail.alertId)"
     *
     * @param string $alertId NWS alert identifier.
     */
    public function selectAlert(string $alertId): void
    {
        $this->selectedAlertId = $alertId;
        $this->dispatch('flood-alert-focus', alertId: $alertId);
    }

    /**
     * Render the FloodAlerts component.
     */
    public function render(): View
    {
        $selectedAlert = null;

        if ($this->selectedAlertId !== null && $this->alerts !== null) {
            foreach ($this->alerts as $alert) {
                if ($alert['id'] === $this->selectedAlertId) {
                    $selectedAlert = $alert;
                    break;
                }
            }
        }

        return view('livewire.hydro.flood-alerts', [
            'selectedAlert' => $selectedAlert,
        ]);
    }

    /**
     * Fetch active flood alerts for the current state and store as arrays.
     *
     * Only flood-related NWS event types are returned (filtered by NWSAlertsService).
     * Dispatches 'flood-alerts-updated' so the map redraws polygons without a DOM reset.
     */
    private function loadAlerts(): void
    {
        $this->error = null;

        try {
            $collection = $this->nwsAlertsService->activeFloodAlerts(
                NWSAlertsQuery::make()->area($this->stateCd),
            );

            $this->alerts = $collection
                ->map(fn (FloodAlertData $alert): array => $alert->toArray())
                ->values()
                ->toArray();
        } catch (Throwable) {
            $this->error  = 'Failed to reach the NWS Alerts API. Please try again.';
            $this->alerts = [];
        }

        $this->dispatch('flood-alerts-updated', alerts: $this->alerts ?? []);
    }
}
