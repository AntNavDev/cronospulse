<?php

declare(strict_types=1);

namespace App\Api;

use App\Api\Queries\VolcanoQuery;
use Illuminate\Http\Client\Response;

/**
 * API client for the USGS Volcano Hazards Program monitoring service.
 *
 * Base URL: https://volcanoes.usgs.gov/vsc/api/
 *
 * Returns real-time monitoring data for all USGS-tracked US volcanoes,
 * including current ground alert levels, aviation color codes, and
 * activity descriptions. Coverage includes Hawaii, Alaska, the Cascades,
 * and other US territories.
 *
 * This API is public and does not require an API key. The parent constructor
 * accepts one for consistency, but it can be left null.
 *
 * @see https://volcanoes.usgs.gov/vhp/
 */
class USGSVolcano extends ApiConnection
{
    protected string $baseUrl = 'https://volcanoes.usgs.gov/vsc/api/';

    /**
     * Retrieve current monitoring data for USGS-tracked volcanoes.
     *
     * Returns the full list when no filters are set on the query.
     * The API may return all records regardless of query parameters;
     * callers should apply additional filtering on the response if needed.
     *
     * Example:
     *   $response = (new USGSVolcano())->volcanoInfo(VolcanoQuery::make());
     *   $response = (new USGSVolcano())->volcanoInfo(VolcanoQuery::make()->state('Alaska'));
     *
     * @param VolcanoQuery $query Built query object. toArray() is handled internally.
     */
    public function volcanoInfo(VolcanoQuery $query): Response
    {
        return $this->get('volcanoInfo', $query->toArray());
    }
}
