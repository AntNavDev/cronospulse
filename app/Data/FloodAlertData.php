<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\Carbon;

/**
 * Typed value object representing one NWS active flood alert.
 *
 * Constructed by NWSAlertsService from a GeoJSON feature in the
 * `/alerts/active` response. Covers watches, warnings, advisories,
 * and statements for all flood-related NWS event types.
 *
 * CAP severity scale (high → low): Extreme, Severe, Moderate, Minor, Unknown.
 * CAP urgency scale (most urgent → least): Immediate, Expected, Future, Past, Unknown.
 */
readonly class FloodAlertData
{
    /**
     * All NWS event type names considered flood-related.
     *
     * Used by NWSAlertsService to filter the full alerts feed.
     * This list reflects current NWS product definitions — update
     * if NWS introduces new product types.
     *
     * @var array<int, string>
     */
    public const FLOOD_EVENT_TYPES = [
        'Flood Watch',
        'Flood Warning',
        'Flood Advisory',
        'Flood Statement',
        'Flash Flood Watch',
        'Flash Flood Warning',
        'Flash Flood Statement',
        'Coastal Flood Watch',
        'Coastal Flood Warning',
        'Coastal Flood Advisory',
        'Coastal Flood Statement',
        'Lakeshore Flood Watch',
        'Lakeshore Flood Warning',
        'Lakeshore Flood Advisory',
        'Lakeshore Flood Statement',
        'Arroyo And Small Stream Flood Advisory',
        'Small Stream Flood Advisory',
        'Hydrologic Advisory',
        'Hydrologic Outlook',
    ];

    /**
     * @param string      $id          NWS alert identifier (e.g. 'urn:oid:2.49.0.1.840.0.XYZ').
     * @param string      $event       NWS event type name (e.g. 'Flash Flood Warning').
     * @param string      $severity    CAP severity: Extreme, Severe, Moderate, Minor, or Unknown.
     * @param string      $urgency     CAP urgency: Immediate, Expected, Future, Past, or Unknown.
     * @param string      $certainty   CAP certainty: Observed, Likely, Possible, Unlikely, or Unknown.
     * @param string      $headline    Short one-line summary of the alert.
     * @param string      $areaDesc    Geographic description of the affected area.
     * @param string      $description Full text of the alert body.
     * @param string|null $instruction Protective action instructions, or null if not provided.
     * @param string|null $effective   ISO 8601 datetime when the alert becomes effective.
     * @param string|null $expires     ISO 8601 datetime when the alert expires.
     * @param array|null  $geometry    GeoJSON geometry object (Polygon or MultiPolygon), or null.
     * @param string|null $stateCode   Two-letter lowercase state code derived from UGC geocode (e.g. 'va'), or null.
     */
    public function __construct(
        public string $id,
        public string $event,
        public string $severity,
        public string $urgency,
        public string $certainty,
        public string $headline,
        public string $areaDesc,
        public string $description,
        public ?string $instruction,
        public ?string $effective,
        public ?string $expires,
        public ?array $geometry,
        public ?string $stateCode = null,
    ) {
    }

    /**
     * Return a Tailwind text-color class based on CAP severity.
     *
     * Extreme/Severe → danger, Moderate → warning, Minor → info, Unknown → muted.
     */
    public function severityClass(): string
    {
        return match ($this->severity) {
            'Extreme', 'Severe' => 'text-danger',
            'Moderate'          => 'text-warning',
            'Minor'             => 'text-info',
            default             => 'text-muted',
        };
    }

    /**
     * Return inline style values for the severity badge.
     *
     * Maps to the `--color-badge-flood-*` CSS variables defined in app.css,
     * using the flood badge family for all flood alert types.
     *
     * Returns an associative array with 'bg', 'text', and 'border' keys.
     *
     * @return array{bg: string, text: string, border: string}
     */
    public function severityBadgeStyle(): array
    {
        return match ($this->severity) {
            'Extreme', 'Severe' => [
                'bg'     => 'var(--color-danger)',
                'text'   => '#ffffff',
                'border' => 'var(--color-danger)',
            ],
            'Moderate' => [
                'bg'     => 'var(--color-badge-flood-bg)',
                'text'   => 'var(--color-badge-flood-text)',
                'border' => 'var(--color-badge-flood-border)',
            ],
            default => [
                'bg'     => 'var(--color-surface-raised)',
                'text'   => 'var(--color-text-muted)',
                'border' => 'var(--color-border)',
            ],
        };
    }

    /**
     * Return whether the alert has a geographic polygon suitable for map rendering.
     */
    public function hasGeometry(): bool
    {
        return $this->geometry !== null;
    }

    /**
     * Return the effective datetime formatted for display.
     *
     * @param string $timezone IANA timezone identifier (e.g. 'America/New_York').
     */
    public function formattedEffective(string $timezone): ?string
    {
        if ($this->effective === null) {
            return null;
        }

        return Carbon::parse($this->effective)
            ->setTimezone($timezone)
            ->format('l g:ia, F jS');
    }

    /**
     * Return the expiration datetime formatted for display.
     *
     * @param string $timezone IANA timezone identifier (e.g. 'America/New_York').
     */
    public function formattedExpires(string $timezone): ?string
    {
        if ($this->expires === null) {
            return null;
        }

        return Carbon::parse($this->expires)
            ->setTimezone($timezone)
            ->format('l g:ia, F jS');
    }

    /**
     * Return whether the alert has already expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires === null) {
            return false;
        }

        return Carbon::parse($this->expires)->isPast();
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
            'id'                 => $this->id,
            'event'              => $this->event,
            'severity'           => $this->severity,
            'urgency'            => $this->urgency,
            'certainty'          => $this->certainty,
            'headline'           => $this->headline,
            'area_desc'          => $this->areaDesc,
            'description'        => $this->description,
            'instruction'        => $this->instruction,
            'effective'          => $this->effective,
            'expires'            => $this->expires,
            'formatted_effective' => $this->formattedEffective($timezone),
            'formatted_expires'  => $this->formattedExpires($timezone),
            'is_expired'         => $this->isExpired(),
            'has_geometry'       => $this->hasGeometry(),
            'geometry'           => $this->geometry,
            'severity_class'     => $this->severityClass(),
            'severity_badge'     => $this->severityBadgeStyle(),
            'state_code'         => $this->stateCode,
        ];
    }
}
