<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseConstraint extends Model
{
    use HasUuids;

    protected $table = 'course_constraints';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'course_id', 'min_student_year_level', 'max_student_year_level', 'notes',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
