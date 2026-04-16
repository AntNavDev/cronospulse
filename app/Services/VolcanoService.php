<?php

declare(strict_types=1);

namespace App\Services;

use App\Api\Queries\VolcanoQuery;
use App\Api\USGSVolcano;
use App\Data\VolcanoData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Application service for volcano monitoring data.
 *
 * Wraps the raw USGSVolcano API client and parses VHP status records into
 * typed VolcanoData objects. Results are cached for 5 minutes — the endpoint
 * returns a full dataset with no filter parameters, making it a good cache
 * candidate.
 */
class VolcanoService
{
    /**
     * @param USGSVolcano $client Raw USGS Volcano Hazards Program API client.
     */
    public function __construct(private readonly USGSVolcano $client)
    {
    }

    /**
     * Retrieve all USGS-tracked volcanoes as a typed collection.
     *
     * Raw API JSON is cached rather than serialized PHP objects. Caching object
     * instances causes __PHP_Incomplete_Class errors when the class definition
     * changes between cache writes and reads. Plain arrays are immune to this.
     *
     * @return Collection<int, VolcanoData>
     *
     * @throws RuntimeException If the USGS Volcano API returns a non-successful response.
     */
    public function all(): Collection
    {
        /** @var array<int, array<string, mixed>> $raw */
        $raw = Cache::remember('usgs.volcanoes.all', 300, function (): array {
            $response = $this->client->vhpStatus(VolcanoQuery::make());

            if (! $response->successful()) {
                throw new RuntimeException('The USGS Volcano API returned an error.');
            }

            return $response->json() ?? [];
        });

        return collect($raw)
            ->map(fn (array $v): VolcanoData => new VolcanoData(
                vnum: (string) ($v['vnum'] ?? ''),
                name: $v['vName'] ?? '',
                region: $v['region'] ?? '',
                latitude: (float) ($v['lat'] ?? 0),
                longitude: (float) ($v['long'] ?? 0),
                alertLevel: $v['alertLevel'] ?? 'UNASSIGNED',
                colorCode: $v['colorCode'] ?? 'UNASSIGNED',
                synopsis: $v['noticeSynopsis'] ?? null,
                url: $v['vUrl'] ?? null,
            ));
    }
}
