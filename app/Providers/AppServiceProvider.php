<?php

declare(strict_types=1);

namespace App\Providers;

use App\Api\NWSAlerts;
use App\Api\USGSEarthquake;
use App\Api\USGSVolcano;
use App\Api\USGSWaterServices;
use App\Services\EarthquakeService;
use App\Services\NWSAlertsService;
use App\Services\VolcanoService;
use App\Services\WaterServicesService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(USGSEarthquake::class);
        $this->app->singleton(USGSVolcano::class);
        $this->app->singleton(USGSWaterServices::class);
        $this->app->singleton(NWSAlerts::class);
        $this->app->singleton(EarthquakeService::class);
        $this->app->singleton(VolcanoService::class);
        $this->app->singleton(WaterServicesService::class);
        $this->app->singleton(NWSAlertsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
