<?php

namespace App\Scheduling;




final class SchedulingContext
{
    




    public function __construct(
        public readonly array $legalCellKeys,
        public readonly array $instructorAvailabilityByInstructorId,
        public readonly array $sectionPairsWithSharedStudents,
    ) {}

    public static function empty(): self
    {
        return new self([], [], []);
    }
}
