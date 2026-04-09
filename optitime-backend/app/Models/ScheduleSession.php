<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleSession extends Model
{
    use HasUuids;

    protected $table = 'schedule_sessions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'room_id', 'schedule_plan_id', 'section_instructor_id', 'course_offering_id',
        'day_value', 'start_time', 'end_time',
        'conflict_state', 'conflict_reasons',
        'grades_entry_status', 'grades_marked_done_at',
    ];

    protected $casts = [
        'grades_marked_done_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SemesterSchedulePlan::class, 'schedule_plan_id');
    }

    public function sectionInstructor(): BelongsTo
    {
        return $this->belongsTo(CourseSectionInstructor::class, 'section_instructor_id');
    }

    public function courseOffering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function sessionStudents(): HasMany
    {
        return $this->hasMany(ScheduleSessionStudent::class, 'schedule_session_id');
    }
}
