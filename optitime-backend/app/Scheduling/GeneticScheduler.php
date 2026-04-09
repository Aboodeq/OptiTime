<?php

namespace App\Scheduling;





final class GeneticScheduler
{
    private ConstraintEvaluator $evaluator;

    private SeededRandom $rng;

    
    private array $cells;

    
    private array $rooms;

    





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
    }

    








    public function solve(): array
    {
        $deadline = microtime(true) + $this->maxSeconds;
        $population = [];
        for ($i = 0; $i < $this->populationSize; $i++) {
            $population[] = $this->repair($this->randomIndividual());
        }

        $best = $population[0];
        $bestFitness = -INF;
        $bestHard = PHP_INT_MAX;
        $bestSoft = INF;
        $gen = 0;

        while ($gen < $this->maxGenerations && microtime(true) < $deadline) {
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

            while (count($next) < $this->populationSize) {
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
        $nc = count($this->cells);
        $nr = count($this->rooms);
        $tries = max(20, $nc * $nr);
        for ($t = 0; $t < $tries; $t++) {
            if ($nc < 1 || $nr < 1) {
                break;
            }
            $cell = $this->cells[$this->rng->nextInt(0, $nc - 1)];
            $room = $this->rooms[$this->rng->nextInt(0, $nr - 1)];
            if (! $this->settings->isRoomAllowedForCourse($ev->courseId, $room->id)) {
                continue;
            }
            if ($this->settings->isHardEnabled('room_capacity') && $ev->enrollment > $room->capacity) {
                continue;
            }
            if ($this->settings->isHardEnabled('lab_for_lab') && $ev->requiresLab && ! $room->isLab) {
                continue;
            }

            return Placement::fromGridCell($cell, $room->id);
        }

        $cell = $this->cells[0];
        $room = $this->rooms[0];

        return Placement::fromGridCell($cell, $room->id);
    }

    



    private function repair(array $assignments): array
    {
        $maxIter = max(80, count($this->events) * 12);
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
        foreach ($this->events as $ev) {
            if ($ev->isFixed()) {
                continue;
            }
            if ($this->rng->nextFloat() < $this->mutationRate) {
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
}
