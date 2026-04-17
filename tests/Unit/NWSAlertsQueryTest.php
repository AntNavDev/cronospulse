<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Api\Queries\NWSAlertsQuery;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class NWSAlertsQueryTest extends TestCase
{
    /**
     * A bare query always injects status=actual.
     */
    public function test_status_actual_always_injected(): void
    {
        $params = NWSAlertsQuery::make()->area('va')->toArray();

        $this->assertSame('actual', $params['status']);
    }

    /**
     * area() is uppercased and included in the output.
     */
    public function test_area_is_uppercased(): void
    {
        $params = NWSAlertsQuery::make()->area('va')->toArray();

        $this->assertSame('VA', $params['area']);
    }

    /**
     * point() formats lat/lng as a comma-separated string.
     */
    public function test_point_formats_coordinates(): void
    {
        $params = NWSAlertsQuery::make()->point(38.9, -77.1)->toArray();

        $this->assertSame('38.9,-77.1', $params['point']);
        $this->assertArrayNotHasKey('area', $params);
    }

    /**
     * zone() is uppercased and included in the output.
     */
    public function test_zone_is_uppercased(): void
    {
        $params = NWSAlertsQuery::make()->zone('vaz505')->toArray();

        $this->assertSame('VAZ505', $params['zone']);
    }

    /**
     * events() are joined with commas.
     */
    public function test_events_are_joined(): void
    {
        $params = NWSAlertsQuery::make()
            ->area('va')
            ->events(['Flood Watch', 'Flash Flood Warning'])
            ->toArray();

        $this->assertSame('Flood Watch,Flash Flood Warning', $params['event']);
    }

    /**
     * severity() accepts valid CAP values and joins them with commas.
     */
    public function test_severity_joined(): void
    {
        $params = NWSAlertsQuery::make()
            ->area('va')
            ->severity(['Severe', 'Extreme'])
            ->toArray();

        $this->assertSame('Severe,Extreme', $params['severity']);
    }

    /**
     * urgency() accepts valid CAP values and joins them with commas.
     */
    public function test_urgency_joined(): void
    {
        $params = NWSAlertsQuery::make()
            ->area('va')
            ->urgency(['Immediate', 'Expected'])
            ->toArray();

        $this->assertSame('Immediate,Expected', $params['urgency']);
    }

    /**
     * certainty() accepts valid CAP values and joins them with commas.
     */
    public function test_certainty_joined(): void
    {
        $params = NWSAlertsQuery::make()
            ->area('va')
            ->certainty(['Observed', 'Likely'])
            ->toArray();

        $this->assertSame('Observed,Likely', $params['certainty']);
    }

    /**
     * Omitted optional parameters do not appear in toArray() output.
     */
    public function test_optional_params_omitted_when_not_set(): void
    {
        $params = NWSAlertsQuery::make()->area('va')->toArray();

        $this->assertArrayNotHasKey('event', $params);
        $this->assertArrayNotHasKey('urgency', $params);
        $this->assertArrayNotHasKey('severity', $params);
        $this->assertArrayNotHasKey('certainty', $params);
        $this->assertArrayNotHasKey('zone', $params);
        $this->assertArrayNotHasKey('point', $params);
    }

    /**
     * Mixing two location filters throws InvalidArgumentException.
     */
    public function test_multiple_location_filters_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/mutually exclusive/');

        NWSAlertsQuery::make()
            ->area('va')
            ->zone('VAZ505')
            ->toArray();
    }

    /**
     * An invalid severity value throws InvalidArgumentException.
     */
    public function test_invalid_severity_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Invalid severity 'Critical'/");

        NWSAlertsQuery::make()->severity(['Critical']);
    }

    /**
     * An invalid urgency value throws InvalidArgumentException.
     */
    public function test_invalid_urgency_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NWSAlertsQuery::make()->urgency(['Now']);
    }

    /**
     * An invalid certainty value throws InvalidArgumentException.
     */
    public function test_invalid_certainty_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        NWSAlertsQuery::make()->certainty(['Definite']);
    }
}
