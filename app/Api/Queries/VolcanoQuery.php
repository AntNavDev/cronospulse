<?php

declare(strict_types=1);

namespace App\Api\Queries;

use InvalidArgumentException;

/**
 * Fluent builder for USGS Volcano Hazards Program API parameters.
 *
 * The vhpstatus endpoint returns all USGS-monitored volcanoes. Filtering
 * by region or alert level is optional — the API returns the full list
 * regardless of params; any filtering is applied in the caller.
 * Call toArray() to produce the final parameter map for the HTTP request.
 *
 * Usage:
 *   VolcanoQuery::make()
 *       ->region('Aleutians')
 *       ->alertLevel('WATCH')
 *       ->toArray();
 */
class VolcanoQuery
{
    /**
     * Valid USGS alert levels in ascending severity order.
     */
    private const ALERT_LEVELS = ['NORMAL', 'ADVISORY', 'WATCH', 'WARNING'];

    private ?string $region = null;

    private ?string $alertLevel = null;

    private function __construct()
    {
    }

    /**
     * Create a new VolcanoQuery with no filters applied.
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Filter results to volcanoes in the given USGS region.
     *
     * @param string $region Region name as returned by the API (e.g. 'Aleutians', 'Hawaii').
     */
    public function region(string $region): self
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Filter results to volcanoes at the given USGS ground alert level.
     *
     * Allowed values: 'NORMAL', 'ADVISORY', 'WATCH', 'WARNING'.
     *
     * @throws InvalidArgumentException If an unsupported alert level is given.
     */
    public function alertLevel(string $alertLevel): self
    {
        if (! in_array($alertLevel, self::ALERT_LEVELS, strict: true)) {
            throw new InvalidArgumentException(
                "Invalid alert level '{$alertLevel}'. Allowed: " . implode(', ', self::ALERT_LEVELS),
            );
        }

        $this->alertLevel = $alertLevel;

        return $this;
    }

    /**
     * Build and return the parameter array for the HTTP request.
     *
     * Strips null values — unset filters are omitted from the request.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'region'     => $this->region,
            'alertLevel' => $this->alertLevel,
        ], fn ($value) => $value !== null);
    }
}
