<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Typed value object representing a single USGS-monitored volcano.
 *
 * Constructed by VolcanoService from a VHP status record. Callers never
 * interact with raw camelCase API fields — this is the canonical shape
 * throughout the app.
 */
readonly class VolcanoData
{
    /**
     * @param string      $vnum       USGS volcano number (unique identifier).
     * @param string      $name       Volcano name.
     * @param string      $region     Geographic region (e.g. 'Alaska', 'Hawaii').
     * @param float       $latitude   Decimal degrees latitude of the volcano.
     * @param float       $longitude  Decimal degrees longitude of the volcano.
     * @param string      $alertLevel Ground-based alert level (NORMAL/ADVISORY/WATCH/WARNING/UNASSIGNED).
     * @param string      $colorCode  Aviation color code (GREEN/YELLOW/ORANGE/RED/UNASSIGNED).
     * @param string|null $synopsis   Latest monitoring notice synopsis, or null if none.
     * @param string|null $url        USGS volcano detail page URL.
     */
    public function __construct(
        public string $vnum,
        public string $name,
        public string $region,
        public float $latitude,
        public float $longitude,
        public string $alertLevel,
        public string $colorCode,
        public ?string $synopsis,
        public ?string $url,
    ) {
    }

    /**
     * Return Tailwind badge classes for this volcano's ground alert level.
     *
     * Levels in ascending severity: NORMAL → ADVISORY → WATCH → WARNING.
     * UNASSIGNED indicates no active monitoring notice.
     */
    public function alertClass(): string
    {
        return match ($this->alertLevel) {
            'WARNING'  => 'bg-danger/15 text-danger',
            'WATCH'    => 'bg-warning/15 text-warning',
            'ADVISORY' => 'bg-info/15 text-info',
            'NORMAL'   => 'bg-success/15 text-success',
            default    => 'bg-surface-raised text-muted',
        };
    }

    /**
     * Return Tailwind badge classes for this volcano's aviation color code.
     *
     * Codes in ascending severity: GREEN → YELLOW → ORANGE → RED.
     * UNASSIGNED indicates no active aviation notice.
     */
    public function colorClass(): string
    {
        return match ($this->colorCode) {
            'RED'    => 'bg-danger/15 text-danger',
            'ORANGE' => 'bg-warning/15 text-warning',
            'YELLOW' => 'bg-info/15 text-info',
            'GREEN'  => 'bg-success/15 text-success',
            default  => 'bg-surface-raised text-muted',
        };
    }

    /**
     * Convert to an array suitable for Livewire public property storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'vnum'        => $this->vnum,
            'name'        => $this->name,
            'region'      => $this->region,
            'latitude'    => $this->latitude,
            'longitude'   => $this->longitude,
            'alert_level' => $this->alertLevel,
            'alert_class' => $this->alertClass(),
            'color_code'  => $this->colorCode,
            'color_class' => $this->colorClass(),
            'synopsis'    => $this->synopsis,
            'url'         => $this->url,
        ];
    }
}
