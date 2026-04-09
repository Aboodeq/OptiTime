<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleSessionStudent extends Model
{
    use HasUuids;

    protected $table = 'schedule_session_students';

    public $incrementing = false;

    protected $keyType = 'string';

    const UPDATED_AT = null;

    protected $fillable = [
        'schedule_session_id', 'student_id', 'assignment_source',
        'oral', 'lab', 'midterm', 'final', 'total', 'letter_grade', 'grade_entered_at',
    ];

    protected $casts = [
        'grade_entered_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ScheduleSession::class, 'schedule_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'user_id');
    }
}
