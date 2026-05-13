<?php

namespace App\Scheduling;

final class RoomRef
{
    public function __construct(
        public readonly string $id,
        public readonly int $capacity,
        public readonly bool $isLab,
        public readonly string $status = 'available',
    ) {}
}
