<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class Earthquake extends Model
{
    /** @use HasFactory<\Database\Factories\EarthquakeFactory> */
    use HasFactory;
    use MassPrunable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'usgs_id',
        'magnitude',
        'magnitude_type',
        'depth_km',
        'latitude',
        'longitude',
        'place',
        'status',
        'alert',
        'felt',
        'cdi',
        'mmi',
        'significance',
        'url',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'magnitude'    => 'float',
            'depth_km'     => 'float',
            'latitude'     => 'float',
            'longitude'    => 'float',
            'cdi'          => 'float',
            'mmi'          => 'float',
            'felt'         => 'integer',
            'significance' => 'integer',
        ];
    }

    /**
     * Parse occurred_at as UTC regardless of the application timezone.
     *
     * MySQL stores the raw UTC datetime string. The default 'datetime' cast
     * would call Carbon::parse() which applies APP_TIMEZONE, misreading e.g.
     * "11:00:00 UTC" as "11:00:00 America/Los_Angeles" — making recent events
     * appear to be 7–8 hours in the future.
     *
     * The setter converts any incoming value to UTC before storage so the
     * column always contains UTC strings on both read and write paths.
     */
    protected function occurredAt(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Carbon::parse($value, 'UTC') : null,
            set: fn (\DateTimeInterface|string $value) => Carbon::parse($value)->utc()->toDateTimeString(),
        );
    }

    /**
     * Scope the query to records eligible for pruning.
     *
     * Events older than 30 days are removed. This keeps the table bounded
     * at roughly 1,500 rows (50 M4+ events/day × 30 days) and is run daily
     * by the model:prune schedule entry in routes/console.php.
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function prunable(): \Illuminate\Database\Eloquent\Builder
    {
        return static::where('occurred_at', '<', now()->utc()->subDays(30)->toDateTimeString());
    }

    /**
     * Return a Tailwind class string appropriate for this event's magnitude.
     */
    public function magClass(): string
    {
        return match (true) {
            $this->magnitude >= 6.0 => 'text-danger font-bold',
            $this->magnitude >= 5.0 => 'text-warning font-semibold',
            $this->magnitude >= 4.0 => 'text-warning',
            default                 => 'text-muted',
        };
    }

    /**
     * Return the x-label variant string for the PAGER alert level.
     *
     * Maps PAGER colours to the semantic label variants defined in the
     * label component. Orange falls back to 'warning' (no dedicated variant).
     */
    public function alertLabelVariant(): string
    {
        return match ($this->alert) {
            'red'            => 'danger',
            'orange', 'yellow' => 'warning',
            'green'          => 'success',
            default          => 'neutral',
        };
    }
}
