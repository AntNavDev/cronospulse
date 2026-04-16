<?php

declare(strict_types=1);

namespace App\Api;

use App\Api\Queries\VolcanoQuery;
use Illuminate\Http\Client\Response;

/**
 * API client for the USGS Volcano Hazards Program monitoring service.
 *
 * Base URL: https://volcanoes.usgs.gov/vsc/api/volcanoApi/
 *
 * Returns real-time monitoring data for all USGS-tracked volcanoes including
 * current ground alert levels, aviation color codes, coordinates, and the
 * latest notice synopsis. Coverage includes Hawaii, Alaska, the Cascades,
 * the Northern Mariana Islands, and other US territories.
 *
 * This API is public and does not require an API key. The parent constructor
 * accepts one for consistency, but it can be left null.
 *
 * @see https://volcanoes.usgs.gov/vhp/
 */
class USGSVolcano extends ApiConnection
{
    protected string $baseUrl = 'https://volcanoes.usgs.gov/vsc/api/volcanoApi/';

    /**
     * Retrieve current VHP status for all USGS-tracked volcanoes.
     *
     * Returns a flat array of volcano objects including coordinates,
     * alert level, aviation color code, and the latest notice synopsis.
     * The API returns all records — filtering by region or alert level
     * is applied in the caller rather than server-side.
     *
     * Example:
     *   $response = (new USGSVolcano())->vhpStatus(VolcanoQuery::make());
     *
     * @param VolcanoQuery $query Built query object. toArray() is handled internally.
     */
    public function vhpStatus(VolcanoQuery $query): Response
    {
        return $this->get('vhpstatus', $query->toArray());
    }
}
