<?php

declare(strict_types=1);

namespace App\Services;

use App\Api\Queries\VolcanoQuery;
use App\Api\USGSVolcano;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Application service for volcano monitoring data.
 *
 * Wraps the raw USGSVolcano API client and normalises VHP status records
 * into a consistent array shape. Results are cached for 5 minutes — the
 * endpoint returns a full dataset with no filter parameters, making it a
 * good cache candidate.
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
     * Retrieve and normalise all USGS-tracked volcanoes.
     *
     * Each record contains: vnum, name, region, latitude, longitude,
     * alert_level, alert_class, color_code, color_class, synopsis, url.
     *
     * @return list<array<string, mixed>>
     *
     * @throws RuntimeException If the USGS Volcano API returns a non-successful response.
     */
    public function all(): array
    {
        return Cache::remember('usgs.volcanoes.all', 300, function (): array {
            $response = $this->client->vhpStatus(VolcanoQuery::make());

            if (! $response->successful()) {
                throw new RuntimeException('The USGS Volcano API returned an error.');
            }

            return collect($response->json() ?? [])
                ->map(function (array $v): array {
                    $alertLevel = $v['alertLevel'] ?? 'UNASSIGNED';
                    $colorCode  = $v['colorCode'] ?? 'UNASSIGNED';

                    return [
                        'vnum'        => (string) ($v['vnum'] ?? ''),
                        'name'        => $v['vName'] ?? '',
                        'region'      => $v['region'] ?? '',
                        'latitude'    => (float) ($v['lat'] ?? 0),
                        'longitude'   => (float) ($v['long'] ?? 0),
                        'alert_level' => $alertLevel,
                        'alert_class' => $this->alertLevelClass($alertLevel),
                        'color_code'  => $colorCode,
                        'color_class' => $this->aviationColorClass($colorCode),
                        'synopsis'    => $v['noticeSynopsis'] ?? null,
                        'url'         => $v['vUrl'] ?? null,
                    ];
                })
                ->values()
                ->toArray();
        });
    }

    /**
     * Return Tailwind badge classes for the given USGS ground alert level.
     *
     * Levels in ascending severity: NORMAL → ADVISORY → WATCH → WARNING.
     */
    private function alertLevelClass(string $alertLevel): string
    {
        return match ($alertLevel) {
            'WARNING'  => 'bg-danger/15 text-danger',
            'WATCH'    => 'bg-warning/15 text-warning',
            'ADVISORY' => 'bg-info/15 text-info',
            'NORMAL'   => 'bg-success/15 text-success',
            default    => 'bg-surface-raised text-muted',
        };
    }

    /**
     * Return Tailwind badge classes for the given USGS aviation color code.
     *
     * Codes in ascending severity: GREEN → YELLOW → ORANGE → RED.
     */
    private function aviationColorClass(string $colorCode): string
    {
        return match ($colorCode) {
            'RED'    => 'bg-danger/15 text-danger',
            'ORANGE' => 'bg-warning/15 text-warning',
            'YELLOW' => 'bg-info/15 text-info',
            'GREEN'  => 'bg-success/15 text-success',
            default  => 'bg-surface-raised text-muted',
        };
    }
}
