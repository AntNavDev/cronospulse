<?php

declare(strict_types=1);

namespace Tests\Feature\Hydro;

use App\Data\FloodAlertData;
use App\Livewire\Hydro\NationalFloodAlerts;
use App\Services\NWSAlertsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class NationalFloodAlertsTest extends TestCase
{
    /**
     * Component renders without errors when the service returns alerts.
     */
    public function test_component_renders_with_alerts(): void
    {
        $this->mockService($this->fakeAlerts());

        Livewire::test(NationalFloodAlerts::class)
            ->assertSet('error', null)
            ->assertSet('totalCount', 2);
    }

    /**
     * Alerts are sorted Extreme first, then Severe.
     */
    public function test_extreme_alerts_sorted_before_severe(): void
    {
        $this->mockService($this->fakeAlerts());

        Livewire::test(NationalFloodAlerts::class)
            ->assertSet('alerts', function (array $alerts): bool {
                return $alerts[0]['severity'] === 'Extreme'
                    && $alerts[1]['severity'] === 'Severe';
            });
    }

    /**
     * totalCount reflects the full collection before capping at MAX_DISPLAY.
     */
    public function test_total_count_reflects_full_collection(): void
    {
        $this->mockService($this->fakeAlerts());

        Livewire::test(NationalFloodAlerts::class)
            ->assertSet('totalCount', 2);
    }

    /**
     * refreshAlerts() can be called without error (poll mechanism).
     */
    public function test_refresh_alerts_does_not_throw(): void
    {
        $this->mockService($this->fakeAlerts());

        Livewire::test(NationalFloodAlerts::class)
            ->call('refreshAlerts')
            ->assertSet('error', null);
    }

    /**
     * A service error sets the error property.
     */
    public function test_api_error_sets_error_message(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        Livewire::test(NationalFloodAlerts::class)
            ->assertSet('error', 'Failed to reach the NWS Alerts API.');
    }

    /**
     * An empty collection leaves alerts as an empty array with totalCount 0.
     */
    public function test_empty_collection_sets_zero_total(): void
    {
        $this->mockService(collect());

        Livewire::test(NationalFloodAlerts::class)
            ->assertSet('totalCount', 0)
            ->assertSet('alerts', []);
    }

    /**
     * Bind a fake NWSAlertsService that returns a preset collection.
     */
    private function mockService(Collection $alerts): void
    {
        $mock = $this->createMock(NWSAlertsService::class);
        $mock->method('activeFloodAlerts')->willReturn($alerts);
        $this->app->instance(NWSAlertsService::class, $mock);
    }

    /**
     * Build a minimal fake collection with one Extreme and one Severe alert.
     */
    private function fakeAlerts(): Collection
    {
        return collect([
            new FloodAlertData(
                id: 'urn:oid:2.49.0.1.840.0.SEVERE',
                event: 'Flood Warning',
                severity: 'Severe',
                urgency: 'Immediate',
                certainty: 'Observed',
                headline: 'Flood Warning for Test County.',
                areaDesc: 'Test County, VA',
                description: 'River flooding in progress.',
                instruction: null,
                effective: '2025-04-14T12:00:00+00:00',
                expires: '2099-12-31T23:59:00+00:00',
                geometry: null,
            ),
            new FloodAlertData(
                id: 'urn:oid:2.49.0.1.840.0.EXTREME',
                event: 'Flash Flood Warning',
                severity: 'Extreme',
                urgency: 'Immediate',
                certainty: 'Observed',
                headline: 'Extreme Flash Flood Warning for Sample County.',
                areaDesc: 'Sample County, LA',
                description: 'Life-threatening flooding.',
                instruction: 'Evacuate immediately.',
                effective: '2025-04-14T14:00:00+00:00',
                expires: '2099-12-31T23:59:00+00:00',
                geometry: null,
            ),
        ]);
    }
}
