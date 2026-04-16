<?php

declare(strict_types=1);

namespace App\Api;

use App\Api\Queries\WaterServicesQuery;
use Illuminate\Http\Client\Response;

/**
 * API client for the USGS National Water Information System (NWIS) instantaneous values service.
 *
 * Base URL: https://waterservices.usgs.gov/nwis/iv/
 *
 * Returns real-time and historical instantaneous hydrological readings from
 * USGS monitoring sites — streamflow, gage height, water temperature, and
 * dozens of other parameter codes. Sites span rivers, lakes, springs, and
 * aquifers across the United States and territories.
 *
 * This API is public and does not require an API key. The response format is
 * WaterML-JSON when `format=json` is included in the request (injected automatically
 * by WaterServicesQuery::toArray()).
 *
 * @see https://waterservices.usgs.gov/docs/instantaneous-values/
 */
class USGSWaterServices extends ApiConnection
{
    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = config('api.usgs.water_services_url');
    }

    /**
     * Retrieve instantaneous values for one or more USGS monitoring sites.
     *
     * Returns a WaterML-JSON response containing one or more time series, each
     * representing one site × one parameter combination. The response is parsed
     * by WaterServicesService into WaterServicesData DTOs.
     *
     * Example:
     *   $response = (new USGSWaterServices())->instantaneousValues(
     *       WaterServicesQuery::make()->sites(['01646500'])->parameterCd(['00060'])->period('P1D')
     *   );
     *
     * @param WaterServicesQuery $query Built query object. toArray() is handled internally.
     */
    public function instantaneousValues(WaterServicesQuery $query): Response
    {
        return $this->get('', $query->toArray());
    }
}
