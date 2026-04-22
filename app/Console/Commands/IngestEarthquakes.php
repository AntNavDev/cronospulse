<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Api\Queries\EarthquakeQuery;
use App\Data\EarthquakeData;
use App\Models\Earthquake;
use App\Services\EarthquakeService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Ingests M4+ earthquakes from the USGS ComCat API into the local database.
 *
 * On the first run (no cached timestamp), seeds the table with the last 24 hours.
 * On subsequent runs, fetches only events updated since the previous successful
 * run using the USGS `updatedafter` parameter — this captures both new events
 * and analyst revisions to existing ones.
 *
 * Schedule: hourly via routes/console.php.
 */
class IngestEarthquakes extends Command
{
    protected $signature = 'app:ingest-earthquakes';

    protected $description = 'Ingest M4+ earthquakes from USGS ComCat into the local database.';

    /**
     * Cache key storing the ISO8601 timestamp of the last successful run.
     */
    private const CACHE_KEY = 'earthquake.last_ingestion';

    /**
     * Minimum magnitude ingested. M4+ is meaningful globally and manageable in volume.
     */
    private const MIN_MAGNITUDE = 4.0;

    /**
     * Maximum events to request per run. Covers ~10 days of M4+ activity globally.
     */
    private const LIMIT = 500;

    public function __construct(private readonly EarthquakeService $earthquakeService)
    {
        parent::__construct();
    }

    /**
     * Execute the ingestion.
     *
     * Queries the USGS API, upserts each event by usgs_id, and stores the
     * current timestamp for the next run's updatedafter parameter.
     */
    public function handle(): int
    {
        $lastRun = Cache::get(self::CACHE_KEY);

        $query = EarthquakeQuery::makeGlobal()
            ->minmagnitude(self::MIN_MAGNITUDE)
            ->orderby('time')
            ->limit(self::LIMIT);

        if ($lastRun !== null) {
            $query->updatedafter(Carbon::parse($lastRun));
            $this->line("Fetching events updated after {$lastRun}.");
        } else {
            $query->starttime(now()->subHours(24));
            $this->line('No prior run found — seeding with last 24 hours.');
        }

        try {
            $earthquakes = $this->earthquakeService->query($query);
        } catch (RuntimeException $e) {
            $this->error("USGS API error: {$e->getMessage()}");

            return self::FAILURE;
        }

        $ingested = 0;

        foreach ($earthquakes as $eq) {
            /** @var EarthquakeData $eq */
            if ($eq->usgsId === null) {
                continue;
            }

            Earthquake::updateOrCreate(
                ['usgs_id' => $eq->usgsId],
                [
                    'magnitude'      => $eq->magnitude,
                    'magnitude_type' => $eq->magnitudeType,
                    'depth_km'       => $eq->depthKm,
                    'latitude'       => $eq->lat,
                    'longitude'      => $eq->lng,
                    'place'          => $eq->place,
                    'status'         => $eq->status,
                    'alert'          => $eq->alert,
                    'felt'           => $eq->felt,
                    'cdi'            => $eq->cdi,
                    'mmi'            => $eq->mmi,
                    'significance'   => $eq->significance,
                    'url'            => $eq->url,
                    'occurred_at'    => Carbon::createFromTimestampMs($eq->timeMs),
                ],
            );

            $ingested++;
        }

        // Store the run time AFTER a successful upsert pass so that a partial
        // failure on the next run re-fetches from the last clean checkpoint.
        Cache::put(self::CACHE_KEY, now()->toIso8601String(), now()->addDays(2));

        $this->info("Ingested / updated {$ingested} earthquake(s).");

        return self::SUCCESS;
    }
}
