<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Api\Queries\WaterServicesQuery;
use Carbon\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class WaterServicesQueryTest extends TestCase
{
    /**
     * A sites() + period() query produces the expected parameter array.
     */
    public function test_sites_and_period_produces_correct_params(): void
    {
        $params = WaterServicesQuery::make()
            ->sites(['01646500'])
            ->parameterCd(['00060'])
            ->period('P7D')
            ->toArray();

        $this->assertSame('json', $params['format']);
        $this->assertSame('01646500', $params['sites']);
        $this->assertSame('00060', $params['parameterCd']);
        $this->assertSame('P7D', $params['period']);
        $this->assertArrayNotHasKey('startDT', $params);
        $this->assertArrayNotHasKey('endDT', $params);
    }

    /**
     * Multiple sites are joined as a comma-separated string.
     */
    public function test_multiple_sites_are_comma_separated(): void
    {
        $params = WaterServicesQuery::make()
            ->sites(['01646500', '01638500', '01594440'])
            ->toArray();

        $this->assertSame('01646500,01638500,01594440', $params['sites']);
    }

    /**
     * Multiple parameter codes are joined as a comma-separated string.
     */
    public function test_multiple_parameter_codes_are_comma_separated(): void
    {
        $params = WaterServicesQuery::make()
            ->sites(['01646500'])
            ->parameterCd(['00060', '00065'])
            ->toArray();

        $this->assertSame('00060,00065', $params['parameterCd']);
    }

    /**
     * stateCd() provides a valid site selector without sites().
     */
    public function test_state_code_is_a_valid_site_selector(): void
    {
        $params = WaterServicesQuery::make()
            ->stateCd('VA')
            ->toArray();

        $this->assertSame('va', $params['stateCd']);
        $this->assertArrayNotHasKey('sites', $params);
    }

    /**
     * A startDT/endDT range is formatted as ISO 8601 strings.
     */
    public function test_start_and_end_dt_are_formatted_as_iso8601(): void
    {
        $start = Carbon::parse('2025-01-01 00:00:00', 'UTC');
        $end = Carbon::parse('2025-01-07 23:59:59', 'UTC');

        $params = WaterServicesQuery::make()
            ->sites(['01646500'])
            ->startDt($start)
            ->endDt($end)
            ->toArray();

        $this->assertArrayHasKey('startDT', $params);
        $this->assertArrayHasKey('endDT', $params);
        $this->assertArrayNotHasKey('period', $params);
    }

    /**
     * format=json is always present in the output.
     */
    public function test_format_json_is_always_injected(): void
    {
        $params = WaterServicesQuery::make()
            ->sites(['01646500'])
            ->toArray();

        $this->assertSame('json', $params['format']);
    }

    /**
     * Null fields are stripped from the output.
     */
    public function test_unset_optional_params_are_omitted(): void
    {
        $params = WaterServicesQuery::make()
            ->sites(['01646500'])
            ->toArray();

        $this->assertArrayNotHasKey('stateCd', $params);
        $this->assertArrayNotHasKey('countyCd', $params);
        $this->assertArrayNotHasKey('parameterCd', $params);
        $this->assertArrayNotHasKey('period', $params);
        $this->assertArrayNotHasKey('startDT', $params);
        $this->assertArrayNotHasKey('endDT', $params);
        $this->assertArrayNotHasKey('siteType', $params);
        $this->assertArrayNotHasKey('siteStatus', $params);
    }

    /**
     * Omitting all site selectors throws an exception.
     */
    public function test_missing_site_selector_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/site selector/');

        WaterServicesQuery::make()->toArray();
    }

    /**
     * Mixing period() and startDt()/endDt() throws an exception.
     */
    public function test_period_and_date_range_together_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/mutually exclusive/');

        WaterServicesQuery::make()
            ->sites(['01646500'])
            ->period('P7D')
            ->startDt(Carbon::now())
            ->toArray();
    }

    /**
     * An invalid site type throws an exception.
     */
    public function test_invalid_site_type_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WaterServicesQuery::make()->siteType('INVALID');
    }

    /**
     * An invalid site status throws an exception.
     */
    public function test_invalid_site_status_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        WaterServicesQuery::make()->siteStatus('unknown');
    }

    /**
     * Valid site type and status are included in the output.
     */
    public function test_valid_site_type_and_status_are_included(): void
    {
        $params = WaterServicesQuery::make()
            ->sites(['01646500'])
            ->siteType('ST')
            ->siteStatus('active')
            ->toArray();

        $this->assertSame('ST', $params['siteType']);
        $this->assertSame('active', $params['siteStatus']);
    }
}
