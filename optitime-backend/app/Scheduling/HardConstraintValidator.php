<?php

namespace App\Scheduling;

final class HardConstraintValidator
{
    private const MAX_ERROR_MESSAGES = 200;

    /**
     * @param  array<int, Placement>  $assignments
     * @param  array<int, PlacementEvent>  $events
     * @param  array<string, RoomRef>  $roomById
     * @return array{count:int,by_constraint:array<string,int>,by_event:array<int,int>,event_indexes:array<string,list<int>>,errors:list<string>}
     */
    public function evaluate(
        array $assignments,
        array $events,
        ScheduleSettings $settings,
        array $roomById,
        SchedulingContext $ctx,
    ): array {
        $state = [
            'count' => 0,
            'by_constraint' => [],
            'by_event' => [],
            'event_indexes' => [],
            'errors' => [],
        ];

        $byIndex = $assignments;
        $n = count($events);

        for ($i = 0; $i < $n; $i++) {
            if (! isset($byIndex[$i])) {
                continue;
            }
            $placement = $byIndex[$i];
            $event = $events[$i];
            $room = $roomById[$placement->roomId] ?? null;

            if ($room === null) {
                $this->addViolation($state, 'room_exists', "Unknown room for event index {$i}", [$i]);
                continue;
            }

            if ($settings->isHardEnabled('working_hours') && $ctx->legalCellKeys !== []) {
                if (! isset($ctx->legalCellKeys[$this->cellKey($placement)])) {
                    $this->addViolation($state, 'working_hours', "Event index {$i} is outside legal study grid", [$i]);
                }
            }

            if ($settings->isHardEnabled('instructor_availability')) {
                if (! $this->placementInsideInstructorAvailability($event->instructorId, $placement, $ctx)) {
                    $this->addViolation($state, 'instructor_availability', "Event index {$i} violates instructor availability", [$i]);
                }
            }

            if ($settings->isHardEnabled('room_status_available')) {
                $status = strtolower(trim($room->status));
                if ($status !== '' && $status !== 'available') {
                    $this->addViolation($state, 'room_status_available', "Room {$room->id} is not available for event index {$i}", [$i]);
                }
            }

            if ($settings->isHardEnabled('room_capacity') && $event->enrollment > $room->capacity) {
                $this->addViolation(
                    $state,
                    'room_capacity',
                    "Event index {$i} enrollment {$event->enrollment} exceeds room capacity {$room->capacity}",
                    [$i]
                );
            }

            if ($settings->isCapacityThresholdHard() && $room->capacity > 0) {
                $ratio = $event->enrollment / $room->capacity;
                if ($ratio > $settings->capacityThreshold()) {
                    $this->addViolation($state, 'capacity_threshold', "Event index {$i} exceeds capacity threshold", [$i]);
                }
            }

            if ($settings->isHardEnabled('lab_for_lab') && $event->requiresLab && ! $room->isLab) {
                $this->addViolation($state, 'lab_for_lab', "Event index {$i} requires lab room", [$i]);
            }
        }

        if ($settings->isMaxDailyLecturesHardEnabled()) {
            $maxPerDay = max(1, $settings->maxDailyLectures());
            $byInstructorDay = [];
            $eventIndexesByInstructorDay = [];
            foreach ($byIndex as $idx => $placement) {
                $event = $events[$idx] ?? null;
                if ($event === null) {
                    continue;
                }
                $key = $event->instructorId.'|'.$placement->day;
                $byInstructorDay[$key] = ($byInstructorDay[$key] ?? 0) + 1;
                $eventIndexesByInstructorDay[$key][] = (int) $idx;
            }
            foreach ($byInstructorDay as $key => $count) {
                if ($count <= $maxPerDay) {
                    continue;
                }
                $excess = $count - $maxPerDay;
                $indexes = $eventIndexesByInstructorDay[$key] ?? [];
                for ($k = 0; $k < $excess; $k++) {
                    $this->addViolation($state, 'max_daily_lectures', "Instructor/day {$key} exceeds max daily lectures", $indexes);
                }
            }
        }

        if ($ctx->instructorWeeklyLoadLimitsByInstructorId !== []) {
            $minutesByInstructor = [];
            foreach ($byIndex as $idx => $placement) {
                $event = $events[$idx] ?? null;
                if ($event === null) {
                    continue;
                }
                $minutesByInstructor[$event->instructorId] = ($minutesByInstructor[$event->instructorId] ?? 0)
                    + max(0, $placement->endMin - $placement->startMin);
            }

            foreach ($minutesByInstructor as $instructorId => $minutes) {
                $limits = $ctx->instructorWeeklyLoadLimitsByInstructorId[$instructorId] ?? null;
                if (! is_array($limits)) {
                    continue;
                }
                $maxHours = (int) ($limits['max'] ?? 0);
                if ($maxHours > 0 && $minutes > ($maxHours * 60)) {
                    $this->addViolation(
                        $state,
                        'instructor_max_weekly_load',
                        "Instructor {$instructorId} weekly load exceeds max ({$minutes}min > ".($maxHours * 60).'min)'
                    );
                }
            }
        }

        for ($i = 0; $i < $n; $i++) {
            if (! isset($byIndex[$i])) {
                continue;
            }
            $pi = $byIndex[$i];
            $ei = $events[$i];
            for ($j = $i + 1; $j < $n; $j++) {
                if (! isset($byIndex[$j])) {
                    continue;
                }
                $pj = $byIndex[$j];
                $ej = $events[$j];
                if ($pi->day !== $pj->day) {
                    continue;
                }
                if (! TimeUtil::intervalsOverlap($pi->startMin, $pi->endMin, $pj->startMin, $pj->endMin)) {
                    continue;
                }

                if ($settings->isHardEnabled('no_instructor_overlap') && $ei->instructorId === $ej->instructorId) {
                    $this->addViolation($state, 'no_instructor_overlap', "Instructor overlap between event indexes {$i} and {$j}", [$i, $j]);
                }
                if ($settings->isHardEnabled('no_room_overlap') && $pi->roomId === $pj->roomId) {
                    $this->addViolation($state, 'no_room_overlap', "Room overlap between event indexes {$i} and {$j}", [$i, $j]);
                }
                if ($settings->isHardEnabled('no_section_overlap') && $ei->courseSectionId === $ej->courseSectionId) {
                    $this->addViolation($state, 'no_section_overlap', "Section overlap between event indexes {$i} and {$j}", [$i, $j]);
                }
            }
        }

        foreach ($state['event_indexes'] as $key => $indexMap) {
            $state['event_indexes'][$key] = array_map('intval', array_keys($indexMap));
        }

        return $state;
    }

    /**
     * @param  array<int, Placement>  $assignments
     * @param  array<int, PlacementEvent>  $events
     * @param  array<string, RoomRef>  $roomById
     */
    public function countViolations(
        array $assignments,
        array $events,
        ScheduleSettings $settings,
        array $roomById,
        SchedulingContext $ctx,
    ): int {
        return $this->evaluate($assignments, $events, $settings, $roomById, $ctx)['count'];
    }

    /**
     * @param  array<int, Placement>  $assignments
     * @param  array<int, PlacementEvent>  $events
     * @param  array<string, RoomRef>  $roomById
     */
    public function violatesForCandidate(
        PlacementEvent $event,
        Placement $candidate,
        array $assignments,
        array $events,
        ScheduleSettings $settings,
        array $roomById,
        SchedulingContext $ctx,
    ): bool {
        $tmp = $assignments;
        $tmp[$event->index] = $candidate;

        return $this->countViolations($tmp, $events, $settings, $roomById, $ctx) > 0;
    }

    private function cellKey(Placement $placement): string
    {
        return $placement->day.'|'.$placement->startMin.'|'.$placement->endMin;
    }

    private function placementInsideInstructorAvailability(string $instructorId, Placement $placement, SchedulingContext $ctx): bool
    {
        $preferred = $ctx->instructorPreferredAvailabilityByInstructorId[$instructorId] ?? [];
        $unavailable = $ctx->instructorUnavailableAvailabilityByInstructorId[$instructorId] ?? [];

        foreach ($unavailable as $window) {
            if ($window['day'] !== $placement->day) {
                continue;
            }
            if (TimeUtil::intervalsOverlap($placement->startMin, $placement->endMin, $window['startMin'], $window['endMin'])) {
                return false;
            }
        }

        if ($preferred === []) {
            return true;
        }

        foreach ($preferred as $window) {
            if ($window['day'] !== $placement->day) {
                continue;
            }
            if ($window['startMin'] <= $placement->startMin && $window['endMin'] >= $placement->endMin) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{count:int,by_constraint:array<string,int>,by_event:array<int,int>,event_indexes:array<string,list<int>>,errors:list<string>}  $state
     * @param  list<int>  $eventIndexes
     */
    private function addViolation(array &$state, string $key, string $message, array $eventIndexes = []): void
    {
        $state['count']++;
        $state['by_constraint'][$key] = ($state['by_constraint'][$key] ?? 0) + 1;
        foreach ($eventIndexes as $idx) {
            $state['by_event'][$idx] = ($state['by_event'][$idx] ?? 0) + 1;
            $state['event_indexes'][$key][$idx] = true;
        }
        if (count($state['errors']) < self::MAX_ERROR_MESSAGES) {
            $state['errors'][] = $message;
        }
    }
}
