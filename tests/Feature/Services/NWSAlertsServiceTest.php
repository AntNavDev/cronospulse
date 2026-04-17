<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Api\Queries\NWSAlertsQuery;
use App\Data\FloodAlertData;
use App\Services\NWSAlertsService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class NWSAlertsServiceTest extends TestCase
{
    /**
     * A successful response is parsed into a typed collection of FloodAlertData.
     */
    public function test_active_flood_alerts_returns_typed_collection(): void
    {
        Http::fake([
            '*' => Http::response($this->fakeResponse(), 200),
        ]);

        $service    = $this->app->make(NWSAlertsService::class);
        $collection = $service->activeFloodAlerts(
            NWSAlertsQuery::make()->area('va'),
        );

        $this->assertCount(1, $collection);
        $this->assertInstanceOf(FloodAlertData::class, $collection->first());
    }

    /**
     * The service parses the core alert fields correctly.
     */
    public function test_alert_fields_are_parsed(): void
    {
        Http::fake([
            '*' => Http::response($this->fakeResponse(), 200),
        ]);

        $service = $this->app->make(NWSAlertsService::class);
        $alert   = $service->activeFloodAlerts(NWSAlertsQuery::make()->area('va'))->first();

        $this->assertSame('urn:oid:2.49.0.1.840.0.TEST', $alert->id);
        $this->assertSame('Flash Flood Warning', $alert->event);
        $this->assertSame('Severe', $alert->severity);
        $this->assertSame('Immediate', $alert->urgency);
        $this->assertSame('Observed', $alert->certainty);
        $this->assertSame('Test County, VA', $alert->areaDesc);
    }

    /**
     * Non-flood event types are filtered out of the returned collection.
     */
    public function test_non_flood_events_are_filtered(): void
    {
        Http::fake([
            '*' => Http::response($this->fakeResponseWithMixedEvents(), 200),
        ]);

        $service    = $this->app->make(NWSAlertsService::class);
        $collection = $service->activeFloodAlerts(NWSAlertsQuery::make()->area('va'));

        // 2 alerts in fixture, only 1 is flood-related
        $this->assertCount(1, $collection);
        $this->assertSame('Flash Flood Warning', $collection->first()->event);
    }

    /**
     * An alert without geometry has hasGeometry() === false.
     */
    public function test_alert_without_geometry_has_no_geometry(): void
    {
        Http::fake([
            '*' => Http::response($this->fakeResponseNoGeometry(), 200),
        ]);

        $service = $this->app->make(NWSAlertsService::class);
        $alert   = $service->activeFloodAlerts(NWSAlertsQuery::make()->area('va'))->first();

        $this->assertFalse($alert->hasGeometry());
    }

    /**
     * An empty features array returns an empty collection without error.
     */
    public function test_empty_features_returns_empty_collection(): void
    {
        Http::fake([
            '*' => Http::response(['type' => 'FeatureCollection', 'features' => []], 200),
        ]);

        $service    = $this->app->make(NWSAlertsService::class);
        $collection = $service->activeFloodAlerts(NWSAlertsQuery::make()->area('va'));

        $this->assertCount(0, $collection);
    }

    /**
     * A non-200 response throws a RuntimeException.
     */
    public function test_api_error_throws_runtime_exception(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The NWS Alerts API returned an error.');

        $service = $this->app->make(NWSAlertsService::class);
        $service->activeFloodAlerts(NWSAlertsQuery::make()->area('va'));
    }

    /**
     * Minimal GeoJSON FeatureCollection with one flood alert.
     *
     * @return array<string, mixed>
     */
    private function fakeResponse(): array
    {
        return [
            'type'     => 'FeatureCollection',
            'features' => [$this->floodAlertFeature()],
        ];
    }

    /**
     * FeatureCollection with one flood alert and one non-flood alert.
     *
     * @return array<string, mixed>
     */
    private function fakeResponseWithMixedEvents(): array
    {
        return [
            'type'     => 'FeatureCollection',
            'features' => [
                $this->floodAlertFeature(),
                [
                    'id'         => 'urn:oid:2.49.0.1.840.0.WIND',
                    'type'       => 'Feature',
                    'properties' => [
                        'event'       => 'Wind Advisory',
                        'severity'    => 'Minor',
                        'urgency'     => 'Expected',
                        'certainty'   => 'Likely',
                        'headline'    => 'Wind Advisory until 6 PM.',
                        'areaDesc'    => 'Northern Virginia',
                        'description' => 'Gusty winds expected.',
                        'instruction' => null,
                        'effective'   => '2025-04-14T12:00:00+00:00',
                        'expires'     => '2025-04-14T22:00:00+00:00',
                    ],
                    'geometry' => null,
                ],
            ],
        ];
    }

    /**
     * FeatureCollection with one flood alert that has null geometry.
     *
     * @return array<string, mixed>
     */
    private function fakeResponseNoGeometry(): array
    {
        $feature             = $this->floodAlertFeature();
        $feature['geometry'] = null;

        return [
            'type'     => 'FeatureCollection',
            'features' => [$feature],
        ];
    }

    /**
     * Build a single minimal NWS GeoJSON feature for a Flash Flood Warning.
     *
     * @return array<string, mixed>
     */
    private function floodAlertFeature(): array
    {
        return [
            'id'         => 'urn:oid:2.49.0.1.840.0.TEST',
            'type'       => 'Feature',
            'properties' => [
                'event'       => 'Flash Flood Warning',
                'severity'    => 'Severe',
                'urgency'     => 'Immediate',
                'certainty'   => 'Observed',
                'headline'    => 'Flash Flood Warning issued for Test County until 8 PM.',
                'areaDesc'    => 'Test County, VA',
                'description' => 'Heavy rainfall is causing rapid rises on area creeks.',
                'instruction' => 'Move to higher ground immediately.',
                'effective'   => '2025-04-14T15:00:00+00:00',
                'expires'     => '2025-04-15T01:00:00+00:00',
            ],
            'geometry' => [
                'type'        => 'Polygon',
                'coordinates' => [[[-77.5, 38.8], [-77.4, 38.8], [-77.4, 38.9], [-77.5, 38.9], [-77.5, 38.8]]],
            ],
        ];
    }
}
