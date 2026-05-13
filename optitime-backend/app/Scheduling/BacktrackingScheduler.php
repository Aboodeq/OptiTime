<?php

namespace App\Scheduling;

final class BacktrackingScheduler
{
    private const DEFAULT_FAILED_STATE_CACHE_MAX = 20_000;

    private const DEFAULT_FAILED_STATE_CACHE_MIN_DEPTH = 6;

    private const DEFAULT_DYNAMIC_MCV_SAMPLE_SIZE = 28;

    private const DEFAULT_FORWARD_CHECK_SAMPLE_SIZE = 14;

    private const DEFAULT_HARDEST_UNSCHEDULED_LIMIT = 8;

    private const DEFAULT_PROGRESS_PROBE_INTERVAL = 256;

    private int $backtracks = 0;

    private int $recursiveSteps = 0;

    private int $candidateChecks = 0;

    private int $prunedBranches = 0;

    private int $forwardCheckPrunes = 0;

    private int $failedStateCacheHits = 0;

    private int $symmetryPrunes = 0;

    private int $maxDepth = 0;

    private int $precomputeCandidatesTotal = 0;

    private float $precomputeSeconds = 0.0;

    private array $dayOrder;

    private array $dayRank = [];

    private float $deadline = 0.0;

    private ?string $terminationReason = null;

    private ?string $terminationDetail = null;

    private array $lastHardestUnscheduled = [];

    private HardConstraintValidator $hardValidator;

    private SoftConstraintScorer $softScorer;

    /** @var array<int, list<Placement>> */
    private array $precomputedCandidates = [];

    /** @var array<int, int> */
    private array $initialDomainCountByEvent = [];

    /** @var array<int, PlacementEvent> */
    private array $eventByIndex = [];

    /** @var list<int> */
    private array $nonFixedStaticOrder = [];

    /** @var array<string, list<int>> */
    private array $eventsByInstructor = [];

    /** @var array<string, list<int>> */
    private array $eventsBySection = [];

    /** @var array<string, list<int>> */
    private array $eventsBySectionInstructor = [];

    /** @var array<int, list<int>> */
    private array $siblingEventsByEventIndex = [];

    /** @var array<int, int> */
    private array $siblingPositionByEventIndex = [];

    /** @var array<string, int> */
    private array $slotCountByDay = [];

    /** @var array<string, true> */
    private array $failedStateCache = [];

    /** @var list<string> */
    private array $failedStateQueue = [];

    private int $failedStateCacheMax;

    private int $failedStateCacheMinDepth;

    private int $dynamicMcvSampleSize;

    private int $forwardCheckSampleSize;

    private int $hardestUnscheduledLimit;

    private int $progressProbeInterval;

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
        private array $tuning = [],
    ) {
        $this->hardValidator = new HardConstraintValidator;
        $this->softScorer = new SoftConstraintScorer;
        $this->dayOrder = $settings->enabledStudyDayKeys();
        $this->dayRank = array_flip($this->dayOrder);

        $this->failedStateCacheMax = $this->tuningInt('failed_state_cache_max', self::DEFAULT_FAILED_STATE_CACHE_MAX, 0);
        $this->failedStateCacheMinDepth = $this->tuningInt('failed_state_cache_min_depth', self::DEFAULT_FAILED_STATE_CACHE_MIN_DEPTH, 1);
        $this->dynamicMcvSampleSize = $this->tuningInt('dynamic_mcv_sample_size', self::DEFAULT_DYNAMIC_MCV_SAMPLE_SIZE, 1);
        $this->forwardCheckSampleSize = $this->tuningInt('forward_check_sample_size', self::DEFAULT_FORWARD_CHECK_SAMPLE_SIZE, 1);
        $this->hardestUnscheduledLimit = $this->tuningInt('hardest_unscheduled_limit', self::DEFAULT_HARDEST_UNSCHEDULED_LIMIT, 1);
        $this->progressProbeInterval = $this->tuningInt('progress_probe_interval', self::DEFAULT_PROGRESS_PROBE_INTERVAL, 1);

        foreach ($this->events as $event) {
            $this->eventByIndex[$event->index] = $event;
            $this->eventsByInstructor[$event->instructorId][] = $event->index;
            $this->eventsBySection[$event->courseSectionId][] = $event->index;
            $this->eventsBySectionInstructor[$event->sectionInstructorId][] = $event->index;
        }

        foreach ($this->eventsBySectionInstructor as $indexes) {
            if (count($indexes) < 2) {
                continue;
            }
            sort($indexes);
            foreach ($indexes as $pos => $idx) {
                $this->siblingEventsByEventIndex[$idx] = $indexes;
                $this->siblingPositionByEventIndex[$idx] = $pos;
            }
        }

        $slotSets = [];
        foreach ($this->gridCells as $cell) {
            $slotSets[$cell->day][$cell->startMin.'|'.$cell->endMin] = true;
        }
        foreach ($slotSets as $day => $slots) {
            $this->slotCountByDay[$day] = count($slots);
        }
    }

    public function solve(): array
    {
        $this->resetDiagnostics();
        $this->deadline = microtime(true) + $this->maxSeconds;

        $assignments = [];
        foreach ($this->events as $event) {
            if ($event->isFixed() && $event->fixed !== null) {
                $assignments[$event->index] = $event->fixed;
            }
        }

        $state = $this->buildInitialState($assignments);

        $baseHardEval = $this->hardValidator->evaluate($assignments, $this->events, $this->settings, $this->roomById, $this->ctx);
        if (($baseHardEval['count'] ?? 0) > 0) {
            $this->setTermination('base_hard_conflict');

            return [
                'ok' => false,
                'reason' => 'baseDraft fixed sessions conflict with hard constraints',
                'hard_count' => $baseHardEval['count'],
                'hard_breakdown' => $baseHardEval['by_constraint'],
                'hard_errors' => $baseHardEval['errors'],
                'diagnostics' => $this->getDiagnostics(),
            ];
        }

        if ($this->limitExceeded()) {
            return $this->terminateFailureResponse();
        }

        $candidateBuild = $this->prepareCandidatePools();
        if (! $candidateBuild['ok']) {
            return [
                'ok' => false,
                'reason' => $candidateBuild['reason'],
                'diagnostics' => $this->getDiagnostics(),
            ];
        }

        $impossible = $this->precheckImpossible($state);
        if ($impossible !== null) {
            $this->setTermination('precheck_impossible', $impossible);

            return [
                'ok' => false,
                'reason' => $impossible,
                'diagnostics' => $this->getDiagnostics(),
            ];
        }

        $result = $this->search($state, 0);
        if ($result !== null) {
            $improved = count($this->events) > 120
                ? $result
                : $this->localSoftImprove($result);

            return [
                'ok' => true,
                'assignments' => $improved,
                'diagnostics' => $this->getDiagnostics(),
            ];
        }

        $this->lastHardestUnscheduled = $this->hardestUnscheduledEvents($state);

        return $this->terminateFailureResponse();
    }

    public function getBacktracks(): int
    {
        return $this->backtracks;
    }

    public function getRecursiveSteps(): int
    {
        return $this->recursiveSteps;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDiagnostics(): array
    {
        return [
            'termination_reason' => $this->terminationReason,
            'termination_detail' => $this->terminationDetail,
            'backtracks' => $this->backtracks,
            'recursive_steps' => $this->recursiveSteps,
            'candidate_checks' => $this->candidateChecks,
            'pruned_branches' => $this->prunedBranches,
            'forward_check_prunes' => $this->forwardCheckPrunes,
            'failed_state_cache_hits' => $this->failedStateCacheHits,
            'failed_state_cache_size' => count($this->failedStateCache),
            'symmetry_prunes' => $this->symmetryPrunes,
            'max_depth' => $this->maxDepth,
            'precompute_candidates_total' => $this->precomputeCandidatesTotal,
            'precompute_seconds' => round($this->precomputeSeconds, 6),
            'hardest_unscheduled_events' => $this->lastHardestUnscheduled,
        ];
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     * @return array<int, Placement>|null
     */
    private function search(array &$state, int $depth): ?array
    {
        $this->recursiveSteps++;
        if ($depth > $this->maxDepth) {
            $this->maxDepth = $depth;
        }

        if ($this->limitExceeded()) {
            return null;
        }

        if ($state['assigned_count'] >= count($this->events)) {
            return $state['assignments'];
        }

        $next = $this->pickNextEvent($state);
        if ($next === null) {
            return $state['assignments'];
        }

        $eventIndex = $next['index'];
        if ($next['domain_count'] < 1) {
            $this->prunedBranches++;

            return null;
        }

        $event = $this->eventByIndex[$eventIndex] ?? null;
        if ($event === null) {
            $this->prunedBranches++;

            return null;
        }

        $candidates = $this->feasibleCandidatesForState($event, $state);
        if ($candidates === []) {
            $this->prunedBranches++;

            return null;
        }

        foreach ($candidates as $candidate) {
            if ($this->limitExceeded()) {
                return null;
            }

            if ($this->violatesSymmetry($event, $candidate, $state)) {
                $this->symmetryPrunes++;
                $this->prunedBranches++;
                continue;
            }

            if (! $this->applyCandidate($event, $candidate, $state)) {
                $this->prunedBranches++;
                continue;
            }

            $childKey = $this->buildStateKey($state, $depth + 1);
            if ($childKey !== '' && isset($this->failedStateCache[$childKey])) {
                $this->failedStateCacheHits++;
                $this->removeCandidate($event, $state);
                $this->backtracks++;
                continue;
            }

            if (! $this->forwardCheckingFails($state, $eventIndex)) {
                $sub = $this->search($state, $depth + 1);
                if ($sub !== null) {
                    return $sub;
                }
            } else {
                $this->forwardCheckPrunes++;
                $this->prunedBranches++;
            }

            if ($childKey !== '') {
                $this->rememberFailedState($childKey);
            }

            $this->removeCandidate($event, $state);
            $this->backtracks++;
        }

        return null;
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     */
    private function precheckImpossible(array $state): ?string
    {
        $boundFailure = $this->crudeBoundsFail();
        if ($boundFailure !== null) {
            return $boundFailure;
        }

        $weeklyFailure = $this->instructorWeeklyLoadImpossible($state);
        if ($weeklyFailure !== null) {
            return $weeklyFailure;
        }

        $maxDailyFailure = $this->maxDailyLecturesImpossible($state);
        if ($maxDailyFailure !== null) {
            return $maxDailyFailure;
        }

        return null;
    }

    /**
     * @return array{ok:bool,reason?:string}
     */
    private function prepareCandidatePools(): array
    {
        $startedAt = microtime(true);
        $this->precomputedCandidates = [];
        $this->initialDomainCountByEvent = [];
        $this->precomputeCandidatesTotal = 0;

        $probe = 0;

        foreach ($this->events as $event) {
            if ($event->isFixed()) {
                continue;
            }

            $entries = [];
            foreach ($this->gridCells as $cell) {
                foreach ($this->rooms as $room) {
                    $probe++;
                    if ($probe % $this->progressProbeInterval === 0 && $this->limitExceeded()) {
                        $this->precomputeSeconds = microtime(true) - $startedAt;

                        return [
                            'ok' => false,
                            'reason' => 'backtracking terminated while preparing candidates',
                        ];
                    }

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

                    $placement = Placement::fromGridCell($cell, $room->id);
                    if ($this->settings->isHardEnabled('instructor_availability')
                        && ! $this->placementInsideInstructorAvailability($event->instructorId, $placement)) {
                        continue;
                    }

                    $entries[] = [
                        'placement' => $placement,
                        'score' => $this->softScorer->candidateValueOrderingScore($event, $placement, $this->settings, $this->gridCells, $this->ctx),
                        'day_rank' => $this->dayRank[$placement->day] ?? 99,
                    ];
                }
            }

            usort($entries, function (array $a, array $b): int {
                if ($a['score'] !== $b['score']) {
                    return $a['score'] <=> $b['score'];
                }
                if ($a['day_rank'] !== $b['day_rank']) {
                    return $a['day_rank'] <=> $b['day_rank'];
                }
                if ($a['placement']->startMin !== $b['placement']->startMin) {
                    return $a['placement']->startMin <=> $b['placement']->startMin;
                }

                return strcmp($a['placement']->roomId, $b['placement']->roomId);
            });

            $candidates = [];
            foreach ($entries as $entry) {
                $candidates[] = $entry['placement'];
            }

            $this->precomputedCandidates[$event->index] = $candidates;
            $this->initialDomainCountByEvent[$event->index] = count($candidates);
            $this->precomputeCandidatesTotal += count($candidates);

            if ($candidates === []) {
                $this->setTermination('zero_domain_precheck', 'event_index:'.$event->index);
                $this->precomputeSeconds = microtime(true) - $startedAt;

                return [
                    'ok' => false,
                    'reason' => "backtracking precheck failed: event index {$event->index} has zero feasible candidates",
                ];
            }
        }

        $this->precomputeSeconds = microtime(true) - $startedAt;
        $this->buildStaticEventOrder();

        return ['ok' => true];
    }

    private function buildStaticEventOrder(): void
    {
        $rows = [];
        foreach ($this->events as $event) {
            if ($event->isFixed()) {
                continue;
            }
            $rows[] = [
                'index' => $event->index,
                'domain' => $this->initialDomainCountByEvent[$event->index] ?? PHP_INT_MAX,
                'requires_lab' => $event->requiresLab ? 1 : 0,
                'enrollment' => $event->enrollment,
                'instructor_span' => count($this->eventsByInstructor[$event->instructorId] ?? []),
                'section_span' => count($this->eventsBySection[$event->courseSectionId] ?? []),
                'section_instructor_span' => count($this->eventsBySectionInstructor[$event->sectionInstructorId] ?? []),
            ];
        }

        usort($rows, function (array $a, array $b): int {
            if ($a['domain'] !== $b['domain']) {
                return $a['domain'] <=> $b['domain'];
            }
            if ($a['section_instructor_span'] !== $b['section_instructor_span']) {
                return $b['section_instructor_span'] <=> $a['section_instructor_span'];
            }
            if ($a['section_span'] !== $b['section_span']) {
                return $b['section_span'] <=> $a['section_span'];
            }
            if ($a['requires_lab'] !== $b['requires_lab']) {
                return $b['requires_lab'] <=> $a['requires_lab'];
            }
            if ($a['enrollment'] !== $b['enrollment']) {
                return $b['enrollment'] <=> $a['enrollment'];
            }
            if ($a['instructor_span'] !== $b['instructor_span']) {
                return $b['instructor_span'] <=> $a['instructor_span'];
            }

            return $a['index'] <=> $b['index'];
        });

        $this->nonFixedStaticOrder = [];
        foreach ($rows as $row) {
            $this->nonFixedStaticOrder[] = (int) $row['index'];
        }
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     * @return array{index:int,domain_count:int}|null
     */
    private function pickNextEvent(array $state): ?array
    {
        $unscheduled = [];
        foreach ($this->nonFixedStaticOrder as $index) {
            if (! isset($state['assignments'][$index])) {
                $unscheduled[] = $index;
            }
        }

        if ($unscheduled === []) {
            return null;
        }

        $sampleSize = min(count($unscheduled), max(1, $this->dynamicMcvSampleSize));

        $bestIndex = $unscheduled[0];
        $bestCount = PHP_INT_MAX;

        for ($i = 0; $i < $sampleSize; $i++) {
            $idx = $unscheduled[$i];
            $event = $this->eventByIndex[$idx] ?? null;
            if ($event === null) {
                continue;
            }

            $count = $this->countFeasibleCandidates($event, $state, $bestCount);
            if ($count < $bestCount) {
                $bestCount = $count;
                $bestIndex = $idx;
            }

            if ($bestCount < 1) {
                break;
            }
        }

        if ($bestCount === PHP_INT_MAX) {
            $bestCount = 0;
        }

        return [
            'index' => $bestIndex,
            'domain_count' => $bestCount,
        ];
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     */
    private function countFeasibleCandidates(PlacementEvent $event, array $state, int $stopAt = PHP_INT_MAX): int
    {
        $count = 0;
        foreach ($this->precomputedCandidates[$event->index] ?? [] as $candidate) {
            if ($this->isCandidateFeasible($event, $candidate, $state)) {
                $count++;
                if ($count >= $stopAt) {
                    break;
                }
            }
        }

        return $count;
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     * @return list<Placement>
     */
    private function feasibleCandidatesForState(PlacementEvent $event, array $state): array
    {
        $out = [];
        foreach ($this->precomputedCandidates[$event->index] ?? [] as $candidate) {
            if ($this->isCandidateFeasible($event, $candidate, $state)) {
                $out[] = $candidate;
            }
        }

        return $out;
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     */
    private function hasAnyFeasibleCandidate(PlacementEvent $event, array $state): bool
    {
        foreach ($this->precomputedCandidates[$event->index] ?? [] as $candidate) {
            if ($this->isCandidateFeasible($event, $candidate, $state)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     */
    private function forwardCheckingFails(array $state, int $lastAssignedEventIndex): bool
    {
        foreach ($this->impactedIndexesForForwardCheck($state, $lastAssignedEventIndex) as $idx) {
            $event = $this->eventByIndex[$idx] ?? null;
            if ($event === null || isset($state['assignments'][$idx])) {
                continue;
            }

            if (! $this->hasAnyFeasibleCandidate($event, $state)) {
                return true;
            }

            if ($this->limitExceeded()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     * @return list<int>
     */
    private function impactedIndexesForForwardCheck(array $state, int $lastAssignedEventIndex): array
    {
        $event = $this->eventByIndex[$lastAssignedEventIndex] ?? null;
        if ($event === null) {
            return [];
        }

        $picked = [];

        foreach ($this->eventsByInstructor[$event->instructorId] ?? [] as $idx) {
            if (! isset($state['assignments'][$idx])) {
                $picked[$idx] = true;
            }
        }
        foreach ($this->eventsBySection[$event->courseSectionId] ?? [] as $idx) {
            if (! isset($state['assignments'][$idx])) {
                $picked[$idx] = true;
            }
        }

        $added = 0;
        foreach ($this->nonFixedStaticOrder as $idx) {
            if ($added >= $this->forwardCheckSampleSize) {
                break;
            }
            if (isset($state['assignments'][$idx]) || isset($picked[$idx])) {
                continue;
            }
            $picked[$idx] = true;
            $added++;
        }

        return array_map('intval', array_keys($picked));
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     */
    private function applyCandidate(PlacementEvent $event, Placement $candidate, array &$state): bool
    {
        if (! $this->isCandidateFeasible($event, $candidate, $state)) {
            return false;
        }

        $this->storePlacementInState($event, $candidate, $state);

        return true;
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     */
    private function removeCandidate(PlacementEvent $event, array &$state): void
    {
        $placement = $state['assignments'][$event->index] ?? null;
        if ($placement === null) {
            return;
        }

        unset($state['assignments'][$event->index], $state['assigned_keys'][$event->index]);
        $state['assigned_count'] = max(0, $state['assigned_count'] - 1);

        unset($state['room_day_intervals'][$placement->roomId][$placement->day][$event->index]);
        unset($state['instructor_day_intervals'][$event->instructorId][$placement->day][$event->index]);
        unset($state['section_day_intervals'][$event->courseSectionId][$placement->day][$event->index]);

        if (isset($state['instructor_day_counts'][$event->instructorId][$placement->day])) {
            $state['instructor_day_counts'][$event->instructorId][$placement->day] = max(0, $state['instructor_day_counts'][$event->instructorId][$placement->day] - 1);
        }

        $duration = $this->durationMinutes($placement);
        $state['instructor_weekly_minutes'][$event->instructorId] = max(0, (int) ($state['instructor_weekly_minutes'][$event->instructorId] ?? 0) - $duration);
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     */
    private function storePlacementInState(PlacementEvent $event, Placement $placement, array &$state): void
    {
        $index = $event->index;
        $state['assignments'][$index] = $placement;
        $state['assigned_keys'][$index] = $this->placementKey($placement);
        $state['assigned_count']++;

        $state['room_day_intervals'][$placement->roomId][$placement->day][$index] = [
            'start' => $placement->startMin,
            'end' => $placement->endMin,
        ];
        $state['instructor_day_intervals'][$event->instructorId][$placement->day][$index] = [
            'start' => $placement->startMin,
            'end' => $placement->endMin,
        ];
        $state['section_day_intervals'][$event->courseSectionId][$placement->day][$index] = [
            'start' => $placement->startMin,
            'end' => $placement->endMin,
        ];

        $state['instructor_day_counts'][$event->instructorId][$placement->day] = ((int) ($state['instructor_day_counts'][$event->instructorId][$placement->day] ?? 0)) + 1;
        $state['instructor_weekly_minutes'][$event->instructorId] = ((int) ($state['instructor_weekly_minutes'][$event->instructorId] ?? 0)) + $this->durationMinutes($placement);
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     */
    private function isCandidateFeasible(PlacementEvent $event, Placement $candidate, array $state): bool
    {
        $this->candidateChecks++;

        if ($this->settings->isCapacityThresholdHard()) {
            $room = $this->roomById[$candidate->roomId] ?? null;
            if ($room === null) {
                return false;
            }
            if ($room->capacity > 0) {
                $ratio = $event->enrollment / $room->capacity;
                if ($ratio > $this->settings->capacityThreshold()) {
                    return false;
                }
            }
        }

        if ($this->settings->isHardEnabled('no_room_overlap')) {
            $roomIntervals = $state['room_day_intervals'][$candidate->roomId][$candidate->day] ?? [];
            if ($this->overlapsAny($roomIntervals, $candidate->startMin, $candidate->endMin)) {
                return false;
            }
        }

        if ($this->settings->isHardEnabled('no_instructor_overlap')) {
            $instructorIntervals = $state['instructor_day_intervals'][$event->instructorId][$candidate->day] ?? [];
            if ($this->overlapsAny($instructorIntervals, $candidate->startMin, $candidate->endMin)) {
                return false;
            }
        }

        if ($this->settings->isHardEnabled('no_section_overlap')) {
            $sectionIntervals = $state['section_day_intervals'][$event->courseSectionId][$candidate->day] ?? [];
            if ($this->overlapsAny($sectionIntervals, $candidate->startMin, $candidate->endMin)) {
                return false;
            }
        }

        if ($this->settings->isHardEnabled('instructor_availability')
            && ! $this->placementInsideInstructorAvailability($event->instructorId, $candidate)) {
            return false;
        }

        if ($this->settings->isMaxDailyLecturesHardEnabled()) {
            $currentDayCount = (int) ($state['instructor_day_counts'][$event->instructorId][$candidate->day] ?? 0);
            if ($currentDayCount + 1 > max(1, $this->settings->maxDailyLectures())) {
                return false;
            }
        }

        $limits = $this->ctx->instructorWeeklyLoadLimitsByInstructorId[$event->instructorId] ?? null;
        if (is_array($limits)) {
            $maxHours = (int) ($limits['max'] ?? 0);
            if ($maxHours > 0) {
                $currentMinutes = (int) ($state['instructor_weekly_minutes'][$event->instructorId] ?? 0);
                if (($currentMinutes + $this->durationMinutes($candidate)) > ($maxHours * 60)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  array<int, array{start:int,end:int}>  $intervals
     */
    private function overlapsAny(array $intervals, int $startMin, int $endMin): bool
    {
        foreach ($intervals as $window) {
            if (TimeUtil::intervalsOverlap($startMin, $endMin, $window['start'], $window['end'])) {
                return true;
            }
        }

        return false;
    }

    private function placementInsideInstructorAvailability(string $instructorId, Placement $placement): bool
    {
        $preferred = $this->ctx->instructorPreferredAvailabilityByInstructorId[$instructorId] ?? [];
        $unavailable = $this->ctx->instructorUnavailableAvailabilityByInstructorId[$instructorId] ?? [];

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

    private function crudeBoundsFail(): ?string
    {
        $unplaced = 0;
        foreach ($this->events as $event) {
            if (! $event->isFixed()) {
                $unplaced++;
            }
        }

        $slots = count($this->gridCells) * max(1, count($this->rooms));
        if ($unplaced > $slots) {
            return 'required slots exceed legal cells × rooms (crude bound)';
        }

        return null;
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     */
    private function instructorWeeklyLoadImpossible(array $state): ?string
    {
        $requiredMinutesByInstructor = [];
        foreach ($this->events as $event) {
            $placement = $state['assignments'][$event->index] ?? null;
            if ($placement !== null) {
                $requiredMinutesByInstructor[$event->instructorId] = ($requiredMinutesByInstructor[$event->instructorId] ?? 0) + $this->durationMinutes($placement);
                continue;
            }
            $requiredMinutesByInstructor[$event->instructorId] = ($requiredMinutesByInstructor[$event->instructorId] ?? 0) + $this->settings->slotMinutes();
        }

        foreach ($requiredMinutesByInstructor as $instructorId => $requiredMinutes) {
            $limits = $this->ctx->instructorWeeklyLoadLimitsByInstructorId[$instructorId] ?? null;
            if (! is_array($limits)) {
                continue;
            }
            $maxHours = (int) ($limits['max'] ?? 0);
            if ($maxHours > 0 && $requiredMinutes > ($maxHours * 60)) {
                return "instructor max weekly load infeasible for {$instructorId} ({$requiredMinutes}min > ".($maxHours * 60).'min)';
            }
        }

        return null;
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     */
    private function maxDailyLecturesImpossible(array $state): ?string
    {
        if (! $this->settings->isMaxDailyLecturesHardEnabled()) {
            return null;
        }

        $maxDaily = max(1, $this->settings->maxDailyLectures());

        foreach ($this->eventsByInstructor as $instructorId => $eventIndexes) {
            $requiredUnfixed = 0;
            $possibleSlotsByDay = [];

            foreach ($eventIndexes as $idx) {
                if (isset($state['assignments'][$idx])) {
                    continue;
                }

                $requiredUnfixed++;
                foreach ($this->precomputedCandidates[$idx] ?? [] as $candidate) {
                    $possibleSlotsByDay[$candidate->day][$candidate->startMin.'|'.$candidate->endMin] = true;
                }
            }

            if ($requiredUnfixed < 1) {
                continue;
            }

            $availableCapacity = 0;
            foreach ($possibleSlotsByDay as $day => $slotMap) {
                $dayCap = min($maxDaily, count($slotMap));
                $fixedOnDay = (int) ($state['instructor_day_counts'][$instructorId][$day] ?? 0);
                if ($fixedOnDay > $dayCap) {
                    return "max daily lectures infeasible for {$instructorId} on {$day}";
                }
                $availableCapacity += max(0, $dayCap - $fixedOnDay);
            }

            if ($requiredUnfixed > $availableCapacity) {
                return "max daily lectures infeasible for {$instructorId}: required {$requiredUnfixed} > available {$availableCapacity}";
            }
        }

        return null;
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     */
    private function violatesSymmetry(PlacementEvent $event, Placement $candidate, array $state): bool
    {
        $siblings = $this->siblingEventsByEventIndex[$event->index] ?? null;
        $position = $this->siblingPositionByEventIndex[$event->index] ?? null;
        if (! is_array($siblings) || $position === null || $position < 1) {
            return false;
        }

        $candidateKey = $this->placementKey($candidate);

        for ($pos = $position - 1; $pos >= 0; $pos--) {
            $siblingIndex = $siblings[$pos];
            $siblingPlacementKey = $state['assigned_keys'][$siblingIndex] ?? null;
            if ($siblingPlacementKey === null) {
                continue;
            }

            return strcmp($candidateKey, $siblingPlacementKey) < 0;
        }

        return false;
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     */
    private function buildStateKey(array $state, int $depth): string
    {
        if ($this->failedStateCacheMax < 1 || $depth < $this->failedStateCacheMinDepth) {
            return '';
        }

        if ($state['assigned_keys'] === []) {
            return '';
        }

        $keys = $state['assigned_keys'];
        ksort($keys);

        $parts = [];
        foreach ($keys as $idx => $value) {
            $parts[] = $idx.'='.$value;
        }

        return implode(';', $parts);
    }

    private function rememberFailedState(string $key): void
    {
        if ($key === '' || isset($this->failedStateCache[$key])) {
            return;
        }

        $this->failedStateCache[$key] = true;
        $this->failedStateQueue[] = $key;

        if (count($this->failedStateQueue) <= $this->failedStateCacheMax) {
            return;
        }

        $oldest = array_shift($this->failedStateQueue);
        if ($oldest !== null) {
            unset($this->failedStateCache[$oldest]);
        }
    }

    /**
     * @param  array<int, Placement>  $assignments
     * @return array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}
     */
    private function buildInitialState(array $assignments): array
    {
        $state = [
            'assignments' => [],
            'assigned_keys' => [],
            'room_day_intervals' => [],
            'instructor_day_intervals' => [],
            'section_day_intervals' => [],
            'instructor_day_counts' => [],
            'instructor_weekly_minutes' => [],
            'assigned_count' => 0,
        ];

        foreach ($assignments as $index => $placement) {
            $event = $this->eventByIndex[$index] ?? null;
            if ($event === null) {
                continue;
            }
            $this->storePlacementInState($event, $placement, $state);
        }

        return $state;
    }

    private function durationMinutes(Placement $placement): int
    {
        return max(0, $placement->endMin - $placement->startMin);
    }

    private function placementKey(Placement $placement): string
    {
        $dayRank = $this->dayRank[$placement->day] ?? 99;

        return sprintf('%02d|%04d|%04d|%s', (int) $dayRank, $placement->startMin, $placement->endMin, $placement->roomId);
    }

    private function setTermination(string $reason, ?string $detail = null): void
    {
        if ($this->terminationReason !== null) {
            return;
        }

        $this->terminationReason = $reason;
        $this->terminationDetail = $detail;
    }

    private function limitExceeded(): bool
    {
        if ($this->deadline > 0 && microtime(true) > $this->deadline) {
            $this->setTermination('max_seconds_exceeded');

            return true;
        }
        if ($this->backtracks >= $this->maxBacktracks) {
            $this->setTermination('max_backtracks_exceeded');

            return true;
        }
        if ($this->recursiveSteps >= $this->maxRecursiveSteps) {
            $this->setTermination('max_recursive_steps_exceeded');

            return true;
        }

        return false;
    }

    /**
     * @return array{ok:false,reason:string,diagnostics:array<string,mixed>}
     */
    private function terminateFailureResponse(): array
    {
        $reason = $this->terminationReason !== null
            ? 'backtracking terminated: '.$this->terminationReason
            : 'no feasible schedule found';

        return [
            'ok' => false,
            'reason' => $reason,
            'diagnostics' => $this->getDiagnostics(),
        ];
    }

    private function resetDiagnostics(): void
    {
        $this->backtracks = 0;
        $this->recursiveSteps = 0;
        $this->candidateChecks = 0;
        $this->prunedBranches = 0;
        $this->forwardCheckPrunes = 0;
        $this->failedStateCacheHits = 0;
        $this->symmetryPrunes = 0;
        $this->maxDepth = 0;
        $this->precomputeCandidatesTotal = 0;
        $this->precomputeSeconds = 0.0;
        $this->terminationReason = null;
        $this->terminationDetail = null;
        $this->lastHardestUnscheduled = [];
        $this->failedStateCache = [];
        $this->failedStateQueue = [];
    }

    /**
     * @param  array{assignments:array<int, Placement>,assigned_keys:array<int, string>,room_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,section_day_intervals:array<string, array<string, array<int, array{start:int,end:int}>>>,instructor_day_counts:array<string, array<string, int>>,instructor_weekly_minutes:array<string, int>,assigned_count:int}  $state
     * @return list<array<string,mixed>>
     */
    private function hardestUnscheduledEvents(array $state): array
    {
        $rows = [];

        foreach ($this->nonFixedStaticOrder as $idx) {
            if (isset($state['assignments'][$idx])) {
                continue;
            }

            $event = $this->eventByIndex[$idx] ?? null;
            if ($event === null) {
                continue;
            }

            $rows[] = [
                'event_index' => $idx,
                'course_id' => $event->courseId,
                'section_id' => $event->courseSectionId,
                'section_instructor_id' => $event->sectionInstructorId,
                'instructor_id' => $event->instructorId,
                'domain_count' => $this->countFeasibleCandidates($event, $state),
                'enrollment' => $event->enrollment,
                'requires_lab' => $event->requiresLab,
            ];
        }

        usort($rows, function (array $a, array $b): int {
            if ($a['domain_count'] !== $b['domain_count']) {
                return $a['domain_count'] <=> $b['domain_count'];
            }
            if ($a['requires_lab'] !== $b['requires_lab']) {
                return ($b['requires_lab'] ? 1 : 0) <=> ($a['requires_lab'] ? 1 : 0);
            }

            return $b['enrollment'] <=> $a['enrollment'];
        });

        return array_slice($rows, 0, $this->hardestUnscheduledLimit);
    }

    /**
     * @param  array<int, Placement>  $assignments
     * @return array<int, Placement>
     */
    private function localSoftImprove(array $assignments): array
    {
        if ($assignments === []) {
            return $assignments;
        }
        if (count($this->events) > 120) {
            return $assignments;
        }

        $best = $assignments;
        $bestSoft = $this->softScorer->penalty($best, $this->events, $this->settings, $this->roomById, $this->gridCells, $this->ctx);
        $state = $this->buildInitialState($best);

        $changed = true;
        $guard = 0;

        while ($changed && $guard < 80) {
            $changed = false;
            $guard++;

            foreach ($this->events as $event) {
                if ($event->isFixed()) {
                    continue;
                }

                $orig = $state['assignments'][$event->index] ?? null;
                if ($orig === null) {
                    continue;
                }

                $this->removeCandidate($event, $state);
                $accepted = false;

                foreach ($this->precomputedCandidates[$event->index] ?? [] as $candidate) {
                    if ($this->limitExceeded()) {
                        $this->storePlacementInState($event, $orig, $state);

                        return $best;
                    }
                    if ($candidate->day === $orig->day && $candidate->startMin === $orig->startMin && $candidate->roomId === $orig->roomId) {
                        continue;
                    }

                    if (! $this->isCandidateFeasible($event, $candidate, $state)) {
                        continue;
                    }

                    $this->storePlacementInState($event, $candidate, $state);
                    $trialAssignments = $state['assignments'];
                    $soft = $this->softScorer->penalty($trialAssignments, $this->events, $this->settings, $this->roomById, $this->gridCells, $this->ctx);

                    if ($soft < $bestSoft - 0.001) {
                        $bestSoft = $soft;
                        $best = $trialAssignments;
                        $accepted = true;
                        $changed = true;
                        break;
                    }

                    $this->removeCandidate($event, $state);
                }

                if (! $accepted) {
                    $this->storePlacementInState($event, $orig, $state);
                    continue;
                }

                if ($accepted) {
                    break;
                }
            }
        }

        return $best;
    }

    private function tuningInt(string $key, int $default, int $min): int
    {
        $value = $this->tuning[$key] ?? $default;

        return max($min, (int) $value);
    }
}
