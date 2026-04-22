<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Data\EarthquakeData;
use App\Models\Earthquake;
use App\Services\EarthquakeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class IngestEarthquakesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The command upserts fetched events and stores the last-run timestamp.
     */
    public function test_it_ingests_events_and_caches_run_timestamp(): void
    {
        $earthquake = new EarthquakeData(
            lat: 37.77,
            lng: -122.41,
            magnitude: 4.5,
            place: '10km NW of San Francisco, CA',
            timeMs: Carbon::now()->subHour()->getTimestampMs(),
            depthKm: 12.3,
            alert: 'green',
            status: 'reviewed',
            url: 'https://earthquake.usgs.gov/earthquakes/eventpage/us7000test01',
            usgsId: 'us7000test01',
            magnitudeType: 'mw',
            felt: 42,
            cdi: 3.5,
            mmi: 4.2,
            significance: 350,
        );

        $this->mock(EarthquakeService::class, function (MockInterface $mock) use ($earthquake): void {
            $mock->shouldReceive('query')
                ->once()
                ->andReturn(collect([$earthquake]));
        });

        $this->artisan('app:ingest-earthquakes')
            ->assertSuccessful()
            ->expectsOutputToContain('1 earthquake');

        $this->assertDatabaseHas('earthquakes', [
            'usgs_id'   => 'us7000test01',
            'magnitude' => 4.5,
            'place'     => '10km NW of San Francisco, CA',
            'alert'     => 'green',
            'felt'      => 42,
        ]);

        $this->assertNotNull(Cache::get('earthquake.last_ingestion'));
    }

    /**
     * Re-running the command updates existing records rather than duplicating them.
     */
    public function test_it_upserts_existing_events(): void
    {
        Earthquake::factory()->create([
            'usgs_id'   => 'us7000upsert',
            'magnitude' => 4.1,
            'status'    => 'automatic',
        ]);

        $revised = new EarthquakeData(
            lat: 34.05,
            lng: -118.24,
            magnitude: 4.1,
            place: 'Los Angeles, CA',
            timeMs: Carbon::now()->subHours(2)->getTimestampMs(),
            depthKm: 8.0,
            alert: null,
            status: 'reviewed', // analyst updated this
            url: null,
            usgsId: 'us7000upsert',
        );

        $this->mock(EarthquakeService::class, function (MockInterface $mock) use ($revised): void {
            $mock->shouldReceive('query')->once()->andReturn(collect([$revised]));
        });

        $this->artisan('app:ingest-earthquakes')->assertSuccessful();

        $this->assertDatabaseCount('earthquakes', 1);
        $this->assertDatabaseHas('earthquakes', [
            'usgs_id' => 'us7000upsert',
            'status'  => 'reviewed',
        ]);
    }

    /**
     * Events with no usgs_id are silently skipped.
     */
    public function test_it_skips_events_with_no_usgs_id(): void
    {
        $noId = new EarthquakeData(
            lat: 0.0,
            lng: 0.0,
            magnitude: 4.0,
            place: 'Unknown',
            timeMs: Carbon::now()->getTimestampMs(),
            depthKm: 10.0,
            alert: null,
            status: null,
            url: null,
            usgsId: null,
        );

        $this->mock(EarthquakeService::class, function (MockInterface $mock) use ($noId): void {
            $mock->shouldReceive('query')->once()->andReturn(collect([$noId]));
        });

        $this->artisan('app:ingest-earthquakes')->assertSuccessful();

        $this->assertDatabaseCount('earthquakes', 0);
    }

    /**
     * A USGS API failure returns a non-zero exit code and leaves the cache untouched.
     */
    public function test_it_fails_gracefully_on_api_error(): void
    {
        Cache::forget('earthquake.last_ingestion');

        $this->mock(EarthquakeService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('query')
                ->once()
                ->andThrow(new RuntimeException('The USGS API returned an error.'));
        });

        $this->artisan('app:ingest-earthquakes')->assertFailed();

        $this->assertNull(Cache::get('earthquake.last_ingestion'));
    }

    /**
     * On the first run (no cached timestamp), the command seeds from the last 24 hours.
     */
    public function test_first_run_seeds_from_last_24_hours(): void
    {
        Cache::forget('earthquake.last_ingestion');

        $this->mock(EarthquakeService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('query')
                ->once()
                ->andReturn(collect());
        });

        $this->artisan('app:ingest-earthquakes')
            ->assertSuccessful()
            ->expectsOutputToContain('seeding with last 24 hours');
    }
}
