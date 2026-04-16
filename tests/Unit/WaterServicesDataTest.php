<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Data\WaterServicesData;
use PHPUnit\Framework\TestCase;

class WaterServicesDataTest extends TestCase
{
    /**
     * isProvisional() returns true when the 'P' qualifier is present.
     */
    public function test_is_provisional_returns_true_for_p_qualifier(): void
    {
        $data = $this->makeData(qualifiers: ['P']);

        $this->assertTrue($data->isProvisional());
    }

    /**
     * isProvisional() returns false when only the 'A' (approved) qualifier is present.
     */
    public function test_is_provisional_returns_false_for_approved_qualifier(): void
    {
        $data = $this->makeData(qualifiers: ['A']);

        $this->assertFalse($data->isProvisional());
    }

    /**
     * isProvisional() returns false when qualifiers are empty.
     */
    public function test_is_provisional_returns_false_for_empty_qualifiers(): void
    {
        $data = $this->makeData(qualifiers: []);

        $this->assertFalse($data->isProvisional());
    }

    /**
     * formattedTime() returns null when latestDateTime is null.
     */
    public function test_formatted_time_returns_null_when_no_datetime(): void
    {
        $data = $this->makeData(latestDateTime: null);

        $this->assertNull($data->formattedTime('America/New_York'));
    }

    /**
     * formattedTime() formats an ISO 8601 datetime into a human-readable string.
     */
    public function test_formatted_time_returns_correct_format(): void
    {
        $data = $this->makeData(latestDateTime: '2025-04-14T15:45:00.000-04:00');

        $formatted = $data->formattedTime('America/New_York');

        $this->assertStringContainsString('2025', $formatted);
        $this->assertStringContainsString('April', $formatted);
    }

    /**
     * valueClass() returns 'text-muted' when latestValue is null.
     */
    public function test_value_class_returns_muted_when_no_value(): void
    {
        $data = $this->makeData(latestValue: null);

        $this->assertSame('text-muted', $data->valueClass());
    }

    /**
     * valueClass() returns 'text-text' when a value is present.
     */
    public function test_value_class_returns_text_when_value_present(): void
    {
        $data = $this->makeData(latestValue: 1234.5);

        $this->assertSame('text-text', $data->valueClass());
    }

    /**
     * toArray() includes all expected keys.
     */
    public function test_to_array_includes_all_keys(): void
    {
        $data = $this->makeData(
            latestValue: 1234.5,
            latestDateTime: '2025-04-14T15:45:00.000-04:00',
            qualifiers: ['P'],
        );

        $array = $data->toArray('America/New_York');

        $this->assertArrayHasKey('site_code', $array);
        $this->assertArrayHasKey('site_name', $array);
        $this->assertArrayHasKey('lat', $array);
        $this->assertArrayHasKey('lng', $array);
        $this->assertArrayHasKey('parameter_code', $array);
        $this->assertArrayHasKey('parameter_name', $array);
        $this->assertArrayHasKey('unit_code', $array);
        $this->assertArrayHasKey('latest_value', $array);
        $this->assertArrayHasKey('latest_datetime', $array);
        $this->assertArrayHasKey('latest_time', $array);
        $this->assertArrayHasKey('qualifiers', $array);
        $this->assertArrayHasKey('is_provisional', $array);
        $this->assertArrayHasKey('value_class', $array);
    }

    /**
     * toArray() reflects the is_provisional flag correctly.
     */
    public function test_to_array_reflects_is_provisional(): void
    {
        $provisional = $this->makeData(qualifiers: ['P']);
        $approved = $this->makeData(qualifiers: ['A']);

        $this->assertTrue($provisional->toArray()['is_provisional']);
        $this->assertFalse($approved->toArray()['is_provisional']);
    }

    /**
     * Build a WaterServicesData instance with sensible defaults.
     *
     * @param string[]    $qualifiers
     */
    private function makeData(
        ?float $latestValue = 1234.5,
        ?string $latestDateTime = '2025-04-14T15:45:00.000-04:00',
        array $qualifiers = ['P'],
    ): WaterServicesData {
        return new WaterServicesData(
            siteCode: '01646500',
            siteName: 'Potomac River near Washington DC',
            lat: 38.9495,
            lng: -77.1228,
            parameterCode: '00060',
            parameterName: 'Streamflow, ft³/s',
            unitCode: 'ft3/s',
            latestValue: $latestValue,
            latestDateTime: $latestDateTime,
            qualifiers: $qualifiers,
        );
    }
}
