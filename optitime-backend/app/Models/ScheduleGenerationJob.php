<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ScheduleGenerationJob extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'requested_by_user_id',
        'status',
        'algorithm',
        'semester_id',
        'request_payload',
        'result_payload',
        'error_message',
        'progress',
        'queued_at',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'result_payload' => 'array',
        'progress' => 'integer',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
