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
 * Combined flood alerts component for HydroWatch.
 *
 * Presents two panels side by side:
 *   - National list: all active NWS flood alerts across the US, sorted by severity
 *     (Extreme → Severe → Moderate → Minor → Unknown), paginated 12 per page.
 *     Clicking an alert switches the map to that alert's state and highlights
 *     the polygon.
 *   - State map: GeoJSON polygon overlays for the selected US state, coloured
 *     by CAP severity. The state selector lets users browse any state directly.
 *
 * Polling: wire:poll.300s="refreshAlerts" keeps both feeds current.
 * The map (wire:ignore) survives re-renders — updated alert data is pushed
 * via the 'flood-alerts-updated' browser event after each map reload.
 * The 'flood-alert-focus' browser event is dispatched when an alert is
 * selected so the map can fly to and highlight its polygon.
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

    /**
     * Sort order for CAP severity levels (lower = higher priority).
     *
     * @var array<string, int>
     */
    private const SEVERITY_ORDER = [
        'Extreme' => 0,
        'Severe'  => 1,
        'Moderate' => 2,
        'Minor'   => 3,
        'Unknown' => 4,
    ];

    /**
     * Number of alerts shown per page in the national list.
     */
    private const LIST_PER_PAGE = 12;

    protected NWSAlertsService $nwsAlertsService;

    /**
     * Resolve services on every component lifecycle request.
     */
    public function boot(NWSAlertsService $nwsAlertsService): void
    {
        $this->nwsAlertsService = $nwsAlertsService;
    }

    /**
     * Two-letter US state or territory code for the map panel.
     */
    public string $stateCd = 'va';

    /**
     * All active national flood alerts, sorted by severity.
     * Each entry is the result of FloodAlertData::toArray().
     *
     * @var list<array<string, mixed>>|null
     */
    public ?array $allAlerts = null;

    /**
     * Active flood alerts for the selected state, used for map rendering.
     * Each entry is the result of FloodAlertData::toArray().
     *
     * @var list<array<string, mixed>>|null
     */
    public ?array $mapAlerts = null;

    /**
     * The NWS alert id of the currently selected alert (null = none).
     */
    public ?string $selectedAlertId = null;

    /**
     * Current page in the national alert list (1-based).
     */
    public int $listPage = 1;

    /**
     * Map panel API error shown to the user.
     */
    public ?string $error = null;

    /**
     * National list API error shown to the user.
     */
    public ?string $listError = null;

    /**
     * Load both alert feeds on initial mount.
     */
    public function mount(): void
    {
        $this->loadAllAlerts();
        $this->loadMapAlerts(zoom: true);
    }

    /**
     * Reload the map panel and clear the selection when the state selector changes.
     */
    public function updatedStateCd(): void
    {
        $this->selectedAlertId = null;
        $this->loadMapAlerts(zoom: true);
    }

    /**
     * Called by wire:poll.300s to refresh both alert feeds without a full page reload.
     *
     * Does not zoom — the user's current pan/zoom position is preserved.
     */
    public function refreshAlerts(): void
    {
        $this->loadAllAlerts();
        $this->loadMapAlerts(zoom: false);
    }

    /**
     * Select an alert from the national list.
     *
     * If the alert belongs to a different state than the current map panel,
     * the state is updated and the map reloaded before focusing the polygon.
     *
     * @param string      $alertId   NWS alert identifier.
     * @param string|null $stateCode Two-letter lowercase state code, or null if unknown.
     */
    public function selectAlertFromList(string $alertId, ?string $stateCode): void
    {
        $stateChanged = $stateCode !== null
            && $stateCode !== $this->stateCd
            && isset(self::US_STATES[$stateCode]);

        if ($stateChanged) {
            $this->stateCd = $stateCode;
        }

        // Always reload the map when selecting from the list so the polygon is available
        // to focus. Zoom to the new state only when the state actually changed.
        $this->loadMapAlerts(zoom: $stateChanged);

        $this->selectedAlertId = $alertId;
        $this->dispatch('flood-alert-focus', alertId: $alertId);
    }

    /**
     * Select an alert by its NWS id from a map polygon click.
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
     * Advance to the next page of the national alert list.
     */
    public function nextPage(): void
    {
        $totalPages = $this->totalPages();

        if ($this->listPage < $totalPages) {
            $this->listPage++;
        }
    }

    /**
     * Go back to the previous page of the national alert list.
     */
    public function previousPage(): void
    {
        if ($this->listPage > 1) {
            $this->listPage--;
        }
    }

    /**
     * Render the FloodAlerts component.
     */
    public function render(): View
    {
        $all        = $this->allAlerts ?? [];
        $totalPages = $this->totalPages();
        $offset     = ($this->listPage - 1) * self::LIST_PER_PAGE;
        $pagedAlerts = array_slice($all, $offset, self::LIST_PER_PAGE);

        $selectedAlert = null;

        if ($this->selectedAlertId !== null) {
            foreach (array_merge($this->mapAlerts ?? [], $this->allAlerts ?? []) as $alert) {
                if ($alert['id'] === $this->selectedAlertId) {
                    $selectedAlert = $alert;
                    break;
                }
            }
        }

        return view('livewire.hydro.flood-alerts', [
            'pagedAlerts'  => $pagedAlerts,
            'totalPages'   => $totalPages,
            'totalAlerts'  => count($all),
            'selectedAlert' => $selectedAlert,
        ]);
    }

    /**
     * Fetch all active national flood alerts, sorted by severity.
     *
     * Queries without a location filter to retrieve the full national feed.
     * Results are sorted Extreme → Severe → Moderate → Minor → Unknown.
     */
    private function loadAllAlerts(): void
    {
        $this->listError = null;

        try {
            $collection = $this->nwsAlertsService->activeFloodAlerts(NWSAlertsQuery::make());

            $order = self::SEVERITY_ORDER;

            $this->allAlerts = $collection
                ->sortBy(fn (FloodAlertData $alert): int => $order[$alert->severity] ?? 4)
                ->values()
                ->map(fn (FloodAlertData $alert): array => $alert->toArray())
                ->toArray();
        } catch (Throwable) {
            $this->listError = 'Failed to reach the NWS Alerts API. Please try again.';
            $this->allAlerts = [];
        }
    }

    /**
     * Fetch active flood alerts for the selected state and push them to the map.
     *
     * Dispatches 'flood-alerts-updated' so the Leaflet map redraws polygons.
     * When $zoom is true, also dispatches 'flood-alerts-state-zoom' so the map
     * flies to the selected state's bounding box. Zoom is suppressed on poll
     * refreshes to avoid overriding the user's current pan/zoom position.
     *
     * @param bool $zoom Whether to zoom the map to the selected state after loading.
     */
    private function loadMapAlerts(bool $zoom = false): void
    {
        $this->error = null;

        try {
            $query = NWSAlertsQuery::make()->area($this->stateCd);
            $collection = $this->nwsAlertsService->activeFloodAlerts($query);

            $this->mapAlerts = $collection
                ->map(fn (FloodAlertData $alert): array => $alert->toArray())
                ->values()
                ->toArray();
        } catch (Throwable) {
            $this->error    = 'Failed to reach the NWS Alerts API. Please try again.';
            $this->mapAlerts = [];
        }

        $this->dispatch('flood-alerts-updated', alerts: $this->mapAlerts ?? []);

        if ($zoom) {
            $this->dispatch('flood-alerts-state-zoom', stateCd: $this->stateCd);
        }
    }

    /**
     * Return the total number of pages for the national alert list.
     */
    private function totalPages(): int
    {
        return max(1, (int) ceil(count($this->allAlerts ?? []) / self::LIST_PER_PAGE));
    }
}
