<?php

declare(strict_types=1);

namespace App\Api\Queries;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Fluent builder for USGS earthquake query parameters.
 *
 * Required parameters (latitude, longitude, and a radius) are enforced
 * at construction time via make(). All other parameters are optional and
 * set via chained methods. Call toArray() to produce the final parameter
 * map for the HTTP request — validation runs at that point.
 *
 * Usage:
 *   EarthquakeQuery::make(37.77, -122.41)
 *       ->maxradiuskm(100.0)
 *       ->minmagnitude(4.0)
 *       ->starttime(now()->subDays(7))
 *       ->toArray();
 */
class EarthquakeQuery
{
    private ?float $maxradiuskm = null;

    private ?float $maxradius = null;

    private ?Carbon $starttime = null;

    private ?Carbon $endtime = null;

    private ?Carbon $updatedafter = null;

    private int $limit = 100;

    private ?float $maxdepth = null;

    private ?float $maxmagnitude = null;

    private ?float $mindepth = null;

    private ?float $minmagnitude = null;

    private string $orderby = 'time';

    /**
     * @param float|null $latitude  Latitude of the search centre point, or null for a global query.
     * @param float|null $longitude Longitude of the search centre point, or null for a global query.
     */
    private function __construct(
        private readonly ?float $latitude = null,
        private readonly ?float $longitude = null,
    ) {
    }

    /**
     * Create a new EarthquakeQuery for the given centre point.
     *
     * You must also call maxradiuskm() or maxradius() before calling toArray().
     *
     * @param float $latitude  Latitude of the search centre point (decimal degrees).
     * @param float $longitude Longitude of the search centre point (decimal degrees).
     */
    public static function make(float $latitude, float $longitude): self
    {
        return new self($latitude, $longitude);
    }

    /**
     * Create a global EarthquakeQuery with no geographic centre point.
     *
     * No radius is required. Use minmagnitude(), starttime(), etc. to scope results.
     * Omitting all geographic parameters queries the entire USGS ComCat catalogue.
     */
    public static function makeGlobal(): self
    {
        return new self();
    }

    /**
     * Limit results to events within this many kilometres of the centre point.
     *
     * Mutually exclusive with maxradius(). Only set one.
     *
     * @param float $km Maximum radius in kilometres.
     */
    public function maxradiuskm(float $km): self
    {
        $this->maxradiuskm = $km;

        return $this;
    }

    /**
     * Limit results to events within this many degrees of the centre point.
     *
     * Mutually exclusive with maxradiuskm(). Only set one.
     *
     * @param float $degrees Maximum radius in decimal degrees.
     */
    public function maxradius(float $degrees): self
    {
        $this->maxradius = $degrees;

        return $this;
    }

    /**
     * Limit to events on or after this time.
     *
     * The Carbon instance is converted to UTC ISO8601 in toArray().
     * Defaults to 30 days ago on the USGS side when omitted.
     */
    public function starttime(Carbon $time): self
    {
        $this->starttime = $time;

        return $this;
    }

    /**
     * Limit to events on or before this time.
     *
     * The Carbon instance is converted to UTC ISO8601 in toArray().
     * Defaults to now on the USGS side when omitted.
     */
    public function endtime(Carbon $time): self
    {
        $this->endtime = $time;

        return $this;
    }

    /**
     * Limit to events updated after this time.
     *
     * The Carbon instance is converted to UTC ISO8601 in toArray().
     */
    public function updatedafter(Carbon $time): self
    {
        $this->updatedafter = $time;

        return $this;
    }

    /**
     * Maximum number of results to return. Defaults to 100.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Limit to events with depth less than this value (kilometres).
     */
    public function maxdepth(float $km): self
    {
        $this->maxdepth = $km;

        return $this;
    }

    /**
     * Limit to events with a magnitude smaller than this value.
     */
    public function maxmagnitude(float $magnitude): self
    {
        $this->maxmagnitude = $magnitude;

        return $this;
    }

    /**
     * Limit to events with depth greater than this value (kilometres).
     */
    public function mindepth(float $km): self
    {
        $this->mindepth = $km;

        return $this;
    }

    /**
     * Limit to events with a magnitude larger than this value.
     */
    public function minmagnitude(float $magnitude): self
    {
        $this->minmagnitude = $magnitude;

        return $this;
    }

    /**
     * Set the sort order for results.
     *
     * Allowed values: 'time' (default), 'time-asc', 'magnitude', 'magnitude-asc'.
     *
     * @throws InvalidArgumentException If an unsupported orderby value is given.
     */
    public function orderby(string $orderby): self
    {
        $allowed = ['time', 'time-asc', 'magnitude', 'magnitude-asc'];

        if (! in_array($orderby, $allowed, strict: true)) {
            throw new InvalidArgumentException(
                "Invalid orderby value '{$orderby}'. Allowed: " . implode(', ', $allowed),
            );
        }

        $this->orderby = $orderby;

        return $this;
    }

    /**
     * Build and return the parameter array for the HTTP request.
     *
     * Validates that exactly one radius type is set, then formats all
     * Carbon instances to UTC ISO8601 and strips null values.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException If both or neither radius type is set.
     */
    public function toArray(): array
    {
        if ($this->latitude !== null) {
            if ($this->maxradiuskm !== null && $this->maxradius !== null) {
                throw new InvalidArgumentException(
                    'Set maxradiuskm or maxradius — not both. The USGS API treats simultaneous use as undefined behaviour.',
                );
            }

            if ($this->maxradiuskm === null && $this->maxradius === null) {
                throw new InvalidArgumentException(
                    'A radius is required. Call maxradiuskm() or maxradius() before toArray().',
                );
            }
        }

        $params = [
            'format'       => 'geojson',
            'latitude'     => $this->latitude,
            'longitude'    => $this->longitude,
            'maxradiuskm'  => $this->maxradiuskm,
            'maxradius'    => $this->maxradius,
            'starttime'    => $this->formatTime($this->starttime),
            'endtime'      => $this->formatTime($this->endtime),
            'updatedafter' => $this->formatTime($this->updatedafter),
            'limit'        => $this->limit,
            'maxdepth'     => $this->maxdepth,
            'maxmagnitude' => $this->maxmagnitude,
            'mindepth'     => $this->mindepth,
            'minmagnitude' => $this->minmagnitude,
            'orderby'      => $this->orderby,
        ];

        // Strip null values — USGS treats any repeated or blank parameter as undefined.
        return array_filter($params, fn ($value) => $value !== null);
    }

    /**
     * Format a Carbon instance to UTC ISO8601 for the USGS API.
     *
     * Returns null when no time was set so array_filter can strip it cleanly.
     */
    private function formatTime(?Carbon $time): ?string
    {
        return $time?->utc()->toIso8601String();
    }
}
