<?php

namespace App\Scheduling;

final class Placement
{
    public function __construct(
        public readonly string $day,
        public readonly int $startMin,
        public readonly int $endMin,
        public readonly string $roomId,
    ) {}

    public static function fromGridCell(GridCell $cell, string $roomId): self
    {
        return new self($cell->day, $cell->startMin, $cell->endMin, $roomId);
    }
}
