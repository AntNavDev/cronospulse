<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Api\Queries\WaterServicesQuery;
use App\Data\WaterServicesData;
use App\Services\WaterServicesService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class WaterServicesServiceTest extends TestCase
{
    /**
     * Successful response is parsed into a typed collection.
     */
    public function test_query_returns_typed_collection(): void
    {
        Http::fake([
            '*' => Http::response($this->fakeResponse(), 200),
        ]);

        $service    = $this->app->make(WaterServicesService::class);
        $collection = $service->query(
            WaterServicesQuery::make()->sites(['01646500'])->parameterCd(['00060']),
        );

        $this->assertCount(1, $collection);
        $this->assertInstanceOf(WaterServicesData::class, $collection->first());
    }

    /**
     * The DTO is populated with the correct field values from the response.
     */
    public function test_query_maps_fields_correctly(): void
    {
        Http::fake([
            '*' => Http::response($this->fakeResponse(), 200),
        ]);

        $service = $this->app->make(WaterServicesService::class);
        $data    = $service->query(
            WaterServicesQuery::make()->sites(['01646500'])->parameterCd(['00060']),
        )->first();

        $this->assertSame('01646500', $data->siteCode);
        $this->assertSame('Potomac River near Washington DC', $data->siteName);
        $this->assertSame(38.9495, $data->lat);
        $this->assertSame(-77.1228, $data->lng);
        $this->assertSame('00060', $data->parameterCode);
        $this->assertSame('Streamflow, ft³/s', $data->parameterName);
        $this->assertSame('ft3/s', $data->unitCode);
        $this->assertSame(1234.5, $data->latestValue);
        $this->assertSame('2025-04-14T15:45:00.000-04:00', $data->latestDateTime);
        $this->assertSame(['P'], $data->qualifiers);
    }

    /**
     * A reading matching the noDataValue sentinel is mapped to null.
     */
    public function test_no_data_value_is_mapped_to_null(): void
    {
        Http::fake([
            '*' => Http::response($this->fakeResponse(readingValue: '-999999'), 200),
        ]);

        $service = $this->app->make(WaterServicesService::class);
        $data    = $service->query(
            WaterServicesQuery::make()->sites(['01646500'])->parameterCd(['00060']),
        )->first();

        $this->assertNull($data->latestValue);
    }

    /**
     * An empty values array results in null latestValue and null latestDateTime.
     */
    public function test_empty_values_results_in_null_fields(): void
    {
        Http::fake([
            '*' => Http::response($this->fakeResponse(emptyValues: true), 200),
        ]);

        $service = $this->app->make(WaterServicesService::class);
        $data    = $service->query(
            WaterServicesQuery::make()->sites(['01646500'])->parameterCd(['00060']),
        )->first();

        $this->assertNull($data->latestValue);
        $this->assertNull($data->latestDateTime);
    }

    /**
     * HTML entities in variableName are decoded in the DTO.
     */
    public function test_html_entities_in_parameter_name_are_decoded(): void
    {
        Http::fake([
            '*' => Http::response($this->fakeResponse(variableName: 'Streamflow, ft&#179;/s'), 200),
        ]);

        $service = $this->app->make(WaterServicesService::class);
        $data    = $service->query(
            WaterServicesQuery::make()->sites(['01646500'])->parameterCd(['00060']),
        )->first();

        $this->assertSame('Streamflow, ft³/s', $data->parameterName);
    }

    /**
     * A non-successful HTTP response throws a RuntimeException.
     */
    public function test_non_successful_response_throws(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        $this->expectException(RuntimeException::class);

        $service = $this->app->make(WaterServicesService::class);
        $service->query(WaterServicesQuery::make()->sites(['01646500']));
    }

    /**
     * Build a minimal WaterML-JSON response fixture.
     *
     * @param string $readingValue  The value string for the latest reading.
     * @param string $variableName  The variableName string (may contain HTML entities).
     * @param bool   $emptyValues   When true, the values array is empty.
     * @return array<string, mixed>
     */
    private function fakeResponse(
        string $readingValue = '1234.5',
        string $variableName = 'Streamflow, ft³/s',
        bool $emptyValues = false,
    ): array {
        return [
            'value' => [
                'timeSeries' => [
                    [
                        'sourceInfo' => [
                            'siteName' => 'Potomac River near Washington DC',
                            'siteCode' => [['value' => '01646500']],
                            'geoLocation' => [
                                'geogLocation' => [
                                    'latitude'  => 38.9495,
                                    'longitude' => -77.1228,
                                ],
                            ],
                        ],
                        'variable' => [
                            'variableCode'  => [['value' => '00060']],
                            'variableName'  => $variableName,
                            'unit'          => ['unitCode' => 'ft3/s'],
                            'noDataValue'   => -999999,
                        ],
                        'values' => [
                            [
                                'value' => $emptyValues ? [] : [
                                    ['value' => $readingValue, 'qualifiers' => ['P'], 'dateTime' => '2025-04-14T15:45:00.000-04:00'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
