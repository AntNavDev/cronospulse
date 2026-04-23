<?php

declare(strict_types=1);

namespace App\Api\Queries;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Fluent builder for USGS NWIS Instantaneous Values API parameters.
 *
 * Builds the query string for the `/nwis/iv/` endpoint. Either a `period` or
 * a `startDT`/`endDT` range may be specified — not both. At least one site
 * selector (`sites`, `stateCd`, or `countyCd`) is required and validated in
 * toArray(). The `format=json` parameter is always injected automatically.
 *
 * Usage:
 *   WaterServicesQuery::make()
 *       ->sites(['01646500', '01638500'])
 *       ->parameterCd(['00060', '00065'])
 *       ->period('P7D')
 *       ->toArray();
 *
 * @see https://waterservices.usgs.gov/docs/instantaneous-values/
 */
class WaterServicesQuery
{
    /**
     * Valid USGS site type codes.
     */
    private const SITE_TYPES = ['ST', 'ST-CA', 'ST-DCH', 'ST-TS', 'LK', 'WE', 'ES', 'GW', 'SB', 'SP', 'AT', 'OC', 'OC-CO'];

    /**
     * Valid site status values.
     */
    private const SITE_STATUSES = ['all', 'active', 'inactive'];

    /** @var array<int, string> */
    private array $sites = [];

    private ?string $stateCd = null;

    private ?string $countyCd = null;

    /** @var array<int, string> */
    private array $parameterCd = [];

    private ?string $period = null;

    private ?Carbon $startDt = null;

    private ?Carbon $endDt = null;

    private ?string $siteType = null;

    private ?string $siteStatus = null;

    private ?string $siteOutput = null;

    private function __construct()
    {
    }

    /**
     * Create a new WaterServicesQuery with no filters applied.
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * Filter results to the given USGS site numbers.
     *
     * @param array<int, string> $sites USGS site numbers (e.g. ['01646500', '01638500']).
     */
    public function sites(array $sites): self
    {
        $this->sites = $sites;

        return $this;
    }

    /**
     * Filter results to sites within the given two-letter US state code.
     *
     * @param string $stateCd Two-letter state abbreviation (e.g. 'va', 'ca').
     */
    public function stateCd(string $stateCd): self
    {
        $this->stateCd = strtolower($stateCd);

        return $this;
    }

    /**
     * Filter results to sites within the given FIPS county code.
     *
     * @param string $countyCd FIPS county code (e.g. '51059' for Fairfax County, VA).
     */
    public function countyCd(string $countyCd): self
    {
        $this->countyCd = $countyCd;

        return $this;
    }

    /**
     * Filter results to the given USGS parameter codes.
     *
     * Common codes:
     *   00060 — Streamflow (ft³/s)
     *   00065 — Gage height (ft)
     *   00010 — Water temperature (°C)
     *   00300 — Dissolved oxygen (mg/L)
     *   00400 — pH
     *
     * @param array<int, string> $codes Parameter codes (e.g. ['00060', '00065']).
     */
    public function parameterCd(array $codes): self
    {
        $this->parameterCd = $codes;

        return $this;
    }

    /**
     * Return data for the last N days/hours using an ISO 8601 duration.
     *
     * Mutually exclusive with startDt()/endDt(). Cannot be used together.
     *
     * @param string $period ISO 8601 duration (e.g. 'P7D' for 7 days, 'PT2H' for 2 hours).
     */
    public function period(string $period): self
    {
        $this->period = $period;

        return $this;
    }

    /**
     * Return data starting from the given datetime (inclusive).
     *
     * Mutually exclusive with period(). Pair with endDt() for a bounded range.
     *
     * @param Carbon $startDt Start of the query window.
     */
    public function startDt(Carbon $startDt): self
    {
        $this->startDt = $startDt;

        return $this;
    }

    /**
     * Return data up to the given datetime (inclusive).
     *
     * Mutually exclusive with period(). Pair with startDt() for a bounded range.
     *
     * @param Carbon $endDt End of the query window.
     */
    public function endDt(Carbon $endDt): self
    {
        $this->endDt = $endDt;

        return $this;
    }

    /**
     * Filter results to sites of the given USGS site type.
     *
     * Common types:
     *   ST  — Stream
     *   LK  — Lake, reservoir, or impoundment
     *   GW  — Groundwater well
     *   SP  — Spring
     *   WE  — Wetland
     *   ES  — Estuary
     *
     * @throws InvalidArgumentException If an unsupported site type is given.
     */
    public function siteType(string $siteType): self
    {
        if (! in_array($siteType, self::SITE_TYPES, strict: true)) {
            throw new InvalidArgumentException(
                "Invalid site type '{$siteType}'. Allowed: " . implode(', ', self::SITE_TYPES),
            );
        }

        $this->siteType = $siteType;

        return $this;
    }

    /**
     * Control the amount of site metadata included in the response.
     *
     * Use 'expanded' to include `sourceInfo.siteProperty[]` — an array of key/value
     * pairs containing state FIPS code, HUC, county FIPS code, and site type code.
     * Defaults to 'default' (basic metadata only) when omitted.
     *
     * @param string $output Either 'default' or 'expanded'.
     */
    public function siteOutput(string $output): self
    {
        $this->siteOutput = $output;

        return $this;
    }

    /**
     * Filter results to sites with the given operational status.
     *
     * @param string $siteStatus One of 'all', 'active', or 'inactive'. Defaults to 'all' in the API.
     *
     * @throws InvalidArgumentException If an unsupported status value is given.
     */
    public function siteStatus(string $siteStatus): self
    {
        if (! in_array($siteStatus, self::SITE_STATUSES, strict: true)) {
            throw new InvalidArgumentException(
                "Invalid site status '{$siteStatus}'. Allowed: " . implode(', ', self::SITE_STATUSES),
            );
        }

        $this->siteStatus = $siteStatus;

        return $this;
    }

    /**
     * Build and return the parameter array for the HTTP request.
     *
     * Validates that:
     * - At least one site selector is present (sites, stateCd, or countyCd).
     * - `period` and `startDT`/`endDT` are not mixed.
     *
     * Always injects `format=json` — the USGS IV API does not respect the
     * Accept header and requires this query parameter for JSON output.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException If required parameters are missing or conflicting.
     */
    public function toArray(): array
    {
        $hasSiteSelector = $this->sites !== [] || $this->stateCd !== null || $this->countyCd !== null;

        if (! $hasSiteSelector) {
            throw new InvalidArgumentException(
                'At least one site selector is required: sites(), stateCd(), or countyCd().',
            );
        }

        if ($this->period !== null && ($this->startDt !== null || $this->endDt !== null)) {
            throw new InvalidArgumentException(
                'period() and startDt()/endDt() are mutually exclusive. Use one or the other.',
            );
        }

        return array_filter([
            'format'      => 'json',
            'sites'       => $this->sites !== [] ? implode(',', $this->sites) : null,
            'stateCd'     => $this->stateCd,
            'countyCd'    => $this->countyCd,
            'parameterCd' => $this->parameterCd !== [] ? implode(',', $this->parameterCd) : null,
            'period'      => $this->period,
            'startDT'     => $this->startDt?->toIso8601String(),
            'endDT'       => $this->endDt?->toIso8601String(),
            'siteType'    => $this->siteType,
            'siteStatus'  => $this->siteStatus,
            'siteOutput'  => $this->siteOutput,
        ], fn ($value) => $value !== null);
    }
}
