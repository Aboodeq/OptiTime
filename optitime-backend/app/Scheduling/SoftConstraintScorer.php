<?php

namespace App\Scheduling;

final class SoftConstraintScorer
{
    /**
     * @param  array<int, Placement>  $assignments
     * @param  array<int, PlacementEvent>  $events
     * @param  array<string, RoomRef>  $roomById
     * @param  array<int, GridCell>  $gridCells
     */
    public function penalty(
        array $assignments,
        array $events,
        ScheduleSettings $settings,
        array $roomById,
        array $gridCells,
        SchedulingContext $ctx,
    ): float {
        $penalty = 0.0;
        $slotIndexByDayStart = $this->slotRankPerDay($gridCells);

        $soft = $settings->getSoft('avoid_back_to_back');
        if ($soft['enabled']) {
            $penalty += $soft['weight'] * $this->backToBackPenalty($assignments, $events, $settings);
        }

        $softMorning = $settings->getSoft('morning_preference');
        if ($softMorning['enabled']) {
            foreach ($assignments as $placement) {
                $key = $placement->day.'|'.$placement->startMin;
                $rank = $slotIndexByDayStart[$key] ?? 0;
                $maxRank = $this->maxRankOnDay($gridCells, $placement->day);
                $norm = $maxRank > 0 ? $rank / $maxRank : 0;
                $penalty += $softMorning['weight'] * $norm * 10;
            }
        }

        $pref = $settings->getSoft('instructor_preferences');
        if ($pref['enabled']) {
            foreach ($assignments as $i => $placement) {
                $event = $events[$i] ?? null;
                if ($event === null) {
                    continue;
                }
                $instructorId = $event->instructorId;
                $preferredWindows = $ctx->instructorPreferredAvailabilityByInstructorId[$instructorId] ?? [];
                $unavailableWindows = $ctx->instructorUnavailableAvailabilityByInstructorId[$instructorId] ?? [];

                if ($preferredWindows !== [] && ! $this->placementInsideAnyWindow($placement, $preferredWindows)) {
                    $penalty += $pref['weight'] * 8;
                }
                if ($this->placementOverlapsAnyWindow($placement, $unavailableWindows)) {
                    $penalty += $pref['weight'] * 15;
                }
                if ($preferredWindows === [] && $placement->startMin >= 12 * 60) {
                    $penalty += $pref['weight'] * 2;
                }
            }
        }

        $softLoad = $settings->getSoft('load_balance');
        if ($softLoad['enabled']) {
            $penalty += $softLoad['weight'] * $this->loadBalancePenalty($assignments, $events);
        }

        $softMaxDaily = $settings->getSoft('max_daily_lectures');
        if ($softMaxDaily['enabled']) {
            $penalty += $softMaxDaily['weight'] * $this->maxDailyLecturesSoftPenalty(
                $assignments,
                $events,
                $settings->maxDailyLectures()
            );
        }

        $softStudentGaps = $settings->getSoft('student_gaps');
        if ($softStudentGaps['enabled'] && $ctx->sectionPairsWithSharedStudents !== []) {
            $penalty += $softStudentGaps['weight'] * $this->studentGapsPenalty($assignments, $events, $settings, $ctx);
        }

        $softDeptProximity = $settings->getSoft('department_proximity');
        if ($softDeptProximity['enabled']) {
            $penalty += $softDeptProximity['weight'] * $this->departmentProximityPenalty($assignments, $events, $settings);
        }

        $threshold = $settings->capacityThreshold();
        foreach ($assignments as $i => $placement) {
            $event = $events[$i] ?? null;
            $room = $roomById[$placement->roomId] ?? null;
            if ($event === null || $room === null || $room->capacity < 1) {
                continue;
            }
            if (! $settings->isCapacityThresholdHard()) {
                $ratio = $event->enrollment / $room->capacity;
                if ($ratio > $threshold) {
                    $penalty += ($ratio - $threshold) * 15;
                }
            }
        }

        return $penalty;
    }

    /**
     * Lower score means a better candidate.
     *
     * @param  array<int, GridCell>  $gridCells
     */
    public function candidateValueOrderingScore(
        PlacementEvent $event,
        Placement $placement,
        ScheduleSettings $settings,
        array $gridCells,
        SchedulingContext $ctx,
    ): float {
        $score = 0.0;

        $morning = $settings->getSoft('morning_preference');
        if ($morning['enabled']) {
            $slotIndexByDayStart = $this->slotRankPerDay($gridCells);
            $key = $placement->day.'|'.$placement->startMin;
            $rank = $slotIndexByDayStart[$key] ?? 0;
            $maxRank = $this->maxRankOnDay($gridCells, $placement->day);
            $norm = $maxRank > 0 ? $rank / $maxRank : 0;
            $score += $morning['weight'] * $norm * 10;
        }

        $pref = $settings->getSoft('instructor_preferences');
        if ($pref['enabled']) {
            $preferredWindows = $ctx->instructorPreferredAvailabilityByInstructorId[$event->instructorId] ?? [];
            if ($preferredWindows !== [] && ! $this->placementInsideAnyWindow($placement, $preferredWindows)) {
                $score += $pref['weight'] * 8;
            }
            if ($preferredWindows === [] && $placement->startMin >= 12 * 60) {
                $score += $pref['weight'] * 2;
            }
        }

        return $score;
    }

    /**
     * @param  array<int, Placement>  $assignments
     * @param  array<int, PlacementEvent>  $events
     */
    private function backToBackPenalty(array $assignments, array $events, ScheduleSettings $settings): float
    {
        $gap = $settings->gapMinutes();
        $slotLen = $settings->slotMinutes();
        $step = $slotLen + $gap;
        $byInstructorDay = [];
        foreach ($assignments as $i => $placement) {
            $event = $events[$i] ?? null;
            if ($event === null) {
                continue;
            }
            $byInstructorDay[$event->instructorId][$placement->day][] = $placement->startMin;
        }

        $penalty = 0.0;
        foreach ($byInstructorDay as $byDay) {
            foreach ($byDay as $starts) {
                sort($starts);
                $count = count($starts);
                for ($a = 0; $a < $count - 1; $a++) {
                    if ($starts[$a + 1] - $starts[$a] === $step) {
                        $penalty += 1.0;
                    }
                }
            }
        }

        return $penalty;
    }

    /**
     * @param  array<int, Placement>  $assignments
     * @param  array<int, PlacementEvent>  $events
     */
    private function studentGapsPenalty(
        array $assignments,
        array $events,
        ScheduleSettings $settings,
        SchedulingContext $ctx,
    ): float {
        $step = $settings->slotMinutes() + $settings->gapMinutes();
        $sectionToIndexes = [];
        foreach ($events as $event) {
            $sectionToIndexes[$event->courseSectionId][] = $event->index;
        }

        $penalty = 0.0;
        foreach ($ctx->sectionPairsWithSharedStudents as $pair) {
            [$sectionA, $sectionB] = $pair;
            $indexesA = $sectionToIndexes[$sectionA] ?? [];
            $indexesB = $sectionToIndexes[$sectionB] ?? [];
            if ($indexesA === [] || $indexesB === []) {
                continue;
            }
            foreach ($indexesA as $indexA) {
                $placementA = $assignments[$indexA] ?? null;
                if ($placementA === null) {
                    continue;
                }
                foreach ($indexesB as $indexB) {
                    $placementB = $assignments[$indexB] ?? null;
                    if ($placementB === null || $placementA->day !== $placementB->day) {
                        continue;
                    }

                    if ($placementA->startMin <= $placementB->startMin) {
                        $first = $placementA;
                        $second = $placementB;
                    } else {
                        $first = $placementB;
                        $second = $placementA;
                    }

                    $gap = $second->startMin - $first->endMin;
                    if ($gap < 0) {
                        $penalty += 4.0;
                        continue;
                    }
                    if ($gap === 0) {
                        $penalty += 1.0;
                        continue;
                    }
                    if ($gap === $step) {
                        $penalty += 0.5;
                        continue;
                    }
                    $penalty += min(3.0, $gap / max(1, $step));
                }
            }
        }

        return $penalty;
    }

    /**
     * @param  array<int, Placement>  $assignments
     * @param  array<int, PlacementEvent>  $events
     */
    private function departmentProximityPenalty(
        array $assignments,
        array $events,
        ScheduleSettings $settings,
    ): float {
        $step = $settings->slotMinutes() + $settings->gapMinutes();
        $penalty = 0.0;
        $n = count($events);
        for ($i = 0; $i < $n; $i++) {
            $eventI = $events[$i];
            $placementI = $assignments[$i] ?? null;
            if ($placementI === null || $eventI->departmentId === null) {
                continue;
            }
            for ($j = $i + 1; $j < $n; $j++) {
                $eventJ = $events[$j];
                $placementJ = $assignments[$j] ?? null;
                if ($placementJ === null || $eventJ->departmentId === null) {
                    continue;
                }
                if ($eventI->instructorId !== $eventJ->instructorId) {
                    continue;
                }
                if ($eventI->departmentId === $eventJ->departmentId) {
                    continue;
                }
                if ($placementI->day !== $placementJ->day) {
                    continue;
                }
                $distance = abs($placementI->startMin - $placementJ->startMin);
                if ($distance === $step) {
                    $penalty += 1.0;
                }
            }
        }

        return $penalty;
    }

    /**
     * @param  array<int, Placement>  $assignments
     * @param  array<int, PlacementEvent>  $events
     */
    private function loadBalancePenalty(array $assignments, array $events): float
    {
        $counts = [];
        foreach ($assignments as $i => $placement) {
            $event = $events[$i] ?? null;
            if ($event === null) {
                continue;
            }
            $counts[$event->instructorId] = ($counts[$event->instructorId] ?? 0) + 1;
        }
        if (count($counts) < 2) {
            return 0.0;
        }

        $values = array_values($counts);
        $mean = array_sum($values) / count($values);
        $variance = 0.0;
        foreach ($values as $value) {
            $variance += ($value - $mean) ** 2;
        }
        $variance /= count($values);

        return sqrt($variance);
    }

    /**
     * @param  array<int, Placement>  $assignments
     * @param  array<int, PlacementEvent>  $events
     */
    private function maxDailyLecturesSoftPenalty(array $assignments, array $events, int $maxPerDay): float
    {
        $byInstructorDay = [];
        foreach ($assignments as $i => $placement) {
            $event = $events[$i] ?? null;
            if ($event === null) {
                continue;
            }
            $byInstructorDay[$event->instructorId][$placement->day] = ($byInstructorDay[$event->instructorId][$placement->day] ?? 0) + 1;
        }

        $penalty = 0.0;
        foreach ($byInstructorDay as $byDay) {
            foreach ($byDay as $count) {
                if ($count > $maxPerDay) {
                    $penalty += ($count - $maxPerDay);
                }
            }
        }

        return $penalty;
    }

    /**
     * @param  array<int, GridCell>  $gridCells
     * @return array<string, int>
     */
    private function slotRankPerDay(array $gridCells): array
    {
        $byDay = [];
        foreach ($gridCells as $cell) {
            $byDay[$cell->day][] = $cell->startMin;
        }

        $map = [];
        foreach ($byDay as $day => $starts) {
            $starts = array_values(array_unique($starts));
            sort($starts);
            foreach ($starts as $idx => $start) {
                $map[$day.'|'.$start] = $idx;
            }
        }

        return $map;
    }

    /**
     * @param  array<int, GridCell>  $gridCells
     */
    private function maxRankOnDay(array $gridCells, string $day): int
    {
        $max = 0;
        foreach ($gridCells as $cell) {
            if ($cell->day === $day) {
                $max++;
            }
        }

        return max(0, $max - 1);
    }

    /**
     * @param  list<array{day:string,startMin:int,endMin:int}>  $windows
     */
    private function placementInsideAnyWindow(Placement $placement, array $windows): bool
    {
        foreach ($windows as $window) {
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
     * @param  list<array{day:string,startMin:int,endMin:int}>  $windows
     */
    private function placementOverlapsAnyWindow(Placement $placement, array $windows): bool
    {
        foreach ($windows as $window) {
            if ($window['day'] !== $placement->day) {
                continue;
            }
            if (TimeUtil::intervalsOverlap($placement->startMin, $placement->endMin, $window['startMin'], $window['endMin'])) {
                return true;
            }
        }

        return false;
    }
}
