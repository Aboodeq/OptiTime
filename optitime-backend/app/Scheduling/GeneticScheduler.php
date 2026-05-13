<?php

namespace App\Scheduling;

final class GeneticScheduler
{
    private const DEFAULT_PARENT_CONFLICT_FOCUS_PROBABILITY = 0.65;

    private const DEFAULT_PARENT_CONFLICT_FOCUS_LIMIT = 32;

    private const DEFAULT_LARGE_EVENT_THRESHOLD = 120;

    private const DEFAULT_VERY_LARGE_EVENT_THRESHOLD = 180;

    private const DEFAULT_EARLY_STOP_STAGNATION_PATIENCE = 4;

    private const DEFAULT_EARLY_STOP_MIN_GENERATIONS = 4;

    private const DEFAULT_EARLY_STOP_LOW_GAIN_PATIENCE = 6;

    private HardConstraintValidator $hardValidator;

    private SoftConstraintScorer $softScorer;

    private SeededRandom $rng;

    /** @var array<int, GridCell> */
    private array $cells;

    /** @var array<int, RoomRef> */
    private array $rooms;

    /** @var array<int, list<Placement>> */
    private array $candidatePoolByEvent = [];

    public function __construct(
        private ScheduleSettings $settings,
        private array $events,
        array $gridCells,
        array $rooms,
        private array $roomById,
        private SchedulingContext $ctx,
        private int $populationSize,
        private int $maxGenerations,
        private float $maxSeconds,
        private float $crossoverRate,
        private float $mutationRate,
        private int $elitism,
        ?int $seed,
    ) {
        $this->hardValidator = new HardConstraintValidator;
        $this->softScorer = new SoftConstraintScorer;
        $this->rng = new SeededRandom($seed);
        $this->cells = $gridCells;
        $this->rooms = $rooms;
        foreach ($this->events as $event) {
            $this->candidatePoolByEvent[$event->index] = $this->buildCandidatePool($event);
        }
    }

    public function solve(): array
    {
        $deadline = microtime(true) + $this->maxSeconds;
        $eventCount = count($this->events);
        $effectivePopulation = $this->effectivePopulation($eventCount);
        $effectiveGenerations = $this->effectiveGenerations($eventCount);
        $stagnationPatience = $this->stagnationPatience($eventCount);
        $minGenerationsBeforeStop = min(
            $effectiveGenerations,
            max(self::DEFAULT_EARLY_STOP_MIN_GENERATIONS, intdiv($effectiveGenerations, 10))
        );
        $population = [];
        for ($i = 0; $i < $effectivePopulation; $i++) {
            $population[] = $this->repair($this->randomIndividual());
        }

        $best = $population[0];
        $bestFitness = -INF;
        $bestHard = PHP_INT_MAX;
        $bestSoft = INF;
        $bestHardBreakdown = [];
        $bestHardErrors = [];
        $gen = 0;
        $stagnation = 0;
        $lowGainStagnation = 0;
        $terminationReason = null;

        while ($gen < $effectiveGenerations && microtime(true) < $deadline) {
            $scored = [];
            $improvedInGeneration = false;
            $materialSoftImprovementInGeneration = false;
            foreach ($population as $ind) {
                $hardEval = $this->hardValidator->evaluate($ind, $this->events, $this->settings, $this->roomById, $this->ctx);
                $hard = $hardEval['count'];
                $soft = $this->softScorer->penalty($ind, $this->events, $this->settings, $this->roomById, $this->cells, $this->ctx);
                $fit = $this->fitnessFrom($hard, $soft);
                $scored[] = [
                    'd' => $ind,
                    'f' => $fit,
                    'h' => $hard,
                    's' => $soft,
                    'violating_indexes' => $this->trimIndexes(array_map('intval', array_keys($hardEval['by_event'] ?? []))),
                ];

                if ($fit > $bestFitness || ($fit === $bestFitness && $hard < $bestHard)) {
                    $prevBestSoft = $bestSoft;
                    $bestFitness = $fit;
                    $best = $ind;
                    $bestHard = $hard;
                    $bestSoft = $soft;
                    $bestHardBreakdown = $hardEval['by_constraint'];
                    $bestHardErrors = $hardEval['errors'];
                    $improvedInGeneration = true;
                    if (! is_finite($prevBestSoft) || (($prevBestSoft - $soft) > $this->materialSoftGainEpsilon($prevBestSoft))) {
                        $materialSoftImprovementInGeneration = true;
                    }
                }
            }

            usort($scored, fn ($a, $b) => $b['f'] <=> $a['f']);

            $stagnation = $improvedInGeneration ? 0 : ($stagnation + 1);
            $lowGainStagnation = $materialSoftImprovementInGeneration ? 0 : ($lowGainStagnation + 1);
            if ($bestHard === 0 && ($gen + 1) >= $minGenerationsBeforeStop) {
                if ($stagnation >= $stagnationPatience) {
                    $terminationReason = 'converged_stagnation';
                    $gen++;
                    break;
                }
                if ($lowGainStagnation >= $this->lowGainPatience($eventCount)) {
                    $terminationReason = 'converged_low_gain';
                    $gen++;
                    break;
                }
            }

            $next = [];
            for ($e = 0; $e < $this->elitism && $e < count($scored); $e++) {
                $next[] = $scored[$e]['d'];
            }

            while (count($next) < $effectivePopulation) {
                $p1 = $this->tournamentPick($scored);
                $p2 = $this->tournamentPick($scored);
                $child = $p1['d'];
                if ($this->rng->nextFloat() < $this->crossoverRate) {
                    $child = $this->crossoverPlacements($p1['d'], $p2['d']);
                }

                $focusIndices = [];
                if ($this->rng->nextFloat() < self::DEFAULT_PARENT_CONFLICT_FOCUS_PROBABILITY) {
                    $focusIndices = $this->mergeFocusIndexes($p1['violating_indexes'] ?? [], $p2['violating_indexes'] ?? []);
                }
                $child = $this->mutatePlacements($child, $focusIndices);
                $child = $this->repair($child, $focusIndices);
                $next[] = $child;
            }

            $population = $next;
            $gen++;
        }

        if ($terminationReason === null) {
            $terminationReason = microtime(true) >= $deadline
                ? 'max_seconds_exceeded'
                : ($gen >= $effectiveGenerations ? 'max_generations_reached' : 'completed');
        }

        return [
            'assignments' => $best,
            'fitness' => $bestFitness,
            'hard_violations' => $bestHard,
            'hard_breakdown' => $bestHardBreakdown,
            'hard_errors' => $bestHardErrors,
            'soft_penalty' => $bestSoft,
            'generations' => $gen,
            'termination_reason' => $terminationReason,
            'effective_population' => $effectivePopulation,
            'effective_generations' => $effectiveGenerations,
            'stagnation_generations' => $stagnation,
            'low_gain_stagnation_generations' => $lowGainStagnation,
        ];
    }

    private function fitnessFrom(int $hard, float $soft): float
    {
        return 1_000_000.0 - ($hard * 1_000_000.0) - $soft;
    }

    private function randomIndividual(): array
    {
        $out = [];
        foreach ($this->events as $event) {
            if ($event->isFixed() && $event->fixed !== null) {
                $out[$event->index] = $event->fixed;
                continue;
            }
            $out[$event->index] = $this->randomLegalPlacement($event);
        }

        return $out;
    }

    private function randomLegalPlacement(PlacementEvent $event): Placement
    {
        $pool = $this->candidatePoolByEvent[$event->index] ?? [];
        if ($pool !== []) {
            return $pool[$this->rng->nextInt(0, count($pool) - 1)];
        }

        return $this->bestEffortPlacement($event);
    }

    private function bestEffortPlacement(PlacementEvent $event): Placement
    {
        $cell = $this->cells[0];

        $bestRoom = null;
        $bestCap = -1;
        foreach ($this->rooms as $room) {
            if (! $this->settings->isRoomAllowedForCourse($event->courseId, $room->id)) {
                continue;
            }
            if ($this->settings->isHardEnabled('room_status_available') && strtolower($room->status) !== 'available') {
                continue;
            }
            if ($this->settings->isHardEnabled('room_capacity') && $event->enrollment > $room->capacity) {
                continue;
            }
            if ($this->settings->isHardEnabled('lab_for_lab') && $event->requiresLab && ! $room->isLab) {
                continue;
            }
            if ($room->capacity > $bestCap) {
                $bestCap = $room->capacity;
                $bestRoom = $room;
            }
        }
        if ($bestRoom !== null) {
            return Placement::fromGridCell($cell, $bestRoom->id);
        }

        return Placement::fromGridCell($cell, $this->rooms[0]->id);
    }

    /**
     * @param  list<int>  $priorityIndexes
     */
    private function repair(array $assignments, array $priorityIndexes = []): array
    {
        $eventCount = count($this->events);
        $maxIter = max(24, min(180, (int) floor($eventCount * 1.4)));
        $priority = $this->trimIndexes(array_map('intval', $priorityIndexes));
        $stuck = 0;
        $lastHard = null;

        for ($iter = 0; $iter < $maxIter; $iter++) {
            $hardEval = $this->hardValidator->evaluate($assignments, $this->events, $this->settings, $this->roomById, $this->ctx);
            $hardCount = (int) ($hardEval['count'] ?? 0);
            if ($hardCount === 0) {
                break;
            }

            $violatingIndexes = array_map('intval', array_keys($hardEval['by_event'] ?? []));
            $focusPool = $this->mergeFocusIndexes($priority, $violatingIndexes);

            if ($violatingIndexes !== []) {
                $candidatePool = $focusPool !== [] ? $focusPool : $violatingIndexes;
                $pick = $candidatePool[$this->rng->nextInt(0, count($candidatePool) - 1)];
            } else {
                $pick = $this->rng->nextInt(0, max(0, count($this->events) - 1));
            }

            $event = $this->events[$pick] ?? null;
            if ($event === null || $event->isFixed()) {
                continue;
            }

            $assignments[$pick] = $this->randomLegalPlacement($event);

            if ($lastHard !== null && $hardCount >= $lastHard) {
                $stuck++;
                if ($stuck >= 12) {
                    break;
                }
            } else {
                $stuck = 0;
            }
            $lastHard = $hardCount;
        }

        return $assignments;
    }

    private function crossoverPlacements(array $a, array $b): array
    {
        $child = [];
        foreach ($this->events as $event) {
            $i = $event->index;
            if ($event->isFixed() && $event->fixed !== null) {
                $child[$i] = $event->fixed;
                continue;
            }
            $child[$i] = ($this->rng->nextFloat() < 0.5) ? ($a[$i] ?? $b[$i]) : ($b[$i] ?? $a[$i]);
        }

        return $child;
    }

    /**
     * @param  list<int>  $focusIndexes
     */
    private function mutatePlacements(array $ind, array $focusIndexes = []): array
    {
        $focusSet = [];
        foreach ($focusIndexes as $idx) {
            $focusSet[(int) $idx] = true;
        }

        $eventCount = count($this->events);
        $effectiveMutationRate = $eventCount > 180 ? min(0.08, $this->mutationRate * 1.8) : $this->mutationRate;
        foreach ($this->events as $event) {
            if ($event->isFixed()) {
                continue;
            }

            $prob = $effectiveMutationRate;
            if (isset($focusSet[$event->index])) {
                $prob = max($prob, min(0.75, ($effectiveMutationRate * 4) + 0.08));
            }

            if ($this->rng->nextFloat() < $prob) {
                $ind[$event->index] = $this->randomLegalPlacement($event);
            }
        }

        return $ind;
    }

    private function tournamentPick(array $scored): array
    {
        $best = $scored[0];
        $bestF = $scored[0]['f'];
        $maxIdx = max(0, count($scored) - 1);
        for ($t = 0; $t < 3; $t++) {
            $pick = $scored[$this->rng->nextInt(0, $maxIdx)];
            if ($pick['f'] > $bestF) {
                $bestF = $pick['f'];
                $best = $pick;
            }
        }

        return $best;
    }

    private function buildCandidatePool(PlacementEvent $event): array
    {
        $pool = [];
        foreach ($this->cells as $cell) {
            foreach ($this->rooms as $room) {
                if (! $this->settings->isRoomAllowedForCourse($event->courseId, $room->id)) {
                    continue;
                }
                if ($this->settings->isHardEnabled('room_status_available') && strtolower($room->status) !== 'available') {
                    continue;
                }
                if ($this->settings->isHardEnabled('room_capacity') && $event->enrollment > $room->capacity) {
                    continue;
                }
                if ($this->settings->isCapacityThresholdHard() && $room->capacity > 0) {
                    $ratio = $event->enrollment / $room->capacity;
                    if ($ratio > $this->settings->capacityThreshold()) {
                        continue;
                    }
                }
                if ($this->settings->isHardEnabled('lab_for_lab') && $event->requiresLab && ! $room->isLab) {
                    continue;
                }
                $pool[] = Placement::fromGridCell($cell, $room->id);
            }
        }

        return $pool;
    }

    private function effectivePopulation(int $eventCount): int
    {
        $cap = $this->populationSize;
        if ($eventCount > self::DEFAULT_VERY_LARGE_EVENT_THRESHOLD) {
            $cap = min($cap, 32);
        } elseif ($eventCount > self::DEFAULT_LARGE_EVENT_THRESHOLD) {
            $cap = min($cap, 36);
        } else {
            $cap = min($cap, 48);
        }

        return max(20, $cap);
    }

    private function effectiveGenerations(int $eventCount): int
    {
        $cap = $this->maxGenerations;
        if ($eventCount > self::DEFAULT_VERY_LARGE_EVENT_THRESHOLD) {
            $cap = min($cap, 110);
        } elseif ($eventCount > self::DEFAULT_LARGE_EVENT_THRESHOLD) {
            $cap = min($cap, 120);
        }

        return max(24, $cap);
    }

    private function stagnationPatience(int $eventCount): int
    {
        if ($eventCount > self::DEFAULT_VERY_LARGE_EVENT_THRESHOLD) {
            return 3;
        }

        return self::DEFAULT_EARLY_STOP_STAGNATION_PATIENCE;
    }

    private function lowGainPatience(int $eventCount): int
    {
        if ($eventCount > self::DEFAULT_VERY_LARGE_EVENT_THRESHOLD) {
            return 5;
        }

        return self::DEFAULT_EARLY_STOP_LOW_GAIN_PATIENCE;
    }

    private function materialSoftGainEpsilon(float $softPenalty): float
    {
        return max(0.5, abs($softPenalty) * 0.0025);
    }

    /**
     * @param  list<int>  $left
     * @param  list<int>  $right
     * @return list<int>
     */
    private function mergeFocusIndexes(array $left, array $right): array
    {
        $merged = [];
        foreach ($left as $idx) {
            $merged[(int) $idx] = true;
        }
        foreach ($right as $idx) {
            $merged[(int) $idx] = true;
        }

        return $this->trimIndexes(array_map('intval', array_keys($merged)));
    }

    /**
     * @param  list<int>  $indexes
     * @return list<int>
     */
    private function trimIndexes(array $indexes): array
    {
        if (count($indexes) <= self::DEFAULT_PARENT_CONFLICT_FOCUS_LIMIT) {
            return array_values($indexes);
        }

        return array_slice(array_values($indexes), 0, self::DEFAULT_PARENT_CONFLICT_FOCUS_LIMIT);
    }
}
