<?php

declare(strict_types=1);

namespace App\Api\Queries;

use InvalidArgumentException;

/**
 * Fluent builder for the NWS active alerts endpoint.
 *
 * Builds the query string for `GET /alerts/active`. The `status=actual`
 * parameter is always injected — the NWS API also serves exercise, test,
 * and draft messages, which are irrelevant for end-user display.
 *
 * Usage:
 *   NWSAlertsQuery::make()
 *       ->area('va')
 *       ->events(['Flood Watch', 'Flash Flood Warning'])
 *       ->severity(['Severe', 'Extreme'])
 *       ->toArray();
 *
 * @see https://www.weather.gov/documentation/services-web-api#/default/alerts_active
 */
class NWSAlertsQuery
{
    /**
     * Valid message status values. 'actual' is always injected; others listed for reference.
     */
    private const STATUSES = ['actual', 'exercise', 'system', 'test', 'draft'];

    /**
     * Valid urgency values as defined by CAP (Common Alerting Protocol).
     */
    private const URGENCIES = ['Unknown', 'Past', 'Future', 'Expected', 'Immediate'];

    /**
     * Valid severity values as defined by CAP.
     */
    private const SEVERITIES = ['Unknown', 'Minor', 'Moderate', 'Severe', 'Extreme'];

    /**
     * Valid certainty values as defined by CAP.
     */
    private const CERTAINTIES = ['Unknown', 'Unlikely', 'Possible', 'Likely', 'Observed'];

    private ?string $area = null;

    private ?string $point = null;

    private ?string $zone = null;

    /** @var array<int, string> */
    private array $events = [];

    /** @var array<int, string> */
    private array $urgencies = [];

    /** @var array<int, string> */
    private array $severities = [];

    /** @var array<int, string> */
    private array $certainties = [];

    private string $regionType = 'land';

    private function __construct()
    {
    }

    /**
     * Create a new NWSAlertsQuery with no filters applied.
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Filter alerts to a specific US state or territory.
     *
     * @param string $area Two-letter state/territory code (e.g. 'va', 'ca', 'pr').
     */
    public function area(string $area): self
    {
        $this->area = strtoupper($area);

        return $this;
    }

    /**
     * Filter alerts affecting the given latitude/longitude point.
     *
     * Mutually exclusive with area() and zone() — the NWS API only accepts one
     * location filter at a time.
     *
     * @param float $lat Latitude in decimal degrees.
     * @param float $lng Longitude in decimal degrees.
     */
    public function point(float $lat, float $lng): self
    {
        $this->point = "{$lat},{$lng}";

        return $this;
    }

    /**
     * Filter alerts to a specific NWS forecast zone (e.g. 'VAZ505').
     *
     * Mutually exclusive with area() and point().
     *
     * @param string $zone NWS zone identifier.
     */
    public function zone(string $zone): self
    {
        $this->zone = strtoupper($zone);

        return $this;
    }

    /**
     * Filter alerts to the given event type names.
     *
     * Common flood-related events:
     *   'Flood Watch', 'Flood Warning', 'Flood Advisory', 'Flood Statement',
     *   'Flash Flood Watch', 'Flash Flood Warning', 'Flash Flood Statement',
     *   'Coastal Flood Watch', 'Coastal Flood Warning', 'Coastal Flood Advisory',
     *   'Lakeshore Flood Watch', 'Lakeshore Flood Warning'
     *
     * @param array<int, string> $events Event type strings.
     */
    public function events(array $events): self
    {
        $this->events = $events;

        return $this;
    }

    /**
     * Filter alerts to the given CAP urgency levels.
     *
     * @param array<int, string> $urgencies Subset of: Unknown, Past, Future, Expected, Immediate.
     *
     * @throws InvalidArgumentException If any value is not a valid urgency.
     */
    public function urgency(array $urgencies): self
    {
        foreach ($urgencies as $u) {
            if (! in_array($u, self::URGENCIES, strict: true)) {
                throw new InvalidArgumentException(
                    "Invalid urgency '{$u}'. Allowed: " . implode(', ', self::URGENCIES),
                );
            }
        }

        $this->urgencies = $urgencies;

        return $this;
    }

    /**
     * Filter alerts to the given CAP severity levels.
     *
     * @param array<int, string> $severities Subset of: Unknown, Minor, Moderate, Severe, Extreme.
     *
     * @throws InvalidArgumentException If any value is not a valid severity.
     */
    public function severity(array $severities): self
    {
        foreach ($severities as $s) {
            if (! in_array($s, self::SEVERITIES, strict: true)) {
                throw new InvalidArgumentException(
                    "Invalid severity '{$s}'. Allowed: " . implode(', ', self::SEVERITIES),
                );
            }
        }

        $this->severities = $severities;

        return $this;
    }

    /**
     * Filter alerts to the given CAP certainty levels.
     *
     * @param array<int, string> $certainties Subset of: Unknown, Unlikely, Possible, Likely, Observed.
     *
     * @throws InvalidArgumentException If any value is not a valid certainty.
     */
    public function certainty(array $certainties): self
    {
        foreach ($certainties as $c) {
            if (! in_array($c, self::CERTAINTIES, strict: true)) {
                throw new InvalidArgumentException(
                    "Invalid certainty '{$c}'. Allowed: " . implode(', ', self::CERTAINTIES),
                );
            }
        }

        $this->certainties = $certainties;

        return $this;
    }

    /**
     * Build and return the parameter array for the HTTP request.
     *
     * Always injects `status=actual`. Validates that at most one location
     * filter (area, point, zone) is present.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException If conflicting location filters are set.
     */
    public function toArray(): array
    {
        $locationFilters = array_filter([$this->area, $this->point, $this->zone]);

        if (count($locationFilters) > 1) {
            throw new InvalidArgumentException(
                'area(), point(), and zone() are mutually exclusive — use only one location filter.',
            );
        }

        return array_filter([
            'status'    => 'actual',
            'area'      => $this->area,
            'point'     => $this->point,
            'zone'      => $this->zone,
            'event'     => $this->events !== [] ? implode(',', $this->events) : null,
            'urgency'   => $this->urgencies !== [] ? implode(',', $this->urgencies) : null,
            'severity'  => $this->severities !== [] ? implode(',', $this->severities) : null,
            'certainty' => $this->certainties !== [] ? implode(',', $this->certainties) : null,
        ], fn ($value) => $value !== null);
    }
}
