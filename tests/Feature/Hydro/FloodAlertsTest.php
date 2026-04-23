<?php

declare(strict_types=1);

namespace Tests\Feature\Hydro;

use App\Data\FloodAlertData;
use App\Livewire\Hydro\FloodAlerts;
use App\Services\NWSAlertsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class FloodAlertsTest extends TestCase
{
    /**
     * Flush the in-memory array cache before each test so that cache entries
     * warmed by earlier tests (e.g. nws.flood.alerts.national) don't bleed
     * through and cause Http::fake() calls to be silently skipped.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Component renders with default state and no errors.
     */
    public function test_component_renders_with_alerts(): void
    {
        $this->mockAlertService($this->fakeAlerts());

        Livewire::test(FloodAlerts::class)
            ->assertSet('stateCd', 'va')
            ->assertSet('error', null)
            ->assertSet('listError', null);
    }

    /**
     * Changing the state clears the selected alert and reloads the map.
     */
    public function test_updating_state_clears_selected_alert(): void
    {
        $this->mockAlertService($this->fakeAlerts());

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
        $this->mockAlertService($this->fakeAlerts());

        Livewire::test(FloodAlerts::class)
            ->call('selectAlert', 'urn:oid:2.49.0.1.840.0.TEST')
            ->assertSet('selectedAlertId', 'urn:oid:2.49.0.1.840.0.TEST')
            ->assertDispatched('flood-alert-focus');
    }

    /**
     * selectAlertFromList() sets the alert and dispatches flood-alert-focus.
     */
    public function test_select_alert_from_list_dispatches_focus(): void
    {
        $this->mockAlertService($this->fakeAlerts());

        Livewire::test(FloodAlerts::class)
            ->call('selectAlertFromList', 'urn:oid:2.49.0.1.840.0.TEST', 'va')
            ->assertSet('selectedAlertId', 'urn:oid:2.49.0.1.840.0.TEST')
            ->assertDispatched('flood-alert-focus');
    }

    /**
     * selectAlertFromList() updates the state when the alert is from a different state.
     */
    public function test_select_alert_from_list_changes_state(): void
    {
        $this->mockAlertService($this->fakeAlerts());

        Livewire::test(FloodAlerts::class)
            ->assertSet('stateCd', 'va')
            ->call('selectAlertFromList', 'urn:oid:2.49.0.1.840.0.TEST', 'md')
            ->assertSet('stateCd', 'md');
    }

    /**
     * selectAlertFromList() ignores an unknown state code.
     */
    public function test_select_alert_from_list_ignores_unknown_state(): void
    {
        $this->mockAlertService($this->fakeAlerts());

        Livewire::test(FloodAlerts::class)
            ->assertSet('stateCd', 'va')
            ->call('selectAlertFromList', 'urn:oid:2.49.0.1.840.0.TEST', 'xx')
            ->assertSet('stateCd', 'va');
    }

    /**
     * refreshAlerts() can be called without error (poll mechanism).
     */
    public function test_refresh_alerts_does_not_throw(): void
    {
        $this->mockAlertService($this->fakeAlerts());

        Livewire::test(FloodAlerts::class)
            ->call('refreshAlerts')
            ->assertSet('error', null)
            ->assertSet('listError', null);
    }

    /**
     * A service error sets the error message.
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
     * nextPage() increments the page when more pages exist.
     */
    public function test_next_page_increments_page(): void
    {
        // Build 13 alerts so there are 2 pages with LIST_PER_PAGE = 12.
        $alerts = collect(range(1, 13))->map(fn ($i) => $this->fakeAlert("id-{$i}"));
        $this->mockAlertService($alerts);

        Livewire::test(FloodAlerts::class)
            ->assertSet('listPage', 1)
            ->call('nextPage')
            ->assertSet('listPage', 2);
    }

    /**
     * nextPage() does not go past the last page.
     */
    public function test_next_page_does_not_exceed_total(): void
    {
        $this->mockAlertService($this->fakeAlerts()); // only 1 alert → 1 page

        Livewire::test(FloodAlerts::class)
            ->assertSet('listPage', 1)
            ->call('nextPage')
            ->assertSet('listPage', 1);
    }

    /**
     * previousPage() decrements the page when on page > 1.
     */
    public function test_previous_page_decrements_page(): void
    {
        $alerts = collect(range(1, 13))->map(fn ($i) => $this->fakeAlert("id-{$i}"));
        $this->mockAlertService($alerts);

        Livewire::test(FloodAlerts::class)
            ->set('listPage', 2)
            ->call('previousPage')
            ->assertSet('listPage', 1);
    }

    /**
     * previousPage() does not go below page 1.
     */
    public function test_previous_page_does_not_go_below_one(): void
    {
        $this->mockAlertService($this->fakeAlerts());

        Livewire::test(FloodAlerts::class)
            ->assertSet('listPage', 1)
            ->call('previousPage')
            ->assertSet('listPage', 1);
    }

    /**
     * Bind a fake NWSAlertsService that returns a preset collection for all calls.
     */
    private function mockAlertService(Collection $alerts): void
    {
        $mock = $this->createMock(NWSAlertsService::class);
        $mock->method('activeFloodAlerts')->willReturn($alerts);
        $this->app->instance(NWSAlertsService::class, $mock);
    }

    /**
     * Build a minimal fake flood alert collection with one alert.
     */
    private function fakeAlerts(): Collection
    {
        return collect([$this->fakeAlert('urn:oid:2.49.0.1.840.0.TEST')]);
    }

    /**
     * Build a single fake FloodAlertData with the given id.
     */
    private function fakeAlert(string $id): FloodAlertData
    {
        return new FloodAlertData(
            id: $id,
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
            stateCode: 'va',
        );
    }
}
