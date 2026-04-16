# Services Layer — CronosPulse

## Purpose

Service classes in `app/Services/` sit between raw API clients (`app/Api/`) and Livewire components. They are responsible for:
- Calling the API client and handling non-successful responses
- Transforming raw API responses into normalised, snake_case array shapes
- Caching where appropriate

**Livewire components must never instantiate or call API clients directly.** Always go through a service.

## Current services

| Class | Wraps | Caching |
|---|---|---|
| `EarthquakeService` | `USGSEarthquake` | None — parameterized search |
| `VolcanoService` | `USGSVolcano` | `Cache::remember('usgs.volcanoes.all', 300)` — full dataset, no params |

## Injection into Livewire components

Services are injected via a `boot()` method, which Livewire calls on every lifecycle request (initial render and hydration). This avoids the "typed property must not be accessed before initialization" error that `#[Inject]` can trigger.

```php
use App\Services\EarthquakeService;

class QuakeWatch extends Component
{
    protected EarthquakeService $earthquakeService;

    public function boot(EarthquakeService $earthquakeService): void
    {
        $this->earthquakeService = $earthquakeService;
    }
}
```

Do not use `#[Inject]` for services in this project. Do not inject via `mount()` — that only runs on initial render, so the property would be unset on subsequent Livewire requests.

All services are registered as singletons in `AppServiceProvider` alongside their underlying API clients.

## Error handling

Services throw `\RuntimeException` when the API returns a non-successful HTTP response. They do not catch network-level exceptions — those propagate to the component, which catches `Throwable` and sets a user-facing `$error` message.

Do not return `null` or empty arrays on failure — throw so the component can distinguish between "no results" and "something went wrong".

## Caching conventions

- Use `Cache::remember($key, $ttl, $callback)` — never manually get/put.
- Cache key format: `usgs.{service}.{scope}` (e.g. `usgs.volcanoes.all`).
- TTL: 300 seconds (5 minutes) is the default for USGS data. Adjust per endpoint freshness requirements.
- Only cache endpoints that fetch a full, unparameterized dataset. Parameterized searches are not cached.
- To bust a cache in development: `php artisan cache:clear` or `Cache::forget('key')` in Tinker.

## Data transformation rules

- Normalise all API field names to `snake_case`.
- Cast all numeric fields explicitly (`(float)`, `(int)`) — don't trust API types.
- Include display-helper fields (e.g. `mag_class`, `alert_class`) computed from the data — keep Blade templates logic-free.
- **Do not** include display fields that depend on runtime state unavailable to the service (e.g. user timezone). Return raw values (like `time_ms`) and let the component format them.

## Adding a new service

1. Create `app/Services/NewService.php`
2. Inject the corresponding API client via constructor: `public function __construct(private readonly USGSNewService $client) {}`
3. Register both as singletons in `AppServiceProvider`
4. Inject into Livewire components via `#[Inject]`