<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Models\SavedEarthquakeSearch;
use App\Models\SavedStation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard — CronosPulse')]
class Dashboard extends Component
{
    /**
     * Delete a saved earthquake search by ID.
     *
     * Verifies ownership before deleting so IDs cannot be spoofed.
     */
    public function deleteSearch(int $id): void
    {
        $search = SavedEarthquakeSearch::find($id);

        if ($search && $search->user_id === auth()->id()) {
            $search->delete();
        }
    }

    /**
     * Remove a saved stream gauge station by saved_stations ID.
     *
     * Verifies ownership before deleting so IDs cannot be spoofed.
     */
    public function deleteStation(int $id): void
    {
        $record = SavedStation::find($id);

        if ($record && $record->user_id === auth()->id()) {
            $record->delete();
        }
    }

    /**
     * Render the Dashboard page.
     *
     * Passes saved earthquake searches and saved stream gauge stations,
     * both ordered newest-first.
     */
    public function render(): View
    {
        /** @var Collection<int, SavedEarthquakeSearch> $searches */
        $searches = auth()->user()
            ->savedEarthquakeSearches()
            ->latest()
            ->get();

        /** @var Collection<int, SavedStation> $stations */
        $stations = auth()->user()
            ->savedStations()
            ->with('station')
            ->latest()
            ->get();

        return view('livewire.pages.dashboard', [
            'searches' => $searches,
            'stations' => $stations,
        ]);
    }
}
