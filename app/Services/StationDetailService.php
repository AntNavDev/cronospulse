<?php

declare(strict_types=1);

namespace App\Services;

use App\Api\Queries\WaterServicesQuery;
use App\Api\USGSWaterServices;
use App\Models\StationReading;
use App\Models\UsgsStation;
use Carbon\Carbon;
use RuntimeException;

/**
 * Service for loading, persisting, and returning USGS station detail data.
 *
 * On each call to loadStation(), the service:
 *   1. Fetches 30 days of readings from the USGS IV API.
 *   2. Upserts the station record in usgs_stations (creating it on first visit).
 *   3. Bulk-upserts readings into station_readings — the unique constraint on
 *      (station_id, parameter_code, recorded_at) prevents duplicate ingestion.
 *   4. Returns the station model and chart-ready series arrays for the page component.
 *
 * The DB accumulates readings over time as users visit the page. Eventually the DB
 * will hold more than 30 days of history — querying the DB for the chart instead of
 * the live API is a planned future enhancement.
 *
 * Note: the USGS IV API JSON format does not return site metadata (state, HUC,
 * county). Those fields require a separate call to the USGS Site Service
 * (/nwis/site/) and are stored as null until that integration is added.
 */
class StationDetailService
{
    /**
     * @param USGSWaterServices $client Raw USGS NWIS IV API client.
     */
    public function __construct(private readonly USGSWaterServices $client)
    {
    }

    /**
     * Load 30 days of readings for the given USGS site number, persist to DB, and
     * return chart-ready arrays for the station detail page.
     *
     * @param  string $siteNo USGS site number (e.g. '01646500').
     * @return array{
     *     station: UsgsStation,
     *     streamflow: array{labels: list<string|null>, data: list<float|null>},
     *     gage_height: array{labels: list<string|null>, data: list<float|null>},
     * }
     *
     * @throws RuntimeException If the API returns an error or no data for the site.
     */
    public function loadStation(string $siteNo): array
    {
        $response = $this->client->instantaneousValues(
            WaterServicesQuery::make()
                ->sites([$siteNo])
                ->parameterCd(['00060', '00065'])
                ->period('P30D'),
        );

        if (! $response->successful()) {
            throw new RuntimeException('The USGS Water Services API returned an error.');
        }

        /** @var array<int, array<string, mixed>> $timeSeries */
        $timeSeries = $response->json('value.timeSeries', []);

        if (empty($timeSeries)) {
            throw new RuntimeException("No USGS data found for site {$siteNo}.");
        }

        $sourceInfo = $timeSeries[0]['sourceInfo'] ?? [];
        $station    = $this->upsertStation($siteNo, $sourceInfo);

        $streamflow = ['labels' => [], 'data' => []];
        $gageHeight = ['labels' => [], 'data' => []];

        foreach ($timeSeries as $ts) {
            $paramCode = (string) ($ts['variable']['variableCode'][0]['value'] ?? '');
            $paramName = html_entity_decode(
                (string) ($ts['variable']['variableName'] ?? ''),
                ENT_QUOTES | ENT_HTML5,
            );
            $unitCode  = (string) ($ts['variable']['unit']['unitCode'] ?? '');
            $noDataVal = (float) ($ts['variable']['noDataValue'] ?? -999999);

            /** @var array<int, array<string, mixed>> $rawValues */
            $rawValues = $ts['values'][0]['value'] ?? [];

            [$chartLabels, $chartData, $toInsert] = $this->parseReadings(
                $rawValues,
                $noDataVal,
                $station->id,
                $paramCode,
                $paramName,
                $unitCode,
            );

            $this->persistReadings($toInsert);

            if ($paramCode === '00060') {
                $streamflow = ['labels' => $chartLabels, 'data' => $chartData];
            } elseif ($paramCode === '00065') {
                $gageHeight = ['labels' => $chartLabels, 'data' => $chartData];
            }
        }

        return [
            'station'     => $station,
            'streamflow'  => $streamflow,
            'gage_height' => $gageHeight,
        ];
    }

    /**
     * Upsert a usgs_stations record from the IV API sourceInfo block.
     *
     * The IV API JSON format does not include site metadata such as state or HUC,
     * so those fields are stored as null. Site name and coordinates are always
     * available in the sourceInfo block.
     *
     * @param string               $siteNo     USGS site number.
     * @param array<string, mixed> $sourceInfo sourceInfo block from the WaterML-JSON response.
     */
    private function upsertStation(string $siteNo, array $sourceInfo): UsgsStation
    {
        $geoLoc = $sourceInfo['geoLocation']['geogLocation'] ?? [];

        UsgsStation::updateOrCreate(
            ['site_no' => $siteNo],
            [
                'name'         => (string) ($sourceInfo['siteName'] ?? $siteNo),
                'state'        => null,
                'county'       => null,
                'huc'          => null,
                'site_type'    => 'ST',
                'latitude'     => (float) ($geoLoc['latitude'] ?? 0),
                'longitude'    => (float) ($geoLoc['longitude'] ?? 0),
                'elevation_ft' => null,
                'is_active'    => true,
            ],
        );

        return UsgsStation::where('site_no', $siteNo)->firstOrFail();
    }

    /**
     * Parse a WaterML-JSON value array into chart-ready and DB-ready structures.
     *
     * @param  array<int, array<string, mixed>> $rawValues  Raw readings array from the API.
     * @param  float                            $noDataVal  Sentinel value for missing data.
     * @param  int                              $stationId  FK for station_readings.
     * @param  string                           $paramCode  USGS parameter code.
     * @param  string                           $paramName  Human-readable parameter name.
     * @param  string                           $unitCode   Unit abbreviation.
     * @return array{
     *     0: list<string|null>,
     *     1: list<float|null>,
     *     2: list<array<string, mixed>>,
     * } [chartLabels, chartData, rowsToInsert]
     */
    private function parseReadings(
        array $rawValues,
        float $noDataVal,
        int $stationId,
        string $paramCode,
        string $paramName,
        string $unitCode,
    ): array {
        $chartLabels = [];
        $chartData   = [];
        $toInsert    = [];

        foreach ($rawValues as $r) {
            $raw       = (float) ($r['value'] ?? $noDataVal);
            $value     = $raw !== $noDataVal ? $raw : null;
            $dateTime  = ($r['dateTime'] ?? '') ?: null;
            $qualifier = (string) ($r['qualifiers'][0] ?? '');

            $chartLabels[] = $dateTime;
            $chartData[]   = $value;

            if ($value !== null && $dateTime !== null) {
                $toInsert[] = [
                    'station_id'     => $stationId,
                    'parameter_code' => $paramCode,
                    'parameter_name' => $paramName,
                    'value'          => $value,
                    'unit'           => $unitCode,
                    'qualifier'      => $qualifier !== '' ? $qualifier : null,
                    'recorded_at'    => Carbon::parse($dateTime)->toDateTimeString(),
                    'created_at'     => now()->toDateTimeString(),
                    'updated_at'     => now()->toDateTimeString(),
                ];
            }
        }

        return [$chartLabels, $chartData, $toInsert];
    }

    /**
     * Bulk-upsert readings into station_readings in 500-row chunks.
     *
     * The unique constraint on (station_id, parameter_code, recorded_at) prevents
     * duplicate ingestion — re-visiting a station page is idempotent for already-stored
     * readings and only inserts genuinely new observations.
     *
     * @param array<int, array<string, mixed>> $rows Rows prepared by parseReadings().
     */
    private function persistReadings(array $rows): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            StationReading::upsert(
                $chunk,
                ['station_id', 'parameter_code', 'recorded_at'],
                ['value', 'unit', 'qualifier', 'updated_at'],
            );
        }
    }
}
