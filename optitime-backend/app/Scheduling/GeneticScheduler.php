<?php

namespace App\Scheduling;





final class GeneticScheduler
{
    private ConstraintEvaluator $evaluator;

    private SeededRandom $rng;

    
    private array $cells;

    
    private array $rooms;

    

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
        $this->evaluator = new ConstraintEvaluator;
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
        $effectivePopulation = max(20, min($this->populationSize, $eventCount > 180 ? 40 : $this->populationSize));
        $effectiveGenerations = max(40, min($this->maxGenerations, $eventCount > 180 ? 120 : $this->maxGenerations));
        $population = [];
        for ($i = 0; $i < $effectivePopulation; $i++) {
            $population[] = $this->repair($this->randomIndividual());
        }

        $best = $population[0];
        $bestFitness = -INF;
        $bestHard = PHP_INT_MAX;
        $bestSoft = INF;
        $gen = 0;

        while ($gen < $effectiveGenerations && microtime(true) < $deadline) {
            $scored = [];
            foreach ($population as $ind) {
                $hard = $this->evaluator->countHardViolations($ind, $this->events, $this->settings, $this->roomById, $this->ctx);
                $soft = $this->evaluator->softPenalty($ind, $this->events, $this->settings, $this->roomById, $this->cells, $this->ctx);
                $fit = $this->fitnessFrom($hard, $soft);
                $scored[] = ['d' => $ind, 'f' => $fit, 'h' => $hard, 's' => $soft];
                if ($fit > $bestFitness || ($fit === $bestFitness && $hard < $bestHard)) {
                    $bestFitness = $fit;
                    $best = $ind;
                    $bestHard = $hard;
                    $bestSoft = $soft;
                }
            }

            usort($scored, fn ($a, $b) => $b['f'] <=> $a['f']);

            $next = [];
            for ($e = 0; $e < $this->elitism && $e < count($scored); $e++) {
                $next[] = $scored[$e]['d'];
            }

            while (count($next) < $effectivePopulation) {
                $p1 = $this->tournamentPick($scored);
                $p2 = $this->tournamentPick($scored);
                $child = $p1;
                if ($this->rng->nextFloat() < $this->crossoverRate) {
                    $child = $this->crossoverPlacements($p1, $p2);
                }
                $child = $this->mutatePlacements($child);
                $child = $this->repair($child);
                $next[] = $child;
            }
            $population = $next;
            $gen++;
        }

        return [
            'assignments' => $best,
            'fitness' => $bestFitness,
            'hard_violations' => $bestHard,
            'soft_penalty' => $bestSoft,
            'generations' => $gen,
        ];
    }

    private function fitnessFrom(int $hard, float $soft): float
    {
        return 1_000_000.0 - ($hard * 1_000_000.0) - $soft;
    }

    


    private function randomIndividual(): array
    {
        $out = [];
        foreach ($this->events as $ev) {
            if ($ev->isFixed() && $ev->fixed !== null) {
                $out[$ev->index] = $ev->fixed;

                continue;
            }
            $p = $this->randomLegalPlacement($ev);
            $out[$ev->index] = $p;
        }

        return $out;
    }

    private function randomLegalPlacement(PlacementEvent $ev): Placement
    {
        $pool = $this->candidatePoolByEvent[$ev->index] ?? [];
        if ($pool !== []) {
            return $pool[$this->rng->nextInt(0, count($pool) - 1)];
        }

        $cell = $this->cells[0];
        $room = $this->rooms[0];

        return Placement::fromGridCell($cell, $room->id);
    }

    



    private function repair(array $assignments): array
    {
        $maxIter = max(60, count($this->events) * 6);
        for ($iter = 0; $iter < $maxIter; $iter++) {
            $hard = $this->evaluator->countHardViolations($assignments, $this->events, $this->settings, $this->roomById, $this->ctx);
            if ($hard === 0) {
                break;
            }
            $i = $this->rng->nextInt(0, max(0, count($this->events) - 1));
            $ev = $this->events[$i];
            if ($ev->isFixed()) {
                continue;
            }
            $assignments[$i] = $this->randomLegalPlacement($ev);
        }

        return $assignments;
    }

    




    private function crossoverPlacements(array $a, array $b): array
    {
        $child = [];
        foreach ($this->events as $ev) {
            $i = $ev->index;
            if ($ev->isFixed() && $ev->fixed !== null) {
                $child[$i] = $ev->fixed;

                continue;
            }
            $child[$i] = ($this->rng->nextFloat() < 0.5) ? ($a[$i] ?? $b[$i]) : ($b[$i] ?? $a[$i]);
        }

        return $child;
    }

    



    private function mutatePlacements(array $ind): array
    {
        $eventCount = count($this->events);
        $effectiveMutationRate = $eventCount > 180 ? min(0.08, $this->mutationRate * 1.8) : $this->mutationRate;
        foreach ($this->events as $ev) {
            if ($ev->isFixed()) {
                continue;
            }
            if ($this->rng->nextFloat() < $effectiveMutationRate) {
                $ind[$ev->index] = $this->randomLegalPlacement($ev);
            }
        }

        return $ind;
    }

    



    private function tournamentPick(array $scored): array
    {
        $best = $scored[0]['d'];
        $bestF = $scored[0]['f'];
        $maxIdx = max(0, count($scored) - 1);
        for ($t = 0; $t < 3; $t++) {
            $pick = $scored[$this->rng->nextInt(0, $maxIdx)];
            if ($pick['f'] > $bestF) {
                $bestF = $pick['f'];
                $best = $pick['d'];
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
                if ($this->settings->isHardEnabled('room_capacity') && $event->enrollment > $room->capacity) {
                    continue;
                }
                if ($this->settings->isHardEnabled('lab_for_lab') && $event->requiresLab && ! $room->isLab) {
                    continue;
                }
                $pool[] = Placement::fromGridCell($cell, $room->id);
            }
        }

        return $pool;
    }
}
