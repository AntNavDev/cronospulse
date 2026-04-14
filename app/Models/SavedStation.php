<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedStation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'station_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<UsgsStation, $this>
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(UsgsStation::class, 'station_id');
    }
}
