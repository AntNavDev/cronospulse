<?php

declare(strict_types=1);

namespace App\Api;

use App\Api\Queries\EarthquakeQuery;
use Illuminate\Http\Client\Response;

/**
 * API client for the USGS FDSN Earthquake Catalog Web Service.
 *
 * Base URL: https://earthquake.usgs.gov/fdsnws/event/1/
 *
 * The path segment "event/1" is part of the FDSN Web Services standard:
 *   - "event"  identifies the service type (earthquake event catalog)
 *   - "1"      is the API version number
 *
 * The METHOD segment that follows maps to a specific operation. This class
 * currently uses "query" (event search). Other available methods on the same
 * API are: count, catalogs, contributors, version — add new public methods
 * here if those are ever needed.
 *
 * The USGS earthquake catalog API is public and does not require an API key.
 * The parent constructor still accepts one for consistency should this ever
 * change, but it can be left null.
 *
 * @see https://earthquake.usgs.gov/fdsnws/event/1/
 */
class USGSEarthquake extends ApiConnection
{
    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = config('api.usgs.earthquake_url');
    }

    /**
     * Search for earthquakes matching the given query parameters.
     *
     * Always requests GeoJSON format — USGS recommends this for automated
     * applications as it has the best performance and data accuracy.
     *
     * Example:
     *   $response = $usgs->query(
     *       EarthquakeQuery::make(37.77, -122.41)
     *           ->maxradiuskm(150.0)
     *           ->minmagnitude(3.0)
     *           ->starttime(now()->subDays(30))
     *   );
     *
     * @param EarthquakeQuery $query Built query object. Call toArray() is handled internally.
     */
    public function query(EarthquakeQuery $query): Response
    {
        return $this->get('query', $query->toArray());
    }
}
