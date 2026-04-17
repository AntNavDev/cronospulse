<?php

declare(strict_types=1);

namespace App\Api;

use App\Api\Queries\NWSAlertsQuery;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * API client for the National Weather Service (NWS) alerts service.
 *
 * Base URL: https://api.weather.gov/
 *
 * Returns active NWS alerts including flood watches, warnings, and advisories.
 * Coverage is the United States and its territories. This API is public and
 * does not require an API key.
 *
 * NWS User-Agent requirement: the NWS Terms of Service require a User-Agent
 * header identifying the consuming application. buildClient() overrides the
 * parent to inject this header alongside the GeoJSON Accept type.
 *
 * @see https://www.weather.gov/documentation/services-web-api
 */
class NWSAlerts extends ApiConnection
{
    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = config('api.nws.alerts_url');
    }

    /**
     * Retrieve active alerts matching the given query filters.
     *
     * Returns a GeoJSON FeatureCollection. Each feature is one active alert
     * with geometry (polygon of affected area, or null) and properties
     * including event type, severity, urgency, headline, and full description.
     *
     * Example:
     *   $response = (new NWSAlerts())->alerts(
     *       NWSAlertsQuery::make()->area('va')->status('actual')
     *   );
     *
     * @param NWSAlertsQuery $query Built query object. toArray() is handled internally.
     */
    public function alerts(NWSAlertsQuery $query): Response
    {
        return $this->get('alerts/active', $query->toArray());
    }

    /**
     * Override the base HTTP client to inject the NWS-required User-Agent header
     * and the GeoJSON Accept type.
     *
     * NWS identifies applications by User-Agent and may rate-limit or block
     * requests from clients that don't provide one. The recommended format is
     * (your-app-domain, contact-email).
     */
    protected function buildClient(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'User-Agent' => sprintf('(%s, noreply@cronospulse.com)', rtrim(config('app.url'), '/')),
                'Accept'     => 'application/geo+json',
            ]);
    }
}