<?php

namespace App\Scheduling;




final class ConstraintEvaluator
{
    private function cellKey(Placement $p): string
    {
        return $p->day.'|'.$p->startMin.'|'.$p->endMin;
    }

    




    public function violatesHardForCandidate(
        PlacementEvent $event,
        Placement $candidate,
        RoomRef $room,
        array $assignments,
        array $events,
        ScheduleSettings $settings,
        array $roomById,
        SchedulingContext $ctx,
    ): bool {
        $tmp = $assignments;
        $tmp[$event->index] = $candidate;

        return $this->countHardViolations($tmp, $events, $settings, $roomById, $ctx) > 0;
    }

    




    public function countHardViolations(
        array $assignments,
        array $events,
        ScheduleSettings $settings,
        array $roomById,
        SchedulingContext $ctx,
    ): int {
        $violations = 0;
        $byIndex = $assignments;
        $n = count($events);

        for ($i = 0; $i < $n; $i++) {
            if (! isset($byIndex[$i])) {
                continue;
            }
            $pi = $byIndex[$i];
            $ei = $events[$i];
            $ri = $roomById[$pi->roomId] ?? null;
            if ($ri === null) {
                $violations++;

                continue;
            }
            if ($settings->isHardEnabled('working_hours') && $ctx->legalCellKeys !== []) {
                if (! isset($ctx->legalCellKeys[$this->cellKey($pi)])) {
                    $violations++;
                }
            }
            if ($settings->isHardEnabled('instructor_availability')) {
                if (! $this->placementInsideInstructorAvailability($ei->instructorId, $pi, $ctx)) {
                    $violations++;
                }
            }
            if ($settings->isHardEnabled('room_capacity') && $ei->enrollment > $ri->capacity) {
                $violations++;
            }
            if ($settings->isCapacityThresholdHard() && $ri->capacity > 0) {
                if (($ei->enrollment / $ri->capacity) > $settings->capacityThreshold()) {
                    $violations++;
                }
            }
            if ($settings->isHardEnabled('lab_for_lab') && $ei->requiresLab && ! $ri->isLab) {
                $violations++;
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
                if ($settings->isHardEnabled('no_instructor_overlap')
                    && $ei->instructorId === $ej->instructorId
                    && TimeUtil::intervalsOverlap($pi->startMin, $pi->endMin, $pj->startMin, $pj->endMin)) {
                    $violations++;
                }
                if ($settings->isHardEnabled('no_room_overlap')
                    && $pi->roomId === $pj->roomId
                    && TimeUtil::intervalsOverlap($pi->startMin, $pi->endMin, $pj->startMin, $pj->endMin)) {
                    $violations++;
                }
            }
        }

        return $violations;
    }

    


    public function candidateValueOrderingScore(
        PlacementEvent $event,
        Placement $p,
        ScheduleSettings $settings,
        array $gridCells,
    ): float {
        $score = 0.0;
        $slotIndexByDayStart = $this->slotRankPerDay($gridCells);
        $softM = $settings->getSoft('morning_preference');
        if ($softM['enabled']) {
            $key = $p->day.'|'.$p->startMin;
            $rank = $slotIndexByDayStart[$key] ?? 0;
            $maxRank = $this->maxRankOnDay($gridCells, $p->day);
            $norm = $maxRank > 0 ? $rank / $maxRank : 0;
            $score += $softM['weight'] * $norm * 10;
        }
        $pref = $settings->getSoft('instructor_preferences');
        if ($pref['enabled']) {
            if ($p->startMin >= 12 * 60) {
                $score += $pref['weight'] * 5;
            }
        }

        return $score;
    }

    





    public function softPenalty(
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

        $softM = $settings->getSoft('morning_preference');
        if ($softM['enabled']) {
            foreach ($assignments as $i => $p) {
                $key = $p->day.'|'.$p->startMin;
                $rank = $slotIndexByDayStart[$key] ?? 0;
                $maxRank = $this->maxRankOnDay($gridCells, $p->day);
                $norm = $maxRank > 0 ? $rank / $maxRank : 0;
                $penalty += $softM['weight'] * $norm * 10;
            }
        }

        $pref = $settings->getSoft('instructor_preferences');
        if ($pref['enabled']) {
            foreach ($assignments as $i => $p) {
                if ($p->startMin >= 12 * 60) {
                    $penalty += $pref['weight'] * 3;
                }
            }
        }

        $softL = $settings->getSoft('load_balance');
        if ($softL['enabled']) {
            $penalty += $softL['weight'] * $this->loadBalancePenalty($assignments, $events);
        }

        $softMax = $settings->getSoft('max_daily_lectures');
        if ($softMax['enabled']) {
            $penalty += $softMax['weight'] * $this->maxDailyLecturesSoftPenalty(
                $assignments,
                $events,
                $settings->maxDailyLectures()
            );
        }

        $softSg = $settings->getSoft('student_gaps');
        if ($softSg['enabled'] && $ctx->sectionPairsWithSharedStudents !== []) {
            $penalty += $softSg['weight'] * $this->studentGapsPenalty($assignments, $events, $settings, $ctx);
        }

        $softDp = $settings->getSoft('department_proximity');
        if ($softDp['enabled']) {
            $penalty += $softDp['weight'] * $this->departmentProximityPenalty($assignments, $events, $settings);
        }

        $threshold = $settings->capacityThreshold();
        foreach ($assignments as $i => $p) {
            $ei = $events[$i] ?? null;
            $ri = $roomById[$p->roomId] ?? null;
            if ($ei === null || $ri === null || $ri->capacity < 1) {
                continue;
            }
            if (! $settings->isCapacityThresholdHard()) {
                $ratio = $ei->enrollment / $ri->capacity;
                if ($ratio > $threshold) {
                    $penalty += ($ratio - $threshold) * 15;
                }
            }
        }

        return $penalty;
    }

    private function placementInsideInstructorAvailability(string $instructorId, Placement $p, SchedulingContext $ctx): bool
    {
        $windows = $ctx->instructorAvailabilityByInstructorId[$instructorId] ?? [];
        if ($windows === []) {
            return true;
        }
        foreach ($windows as $w) {
            if ($w['day'] !== $p->day) {
                continue;
            }
            if ($w['startMin'] <= $p->startMin && $w['endMin'] >= $p->endMin) {
                return true;
            }
        }

        return false;
    }

    



    private function studentGapsPenalty(
        array $assignments,
        array $events,
        ScheduleSettings $settings,
        SchedulingContext $ctx,
    ): float {
        $step = $settings->slotMinutes() + $settings->gapMinutes();
        $sectionToIndex = [];
        foreach ($events as $ev) {
            $sectionToIndex[$ev->courseSectionId] = $ev->index;
        }
        $pen = 0.0;
        foreach ($ctx->sectionPairsWithSharedStudents as $pair) {
            [$sa, $sb] = $pair;
            $ia = $sectionToIndex[$sa] ?? null;
            $ib = $sectionToIndex[$sb] ?? null;
            if ($ia === null || $ib === null) {
                continue;
            }
            $pa = $assignments[$ia] ?? null;
            $pb = $assignments[$ib] ?? null;
            if ($pa === null || $pb === null) {
                continue;
            }
            if ($pa->day !== $pb->day) {
                continue;
            }
            $d = abs($pa->startMin - $pb->startMin);
            if ($d === $step || $d === 0) {
                $pen += 1.0;
            }
        }

        return $pen;
    }

    



    private function departmentProximityPenalty(
        array $assignments,
        array $events,
        ScheduleSettings $settings,
    ): float {
        $step = $settings->slotMinutes() + $settings->gapMinutes();
        $pen = 0.0;
        $n = count($events);
        for ($i = 0; $i < $n; $i++) {
            $ei = $events[$i];
            $pi = $assignments[$i] ?? null;
            if ($pi === null || $ei->departmentId === null) {
                continue;
            }
            for ($j = $i + 1; $j < $n; $j++) {
                $ej = $events[$j];
                $pj = $assignments[$j] ?? null;
                if ($pj === null || $ej->departmentId === null) {
                    continue;
                }
                if ($ei->instructorId !== $ej->instructorId) {
                    continue;
                }
                if ($ei->departmentId === $ej->departmentId) {
                    continue;
                }
                if ($pi->day !== $pj->day) {
                    continue;
                }
                $d = abs($pi->startMin - $pj->startMin);
                if ($d === $step) {
                    $pen += 1.0;
                }
            }
        }

        return $pen;
    }

    



    private function slotRankPerDay(array $gridCells): array
    {
        $byDay = [];
        foreach ($gridCells as $c) {
            $byDay[$c->day][] = $c->startMin;
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

    
    private function maxRankOnDay(array $gridCells, string $day): int
    {
        $max = 0;
        foreach ($gridCells as $c) {
            if ($c->day === $day) {
                $max++;
            }
        }

        return max(0, $max - 1);
    }

    



    private function backToBackPenalty(array $assignments, array $events, ScheduleSettings $settings): float
    {
        $gap = $settings->gapMinutes();
        $slotLen = $settings->slotMinutes();
        $step = $slotLen + $gap;
        $byInstrDay = [];
        foreach ($assignments as $i => $p) {
            $e = $events[$i];
            $byInstrDay[$e->instructorId][$p->day][] = $p->startMin;
        }
        $pen = 0.0;
        foreach ($byInstrDay as $byDay) {
            foreach ($byDay as $starts) {
                sort($starts);
                $m = count($starts);
                for ($a = 0; $a < $m - 1; $a++) {
                    if ($starts[$a + 1] - $starts[$a] === $step) {
                        $pen += 1.0;
                    }
                }
            }
        }

        return $pen;
    }

    



    private function loadBalancePenalty(array $assignments, array $events): float
    {
        $counts = [];
        foreach ($assignments as $i => $p) {
            $e = $events[$i];
            $counts[$e->instructorId] = ($counts[$e->instructorId] ?? 0) + 1;
        }
        if (count($counts) < 2) {
            return 0.0;
        }
        $vals = array_values($counts);
        $mean = array_sum($vals) / count($vals);
        $var = 0.0;
        foreach ($vals as $v) {
            $var += ($v - $mean) ** 2;
        }
        $var /= count($vals);

        return sqrt($var);
    }

    



    private function maxDailyLecturesSoftPenalty(array $assignments, array $events, int $maxPerDay): float
    {
        $byInstrDay = [];
        foreach ($assignments as $i => $p) {
            $e = $events[$i];
            $byInstrDay[$e->instructorId][$p->day] = ($byInstrDay[$e->instructorId][$p->day] ?? 0) + 1;
        }
        $pen = 0.0;
        foreach ($byInstrDay as $byDay) {
            foreach ($byDay as $cnt) {
                if ($cnt > $maxPerDay) {
                    $pen += ($cnt - $maxPerDay);
                }
            }
        }

        return $pen;
    }
}
