<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;

/**
 * Pivot for room_resources: MySQL requires a value for the UUID `id` column on insert.
 */
class RoomResource extends Pivot
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'room_resources';

    protected static function booted(): void
    {
        static::creating(function (RoomResource $pivot) {
            if (empty($pivot->id)) {
                $pivot->id = (string) Str::uuid();
            }
        });
    }
}
