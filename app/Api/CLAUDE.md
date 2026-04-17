# API Layer — CronosPulse

## Purpose

The `app/Api/` directory contains raw HTTP client classes for external data services. Each class wraps one external API and exposes named methods for each endpoint. No business logic, caching, or data transformation belongs here — that lives in `app/Services/`.

## Structure

```
app/Api/
├── ApiConnection.php        # Abstract base class (shared HTTP client setup)
├── NWSAlerts.php            # NWS active alerts client (overrides buildClient for User-Agent + GeoJSON Accept)
├── USGSEarthquake.php       # USGS FDSN Earthquake Catalog client
├── USGSVolcano.php          # USGS Volcano Hazards Program client
├── USGSWaterServices.php    # USGS NWIS Instantaneous Values client
└── Queries/
    ├── EarthquakeQuery.php      # Fluent builder for earthquake query params
    ├── NWSAlertsQuery.php       # Fluent builder for NWS /alerts/active params
    ├── VolcanoQuery.php         # Fluent builder for volcano query params
    └── WaterServicesQuery.php   # Fluent builder for NWIS IV query params
```

## ApiConnection base class

All clients extend `App\Api\ApiConnection`. It handles:
- HTTP client setup via `Http::baseUrl()`
- Optional Bearer token auth (`$apiKey`)
- `get()` and `post()` helper methods

Child class constructors must call `parent::__construct()` and set `$this->baseUrl` from config:

```php
public function __construct()
{
    parent::__construct();
    $this->baseUrl = config('api.usgs.earthquake_url');
}
```

**Never hardcode URLs in the class body.** All base URLs live in `config/api.php` and are read from environment variables (`USGS_EARTHQUAKE_API_URL`, `USGS_VOLCANO_API_URL`, `USGS_WATER_SERVICES_API_URL`, `NWS_ALERTS_API_URL`).

## Naming conventions

- Client classes: `USGS{Service}` for USGS APIs (e.g. `USGSEarthquake`, `USGSVolcano`); `NWS{Service}` for NWS APIs (e.g. `NWSAlerts`)
- Methods map to API endpoint names: `query()` for the FDSN `/query` endpoint, `vhpStatus()` for `/vhpstatus`
- Query builder classes: `{Service}Query` in `Queries/`

## Query builders (`Queries/`)

Each fluent builder corresponds to one API operation. Rules:
- Use a private constructor with a static `make()` factory
- Chain methods set optional parameters and return `$this`
- `toArray()` validates constraints and returns the final parameter map — this is where `InvalidArgumentException` is thrown for bad combinations
- Carbon instances are formatted to UTC ISO8601 inside `toArray()`

## Adding a new API client

1. Create `app/Api/USGSNewService.php` extending `ApiConnection`
2. Set `$this->baseUrl` from a new `config('api.usgs.new_service_url')` entry
3. Add `USGS_NEW_SERVICE_URL` to `config/api.php`, `.env`, `.env.example`, and `.env.production.example`
4. Create a query builder in `Queries/` if the endpoint has non-trivial parameters
5. Register the client as a singleton in `AppServiceProvider`
6. Create a corresponding service class in `app/Services/`

## Authentication

All current USGS APIs are public — no API key is needed. Pass `null` (the default) to the parent constructor. The `$apiKey` mechanism exists for future authenticated services.

## USGS Water Services note

The NWIS IV API (`USGSWaterServices`) does not respect the `Accept: application/json` header. JSON output requires `format=json` as a query parameter — `WaterServicesQuery::toArray()` always injects this. The `buildClient()` `acceptJson()` call is harmless but has no effect on this endpoint.

## NWS Alerts note

The NWS API requires a `User-Agent` header identifying the consuming application (per their Terms of Service) and returns GeoJSON. `NWSAlerts` overrides `buildClient()` to inject both headers. Alert geometry may be null for some products — always check `hasGeometry()` or `$alert['has_geometry']` before attempting map rendering. The `status=actual` parameter is always injected by `NWSAlertsQuery::toArray()` to exclude test and exercise messages.