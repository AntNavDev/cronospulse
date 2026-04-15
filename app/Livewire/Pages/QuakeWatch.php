<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Api\Queries\EarthquakeQuery;
use App\Api\USGSEarthquake;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('QuakeWatch — Earthquake Radius Search | CronosPulse')]
class QuakeWatch extends Component
{
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
     * Search for earthquakes within the given radius of a map point.
     *
     * Called from Alpine via $wire.search(lat, lng, radius).
     *
     * @param float $latitude  Decimal degrees latitude of the search centre.
     * @param float $longitude Decimal degrees longitude of the search centre.
     * @param float $radius    Search radius in kilometres.
     */
    public function search(float $latitude, float $longitude, float $radius): void
    {
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

        try {
            $query = EarthquakeQuery::make($latitude, $longitude);
            $query->maxradiuskm($radius);

            $response = new USGSEarthquake()->query($query);

            if (! $response->successful()) {
                $this->error = 'The USGS API returned an error. Please try again.';
                $this->dispatch('earthquakes-updated', earthquakes: []);
                return;
            }

            $this->earthquakes = collect($response->json('features', []))
                ->map(function (array $feature): array {
                    $props = $feature['properties'];
                    $mag   = (float) ($props['mag'] ?? 0);

                    // GeoJSON coordinates are [longitude, latitude, depth].
                    $lng = (float) $feature['geometry']['coordinates'][0];
                    $lat = (float) $feature['geometry']['coordinates'][1];

                    return [
                        'lat'       => $lat,
                        'lng'       => $lng,
                        'magnitude' => $mag,
                        'mag_class' => $this->magnitudeClass($mag),
                        'place'     => $props['place'] ?? '—',
                        'time'      => Carbon::createFromTimestampMs((int) $props['time'])
                            ->utc()
                            ->format('Y-m-d H:i:s'),
                        'depth_km'  => round((float) ($feature['geometry']['coordinates'][2] ?? 0), 1),
                        'alert'     => $props['alert'],
                        'status'    => $props['status'] ?? null,
                        'url'       => $props['url'] ?? null,
                    ];
                })
                ->values()
                ->toArray();
        } catch (Throwable) {
            $this->error = 'Failed to reach the USGS API. Please check your connection and try again.';
        }

        // Always dispatch so the map clears stale markers from the previous search.
        $this->dispatch('earthquakes-updated', earthquakes: $this->earthquakes ?? []);
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
     */
    public function render(): View
    {
        return view('livewire.pages.quake-watch');
    }
}
