<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\Carbon;

/**
 * Typed value object representing one USGS monitoring site × parameter time series.
 *
 * Constructed by WaterServicesService from a WaterML-JSON `timeSeries` entry.
 * Each instance represents a single parameter (e.g. streamflow) at a single site.
 * The `latestValue` and `latestDateTime` fields reflect the most recent reading.
 *
 * USGS qualifier codes on readings:
 *   P — Provisional data, subject to revision
 *   A — Approved for publication, processing and review completed
 *   e — Value has been estimated
 *   Ice — Ice affected
 */
readonly class WaterServicesData
{
    /**
     * @param string      $siteCode      USGS site number (e.g. '01646500').
     * @param string      $siteName      Human-readable site name (e.g. 'Potomac River near Washington DC').
     * @param float       $lat           Decimal degrees latitude of the monitoring site.
     * @param float       $lng           Decimal degrees longitude of the monitoring site.
     * @param string      $parameterCode USGS parameter code (e.g. '00060').
     * @param string      $parameterName Human-readable parameter name (e.g. 'Streamflow, ft³/s').
     * @param string      $unitCode      Unit abbreviation (e.g. 'ft3/s', 'ft', 'degC').
     * @param float|null  $latestValue   Most recent reading, or null if the site reported no-data.
     * @param string|null $latestDateTime ISO 8601 datetime of the most recent reading.
     * @param string[]    $qualifiers    Qualifier codes on the latest reading (e.g. ['P'], ['A']).
     */
    public function __construct(
        public string $siteCode,
        public string $siteName,
        public float $lat,
        public float $lng,
        public string $parameterCode,
        public string $parameterName,
        public string $unitCode,
        public ?float $latestValue,
        public ?string $latestDateTime,
        public array $qualifiers,
    ) {
    }

    /**
     * Return whether the latest reading is provisional (subject to revision).
     *
     * Provisional data (qualifier 'P') has not yet been reviewed by USGS and
     * may change. Display a disclaimer when this returns true.
     */
    public function isProvisional(): bool
    {
        return in_array('P', $this->qualifiers, strict: true);
    }

    /**
     * Return the latest reading time formatted for display in the given IANA timezone.
     *
     * Returns null if no datetime is available (e.g. no readings in the period).
     *
     * Example output: "Monday 3:45pm, April 14th, 2025"
     *
     * @param string $timezone IANA timezone identifier (e.g. 'America/New_York').
     */
    public function formattedTime(string $timezone): ?string
    {
        if ($this->latestDateTime === null) {
            return null;
        }

        return Carbon::parse($this->latestDateTime)
            ->setTimezone($timezone)
            ->format('l g:ia, F jS, Y');
    }

    /**
     * Return a Tailwind class string for the latest value based on common thresholds.
     *
     * Currently returns a neutral class — concrete thresholds are parameter-specific
     * (flood stage for gage height, advisory levels for streamflow) and should be
     * implemented in the service or component once alert thresholds are known.
     */
    public function valueClass(): string
    {
        if ($this->latestValue === null) {
            return 'text-muted';
        }

        return 'text-text';
    }

    /**
     * Convert to an array suitable for Livewire public property storage.
     *
     * @param string $timezone IANA timezone identifier (e.g. 'America/New_York').
     * @return array<string, mixed>
     */
    public function toArray(string $timezone = 'UTC'): array
    {
        return [
            'site_code'       => $this->siteCode,
            'site_name'       => $this->siteName,
            'lat'             => $this->lat,
            'lng'             => $this->lng,
            'parameter_code'  => $this->parameterCode,
            'parameter_name'  => $this->parameterName,
            'unit_code'       => $this->unitCode,
            'latest_value'    => $this->latestValue,
            'latest_datetime' => $this->latestDateTime,
            'latest_time'     => $this->formattedTime($timezone),
            'qualifiers'      => $this->qualifiers,
            'is_provisional'  => $this->isProvisional(),
            'value_class'     => $this->valueClass(),
        ];
    }
}
