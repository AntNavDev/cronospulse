<?php

declare(strict_types=1);

namespace App\Api\Queries;

use InvalidArgumentException;

/**
 * Fluent builder for USGS Volcano Hazards Program API parameters.
 *
 * The volcanoInfo endpoint returns all USGS-monitored US volcanoes. Filtering
 * by state or alert level is optional — omit both to fetch the full list.
 * Call toArray() to produce the final parameter map for the HTTP request.
 *
 * Usage:
 *   VolcanoQuery::make()
 *       ->state('Hawaii')
 *       ->alertLevel('Watch')
 *       ->toArray();
 */
class VolcanoQuery
{
    /**
     * Valid USGS alert levels in ascending severity order.
     */
    private const ALERT_LEVELS = ['Normal', 'Advisory', 'Watch', 'Warning'];

    private ?string $state = null;

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
     * Filter results to volcanoes in the given US state or territory.
     *
     * @param string $state State name as returned by the USGS API (e.g. 'Hawaii', 'Alaska').
     */
    public function state(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Filter results to volcanoes at the given USGS ground alert level.
     *
     * Allowed values: 'Normal', 'Advisory', 'Watch', 'Warning'.
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
     * Note: the USGS Volcano API may return the full list regardless of params;
     * server-side filtering support is not guaranteed.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'state'      => $this->state,
            'alertLevel' => $this->alertLevel,
        ], fn ($value) => $value !== null);
    }
}
