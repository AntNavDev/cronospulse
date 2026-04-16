<?php

declare(strict_types=1);

namespace App\Services;

use App\Api\Queries\EarthquakeQuery;
use App\Api\USGSEarthquake;
use RuntimeException;

/**
 * Application service for earthquake data.
 *
 * Wraps the raw USGSEarthquake API client and normalises GeoJSON feature
 * records into a consistent array shape for use in Livewire components and
 * other callers. Keeps timezone-specific time formatting out of scope —
 * callers receive time_ms and are responsible for localising the display.
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
     * Query earthquakes and return normalised feature records.
     *
     * Each record contains: lat, lng, magnitude, mag_class, place,
     * time_ms, depth_km, alert, status, url.
     *
     * time_ms is a Unix timestamp in milliseconds. Callers are responsible
     * for converting it to a localised display string.
     *
     * @return list<array<string, mixed>>
     *
     * @throws RuntimeException If the USGS API returns a non-successful response.
     */
    public function query(EarthquakeQuery $query): array
    {
        $response = $this->client->query($query);

        if (! $response->successful()) {
            throw new RuntimeException('The USGS API returned an error.');
        }

        return collect($response->json('features', []))
            ->map(function (array $feature): array {
                $props  = $feature['properties'];
                $mag    = (float) ($props['mag'] ?? 0);
                $timeMs = (int) $props['time'];
                $lng    = (float) $feature['geometry']['coordinates'][0];
                $lat    = (float) $feature['geometry']['coordinates'][1];

                return [
                    'lat'       => $lat,
                    'lng'       => $lng,
                    'magnitude' => $mag,
                    'mag_class' => $this->magnitudeClass($mag),
                    'place'     => $props['place'] ?? '—',
                    'time_ms'   => $timeMs,
                    'depth_km'  => round((float) ($feature['geometry']['coordinates'][2] ?? 0), 1),
                    'alert'     => $props['alert'] ?? null,
                    'status'    => $props['status'] ?? null,
                    'url'       => $props['url'] ?? null,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Return a Tailwind class string for the given magnitude value.
     */
    private function magnitudeClass(float $magnitude): string
    {
        return match (true) {
            $magnitude >= 6.0 => 'text-danger font-bold',
            $magnitude >= 5.0 => 'text-warning font-semibold',
            $magnitude >= 4.0 => 'text-warning',
            $magnitude >= 2.0 => 'text-text',
            default           => 'text-muted',
        };
    }
}
