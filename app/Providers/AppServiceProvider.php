<?php

declare(strict_types=1);

namespace App\Providers;

use App\Api\USGSEarthquake;
use App\Api\USGSVolcano;
use App\Services\EarthquakeService;
use App\Services\VolcanoService;
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
        $this->app->singleton(EarthquakeService::class);
        $this->app->singleton(VolcanoService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
