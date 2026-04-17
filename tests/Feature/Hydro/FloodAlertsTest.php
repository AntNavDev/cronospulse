<?php

declare(strict_types=1);

namespace Tests\Feature\Hydro;

use App\Data\FloodAlertData;
use App\Livewire\Hydro\FloodAlerts;
use App\Services\NWSAlertsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class FloodAlertsTest extends TestCase
{
    /**
     * Component renders without errors when the service returns alerts.
     */
    public function test_component_renders_with_alerts(): void
    {
        $this->mockService($this->fakeAlerts());

        Livewire::test(FloodAlerts::class)
            ->assertSet('stateCd', 'va')
            ->assertSet('error', null);
    }

    /**
     * Changing the state clears the selected alert and reloads.
     */
    public function test_updating_state_clears_selected_alert(): void
    {
        $this->mockService($this->fakeAlerts());

        Livewire::test(FloodAlerts::class)
            ->set('selectedAlertId', 'urn:oid:2.49.0.1.840.0.TEST')
            ->set('stateCd', 'md')
            ->assertSet('selectedAlertId', null);
    }

    /**
     * selectAlert() sets the selectedAlertId and dispatches flood-alert-focus.
     */
    public function test_select_alert_sets_id_and_dispatches_event(): void
    {
        $this->mockService($this->fakeAlerts());

        Livewire::test(FloodAlerts::class)
            ->call('selectAlert', 'urn:oid:2.49.0.1.840.0.TEST')
            ->assertSet('selectedAlertId', 'urn:oid:2.49.0.1.840.0.TEST')
            ->assertDispatched('flood-alert-focus');
    }

    /**
     * refreshAlerts() can be called without error (poll mechanism).
     */
    public function test_refresh_alerts_does_not_throw(): void
    {
        $this->mockService($this->fakeAlerts());

        Livewire::test(FloodAlerts::class)
            ->call('refreshAlerts')
            ->assertSet('error', null);
    }

    /**
     * A service error sets the error message and uses an empty alerts array.
     */
    public function test_api_error_sets_error_message(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        Livewire::test(FloodAlerts::class)
            ->assertSet('error', 'Failed to reach the NWS Alerts API. Please try again.');
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
     * Build a minimal fake collection with one flood alert.
     */
    private function fakeAlerts(): Collection
    {
        return collect([
            new FloodAlertData(
                id: 'urn:oid:2.49.0.1.840.0.TEST',
                event: 'Flash Flood Warning',
                severity: 'Severe',
                urgency: 'Immediate',
                certainty: 'Observed',
                headline: 'Flash Flood Warning issued for Test County until 8 PM.',
                areaDesc: 'Test County, VA',
                description: 'Heavy rainfall is causing rapid rises on area creeks.',
                instruction: 'Move to higher ground immediately.',
                effective: '2025-04-14T15:00:00+00:00',
                expires: '2099-12-31T23:59:00+00:00',
                geometry: null,
            ),
        ]);
    }
}
