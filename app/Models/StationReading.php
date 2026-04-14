<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationReading extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'station_id',
        'parameter_code',
        'parameter_name',
        'value',
        'unit',
        'qualifier',
        'recorded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'float',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<UsgsStation, $this>
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(UsgsStation::class, 'station_id');
    }
}
