<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\StationDetailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class StationDetailServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * loadStation() upserts the station record and returns chart-ready arrays.
     */
    public function test_load_station_persists_station_and_returns_chart_data(): void
    {
        Http::fake([
            '*' => Http::response($this->fakeApiResponse(), 200),
        ]);

        /** @var StationDetailService $service */
        $service = $this->app->make(StationDetailService::class);
        $result  = $service->loadStation('01646500');

        // Station record upserted — state and HUC are null because the IV API
        // JSON format does not return site metadata (requires Site Service call).
        $this->assertDatabaseHas('usgs_stations', [
            'site_no' => '01646500',
            'name'    => 'Potomac River near Washington DC',
            'state'   => 'VA',
            'huc'     => null,
        ]);

        // Readings persisted for both parameters
        $this->assertDatabaseHas('station_readings', ['parameter_code' => '00060']);
        $this->assertDatabaseHas('station_readings', ['parameter_code' => '00065']);

        // Chart arrays are returned and non-empty
        $this->assertNotEmpty($result['streamflow']['data']);
        $this->assertNotEmpty($result['gage_height']['data']);
        $this->assertSame('01646500', $result['station']->site_no);
    }

    /**
     * Revisiting a station upserts without creating duplicate readings.
     */
    public function test_revisiting_station_does_not_duplicate_readings(): void
    {
        Http::fake([
            '*' => Http::response($this->fakeApiResponse(), 200),
        ]);

        /** @var StationDetailService $service */
        $service = $this->app->make(StationDetailService::class);

        $service->loadStation('01646500');
        $service->loadStation('01646500');

        $this->assertDatabaseCount('station_readings', 4); // 2 parameters × 2 readings each
    }

    /**
     * A non-successful API response throws a RuntimeException.
     */
    public function test_api_error_throws_runtime_exception(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        /** @var StationDetailService $service */
        $service = $this->app->make(StationDetailService::class);

        $this->expectException(RuntimeException::class);
        $service->loadStation('01646500');
    }

    /**
     * An empty timeSeries response throws a RuntimeException.
     */
    public function test_empty_time_series_throws_runtime_exception(): void
    {
        Http::fake([
            '*' => Http::response(['value' => ['timeSeries' => []]], 200),
        ]);

        /** @var StationDetailService $service */
        $service = $this->app->make(StationDetailService::class);

        $this->expectException(RuntimeException::class);
        $service->loadStation('99999999');
    }

    /**
     * Build a WaterML-JSON fixture with expanded site metadata and two parameters.
     *
     * @return array<string, mixed>
     */
    private function fakeApiResponse(): array
    {
        $sourceInfo = [
            'siteName' => 'Potomac River near Washington DC',
            'siteCode' => [['value' => '01646500', 'agencyCode' => 'USGS']],
            'geoLocation' => [
                'geogLocation' => ['latitude' => 38.9495, 'longitude' => -77.1228],
            ],
            'siteProperty' => [
                ['name' => 'stateCd',   'value' => '51'],
                ['name' => 'countyCd',  'value' => '031'],
                ['name' => 'hucCd',     'value' => '02070010'],
                ['name' => 'siteTypeCd','value' => 'ST'],
            ],
        ];

        return [
            'value' => [
                'timeSeries' => [
                    [
                        'sourceInfo' => $sourceInfo,
                        'variable'   => [
                            'variableCode' => [['value' => '00060']],
                            'variableName' => 'Streamflow, ft³/s',
                            'unit'         => ['unitCode' => 'ft3/s'],
                            'noDataValue'  => -999999,
                        ],
                        'values' => [
                            ['value' => [
                                ['value' => '1200', 'qualifiers' => ['P'], 'dateTime' => '2025-04-12T12:00:00.000-04:00'],
                                ['value' => '1234', 'qualifiers' => ['P'], 'dateTime' => '2025-04-13T12:00:00.000-04:00'],
                            ]],
                        ],
                    ],
                    [
                        'sourceInfo' => $sourceInfo,
                        'variable'   => [
                            'variableCode' => [['value' => '00065']],
                            'variableName' => 'Gage height, ft',
                            'unit'         => ['unitCode' => 'ft'],
                            'noDataValue'  => -999999,
                        ],
                        'values' => [
                            ['value' => [
                                ['value' => '4.50', 'qualifiers' => ['P'], 'dateTime' => '2025-04-12T12:00:00.000-04:00'],
                                ['value' => '4.56', 'qualifiers' => ['P'], 'dateTime' => '2025-04-13T12:00:00.000-04:00'],
                            ]],
                        ],
                    ],
                ],
            ],
        ];
    }
}
