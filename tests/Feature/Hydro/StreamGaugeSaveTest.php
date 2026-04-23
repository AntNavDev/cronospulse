<?php

declare(strict_types=1);

namespace Tests\Feature\Hydro;

use App\Livewire\Hydro\StreamGauge;
use App\Models\SavedStation;
use App\Models\User;
use App\Models\UsgsStation;
use App\Services\WaterServicesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class StreamGaugeSaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Authenticated user can save the currently-selected station.
     */
    public function test_authenticated_user_can_save_station(): void
    {
        $user    = User::factory()->create();
        $station = UsgsStation::factory()->create(['site_no' => '01646500']);

        $this->mockServiceWithSite('01646500');

        Livewire::actingAs($user)
            ->test(StreamGauge::class)
            ->call('selectSite', '01646500')
            ->call('saveStation')
            ->assertSet('saveSuccess', true)
            ->assertSet('saveMessage', 'Station saved to your dashboard.');

        $this->assertDatabaseHas('saved_stations', [
            'user_id'    => $user->id,
            'station_id' => $station->id,
        ]);
    }

    /**
     * Saving creates a UsgsStation record when the site is not yet in the DB.
     */
    public function test_saving_creates_usgs_station_if_missing(): void
    {
        $user = User::factory()->create();

        $this->mockServiceWithSite('09380000');

        Livewire::actingAs($user)
            ->test(StreamGauge::class)
            ->call('selectSite', '09380000')
            ->call('saveStation')
            ->assertSet('saveSuccess', true);

        $this->assertDatabaseHas('usgs_stations', ['site_no' => '09380000']);
        $this->assertDatabaseCount('saved_stations', 1);
    }

    /**
     * Saving the same station twice creates only one saved_stations record.
     */
    public function test_saving_same_station_twice_is_idempotent(): void
    {
        $user    = User::factory()->create();
        $station = UsgsStation::factory()->create(['site_no' => '01646500']);

        $this->mockServiceWithSite('01646500');

        $component = Livewire::actingAs($user)->test(StreamGauge::class);
        $component->call('selectSite', '01646500')->call('saveStation');
        $component->call('saveStation'); // second save

        $this->assertDatabaseCount('saved_stations', 1);
    }

    /**
     * Saving is capped at 30 stations per user.
     */
    public function test_save_is_blocked_at_30_station_limit(): void
    {
        $user = User::factory()->create();

        // Pre-fill 30 saved stations.
        UsgsStation::factory()->count(30)->create()->each(function (UsgsStation $station) use ($user) {
            SavedStation::create([
                'user_id'    => $user->id,
                'station_id' => $station->id,
                'state_cd'   => 'va',
            ]);
        });

        $target = UsgsStation::factory()->create(['site_no' => '01646500']);
        $this->mockServiceWithSite('01646500');

        Livewire::actingAs($user)
            ->test(StreamGauge::class)
            ->call('selectSite', '01646500')
            ->call('saveStation')
            ->assertSet('saveSuccess', false)
            ->assertSet('saveMessage', 'You have reached the 30 saved station limit. Delete one to add more.');

        $this->assertDatabaseMissing('saved_stations', [
            'user_id'    => $user->id,
            'station_id' => $target->id,
        ]);
    }

    /**
     * Authenticated user can unsave a previously saved station.
     */
    public function test_authenticated_user_can_unsave_station(): void
    {
        $user    = User::factory()->create();
        $station = UsgsStation::factory()->create(['site_no' => '01646500']);

        SavedStation::create([
            'user_id'    => $user->id,
            'station_id' => $station->id,
            'state_cd'   => 'va',
        ]);

        $this->mockServiceWithSite('01646500');

        Livewire::actingAs($user)
            ->test(StreamGauge::class)
            ->call('selectSite', '01646500')
            ->call('unsaveStation');

        $this->assertDatabaseMissing('saved_stations', [
            'user_id'    => $user->id,
            'station_id' => $station->id,
        ]);
    }

    /**
     * Guest calling saveStation silently does nothing.
     */
    public function test_guest_cannot_save_station(): void
    {
        UsgsStation::factory()->create(['site_no' => '01646500']);
        $this->mockServiceWithSite('01646500');

        Livewire::test(StreamGauge::class)
            ->call('selectSite', '01646500')
            ->call('saveStation');

        $this->assertDatabaseCount('saved_stations', 0);
    }

    /**
     * $savedSiteCodes is populated for an authenticated user with bookmarks.
     */
    public function test_saved_site_codes_loaded_for_authenticated_user(): void
    {
        $user    = User::factory()->create();
        $station = UsgsStation::factory()->create(['site_no' => '01646500']);

        SavedStation::create([
            'user_id'    => $user->id,
            'station_id' => $station->id,
            'state_cd'   => 'va',
        ]);

        $this->mockServiceWithSite('01646500');

        Livewire::actingAs($user)
            ->test(StreamGauge::class)
            ->assertSet('savedSiteCodes', ['01646500']);
    }

    /**
     * $savedSiteCodes is empty for a guest.
     */
    public function test_saved_site_codes_empty_for_guest(): void
    {
        $this->mockServiceWithSite('01646500');

        Livewire::test(StreamGauge::class)
            ->assertSet('savedSiteCodes', []);
    }

    /**
     * Deep-link ?state= pre-selects the state and dispatches the correct event.
     */
    public function test_deep_link_state_param_sets_state_cd(): void
    {
        $this->mockServiceWithSite('01646500');

        Livewire::withQueryParams(['state' => 'md'])
            ->test(StreamGauge::class)
            ->assertSet('stateCd', 'md');
    }

    /**
     * An invalid ?state= param falls back to the default 'va'.
     */
    public function test_invalid_state_param_is_ignored(): void
    {
        $this->mockServiceWithSite('01646500');

        Livewire::withQueryParams(['state' => 'xx'])
            ->test(StreamGauge::class)
            ->assertSet('stateCd', 'wa');
    }

    /**
     * Deep-link ?site= pre-selects the sparkline panel for that site.
     */
    public function test_deep_link_site_param_selects_site(): void
    {
        $this->mockServiceWithSite('01646500');

        Livewire::withQueryParams(['state' => 'va', 'site' => '01646500'])
            ->test(StreamGauge::class)
            ->assertSet('selectedSiteCode', '01646500')
            ->assertSet('sparklineData', fn ($data) => $data !== null);
    }

    /**
     * state_cd is stored on the saved_stations record when saving.
     */
    public function test_state_cd_is_persisted_when_saving(): void
    {
        $user    = User::factory()->create();
        $station = UsgsStation::factory()->create(['site_no' => '01646500']);

        $this->mockServiceWithSite('01646500');

        Livewire::actingAs($user)
            ->test(StreamGauge::class)  // default stateCd = 'wa'
            ->call('selectSite', '01646500')
            ->call('saveStation');

        $this->assertDatabaseHas('saved_stations', [
            'user_id'    => $user->id,
            'station_id' => $station->id,
            'state_cd'   => 'wa',
        ]);
    }

    /**
     * Bind a WaterServicesService mock that returns a fake two-parameter collection
     * for the given site code. Used by tests that need a selectable site.
     */
    private function mockServiceWithSite(string $siteCode): void
    {
        $collection = collect([
            new \App\Data\WaterServicesData(
                siteCode: $siteCode,
                siteName: 'Test Gauge Station',
                lat: 38.9495,
                lng: -77.1228,
                parameterCode: '00060',
                parameterName: 'Streamflow, ft³/s',
                unitCode: 'ft3/s',
                latestValue: 1234.5,
                latestDateTime: '2025-04-14T15:45:00.000-04:00',
                qualifiers: ['P'],
                allValues: [
                    ['value' => 1200.0, 'dateTime' => '2025-04-12T12:00:00.000-04:00'],
                    ['value' => 1234.5, 'dateTime' => '2025-04-14T15:45:00.000-04:00'],
                ],
            ),
            new \App\Data\WaterServicesData(
                siteCode: $siteCode,
                siteName: 'Test Gauge Station',
                lat: 38.9495,
                lng: -77.1228,
                parameterCode: '00065',
                parameterName: 'Gage height, ft',
                unitCode: 'ft',
                latestValue: 4.56,
                latestDateTime: '2025-04-14T15:45:00.000-04:00',
                qualifiers: ['P'],
                allValues: [
                    ['value' => 4.20, 'dateTime' => '2025-04-12T12:00:00.000-04:00'],
                    ['value' => 4.56, 'dateTime' => '2025-04-14T15:45:00.000-04:00'],
                ],
            ),
        ]);

        $mock = $this->createMock(WaterServicesService::class);
        $mock->method('query')->willReturn($collection);
        $this->app->instance(WaterServicesService::class, $mock);
    }
}
