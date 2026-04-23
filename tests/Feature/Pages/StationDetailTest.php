<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use App\Livewire\Pages\StationDetail;
use App\Models\UsgsStation;
use App\Services\StationDetailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class StationDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Page returns HTTP 200 for a valid numeric site number.
     */
    public function test_page_renders_for_valid_site_number(): void
    {
        $this->mockService();

        $response = $this->get('/hydro/station/01646500');

        $response->assertOk();
    }

    /**
     * Page returns 404 for a non-numeric site number.
     */
    public function test_page_returns_404_for_invalid_site_number(): void
    {
        $response = $this->get('/hydro/station/invalid-site');

        $response->assertNotFound();
    }

    /**
     * Successful mount populates stationMeta, streamflowChart, and gageHeightChart.
     */
    public function test_mount_populates_station_data_on_success(): void
    {
        $this->mockService();

        Livewire::test(StationDetail::class, ['siteNo' => '01646500'])
            ->assertSet('stationName', 'Potomac River near Washington DC')
            ->assertSet('error', null)
            ->assertSet('stationMeta', fn ($meta) => $meta !== null && $meta['site_no'] === '01646500')
            ->assertSet('streamflowChart', fn ($chart) => ! empty($chart['data']))
            ->assertSet('gageHeightChart', fn ($chart) => ! empty($chart['data']));
    }

    /**
     * When the service throws, the error property is set and stationMeta stays null.
     */
    public function test_service_failure_sets_error_message(): void
    {
        $mock = $this->createMock(StationDetailService::class);
        $mock->method('loadStation')->willThrowException(new RuntimeException('API error'));
        $this->app->instance(StationDetailService::class, $mock);

        Livewire::test(StationDetail::class, ['siteNo' => '01646500'])
            ->assertSet('stationMeta', null)
            ->assertSet('error', fn ($e) => str_contains($e, '01646500'));
    }

    /**
     * Bind a StationDetailService mock that returns a realistic result.
     */
    private function mockService(): void
    {
        $station = UsgsStation::factory()->create([
            'site_no'   => '01646500',
            'name'      => 'Potomac River near Washington DC',
            'state'     => 'VA',
            'site_type' => 'ST',
            'latitude'  => 38.9495,
            'longitude' => -77.1228,
        ]);

        $mock = $this->createMock(StationDetailService::class);
        $mock->method('loadStation')->willReturn([
            'station'     => $station,
            'streamflow'  => [
                'labels' => ['2025-04-12T12:00:00.000-04:00', '2025-04-13T12:00:00.000-04:00'],
                'data'   => [1200.0, 1234.5],
            ],
            'gage_height' => [
                'labels' => ['2025-04-12T12:00:00.000-04:00', '2025-04-13T12:00:00.000-04:00'],
                'data'   => [4.50, 4.56],
            ],
        ]);

        $this->app->instance(StationDetailService::class, $mock);
    }
}
