<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | USGS API Endpoints
    |--------------------------------------------------------------------------
    |
    | Base URLs for the USGS data services consumed by CronosPulse. Both
    | endpoints are public and do not require authentication. Defaults are
    | provided so existing environments work without adding the variables,
    | but they should be set explicitly in .env for clarity.
    |
    */

    'usgs' => [
        'earthquake_url'     => env('USGS_EARTHQUAKE_API_URL', 'https://earthquake.usgs.gov/fdsnws/event/1/'),
        'volcano_url'        => env('USGS_VOLCANO_API_URL', 'https://volcanoes.usgs.gov/vsc/api/volcanoApi/'),
        'water_services_url' => env('USGS_WATER_SERVICES_API_URL', 'https://waterservices.usgs.gov/nwis/iv/'),
    ],

    'nws' => [
        'alerts_url' => env('NWS_ALERTS_API_URL', 'https://api.weather.gov/'),
    ],

];
