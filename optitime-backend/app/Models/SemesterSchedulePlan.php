<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SemesterSchedulePlan extends Model
{
    use HasUuids;

    protected $table = 'semester_schedule_plans';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'semester_id',
        'status',
        'selected_algorithm',
        'generated_at',
        'notes',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ScheduleSession::class, 'schedule_plan_id');
    }
}
