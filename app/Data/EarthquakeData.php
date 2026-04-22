<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\Carbon;

/**
 * Typed value object representing a single earthquake event from the USGS API.
 *
 * Constructed by EarthquakeService from a GeoJSON feature. Callers never
 * interact with raw GeoJSON — this is the canonical shape throughout the app.
 */
readonly class EarthquakeData
{
    /**
     * @param float       $lat           Decimal degrees latitude of the event.
     * @param float       $lng           Decimal degrees longitude of the event.
     * @param float       $magnitude     Moment magnitude (Mw) of the event.
     * @param string      $place         Human-readable location description from USGS.
     * @param int         $timeMs        Event time as a Unix timestamp in milliseconds.
     * @param float       $depthKm       Hypocentre depth in kilometres.
     * @param string|null $alert         PAGER alert level, or null if not assessed.
     * @param string|null $status        Review status (e.g. 'automatic', 'reviewed').
     * @param string|null $url           USGS event detail page URL.
     * @param string|null $usgsId        USGS ComCat event ID (e.g. 'us7000abc1').
     * @param string|null $magnitudeType Magnitude scale used (e.g. 'mw', 'ml', 'mb').
     * @param int|null    $felt          Number of "Did You Feel It?" reports submitted.
     * @param float|null  $cdi           Maximum community decimal intensity reported.
     * @param float|null  $mmi           Maximum instrumental intensity from ShakeMap (MMI).
     * @param int|null    $significance  USGS significance score 0–1000.
     */
    public function __construct(
        public float $lat,
        public float $lng,
        public float $magnitude,
        public string $place,
        public int $timeMs,
        public float $depthKm,
        public ?string $alert,
        public ?string $status,
        public ?string $url,
        public ?string $usgsId = null,
        public ?string $magnitudeType = null,
        public ?int $felt = null,
        public ?float $cdi = null,
        public ?float $mmi = null,
        public ?int $significance = null,
    ) {
    }

    /**
     * Return a Tailwind class string appropriate for this event's magnitude.
     */
    public function magClass(): string
    {
        return match (true) {
            $this->magnitude >= 6.0 => 'text-danger font-bold',
            $this->magnitude >= 5.0 => 'text-warning font-semibold',
            $this->magnitude >= 4.0 => 'text-warning',
            $this->magnitude >= 2.0 => 'text-text',
            default                 => 'text-muted',
        };
    }

    /**
     * Return the event time formatted for display in the given IANA timezone.
     *
     * Example output: "Monday 3:45pm, April 14th, 2025"
     *
     * @param string $timezone IANA timezone identifier (e.g. 'America/New_York').
     */
    public function formattedTime(string $timezone): string
    {
        return Carbon::createFromTimestampMs($this->timeMs)
            ->setTimezone($timezone)
            ->format('l g:ia, F jS, Y');
    }

    /**
     * Convert to an array suitable for Livewire public property storage.
     *
     * Includes the timezone-formatted time string — pass the component's
     * active timezone so display is consistent with the user's location.
     *
     * @param string $timezone IANA timezone identifier (e.g. 'America/New_York').
     * @return array<string, mixed>
     */
    public function toArray(string $timezone = 'UTC'): array
    {
        return [
            'lat'       => $this->lat,
            'lng'       => $this->lng,
            'magnitude' => $this->magnitude,
            'mag_class' => $this->magClass(),
            'place'     => $this->place,
            'time_ms'   => $this->timeMs,
            'time'      => $this->formattedTime($timezone),
            'depth_km'  => $this->depthKm,
            'alert'     => $this->alert,
            'status'    => $this->status,
            'url'       => $this->url,
        ];
    }
}
