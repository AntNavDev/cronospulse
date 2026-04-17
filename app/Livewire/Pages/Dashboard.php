<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Models\SavedEarthquakeSearch;
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
     * Render the Dashboard page.
     *
     * Passes saved searches ordered newest-first.
     *
     * @param Collection<int, SavedEarthquakeSearch> $searches
     */
    public function render(): View
    {
        /** @var Collection<int, SavedEarthquakeSearch> $searches */
        $searches = auth()->user()
            ->savedEarthquakeSearches()
            ->latest()
            ->get();

        return view('livewire.pages.dashboard', ['searches' => $searches]);
    }
}
