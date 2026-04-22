<?php

declare(strict_types=1);

namespace App\Services;

use App\Api\Queries\EarthquakeQuery;
use App\Api\USGSEarthquake;
use App\Data\EarthquakeData;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Application service for earthquake data.
 *
 * Wraps the raw USGSEarthquake API client and parses GeoJSON feature records
 * into typed EarthquakeData objects. No GeoJSON handling occurs outside this
 * class — callers always receive a typed collection.
 */
class EarthquakeService
{
    /**
     * @param USGSEarthquake $client Raw USGS FDSN API client.
     */
    public function __construct(private readonly USGSEarthquake $client)
    {
    }

    /**
     * Query earthquakes and return a typed collection of results.
     *
     * @return Collection<int, EarthquakeData>
     *
     * @throws RuntimeException If the USGS API returns a non-successful response.
     */
    public function query(EarthquakeQuery $query): Collection
    {
        $response = $this->client->query($query);

        if (! $response->successful()) {
            throw new RuntimeException('The USGS API returned an error.');
        }

        return collect($response->json('features', []))
            ->map(function (array $feature): EarthquakeData {
                $props = $feature['properties'];
                $lng   = (float) $feature['geometry']['coordinates'][0];
                $lat   = (float) $feature['geometry']['coordinates'][1];

                return new EarthquakeData(
                    lat: $lat,
                    lng: $lng,
                    magnitude: (float) ($props['mag'] ?? 0),
                    place: $props['place'] ?? '—',
                    timeMs: (int) $props['time'],
                    depthKm: round((float) ($feature['geometry']['coordinates'][2] ?? 0), 1),
                    alert: $props['alert'] ?? null,
                    status: $props['status'] ?? null,
                    url: $props['url'] ?? null,
                    usgsId: $feature['id'] ?? null,
                    magnitudeType: $props['magType'] ?? null,
                    felt: isset($props['felt']) ? (int) $props['felt'] : null,
                    cdi: isset($props['cdi']) ? (float) $props['cdi'] : null,
                    mmi: isset($props['mmi']) ? (float) $props['mmi'] : null,
                    significance: isset($props['sig']) ? (int) $props['sig'] : null,
                );
            });
    }
}
