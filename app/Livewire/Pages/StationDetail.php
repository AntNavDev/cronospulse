<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Services\StationDetailService;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

/**
 * Full-page component for the USGS station detail view.
 *
 * Mounted via /hydro/station/{siteNo}. On mount, the component calls
 * StationDetailService which fetches 30 days of readings from USGS, upserts the
 * station and readings into the DB, and returns chart-ready arrays.
 *
 * Public properties:
 *   $stationName      — display name (used in the SEO title slot)
 *   $stationMeta      — serialised station metadata for the view
 *   $streamflowChart  — Chart.js-ready {labels, data} for streamflow
 *   $gageHeightChart  — Chart.js-ready {labels, data} for gage height
 *   $error            — user-facing error message on API failure
 */
#[Layout('components.layouts.app')]
#[Title('Station Detail — CronosPulse')]
class StationDetail extends Component
{
    protected StationDetailService $stationDetailService;

    /**
     * Resolve the service on every Livewire lifecycle request.
     */
    public function boot(StationDetailService $stationDetailService): void
    {
        $this->stationDetailService = $stationDetailService;
    }

    /**
     * USGS site number from the route parameter.
     */
    public string $siteNo = '';

    /**
     * Station display name — used in the SEO <title> slot.
     */
    public string $stationName = 'Station Detail';

    /**
     * Station metadata for the view.
     *
     * @var array<string, mixed>|null
     */
    public ?array $stationMeta = null;

    /**
     * Chart.js-ready streamflow series for the 30-day chart.
     *
     * @var array{labels: list<string|null>, data: list<float|null>}|null
     */
    public ?array $streamflowChart = null;

    /**
     * Chart.js-ready gage height series for the 30-day chart.
     *
     * @var array{labels: list<string|null>, data: list<float|null>}|null
     */
    public ?array $gageHeightChart = null;

    /**
     * User-facing error message shown when the API call fails.
     */
    public ?string $error = null;

    /**
     * Load station data from the service and populate page properties.
     *
     * @param string $siteNo USGS site number from the route segment (e.g. '01646500').
     */
    public function mount(string $siteNo): void
    {
        if (! preg_match('/^\d{1,15}$/', $siteNo)) {
            abort(404);
        }

        $this->siteNo = $siteNo;

        try {
            $result = $this->stationDetailService->loadStation($siteNo);

            $station           = $result['station'];
            $this->stationName = $station->name;

            $this->stationMeta = [
                'site_no'      => $station->site_no,
                'name'         => $station->name,
                'state'        => $station->state,
                'county'       => $station->county,
                'huc'          => $station->huc,
                'site_type'    => $station->site_type,
                'latitude'     => $station->latitude,
                'longitude'    => $station->longitude,
                'elevation_ft' => $station->elevation_ft,
                'is_active'    => $station->is_active,
            ];

            $this->streamflowChart = $result['streamflow'];
            $this->gageHeightChart = $result['gage_height'];
        } catch (Throwable $e) {
            Log::error('StationDetail: failed to load site', [
                'site_no'   => $siteNo,
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            $this->error = 'Unable to load data for station ' . $siteNo . '. The site may not exist or the USGS API is temporarily unavailable.';
        }
    }

    /**
     * Render the station detail page.
     */
    public function render(): View
    {
        return view('livewire.pages.station-detail');
    }
}
