<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Api\Queries\EarthquakeQuery;
use App\Api\USGSEarthquake;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('QuakeWatch — Earthquake Radius Search | CronosPulse')]
class QuakeWatch extends Component
{
    use WithPagination;

    /**
     * Earthquake results. Null until the first search is run.
     *
     * @var list<array<string, mixed>>|null
     */
    public ?array $earthquakes = null;

    /**
     * Error message shown when the API call fails.
     */
    public ?string $error = null;

    /**
     * Column currently used for sorting: 'time', 'magnitude', or 'depth_km'.
     */
    public string $sortColumn = 'time';

    /**
     * Sort direction for the active column: 'asc' or 'desc'.
     */
    public string $sortDirection = 'desc';

    /**
     * IANA timezone identifier of the clicked map location (e.g. 'America/New_York').
     * Passed from Alpine/tz-lookup and used to localise event times.
     */
    public string $timezone = 'UTC';

    /**
     * Short timezone label derived from $timezone, shown in the table header.
     * Computed during search() so it reflects the timezone that was active when
     * the results were fetched.
     */
    public string $timezoneLabel = 'UTC';

    /**
     * Number of results to show per page. 0 means show all.
     */
    public int $perPage = 20;

    /**
     * Search for earthquakes within the given radius of a map point.
     *
     * Called from Alpine via $wire.search(lat, lng, radius, minMagnitude, timezone).
     *
     * @param float  $latitude     Decimal degrees latitude of the search centre.
     * @param float  $longitude    Decimal degrees longitude of the search centre.
     * @param float  $radius       Search radius in kilometres.
     * @param float  $minMagnitude Minimum magnitude filter (0.0 = no filter).
     * @param string $timezone     IANA timezone identifier for the clicked location.
     */
    public function search(
        float $latitude,
        float $longitude,
        float $radius,
        float $minMagnitude = 0.0,
        string $timezone = 'UTC',
    ): void {
        $this->error = null;
        $this->earthquakes = null;

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            $this->error = 'Invalid coordinates. Click a point on the map and try again.';
            $this->dispatch('earthquakes-updated', earthquakes: []);
            return;
        }

        if ($radius <= 0) {
            $this->error = 'Radius must be greater than zero.';
            $this->dispatch('earthquakes-updated', earthquakes: []);
            return;
        }

        // Sanitise the timezone — fall back to UTC if the identifier is invalid.
        try {
            $tz = new \DateTimeZone($timezone);
        } catch (\Throwable) {
            $tz = new \DateTimeZone('UTC');
        }

        $this->timezone = $tz->getName();

        try {
            $query = EarthquakeQuery::make($latitude, $longitude);
            $query->maxradiuskm($radius);

            if ($minMagnitude > 0.0) {
                $query->minmagnitude($minMagnitude);
            }

            $response = new USGSEarthquake()->query($query);

            if (! $response->successful()) {
                $this->error = 'The USGS API returned an error. Please try again.';
                $this->dispatch('earthquakes-updated', earthquakes: []);
                return;
            }

            // Use a throwaway Carbon instance to derive the short timezone abbreviation
            // (e.g. 'EST', 'PDT') displayed in the table header.
            $this->timezoneLabel = Carbon::now($this->timezone)->format('T');

            $this->earthquakes = collect($response->json('features', []))
                ->map(function (array $feature): array {
                    $props  = $feature['properties'];
                    $mag    = (float) ($props['mag'] ?? 0);
                    $timeMs = (int) $props['time'];

                    // GeoJSON coordinates are [longitude, latitude, depth].
                    $lng = (float) $feature['geometry']['coordinates'][0];
                    $lat = (float) $feature['geometry']['coordinates'][1];

                    return [
                        'lat'       => $lat,
                        'lng'       => $lng,
                        'magnitude' => $mag,
                        'mag_class' => $this->magnitudeClass($mag),
                        'place'     => $props['place'] ?? '—',
                        // time_ms kept for stable client-side sorting (avoids re-parsing strings).
                        'time_ms'   => $timeMs,
                        'time'      => Carbon::createFromTimestampMs($timeMs)
                            ->setTimezone($this->timezone)
                            ->format('l g:ia, F jS, Y'),
                        'depth_km'  => round((float) ($feature['geometry']['coordinates'][2] ?? 0), 1),
                        'alert'     => $props['alert'],
                        'status'    => $props['status'] ?? null,
                        'url'       => $props['url'] ?? null,
                    ];
                })
                ->values()
                ->toArray();

            $this->applySorting();
            $this->resetPage();
        } catch (Throwable) {
            $this->error = 'Failed to reach the USGS API. Please check your connection and try again.';
        }

        // Always dispatch so the map clears stale markers from the previous search.
        $this->dispatch('earthquakes-updated', earthquakes: $this->earthquakes ?? []);
    }

    /**
     * Toggle sort direction if the same column is clicked, or switch to the new
     * column with descending order. Re-sorts the in-memory results without an
     * additional API call.
     */
    public function sort(string $column): void
    {
        if (! in_array($column, ['time_ms', 'magnitude', 'depth_km'], true)) {
            return;
        }

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn    = $column;
            $this->sortDirection = 'desc';
        }

        $this->applySorting();
        $this->resetPage();
    }

    /**
     * Update the per-page count and return to the first page.
     *
     * Called by the per-page selector component via wire:change.
     */
    public function setPerPage(int $value): void
    {
        $this->perPage = $value;
        $this->resetPage();
    }

    /**
     * Sort $this->earthquakes in place according to the current sort state.
     */
    private function applySorting(): void
    {
        if ($this->earthquakes === null) {
            return;
        }

        $col = $this->sortColumn;
        $asc = $this->sortDirection === 'asc';

        usort($this->earthquakes, static function (array $a, array $b) use ($col, $asc): int {
            $result = $a[$col] <=> $b[$col];
            return $asc ? $result : -$result;
        });
    }

    /**
     * Return a Tailwind class string for the given magnitude value.
     */
    private function magnitudeClass(float $magnitude): string
    {
        return match (true) {
            $magnitude >= 6.0 => 'text-danger font-bold',
            $magnitude >= 5.0 => 'text-warning font-semibold',
            $magnitude >= 4.0 => 'text-warning',
            $magnitude >= 2.0 => 'text-text',
            default           => 'text-muted',
        };
    }

    /**
     * Render the QuakeWatch page.
     *
     * Builds a LengthAwarePaginator from the in-memory $earthquakes array so
     * the existing pagination-bar component can be reused without a database query.
     */
    public function render(): View
    {
        $paginator = null;

        if ($this->earthquakes !== null) {
            $all     = collect($this->earthquakes);
            $perPage = $this->perPage > 0 ? $this->perPage : $all->count();
            $page    = $this->getPage();

            $paginator = new LengthAwarePaginator(
                items: $all->slice(($page - 1) * $perPage, $perPage)->values()->all(),
                total: $all->count(),
                perPage: $perPage,
                currentPage: $page,
            );
        }

        return view('livewire.pages.quake-watch', ['paginator' => $paginator]);
    }
}
