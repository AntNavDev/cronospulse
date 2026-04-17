<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Unauthenticated users are redirected away from the dashboard.
     */
    public function test_guests_are_redirected(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    /**
     * Authenticated users can load the dashboard.
     */
    public function test_authenticated_user_sees_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    /**
     * Saved searches belonging to the user are displayed.
     */
    public function test_saved_searches_are_listed(): void
    {
        $user = User::factory()->create();

        $search = $user->savedEarthquakeSearches()->create([
            'name'          => 'Bay Area M3+',
            'latitude'      => 37.7749,
            'longitude'     => -122.4194,
            'radius_km'     => 100.0,
            'min_magnitude' => 3.0,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Bay Area M3+');
    }

    /**
     * A user can delete their own saved search via the Livewire action.
     */
    public function test_user_can_delete_own_search(): void
    {
        $user = User::factory()->create();

        $search = $user->savedEarthquakeSearches()->create([
            'name'          => 'To be deleted',
            'latitude'      => 34.0522,
            'longitude'     => -118.2437,
            'radius_km'     => 50.0,
            'min_magnitude' => 0.0,
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Pages\Dashboard::class)
            ->call('deleteSearch', $search->id);

        $this->assertDatabaseMissing('saved_earthquake_searches', ['id' => $search->id]);
    }

    /**
     * A user cannot delete a saved search belonging to someone else.
     */
    public function test_user_cannot_delete_another_users_search(): void
    {
        $owner  = User::factory()->create();
        $other  = User::factory()->create();

        $search = $owner->savedEarthquakeSearches()->create([
            'name'          => 'Owner search',
            'latitude'      => 34.0522,
            'longitude'     => -118.2437,
            'radius_km'     => 50.0,
            'min_magnitude' => 0.0,
        ]);

        Livewire::actingAs($other)
            ->test(\App\Livewire\Pages\Dashboard::class)
            ->call('deleteSearch', $search->id);

        $this->assertDatabaseHas('saved_earthquake_searches', ['id' => $search->id]);
    }
}
