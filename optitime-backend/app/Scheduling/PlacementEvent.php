<?php

namespace App\Scheduling;

final class PlacementEvent
{
    public function __construct(
        public readonly int $index,
        public readonly ?string $rowId,
        public readonly string $courseId,
        public readonly string $courseSectionId,
        public readonly string $instructorId,
        public readonly string $courseOfferingId,
        public readonly string $sectionInstructorId,
        public readonly int $enrollment,
        public readonly bool $requiresLab,
        public readonly ?string $courseCode,
        public readonly ?string $courseName,
        public readonly ?Placement $fixed = null,
        public readonly ?string $departmentId = null,
    ) {}

    public function isFixed(): bool
    {
        return $this->fixed !== null;
    }
}
