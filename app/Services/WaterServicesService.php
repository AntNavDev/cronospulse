<?php

declare(strict_types=1);

namespace App\Services;

use App\Api\Queries\WaterServicesQuery;
use App\Api\USGSWaterServices;
use App\Data\WaterServicesData;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Application service for USGS NWIS instantaneous values data.
 *
 * Wraps the raw USGSWaterServices API client and parses WaterML-JSON time series
 * entries into typed WaterServicesData objects. Each object represents one
 * site × parameter combination (e.g. site 01646500 × streamflow 00060).
 *
 * Results are not cached — queries are parameterized by site, parameter code,
 * and time window, making them unsuitable for a shared cache entry.
 *
 * USGS IV WaterML-JSON response shape (per timeSeries entry):
 *   sourceInfo.siteCode[0].value          → site number
 *   sourceInfo.siteName                   → human-readable site name
 *   sourceInfo.geoLocation.geogLocation   → latitude / longitude
 *   variable.variableCode[0].value        → parameter code (e.g. '00060')
 *   variable.variableName                 → parameter name (may contain HTML entities)
 *   variable.unit.unitCode                → unit abbreviation (e.g. 'ft3/s')
 *   variable.noDataValue                  → sentinel value indicating missing data
 *   values[0].value[]                     → readings array; newest entry is last
 */
class WaterServicesService
{
    /**
     * @param USGSWaterServices $client Raw USGS NWIS Instantaneous Values API client.
     */
    public function __construct(private readonly USGSWaterServices $client)
    {
    }

    /**
     * Query NWIS instantaneous values and return a typed collection of time series.
     *
     * Each item in the returned collection is one site × parameter combination.
     * A single query for two sites and two parameter codes will return up to four
     * items (one per combination that has data).
     *
     * @param  WaterServicesQuery              $query Built query object.
     * @return Collection<int, WaterServicesData>
     *
     * @throws RuntimeException If the USGS Water Services API returns a non-successful response.
     */
    public function query(WaterServicesQuery $query): Collection
    {
        $response = $this->client->instantaneousValues($query);

        if (! $response->successful()) {
            throw new RuntimeException('The USGS Water Services API returned an error.');
        }

        /** @var array<int, array<string, mixed>> $timeSeries */
        $timeSeries = $response->json('value.timeSeries', []);

        return collect($timeSeries)
            ->map(fn (array $ts): WaterServicesData => $this->parseTimeSeries($ts));
    }

    /**
     * Parse a single WaterML-JSON timeSeries entry into a typed DTO.
     *
     * @param array<string, mixed> $ts
     */
    private function parseTimeSeries(array $ts): WaterServicesData
    {
        $sourceInfo  = $ts['sourceInfo'] ?? [];
        $variable    = $ts['variable'] ?? [];
        $valuesGroup = $ts['values'][0]['value'] ?? [];

        $siteCode = (string) ($sourceInfo['siteCode'][0]['value'] ?? '');
        $siteName = (string) ($sourceInfo['siteName'] ?? '');

        $geoLocation = $sourceInfo['geoLocation']['geogLocation'] ?? [];
        $lat         = (float) ($geoLocation['latitude'] ?? 0);
        $lng         = (float) ($geoLocation['longitude'] ?? 0);

        $parameterCode = (string) ($variable['variableCode'][0]['value'] ?? '');
        // variableName may contain HTML entities such as &#179; (³)
        $parameterName = html_entity_decode((string) ($variable['variableName'] ?? ''), ENT_QUOTES | ENT_HTML5);
        $unitCode      = (string) ($variable['unit']['unitCode'] ?? '');
        $noDataValue   = (float) ($variable['noDataValue'] ?? -999999);

        $latestReading  = ! empty($valuesGroup) ? end($valuesGroup) : null;
        $rawValue       = $latestReading !== null ? (float) ($latestReading['value'] ?? $noDataValue) : null;
        $latestValue    = ($rawValue !== null && $rawValue !== $noDataValue) ? $rawValue : null;
        $latestDateTime = $latestReading !== null ? ((string) ($latestReading['dateTime'] ?? '')) ?: null : null;
        $qualifiers     = array_values(array_map('strval', (array) ($latestReading['qualifiers'] ?? [])));

        // Build the full readings array for sparkline / time-series chart use.
        // Each entry is oldest-first, mirroring the API's natural ordering.
        $allValues = array_map(
            function (array $r) use ($noDataValue): array {
                $raw = (float) ($r['value'] ?? $noDataValue);
                return [
                    'value'    => $raw !== $noDataValue ? $raw : null,
                    'dateTime' => ($r['dateTime'] ?? '') ?: null,
                ];
            },
            $valuesGroup,
        );

        return new WaterServicesData(
            siteCode: $siteCode,
            siteName: $siteName,
            lat: $lat,
            lng: $lng,
            parameterCode: $parameterCode,
            parameterName: $parameterName,
            unitCode: $unitCode,
            latestValue: $latestValue,
            latestDateTime: $latestDateTime,
            qualifiers: $qualifiers,
            allValues: $allValues,
        );
    }
}
