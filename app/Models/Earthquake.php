<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Earthquake extends Model
{
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
            'magnitude' => 'float',
            'depth_km' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
            'cdi' => 'float',
            'mmi' => 'float',
            'felt' => 'integer',
            'significance' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }
}
