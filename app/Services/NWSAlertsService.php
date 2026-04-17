<?php

declare(strict_types=1);

namespace App\Services;

use App\Api\NWSAlerts;
use App\Api\Queries\NWSAlertsQuery;
use App\Data\FloodAlertData;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Application service for NWS active flood alerts.
 *
 * Wraps the raw NWSAlerts API client and parses GeoJSON features into typed
 * FloodAlertData objects, filtering to flood-related event types only.
 *
 * Results are not cached — the alert feed is highly time-sensitive and
 * parameterized by area, which makes a shared cache entry inappropriate.
 *
 * NWS GeoJSON FeatureCollection shape (per feature):
 *   feature.id                    → alert identifier
 *   feature.properties.event      → event type name (e.g. 'Flash Flood Warning')
 *   feature.properties.severity   → CAP severity
 *   feature.properties.urgency    → CAP urgency
 *   feature.properties.certainty  → CAP certainty
 *   feature.properties.headline   → short summary
 *   feature.properties.areaDesc   → geographic description
 *   feature.properties.description → full alert text
 *   feature.properties.instruction → protective action instructions (may be null)
 *   feature.properties.effective  → ISO 8601 effective datetime
 *   feature.properties.expires    → ISO 8601 expiration datetime
 *   feature.geometry              → GeoJSON Polygon / MultiPolygon, or null
 */
class NWSAlertsService
{
    /**
     * @param NWSAlerts $client Raw NWS alerts API client.
     */
    public function __construct(private readonly NWSAlerts $client)
    {
    }

    /**
     * Fetch active alerts and return only flood-related ones as typed DTOs.
     *
     * Filters the response to events listed in FloodAlertData::FLOOD_EVENT_TYPES.
     * Non-flood NWS products (e.g. Wind Advisories, Winter Storm Warnings) are
     * excluded even when the NWS query returns them alongside flood products.
     *
     * @param  NWSAlertsQuery                 $query Built query object.
     * @return Collection<int, FloodAlertData>
     *
     * @throws RuntimeException If the NWS API returns a non-successful response.
     */
    public function activeFloodAlerts(NWSAlertsQuery $query): Collection
    {
        $response = $this->client->alerts($query);

        if (! $response->successful()) {
            throw new RuntimeException('The NWS Alerts API returned an error.');
        }

        /** @var array<int, array<string, mixed>> $features */
        $features = $response->json('features', []);

        return collect($features)
            ->filter(fn (array $feature): bool => $this->isFloodEvent($feature))
            ->values()
            ->map(fn (array $feature): FloodAlertData => $this->parseFeature($feature));
    }

    /**
     * Return whether a GeoJSON feature is a flood-related alert.
     *
     * @param array<string, mixed> $feature
     */
    private function isFloodEvent(array $feature): bool
    {
        $event = (string) ($feature['properties']['event'] ?? '');

        return in_array($event, FloodAlertData::FLOOD_EVENT_TYPES, strict: true);
    }

    /**
     * Parse a single GeoJSON feature into a typed FloodAlertData DTO.
     *
     * @param array<string, mixed> $feature
     */
    private function parseFeature(array $feature): FloodAlertData
    {
        $props = $feature['properties'] ?? [];

        // Derive the two-letter state code from the first UGC geocode entry.
        // UGC codes follow the pattern {STATE}{TYPE}{number} (e.g. 'VAZ505'),
        // so the first two characters are always the uppercase state abbreviation.
        $ugcCodes  = $props['geocode']['UGC'] ?? [];
        $stateCode = null;

        if (! empty($ugcCodes)) {
            $firstCode = (string) ($ugcCodes[0] ?? '');

            if (strlen($firstCode) >= 2) {
                $stateCode = strtolower(substr($firstCode, 0, 2));
            }
        }

        return new FloodAlertData(
            id: (string) ($feature['id'] ?? ''),
            event: (string) ($props['event'] ?? ''),
            severity: (string) ($props['severity'] ?? 'Unknown'),
            urgency: (string) ($props['urgency'] ?? 'Unknown'),
            certainty: (string) ($props['certainty'] ?? 'Unknown'),
            headline: (string) ($props['headline'] ?? ''),
            areaDesc: (string) ($props['areaDesc'] ?? ''),
            description: (string) ($props['description'] ?? ''),
            instruction: isset($props['instruction']) ? (string) $props['instruction'] : null,
            effective: isset($props['effective']) ? (string) $props['effective'] : null,
            expires: isset($props['expires']) ? (string) $props['expires'] : null,
            geometry: isset($feature['geometry']) && is_array($feature['geometry'])
                ? $feature['geometry']
                : null,
            stateCode: $stateCode,
        );
    }
}
