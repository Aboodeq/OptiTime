<?php

namespace App\Scheduling;




final class SchedulingContext
{
    public function __construct(
        public readonly array $legalCellKeys,
        public readonly array $instructorPreferredAvailabilityByInstructorId,
        public readonly array $instructorUnavailableAvailabilityByInstructorId,
        public readonly array $sectionPairsWithSharedStudents,
        public readonly array $instructorWeeklyLoadLimitsByInstructorId,
    ) {}

    public static function empty(): self
    {
        return new self([], [], [], [], []);
    }
}
