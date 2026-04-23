<?php

declare(strict_types=1);

namespace Tests\Feature\Hydro;

use App\Livewire\Hydro\StreamGauge;
use App\Services\WaterServicesService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class StreamGaugeTest extends TestCase
{
    /**
     * Flush the in-memory array cache before each test so that cache entries
     * warmed by earlier tests (e.g. usgs.water.sites.va) don't bleed through
     * and cause Http::fake() calls to be silently skipped.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Component renders without errors when the service returns sites.
     */
    public function test_component_renders_with_sites(): void
    {
        $this->mockService($this->fakeSites());

        Livewire::test(StreamGauge::class)
            ->assertSet('stateCd', 'wa')
            ->assertSet('error', null);
    }

    /**
     * Changing the state reloads sites and clears any selected site.
     */
    public function test_updating_state_clears_selected_site(): void
    {
        $this->mockService($this->fakeSites());

        Livewire::test(StreamGauge::class)
            ->set('selectedSiteCode', '01646500')
            ->set('stateCd', 'md')
            ->assertSet('selectedSiteCode', null)
            ->assertSet('sparklineData', null);
    }

    /**
     * selectSite() populates sparklineData for the given site code.
     */
    public function test_select_site_populates_sparkline_data(): void
    {
        Http::fake([
            '*' => Http::response($this->fakeApiResponse(), 200),
        ]);

        Livewire::test(StreamGauge::class)
            ->call('selectSite', '01646500')
            ->assertSet('selectedSiteCode', '01646500')
            ->assertSet('sparklineData', fn ($data) => $data !== null);
    }

    /**
     * refreshSites() can be called without error (poll mechanism).
     */
    public function test_refresh_sites_does_not_throw(): void
    {
        $this->mockService($this->fakeSites());

        Livewire::test(StreamGauge::class)
            ->call('refreshSites')
            ->assertSet('error', null);
    }

    /**
     * Service error sets the error message and uses an empty sites array.
     */
    public function test_api_error_sets_error_message(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        Livewire::test(StreamGauge::class)
            ->assertSet('error', 'Failed to reach the USGS Water Services API. Please try again.');
    }

    /**
     * Bind a fake WaterServicesService that returns a preset collection.
     */
    private function mockService(Collection $sites): void
    {
        $mock = $this->createMock(WaterServicesService::class);
        $mock->method('query')->willReturn($sites);
        $this->app->instance(WaterServicesService::class, $mock);
    }

    /**
     * Build a minimal fake collection with one site, two parameters.
     */
    private function fakeSites(): Collection
    {
        return collect([
            new \App\Data\WaterServicesData(
                siteCode: '01646500',
                siteName: 'Potomac River near Washington DC',
                lat: 38.9495,
                lng: -77.1228,
                parameterCode: '00060',
                parameterName: 'Streamflow, ft³/s',
                unitCode: 'ft3/s',
                latestValue: 1234.5,
                latestDateTime: '2025-04-14T15:45:00.000-04:00',
                qualifiers: ['P'],
            ),
            new \App\Data\WaterServicesData(
                siteCode: '01646500',
                siteName: 'Potomac River near Washington DC',
                lat: 38.9495,
                lng: -77.1228,
                parameterCode: '00065',
                parameterName: 'Gage height, ft',
                unitCode: 'ft',
                latestValue: 4.56,
                latestDateTime: '2025-04-14T15:45:00.000-04:00',
                qualifiers: ['P'],
            ),
        ]);
    }

    /**
     * Build a minimal WaterML-JSON API response fixture for Http::fake().
     *
     * @return array<string, mixed>
     */
    private function fakeApiResponse(): array
    {
        return [
            'value' => [
                'timeSeries' => [
                    [
                        'sourceInfo' => [
                            'siteName' => 'Potomac River near Washington DC',
                            'siteCode' => [['value' => '01646500']],
                            'geoLocation' => [
                                'geogLocation' => ['latitude' => 38.9495, 'longitude' => -77.1228],
                            ],
                        ],
                        'variable' => [
                            'variableCode'  => [['value' => '00060']],
                            'variableName'  => 'Streamflow, ft³/s',
                            'unit'          => ['unitCode' => 'ft3/s'],
                            'noDataValue'   => -999999,
                        ],
                        'values' => [
                            ['value' => [
                                ['value' => '1200', 'qualifiers' => ['P'], 'dateTime' => '2025-04-12T12:00:00.000-04:00'],
                                ['value' => '1234', 'qualifiers' => ['P'], 'dateTime' => '2025-04-14T15:45:00.000-04:00'],
                            ]],
                        ],
                    ],
                ],
            ],
        ];
    }
}
