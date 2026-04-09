<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSection extends Model
{
    use HasUuids;

    protected $table = 'course_sections';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'course_id', 'section_name', 'section_type', 'capacity',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function sectionInstructors(): HasMany
    {
        return $this->hasMany(CourseSectionInstructor::class, 'section_id');
    }

    public function instructors()
    {
        return $this->belongsToMany(Instructor::class, 'course_section_instructors', 'section_id', 'instructor_id');
    }
}
