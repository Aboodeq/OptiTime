<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorAvailabilityCell extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'availability_profile_id',
        'day_value',
        'start_time',
        'end_time',
        'status',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(InstructorAvailabilityProfile::class, 'availability_profile_id');
    }
}
