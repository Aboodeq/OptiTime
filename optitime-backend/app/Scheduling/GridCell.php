<?php

namespace App\Scheduling;


final class GridCell
{
    public function __construct(
        public readonly string $day,
        public readonly int $startMin,
        public readonly int $endMin,
    ) {}

    public function startHhmm(): string
    {
        return TimeUtil::toHhmm($this->startMin);
    }

    public function endHhmm(): string
    {
        return TimeUtil::toHhmm($this->endMin);
    }
}
