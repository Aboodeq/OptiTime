<?php

namespace App\Scheduling;

final class ConstraintEvaluator
{
    private HardConstraintValidator $hardValidator;

    private SoftConstraintScorer $softScorer;

    public function __construct()
    {
        $this->hardValidator = new HardConstraintValidator;
        $this->softScorer = new SoftConstraintScorer;
    }

    /**
     * @param  array<int, Placement>  $assignments
     * @param  array<int, PlacementEvent>  $events
     * @param  array<string, RoomRef>  $roomById
     */
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
        unset($room);

        return $this->hardValidator->violatesForCandidate(
            $event,
            $candidate,
            $assignments,
            $events,
            $settings,
            $roomById,
            $ctx
        );
    }

    /**
     * @param  array<int, Placement>  $assignments
     * @param  array<int, PlacementEvent>  $events
     * @param  array<string, RoomRef>  $roomById
     */
    public function countHardViolations(
        array $assignments,
        array $events,
        ScheduleSettings $settings,
        array $roomById,
        SchedulingContext $ctx,
    ): int {
        return $this->hardValidator->countViolations(
            $assignments,
            $events,
            $settings,
            $roomById,
            $ctx
        );
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
        ?SchedulingContext $ctx = null,
    ): float {
        return $this->softScorer->candidateValueOrderingScore(
            $event,
            $placement,
            $settings,
            $gridCells,
            $ctx ?? SchedulingContext::empty()
        );
    }

    /**
     * @param  array<int, Placement>  $assignments
     * @param  array<int, PlacementEvent>  $events
     * @param  array<string, RoomRef>  $roomById
     * @param  array<int, GridCell>  $gridCells
     */
    public function softPenalty(
        array $assignments,
        array $events,
        ScheduleSettings $settings,
        array $roomById,
        array $gridCells,
        SchedulingContext $ctx,
    ): float {
        return $this->softScorer->penalty(
            $assignments,
            $events,
            $settings,
            $roomById,
            $gridCells,
            $ctx
        );
    }
}
