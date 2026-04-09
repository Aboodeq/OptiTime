<?php

namespace App\Services;

use App\Models\CourseSection;
use App\Models\CourseSectionInstructor;

class SectionInstructorSyncService
{
    /**
     * Replace section instructor assignments and return the section with instructors loaded.
     *
     * @param  list<string>  $instructorIds
     */
    public function sync(string $sectionId, array $instructorIds): CourseSection
    {
        CourseSection::query()->findOrFail($sectionId);

        CourseSectionInstructor::query()->where('section_id', $sectionId)->delete();

        foreach ($instructorIds as $instructorId) {
            CourseSectionInstructor::query()->create([
                'section_id' => $sectionId,
                'instructor_id' => $instructorId,
            ]);
        }

        return CourseSection::query()->with('instructors')->findOrFail($sectionId);
    }
}
