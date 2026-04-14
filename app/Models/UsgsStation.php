<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UsgsStation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'site_no',
        'name',
        'state',
        'county',
        'huc',
        'site_type',
        'latitude',
        'longitude',
        'elevation_ft',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'elevation_ft' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<StationReading, $this>
     */
    public function readings(): HasMany
    {
        return $this->hasMany(StationReading::class, 'station_id');
    }

    /**
     * @return HasMany<SavedStation, $this>
     */
    public function savedByUsers(): HasMany
    {
        return $this->hasMany(SavedStation::class, 'station_id');
    }
}
