<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Earthquake;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Displays the 10 most recent M4+ earthquakes ingested from USGS ComCat.
 *
 * Reads from the local `earthquakes` table populated by the
 * `app:ingest-earthquakes` scheduled command. Polls every 5 minutes
 * so the homepage reflects the latest ingestion without a manual refresh.
 */
class RecentEarthquakes extends Component
{
    /**
     * Render the component.
     *
     * Passes the 10 most recent events and the timestamp of the last
     * successful ingestion run (null if the command has never run).
     *
     * @param Collection<int, Earthquake> $earthquakes
     */
    public function render(): View
    {
        /** @var Collection<int, Earthquake> $earthquakes */
        $earthquakes = Earthquake::query()
            ->orderByDesc('occurred_at')
            ->limit(10)
            ->get();

        $lastIngestion = Cache::get('earthquake.last_ingestion')
            ? Carbon::parse(Cache::get('earthquake.last_ingestion'))
            : null;

        return view('livewire.recent-earthquakes', compact('earthquakes', 'lastIngestion'));
    }
}
