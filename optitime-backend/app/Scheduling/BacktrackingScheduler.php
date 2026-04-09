<?php

namespace App\Scheduling;





final class BacktrackingScheduler
{
    private int $backtracks = 0;

    private int $recursiveSteps = 0;

    
    private array $dayOrder;

    private float $deadline;

    private ConstraintEvaluator $evaluator;

    





    public function __construct(
        private ScheduleSettings $settings,
        private array $events,
        private array $gridCells,
        private array $rooms,
        private array $roomById,
        private SchedulingContext $ctx,
        private int $maxBacktracks,
        private float $maxSeconds,
        private int $maxRecursiveSteps,
    ) {
        $this->evaluator = new ConstraintEvaluator;
        $this->dayOrder = $settings->enabledStudyDayKeys();
        $this->deadline = microtime(true) + $maxSeconds;
    }

    


    public function solve(): array
    {
        $this->backtracks = 0;
        $this->recursiveSteps = 0;
        $assignments = [];

        foreach ($this->events as $ev) {
            if ($ev->isFixed() && $ev->fixed !== null) {
                $assignments[$ev->index] = $ev->fixed;
            }
        }

        if ($this->evaluator->countHardViolations($assignments, $this->events, $this->settings, $this->roomById, $this->ctx) > 0) {
            return ['ok' => false, 'reason' => 'baseDraft fixed sessions conflict with hard constraints'];
        }

        $impossible = $this->crudeBoundsFail();
        if ($impossible !== null) {
            return ['ok' => false, 'reason' => $impossible];
        }

        $result = $this->search($assignments);
        if ($result !== null) {
            $improved = $this->localSoftImprove($result);

            return ['ok' => true, 'assignments' => $improved];
        }

        return [
            'ok' => false,
            'reason' => 'no feasible schedule found',
        ];
    }

    public function getBacktracks(): int
    {
        return $this->backtracks;
    }

    public function getRecursiveSteps(): int
    {
        return $this->recursiveSteps;
    }

    



    private function search(array $assignments): ?array
    {
        $this->recursiveSteps++;
        if (microtime(true) > $this->deadline) {
            return null;
        }
        if ($this->backtracks > $this->maxBacktracks) {
            return null;
        }
        if ($this->recursiveSteps > $this->maxRecursiveSteps) {
            return null;
        }

        $next = $this->pickMcvIndex($assignments);
        if ($next === null) {
            return $assignments;
        }

        $candidates = $this->feasibleCandidates($this->events[$next], $assignments);
        foreach ($candidates as $cand) {
            $assignments[$next] = $cand;
            if (! $this->forwardCheckingFails($assignments)) {
                $sub = $this->search($assignments);
                if ($sub !== null) {
                    return $sub;
                }
            }
            unset($assignments[$next]);
            $this->backtracks++;
        }

        return null;
    }

    


    private function forwardCheckingFails(array $assignments): bool
    {
        foreach ($this->events as $ev) {
            if (isset($assignments[$ev->index]) || $ev->isFixed()) {
                continue;
            }
            if (count($this->feasibleCandidates($ev, $assignments)) === 0) {
                return true;
            }
        }

        return false;
    }

    


    private function pickMcvIndex(array $assignments): ?int
    {
        $best = null;
        $bestCount = PHP_INT_MAX;
        foreach ($this->events as $ev) {
            if (isset($assignments[$ev->index])) {
                continue;
            }
            $cnt = count($this->feasibleCandidates($ev, $assignments));
            if ($cnt < $bestCount) {
                $bestCount = $cnt;
                $best = $ev->index;
            }
        }

        return $best;
    }

    



    private function feasibleCandidates(PlacementEvent $event, array $assignments): array
    {
        $out = [];
        foreach ($this->sortedCandidates($event, $assignments) as $cand) {
            if ($this->evaluator->violatesHardForCandidate(
                $event,
                $cand,
                $this->roomById[$cand->roomId],
                $assignments,
                $this->events,
                $this->settings,
                $this->roomById,
                $this->ctx,
            )) {
                continue;
            }
            $out[] = $cand;
        }

        usort($out, function (Placement $a, Placement $b) use ($event) {
            $sa = $this->evaluator->candidateValueOrderingScore($event, $a, $this->settings, $this->gridCells);
            $sb = $this->evaluator->candidateValueOrderingScore($event, $b, $this->settings, $this->gridCells);

            return $sa <=> $sb;
        });

        return $out;
    }

    



    private function sortedCandidates(PlacementEvent $event, array $assignments): array
    {
        $list = [];
        foreach ($this->gridCells as $cell) {
            foreach ($this->rooms as $room) {
                if (! $this->settings->isRoomAllowedForCourse($event->courseId, $room->id)) {
                    continue;
                }
                if ($this->settings->isHardEnabled('room_capacity') && $event->enrollment > $room->capacity) {
                    continue;
                }
                if ($this->settings->isHardEnabled('lab_for_lab') && $event->requiresLab && ! $room->isLab) {
                    continue;
                }
                $p = Placement::fromGridCell($cell, $room->id);
                $list[] = $p;
            }
        }

        usort($list, function (Placement $a, Placement $b) {
            $da = array_search($a->day, $this->dayOrder, true);
            $db = array_search($b->day, $this->dayOrder, true);
            $da = $da === false ? 99 : $da;
            $db = $db === false ? 99 : $db;
            if ($da !== $db) {
                return $da <=> $db;
            }
            if ($a->startMin !== $b->startMin) {
                return $a->startMin <=> $b->startMin;
            }

            return $a->roomId <=> $b->roomId;
        });

        return $list;
    }

    private function crudeBoundsFail(): ?string
    {
        $unplaced = 0;
        foreach ($this->events as $ev) {
            if (! $ev->isFixed()) {
                $unplaced++;
            }
        }
        $cells = count($this->gridCells);
        $roomCount = count($this->rooms);
        $slots = $cells * max(1, $roomCount);
        if ($unplaced > $slots) {
            return 'required slots exceed legal cells × rooms (crude bound)';
        }

        return null;
    }

    



    private function localSoftImprove(array $assignments): array
    {
        $best = $assignments;
        $bestSoft = $this->evaluator->softPenalty($best, $this->events, $this->settings, $this->roomById, $this->gridCells, $this->ctx);
        $changed = true;
        $guard = 0;
        while ($changed && $guard < 80) {
            $changed = false;
            $guard++;
            foreach ($this->events as $ev) {
                if ($ev->isFixed()) {
                    continue;
                }
                $orig = $best[$ev->index] ?? null;
                if ($orig === null) {
                    continue;
                }
                foreach ($this->feasibleCandidates($ev, $best) as $cand) {
                    if ($cand->day === $orig->day && $cand->startMin === $orig->startMin && $cand->roomId === $orig->roomId) {
                        continue;
                    }
                    $trial = $best;
                    $trial[$ev->index] = $cand;
                    if ($this->evaluator->countHardViolations($trial, $this->events, $this->settings, $this->roomById, $this->ctx) > 0) {
                        continue;
                    }
                    $soft = $this->evaluator->softPenalty($trial, $this->events, $this->settings, $this->roomById, $this->gridCells, $this->ctx);
                    if ($soft < $bestSoft - 0.001) {
                        $best = $trial;
                        $bestSoft = $soft;
                        $changed = true;

                        break 2;
                    }
                }
            }
        }

        return $best;
    }
}
