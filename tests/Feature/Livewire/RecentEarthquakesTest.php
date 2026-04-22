<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\RecentEarthquakes;
use App\Models\Earthquake;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class RecentEarthquakesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The component renders without errors when the earthquakes table is empty.
     */
    public function test_it_renders_empty_state_when_no_events(): void
    {
        Livewire::test(RecentEarthquakes::class)
            ->assertOk()
            ->assertSee('No events yet');
    }

    /**
     * The component renders rows for each earthquake in the database.
     */
    public function test_it_renders_recent_earthquakes(): void
    {
        Earthquake::factory()->create([
            'magnitude' => 5.2,
            'place'     => '10km NW of Ridgecrest, CA',
            'occurred_at' => Carbon::now()->subHours(2),
        ]);

        Livewire::test(RecentEarthquakes::class)
            ->assertOk()
            ->assertSee('M5.2')
            ->assertSee('10km NW of Ridgecrest, CA');
    }

    /**
     * Only the 10 most recent events are shown.
     */
    public function test_it_limits_to_10_events(): void
    {
        Earthquake::factory()->count(15)->create();

        $component = Livewire::test(RecentEarthquakes::class);

        // The view renders at most 10 rows — assert the component
        // passes exactly 10 earthquakes to the view.
        $this->assertCount(10, $component->viewData('earthquakes'));
    }

    /**
     * Events are ordered most-recent-first.
     */
    public function test_events_are_ordered_by_most_recent_first(): void
    {
        $older = Earthquake::factory()->create(['occurred_at' => Carbon::now()->subHours(5)]);
        $newer = Earthquake::factory()->create(['occurred_at' => Carbon::now()->subHour()]);

        $earthquakes = Livewire::test(RecentEarthquakes::class)
            ->viewData('earthquakes');

        $this->assertTrue($earthquakes->first()->is($newer));
        $this->assertTrue($earthquakes->last()->is($older));
    }

    /**
     * The last ingestion timestamp is passed to the view when cached.
     */
    public function test_last_ingestion_timestamp_is_passed_to_view(): void
    {
        Cache::put('earthquake.last_ingestion', now()->toIso8601String(), now()->addHour());

        $lastIngestion = Livewire::test(RecentEarthquakes::class)
            ->viewData('lastIngestion');

        $this->assertNotNull($lastIngestion);
    }

    /**
     * The home page renders successfully with the component embedded.
     */
    public function test_home_page_renders_with_component(): void
    {
        $this->get(route('home'))->assertOk();
    }
}
