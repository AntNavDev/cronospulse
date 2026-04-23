<?php

declare(strict_types=1);

namespace App\Livewire\Hydro;

use App\Api\Queries\WaterServicesQuery;
use App\Models\UsgsStation;
use App\Services\WaterServicesService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

/**
 * Stream gauge dashboard component for HydroWatch.
 *
 * Loads all active stream gauge sites for a selected US state and renders them
 * as map markers. Clicking a marker loads a 3-day sparkline for that site's
 * streamflow and gage height readings.
 *
 * Polling: wire:poll.300s="refreshSites" keeps current readings fresh.
 * The map (wire:ignore) survives re-renders — fresh site data is pushed via
 * the 'stream-gauges-updated' browser event after each load.
 *
 * Deep-link: ?state=va&site=01646500 pre-selects a state and opens the
 * sparkline panel for the given site on arrival. Used by Dashboard bookmark links.
 *
 * Note: loading all active sites for a large state (e.g. TX, CA) may be slow.
 * The USGS IV API returns one time series per site per parameter — a state with
 * 400 active stream gauges querying two parameters returns 800 time series entries.
 */
class StreamGauge extends Component
{
    /**
     * US state codes and display names for the state selector dropdown.
     *
     * @var array<string, string>
     */
    public const US_STATES = [
        'al' => 'Alabama',    'ak' => 'Alaska',       'az' => 'Arizona',
        'ar' => 'Arkansas',   'ca' => 'California',   'co' => 'Colorado',
        'ct' => 'Connecticut','de' => 'Delaware',     'fl' => 'Florida',
        'ga' => 'Georgia',    'hi' => 'Hawaii',       'id' => 'Idaho',
        'il' => 'Illinois',   'in' => 'Indiana',      'ia' => 'Iowa',
        'ks' => 'Kansas',     'ky' => 'Kentucky',     'la' => 'Louisiana',
        'me' => 'Maine',      'md' => 'Maryland',     'ma' => 'Massachusetts',
        'mi' => 'Michigan',   'mn' => 'Minnesota',    'ms' => 'Mississippi',
        'mo' => 'Missouri',   'mt' => 'Montana',      'ne' => 'Nebraska',
        'nv' => 'Nevada',     'nh' => 'New Hampshire','nj' => 'New Jersey',
        'nm' => 'New Mexico', 'ny' => 'New York',     'nc' => 'North Carolina',
        'nd' => 'North Dakota','oh' => 'Ohio',        'ok' => 'Oklahoma',
        'or' => 'Oregon',     'pa' => 'Pennsylvania', 'ri' => 'Rhode Island',
        'sc' => 'South Carolina','sd' => 'South Dakota','tn' => 'Tennessee',
        'tx' => 'Texas',      'ut' => 'Utah',         'vt' => 'Vermont',
        'va' => 'Virginia',   'wa' => 'Washington',   'wv' => 'West Virginia',
        'wi' => 'Wisconsin',  'wy' => 'Wyoming',
    ];

    protected WaterServicesService $waterServicesService;

    /**
     * Resolve the service on every component lifecycle request.
     */
    public function boot(WaterServicesService $waterServicesService): void
    {
        $this->waterServicesService = $waterServicesService;
    }

    /**
     * Two-letter US state code used to scope the site query.
     */
    public string $stateCd = 'wa';

    /**
     * All active stream gauge sites for the selected state, grouped by site code.
     * Each entry: ['site_code', 'site_name', 'lat', 'lng', 'streamflow', 'gage_height',
     *              'is_provisional', 'latest_time']
     *
     * @var list<array<string, mixed>>|null
     */
    public ?array $sites = null;

    /**
     * Site code of the currently selected pin (null = no selection).
     */
    public ?string $selectedSiteCode = null;

    /**
     * 3-day sparkline data for the selected site, ready for Chart.js.
     *
     * Shape: ['site_name' => string, 'streamflow' => [...], 'gage_height' => [...]]
     * Each sub-array: ['labels' => string[], 'data' => float[]|null[]]
     *
     * @var array<string, mixed>|null
     */
    public ?array $sparklineData = null;

    /**
     * Error message shown when an API call fails.
     */
    public ?string $error = null;

    /**
     * USGS site numbers that the authenticated user has bookmarked.
     * Empty for guests. Refreshed after every save/unsave action.
     *
     * @var list<string>
     */
    public array $savedSiteCodes = [];

    /**
     * Flash message shown after a save or unsave action.
     */
    public ?string $saveMessage = null;

    /**
     * Whether the last save action succeeded (controls flash message colour).
     */
    public bool $saveSuccess = false;

    /**
     * Load sites on initial mount.
     *
     * If ?state= and/or ?site= are present in the URL (deep-link from Dashboard),
     * pre-select the state and open the sparkline panel for the given site.
     */
    public function mount(): void
    {
        $state = request()->query('state');
        $site  = request()->query('site');

        if (is_string($state) && array_key_exists($state, self::US_STATES)) {
            $this->stateCd = $state;
        }

        // Always fit bounds on initial mount so the map zooms to the active state.
        $this->loadSites(fitBounds: true);
        $this->loadSavedSiteCodes();

        if (is_string($site) && $site !== '') {
            $this->selectSite($site);

            // Fly the map to the pre-selected site and open its popup.
            $siteData = collect($this->sites ?? [])->firstWhere('site_code', $site);

            if ($siteData) {
                $this->dispatch(
                    'stream-gauge-preselect',
                    siteCode: $site,
                    lat: $siteData['lat'],
                    lng: $siteData['lng'],
                );
            }
        }
    }

    /**
     * Reload sites whenever the state selector changes.
     *
     * Passes fitBounds=true so the map zooms to the new state's markers.
     */
    public function updatedStateCd(): void
    {
        $this->selectedSiteCode = null;
        $this->sparklineData    = null;
        $this->saveMessage      = null;
        $this->loadSites(fitBounds: true);
    }

    /**
     * Called by wire:poll.300s to refresh current readings without a full page reload.
     *
     * Dispatches 'stream-gauges-updated' so the map refreshes markers in place.
     * Does not fit bounds — the user may have manually panned/zoomed the map.
     */
    public function refreshSites(): void
    {
        $this->loadSites();
    }

    /**
     * Load 3-day sparkline data for the selected site.
     *
     * Called from Alpine via the 'stream-gauge-selected' browser event:
     *   @stream-gauge-selected.window="$wire.selectSite($event.detail.siteCode)"
     *
     * @param string $siteCode USGS site number (e.g. '01646500').
     */
    public function selectSite(string $siteCode): void
    {
        $this->selectedSiteCode = $siteCode;
        $this->sparklineData    = null;
        $this->error            = null;
        $this->saveMessage      = null;

        try {
            // Cache sparkline data per site at 15 minutes — USGS readings arrive
            // every ~15 min, so there is no benefit in fetching more frequently.
            $this->sparklineData = Cache::remember(
                "usgs.water.sparkline.{$siteCode}",
                900,
                function () use ($siteCode): array {
                    $collection = $this->waterServicesService->query(
                        WaterServicesQuery::make()
                            ->sites([$siteCode])
                            ->parameterCd(['00060', '00065'])
                            ->period('P3D'),
                    );

                    $streamflow = $collection->firstWhere('parameterCode', '00060');
                    $gageHeight = $collection->firstWhere('parameterCode', '00065');
                    $siteName   = $collection->first()?->siteName ?? $siteCode;

                    return [
                        'site_name'   => $siteName,
                        'streamflow'  => $this->buildSparklineSeries($streamflow?->allValues ?? []),
                        'gage_height' => $this->buildSparklineSeries($gageHeight?->allValues ?? []),
                    ];
                },
            );
        } catch (Throwable) {
            $this->error = 'Failed to load sparkline data for the selected site.';
        }
    }

    /**
     * Save the currently-selected stream gauge to the user's Dashboard.
     *
     * Creates a UsgsStation record if one does not yet exist for this site code
     * (using the name and coordinates already held in $sites). Capped at 30
     * bookmarks per user — mirrors the 20-search cap on QuakeWatch.
     */
    public function saveStation(): void
    {
        $user = auth()->user();

        if (! $user || ! $this->selectedSiteCode) {
            return;
        }

        if ($user->savedStations()->count() >= 30) {
            $this->saveMessage = 'You have reached the 30 saved station limit. Delete one to add more.';
            $this->saveSuccess = false;

            return;
        }

        $siteData = collect($this->sites ?? [])->firstWhere('site_code', $this->selectedSiteCode);
        $station  = UsgsStation::firstOrCreate(
            ['site_no' => $this->selectedSiteCode],
            [
                'name'      => $this->sparklineData['site_name'] ?? $this->selectedSiteCode,
                'state'     => null,
                'site_type' => 'ST',
                'latitude'  => (float) ($siteData['lat'] ?? 0),
                'longitude' => (float) ($siteData['lng'] ?? 0),
            ],
        );

        $user->savedStations()->firstOrCreate(
            ['station_id' => $station->id],
            ['state_cd'   => $this->stateCd],
        );

        $this->loadSavedSiteCodes();
        $this->saveMessage = 'Station saved to your dashboard.';
        $this->saveSuccess = true;
    }

    /**
     * Remove the currently-selected stream gauge from the user's saved stations.
     */
    public function unsaveStation(): void
    {
        $user = auth()->user();

        if (! $user || ! $this->selectedSiteCode) {
            return;
        }

        $station = UsgsStation::where('site_no', $this->selectedSiteCode)->first();

        if (! $station) {
            return;
        }

        $user->savedStations()
            ->where('station_id', $station->id)
            ->delete();

        $this->loadSavedSiteCodes();
        $this->saveMessage = null;
        $this->saveSuccess = false;
    }

    /**
     * Render the StreamGauge component.
     */
    public function render(): View
    {
        return view('livewire.hydro.stream-gauge');
    }

    /**
     * Fetch all active stream sites for the current state and store as arrays.
     *
     * Groups the WaterML-JSON time series (one per site × parameter) into one
     * entry per site with both streamflow and gage height values combined.
     * Dispatches 'stream-gauges-updated' so the map refreshes without a DOM reset.
     *
     * @param bool $fitBounds When true, the map will zoom to fit the loaded markers.
     *                        Set on state changes; false on polls so the user's
     *                        manual pan/zoom position is preserved.
     */
    private function loadSites(bool $fitBounds = false): void
    {
        $this->error = null;

        try {
            // Cache per state at 300s — aligned with the wire:poll interval so that
            // polls serve from cache and the API is only hit when the cache expires.
            // This is the most expensive call (up to 800 time series for large states).
            $this->sites = Cache::remember(
                "usgs.water.sites.{$this->stateCd}",
                300,
                function (): array {
                    $collection = $this->waterServicesService->query(
                        WaterServicesQuery::make()
                            ->stateCd($this->stateCd)
                            ->parameterCd(['00060', '00065'])
                            ->siteType('ST')
                            ->siteStatus('active')
                            ->period('PT2H'),
                    );

                    return $collection
                        ->groupBy('siteCode')
                        ->map(function ($series): array {
                            $streamflow = $series->firstWhere('parameterCode', '00060');
                            $gageHeight = $series->firstWhere('parameterCode', '00065');
                            $first      = $series->first();

                            return [
                                'site_code'      => $first->siteCode,
                                'site_name'      => $first->siteName,
                                'lat'            => $first->lat,
                                'lng'            => $first->lng,
                                'streamflow'     => $streamflow?->latestValue,
                                'gage_height'    => $gageHeight?->latestValue,
                                'is_provisional' => ($streamflow?->isProvisional() ?? false)
                                    || ($gageHeight?->isProvisional() ?? false),
                                'latest_time'    => $streamflow?->latestDateTime
                                    ?? $gageHeight?->latestDateTime,
                            ];
                        })
                        ->sortByDesc(fn (array $site): float => $site['streamflow'] ?? -1)
                        ->values()
                        ->toArray();
                },
            );
        } catch (Throwable) {
            $this->error = 'Failed to reach the USGS Water Services API. Please try again.';
            $this->sites = [];
        }

        $this->dispatch('stream-gauges-updated', sites: $this->sites ?? [], fitBounds: $fitBounds, stateCd: $this->stateCd);
    }

    /**
     * Populate $savedSiteCodes for the authenticated user.
     *
     * Stores the set of USGS site numbers the user has bookmarked so the view
     * can toggle between "Save" and "Saved ✓" without an extra query per render.
     */
    private function loadSavedSiteCodes(): void
    {
        if (! auth()->check()) {
            $this->savedSiteCodes = [];

            return;
        }

        $this->savedSiteCodes = auth()->user()
            ->savedStations()
            ->with('station')
            ->get()
            ->pluck('station.site_no')
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Convert a WaterServicesData allValues array into Chart.js-ready labels + data arrays.
     *
     * @param  array<int, array{value: float|null, dateTime: string|null}> $values
     * @return array{labels: string[], data: array<int, float|null>}
     */
    private function buildSparklineSeries(array $values): array
    {
        return [
            'labels' => array_column($values, 'dateTime'),
            'data'   => array_column($values, 'value'),
        ];
    }
}
