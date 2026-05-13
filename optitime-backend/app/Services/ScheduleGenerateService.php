<?php

namespace App\Services;

use App\Models\CourseOffering;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CourseSectionInstructor;
use App\Models\Instructor;
use App\Models\InstructorAvailabilityProfile;
use App\Models\Room;
use App\Models\ScheduleSessionStudent;
use App\Models\ScheduleSetting;
use App\Scheduling\BacktrackingScheduler;
use App\Scheduling\DayMapping;
use App\Scheduling\GeneticScheduler;
use App\Scheduling\GridBuilder;
use App\Scheduling\HardConstraintValidator;
use App\Scheduling\Placement;
use App\Scheduling\PlacementEvent;
use App\Scheduling\RoomRef;
use App\Scheduling\ScheduleSettings;
use App\Scheduling\SchedulingContext;
use App\Scheduling\SoftConstraintScorer;
use App\Scheduling\TimeUtil;

final class ScheduleGenerateService
{
    private HardConstraintValidator $hardValidator;

    private SoftConstraintScorer $softScorer;

    public function __construct()
    {
        $this->hardValidator = new HardConstraintValidator;
        $this->softScorer = new SoftConstraintScorer;
    }

    public function generate(
        string $algorithm,
        string $semesterId,
        ?array $scheduleSettingsPayload,
        ?string $settingsId,
        ?array $baseDraft,
        ?array $roomsOverride,
        ?int $seed,
        ?array $instructorAvailabilitiesPayload = null,
    ): array {
        $bundle = $this->resolveSettingsBundle($scheduleSettingsPayload, $settingsId);
        $settings = $bundle['settings'];

        $settingsGate = ScheduleGenerationSettingsValidator::validateGenerationPrerequisites($bundle['db_row'], $settings);
        if ($settingsGate !== null) {
            return $settingsGate;
        }

        $gridBuilder = new GridBuilder;
        $gridCells = $gridBuilder->build($settings);
        if (count($gridCells) === 0) {
            return [
                'success' => false,
                'reason' => 'no legal grid cells from schedule settings (check study days / breaks / hours)',
                'sessions' => [],
                'meta' => [],
            ];
        }

        $rooms = $this->resolveRooms($roomsOverride);
        if (count($rooms) === 0) {
            return [
                'success' => false,
                'reason' => 'no rooms available',
                'sessions' => [],
                'meta' => [],
            ];
        }

        $roomById = [];
        foreach ($rooms as $room) {
            $roomById[$room->id] = $room;
        }

        $eventBundle = $this->buildPlacementEvents($semesterId, $baseDraft, $settings, $roomById);
        if ($eventBundle['errors'] !== []) {
            return [
                'success' => false,
                'reason' => 'invalid session inputs for schedule generation',
                'sessions' => [],
                'meta' => [
                    'validation_errors' => $eventBundle['errors'],
                ],
            ];
        }

        $events = $eventBundle['events'];
        if (count($events) === 0) {
            return [
                'success' => false,
                'reason' => 'no sessions to place for this semester',
                'sessions' => [],
                'meta' => [],
            ];
        }

        $ctx = $this->buildSchedulingContext($gridCells, $events, $instructorAvailabilitiesPayload);

        $capacityError = $this->capacityFeasibilityMessage($settings, $events, $rooms);
        if ($capacityError !== null) {
            return [
                'success' => false,
                'reason' => $capacityError,
                'sessions' => [],
                'meta' => [],
            ];
        }

        $btCfg = config('optitime.backtracking');
        $gaCfg = config('optitime.genetic');

        if ($algorithm === 'backtracking') {
            $btTuning = [
                'failed_state_cache_max' => (int) ($btCfg['failed_state_cache_max'] ?? 20_000),
                'failed_state_cache_min_depth' => (int) ($btCfg['failed_state_cache_min_depth'] ?? 6),
                'dynamic_mcv_sample_size' => (int) ($btCfg['dynamic_mcv_sample_size'] ?? 28),
                'forward_check_sample_size' => (int) ($btCfg['forward_check_sample_size'] ?? 14),
                'hardest_unscheduled_limit' => (int) ($btCfg['hardest_unscheduled_limit'] ?? 8),
                'progress_probe_interval' => (int) ($btCfg['progress_probe_interval'] ?? 256),
            ];
            $solver = new BacktrackingScheduler(
                $settings,
                $events,
                $gridCells,
                $rooms,
                $roomById,
                $ctx,
                (int) $btCfg['max_backtracks'],
                (float) $btCfg['max_seconds'],
                (int) $btCfg['max_recursive_steps'],
                $btTuning,
            );
            $result = $solver->solve();
            $diagnostics = (array) ($result['diagnostics'] ?? $solver->getDiagnostics());
            if (! $result['ok']) {
                $meta = [
                    'backtracks' => $solver->getBacktracks(),
                    'recursive_steps' => $solver->getRecursiveSteps(),
                ];
                if ($diagnostics !== []) {
                    $meta['diagnostics'] = $diagnostics;
                    if (! empty($diagnostics['termination_reason'])) {
                        $meta['termination_reason'] = (string) $diagnostics['termination_reason'];
                    }
                }
                if (! empty($result['hard_breakdown'])) {
                    $meta['hard_breakdown'] = $result['hard_breakdown'];
                }
                if (! empty($result['hard_errors'])) {
                    $meta['validation_errors'] = $result['hard_errors'];
                }
                if (array_key_exists('hard_count', $result)) {
                    $meta['hard_violations'] = (int) $result['hard_count'];
                }

                return [
                    'success' => false,
                    'reason' => $result['reason'] ?? 'no feasible schedule found',
                    'sessions' => [],
                    'meta' => $meta,
                ];
            }

            return $this->buildGenerationResponse(
                'backtracking',
                $result['assignments'],
                $events,
                $settings,
                $roomById,
                $gridCells,
                $ctx,
                $eventBundle['expected_counts'],
                [
                    'backtracks' => $solver->getBacktracks(),
                    'recursive_steps' => $solver->getRecursiveSteps(),
                    'diagnostics' => $diagnostics,
                ]
            );
        }

        $ga = new GeneticScheduler(
            $settings,
            $events,
            $gridCells,
            $rooms,
            $roomById,
            $ctx,
            (int) $gaCfg['population_size'],
            (int) $gaCfg['max_generations'],
            (float) $gaCfg['max_seconds'],
            (float) $gaCfg['crossover_rate'],
            (float) $gaCfg['mutation_rate'],
            (int) $gaCfg['elitism'],
            $seed,
        );
        $gaResult = $ga->solve();

        return $this->buildGenerationResponse(
            'genetic',
            $gaResult['assignments'],
            $events,
            $settings,
            $roomById,
            $gridCells,
            $ctx,
            $eventBundle['expected_counts'],
            [
                'fitness' => $gaResult['fitness'],
                'generations' => $gaResult['generations'],
                'ga_termination_reason' => $gaResult['termination_reason'] ?? null,
                'ga_effective_population' => $gaResult['effective_population'] ?? null,
                'ga_effective_generations' => $gaResult['effective_generations'] ?? null,
                'ga_stagnation_generations' => $gaResult['stagnation_generations'] ?? null,
                'ga_low_gain_stagnation_generations' => $gaResult['low_gain_stagnation_generations'] ?? null,
                'soft_penalty' => $gaResult['soft_penalty'],
                'ga_hard_breakdown' => $gaResult['hard_breakdown'] ?? [],
                'ga_hard_errors' => $gaResult['hard_errors'] ?? [],
            ]
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $sessions
     * @return array{ok:bool,hard_count:int,hard_breakdown:array<string,int>,errors:list<string>}
     */
    public function validateSessionsBeforePublish(
        string $semesterId,
        array $sessions,
        ?array $scheduleSettingsPayload,
        ?string $settingsId,
        ?array $instructorAvailabilitiesPayload = null,
        bool $enforceRequiredSessions = true,
    ): array {
        $bundle = $this->resolveSettingsBundle($scheduleSettingsPayload, $settingsId);
        $settings = $bundle['settings'];

        $settingsGate = ScheduleGenerationSettingsValidator::validateGenerationPrerequisites($bundle['db_row'], $settings);
        if ($settingsGate !== null) {
            return [
                'ok' => false,
                'hard_count' => 1,
                'hard_breakdown' => ['settings_prerequisites' => 1],
                'errors' => [$settingsGate['reason'] ?? 'schedule settings are incomplete'],
            ];
        }

        $gridCells = (new GridBuilder)->build($settings);
        if ($gridCells === []) {
            return [
                'ok' => false,
                'hard_count' => 1,
                'hard_breakdown' => ['working_hours' => 1],
                'errors' => ['No legal grid cells from schedule settings.'],
            ];
        }

        $rooms = $this->resolveRooms(null);
        $roomById = [];
        foreach ($rooms as $room) {
            $roomById[$room->id] = $room;
        }

        $eventBundle = $this->buildPlacementEvents($semesterId, ['sessions' => $sessions], $settings, $roomById);
        if ($eventBundle['errors'] !== []) {
            return [
                'ok' => false,
                'hard_count' => count($eventBundle['errors']),
                'hard_breakdown' => ['session_payload' => count($eventBundle['errors'])],
                'errors' => $eventBundle['errors'],
            ];
        }

        $events = $eventBundle['events'];
        $assignments = [];
        foreach ($events as $event) {
            if ($event->fixed === null) {
                return [
                    'ok' => false,
                    'hard_count' => 1,
                    'hard_breakdown' => ['session_payload' => 1],
                    'errors' => ['All sessions must include day/start/end/room before publishing.'],
                ];
            }
            $assignments[$event->index] = $event->fixed;
        }

        $ctx = $this->buildSchedulingContext($gridCells, $events, $instructorAvailabilitiesPayload);
        $hardEval = $this->hardValidator->evaluate($assignments, $events, $settings, $roomById, $ctx);

        $completenessErrors = [];
        if ($enforceRequiredSessions) {
            $expectedCounts = $this->expectedSessionCountsForSemester($semesterId);
            $completenessErrors = $this->requiredSessionCompletenessErrors(
                $events,
                $this->assignmentsToSessions($assignments, $events),
                $expectedCounts
            );
        }

        $breakdown = $hardEval['by_constraint'];
        if ($completenessErrors !== []) {
            $breakdown['required_sessions'] = ($breakdown['required_sessions'] ?? 0) + count($completenessErrors);
        }

        $errors = array_values(array_merge($hardEval['errors'], $completenessErrors));
        $hardCount = $hardEval['count'] + count($completenessErrors);

        return [
            'ok' => $hardCount === 0,
            'hard_count' => $hardCount,
            'hard_breakdown' => $breakdown,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<int, GridCell>  $gridCells
     * @param  array<int, PlacementEvent>  $events
     */
    private function buildSchedulingContext(
        array $gridCells,
        array $events,
        ?array $payload,
    ): SchedulingContext {
        $legalCellKeys = [];
        foreach ($gridCells as $cell) {
            $legalCellKeys[$cell->day.'|'.$cell->startMin.'|'.$cell->endMin] = true;
        }

        $instructorIds = [];
        foreach ($events as $event) {
            $instructorIds[$event->instructorId] = true;
        }
        $ids = array_keys($instructorIds);

        $preferredMap = [];
        $unavailableMap = [];
        $profiles = InstructorAvailabilityProfile::query()
            ->whereIn('instructor_id', $ids)
            ->with('cells')
            ->get();
        foreach ($profiles as $profile) {
            foreach ($profile->cells as $cell) {
                $window = [
                    'day' => DayMapping::toLong((string) $cell->day_value),
                    'startMin' => TimeUtil::toMinutes(substr((string) $cell->start_time, 0, 5)),
                    'endMin' => TimeUtil::toMinutes(substr((string) $cell->end_time, 0, 5)),
                ];

                $status = strtolower(trim((string) $cell->status));
                if ($status === 'unavailable') {
                    $unavailableMap[$profile->instructor_id][] = $window;
                    continue;
                }
                if ($status === 'preferred') {
                    $preferredMap[$profile->instructor_id][] = $window;
                }
            }
        }

        if (is_array($payload)) {
            foreach ($payload as $row) {
                $instructorId = (string) ($row['instructor_id'] ?? '');
                if ($instructorId === '') {
                    continue;
                }
                $window = [
                    'day' => DayMapping::toLong((string) ($row['day_of_week'] ?? '')),
                    'startMin' => TimeUtil::toMinutes((string) ($row['start'] ?? '00:00')),
                    'endMin' => TimeUtil::toMinutes((string) ($row['end'] ?? '00:00')),
                ];

                $status = strtolower(trim((string) ($row['status'] ?? 'preferred')));
                if ($status === 'unavailable') {
                    $unavailableMap[$instructorId][] = $window;
                } else {
                    $preferredMap[$instructorId][] = $window;
                }
            }
        }

        $weeklyLimits = [];
        if ($ids !== []) {
            $rows = Instructor::query()
                ->whereIn('id', $ids)
                ->get(['id', 'min_work_hours_per_week', 'max_work_hours_per_week']);
            foreach ($rows as $row) {
                $weeklyLimits[(string) $row->id] = [
                    'min' => max(0, (int) $row->min_work_hours_per_week),
                    'max' => max(0, (int) $row->max_work_hours_per_week),
                ];
            }
        }

        return new SchedulingContext(
            $legalCellKeys,
            $preferredMap,
            $unavailableMap,
            $this->buildSharedStudentSectionPairs(),
            $weeklyLimits
        );
    }

    private function buildSharedStudentSectionPairs(): array
    {
        $studentSections = ScheduleSessionStudent::query()
            ->join('schedule_sessions', 'schedule_sessions.id', '=', 'schedule_session_students.schedule_session_id')
            ->join('course_section_instructors', 'course_section_instructors.id', '=', 'schedule_sessions.section_instructor_id')
            ->select('schedule_session_students.student_id', 'course_section_instructors.section_id')
            ->get();

        $sectionsByStudent = [];
        foreach ($studentSections as $row) {
            $studentId = (string) $row->student_id;
            $sectionId = (string) $row->section_id;
            if ($studentId === '' || $sectionId === '') {
                continue;
            }
            $sectionsByStudent[$studentId][$sectionId] = true;
        }

        $pairs = [];
        foreach ($sectionsByStudent as $sections) {
            $ids = array_keys($sections);
            sort($ids);
            $count = count($ids);
            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $a = $ids[$i];
                    $b = $ids[$j];
                    $pairs[$a.'|'.$b] = [$a, $b];
                }
            }
        }

        return array_values($pairs);
    }

    /**
     * @return array{settings: ScheduleSettings, db_row: ?ScheduleSetting}
     */
    private function resolveSettingsBundle(?array $payload, ?string $settingsId): array
    {
        if ($settingsId !== null && $settingsId !== '') {
            $row = ScheduleSetting::query()->findOrFail($settingsId);

            return [
                'settings' => ScheduleSettingsLoader::toScheduleSettings($row),
                'db_row' => $row,
            ];
        }
        if ($payload !== null && $payload !== []) {
            return [
                'settings' => ScheduleSettings::fromArray($payload),
                'db_row' => null,
            ];
        }

        $row = ScheduleSetting::query()->orderBy('created_at')->firstOrFail();

        return [
            'settings' => ScheduleSettingsLoader::toScheduleSettings($row),
            'db_row' => $row,
        ];
    }

    /**
     * @return array<int, RoomRef>
     */
    private function resolveRooms(?array $override): array
    {
        if ($override !== null && $override !== []) {
            $list = [];
            foreach ($override as $room) {
                $list[] = new RoomRef(
                    (string) $room['id'],
                    (int) ($room['capacity'] ?? 0),
                    (bool) ($room['is_lab'] ?? false),
                    strtolower(trim((string) ($room['status'] ?? 'available'))),
                );
            }
            usort($list, fn (RoomRef $a, RoomRef $b) => strcmp($a->id, $b->id));

            return $list;
        }

        $list = [];
        foreach (Room::query()->orderBy('name_en')->get() as $room) {
            $list[] = new RoomRef(
                (string) $room->id,
                (int) $room->capacity,
                $room->isLabType(),
                strtolower(trim((string) $room->status)),
            );
        }

        return $list;
    }

    /**
     * @param  array<string, RoomRef>  $roomById
     * @return array{events:array<int, PlacementEvent>,errors:list<string>,expected_counts:array<string,int>}
     */
    private function buildPlacementEvents(
        string $semesterId,
        ?array $baseDraft,
        ScheduleSettings $settings,
        array $roomById,
    ): array {
        $sessions = $baseDraft['sessions'] ?? null;
        if (is_array($sessions) && $sessions !== []) {
            return $this->eventsFromDraft($sessions, $settings, $semesterId, $roomById);
        }

        return $this->eventsFromDatabase($semesterId);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, RoomRef>  $roomById
     * @return array{events:array<int, PlacementEvent>,errors:list<string>,expected_counts:array<string,int>}
     */
    private function eventsFromDraft(array $rows, ScheduleSettings $settings, ?string $semesterId, array $roomById): array
    {
        $slotLen = $settings->slotMinutes();
        $events = [];
        $errors = [];
        $expectedCounts = [];
        $index = 0;

        $csiCache = [];
        $offeringCache = [];

        foreach ($rows as $rowIndex => $row) {
            $sectionInstructorId = (string) ($row['section_instructor_id'] ?? '');
            $courseOfferingId = (string) ($row['course_offering_id'] ?? '');
            if ($sectionInstructorId === '' || $courseOfferingId === '') {
                $errors[] = "sessions.{$rowIndex}: section_instructor_id and course_offering_id are required";
                continue;
            }

            if (! array_key_exists($sectionInstructorId, $csiCache)) {
                $csiCache[$sectionInstructorId] = CourseSectionInstructor::query()
                    ->with(['section.course.department', 'instructor.user'])
                    ->find($sectionInstructorId);
            }
            if (! array_key_exists($courseOfferingId, $offeringCache)) {
                $offeringCache[$courseOfferingId] = CourseOffering::query()->with('course')->find($courseOfferingId);
            }

            $csi = $csiCache[$sectionInstructorId];
            $offering = $offeringCache[$courseOfferingId];
            if ($csi === null || $csi->section === null || $offering === null || $offering->course === null) {
                $errors[] = "sessions.{$rowIndex}: invalid section/instructor/offering relation";
                continue;
            }

            if ($semesterId !== null && $semesterId !== '' && (string) $offering->semester_id !== (string) $semesterId) {
                $errors[] = "sessions.{$rowIndex}: course offering semester mismatch";
                continue;
            }

            if ((string) $csi->section->course_id !== (string) $offering->course_id) {
                $errors[] = "sessions.{$rowIndex}: section instructor does not match offering course";
                continue;
            }

            $providedInstructorId = (string) ($row['instructor_id'] ?? '');
            if ($providedInstructorId !== '' && $providedInstructorId !== (string) $csi->instructor_id) {
                $errors[] = "sessions.{$rowIndex}: instructor_id does not match section instructor relation";
                continue;
            }

            $course = $offering->course;
            $section = $csi->section;
            $instructor = $csi->instructor;
            $instructorUser = $instructor?->user;

            if ($instructorUser !== null && ! (bool) $instructorUser->is_active) {
                $errors[] = "sessions.{$rowIndex}: instructor user is inactive";
                continue;
            }

            $roomId = isset($row['room_id']) ? (string) $row['room_id'] : '';
            if ($roomId !== '' && ! isset($roomById[$roomId])) {
                $errors[] = "sessions.{$rowIndex}: room_id is invalid";
                continue;
            }

            $courseId = (string) ($row['course_id'] ?? $course->id);
            $sectionId = (string) ($row['course_section_id'] ?? $row['section_id'] ?? $section->id);
            $departmentId = (string) ($row['department_id'] ?? $course->department_id);
            if ($courseId !== (string) $course->id || $sectionId !== (string) $section->id) {
                $errors[] = "sessions.{$rowIndex}: provided course/section identifiers do not match DB relations";
                continue;
            }

            $enrollment = max(1, (int) ($row['enrollment'] ?? $row['capacity'] ?? $section->capacity));
            $requiresLab = (bool) ($row['is_lab'] ?? $row['requires_lab'] ?? ($section->section_type === 'lab'));

            $fixed = null;
            $day = $row['day'] ?? null;
            $start = $row['start'] ?? null;
            $end = $row['end'] ?? null;
            if ($day !== null || $start !== null || $roomId !== '') {
                if (! $day || ! $start || $roomId === '') {
                    $errors[] = "sessions.{$rowIndex}: day/start/room_id must be provided together for fixed sessions";
                    continue;
                }
                $dayStr = DayMapping::toLong(strtolower((string) $day));
                $startMin = TimeUtil::toMinutes((string) $start);
                $endMin = $end ? TimeUtil::toMinutes((string) $end) : ($startMin + $slotLen);
                if ($startMin >= $endMin) {
                    $errors[] = "sessions.{$rowIndex}: start must be before end";
                    continue;
                }
                $fixed = new Placement($dayStr, $startMin, $endMin, $roomId);
            }

            $events[] = new PlacementEvent(
                $index,
                isset($row['id']) ? (string) $row['id'] : null,
                (string) $course->id,
                (string) $section->id,
                (string) $csi->instructor_id,
                (string) $offering->id,
                (string) $csi->id,
                $enrollment,
                $requiresLab,
                $course->code,
                $course->name_en,
                $fixed,
                $departmentId !== '' ? $departmentId : null,
            );
            $expectedCounts[(string) $csi->id] = ($expectedCounts[(string) $csi->id] ?? 0) + 1;
            $index++;
        }

        return [
            'events' => $events,
            'errors' => $errors,
            'expected_counts' => $expectedCounts,
        ];
    }

    /**
     * @return array{events:array<int, PlacementEvent>,errors:list<string>,expected_counts:array<string,int>}
     */
    private function eventsFromDatabase(string $semesterId): array
    {
        $offerings = CourseOffering::query()
            ->where('semester_id', $semesterId)
            ->where('is_active', true)
            ->with(['course.department', 'course.sections.sectionInstructors.instructor.user'])
            ->orderBy('id')
            ->get();

        $events = [];
        $expectedCounts = [];
        $index = 0;

        foreach ($offerings as $offering) {
            $course = $offering->course;
            if ($course === null) {
                continue;
            }
            if ($course->department !== null && isset($course->department->is_active) && ! (bool) $course->department->is_active) {
                continue;
            }

            foreach ($course->sections as $section) {
                $requiredUnits = $this->requiredSessionUnitsForSection($course, $section);
                foreach ($section->sectionInstructors as $csi) {
                    $instructorUser = $csi->instructor?->user;
                    if ($instructorUser !== null && ! (bool) $instructorUser->is_active) {
                        continue;
                    }

                    $requiresLab = $section->section_type === 'lab';
                    $enrollment = max(1, (int) $section->capacity);
                    for ($unit = 0; $unit < $requiredUnits; $unit++) {
                        $events[] = new PlacementEvent(
                            $index,
                            null,
                            (string) $course->id,
                            (string) $section->id,
                            (string) $csi->instructor_id,
                            (string) $offering->id,
                            (string) $csi->id,
                            $enrollment,
                            $requiresLab,
                            $course->code,
                            $course->name_en,
                            null,
                            $course->department_id !== null ? (string) $course->department_id : null,
                        );
                        $expectedCounts[(string) $csi->id] = ($expectedCounts[(string) $csi->id] ?? 0) + 1;
                        $index++;
                    }
                }
            }
        }

        return [
            'events' => $events,
            'errors' => [],
            'expected_counts' => $expectedCounts,
        ];
    }

    private function requiredSessionUnitsForSection(Course $course, CourseSection $section): int
    {
        $isLab = $section->section_type === 'lab';
        if ($isLab) {
            $raw = $course->lab_consumed_hours ?? $course->required_hours;
        } else {
            $raw = $course->room_consumed_hours ?? $course->required_hours;
        }

        return max(1, (int) $raw);
    }

    /**
     * When room capacity is a hard constraint, ensure at least one eligible room can fit section enrollment.
     *
     * @param  array<int, RoomRef>  $rooms
     * @param  array<int, PlacementEvent>  $events
     */
    private function capacityFeasibilityMessage(ScheduleSettings $settings, array $events, array $rooms): ?string
    {
        if (! $settings->isHardEnabled('room_capacity')) {
            return null;
        }

        $enforceThreshold = $settings->isCapacityThresholdHard();
        $threshold = $settings->capacityThreshold();

        foreach ($events as $event) {
            $maxEligible = 0;
            foreach ($rooms as $room) {
                if (! $settings->isRoomAllowedForCourse($event->courseId, $room->id)) {
                    continue;
                }
                if ($settings->isHardEnabled('room_status_available') && strtolower($room->status) !== 'available') {
                    continue;
                }
                if ($settings->isHardEnabled('lab_for_lab') && $event->requiresLab && ! $room->isLab) {
                    continue;
                }
                if ($event->enrollment > $room->capacity) {
                    continue;
                }
                if ($enforceThreshold && $room->capacity > 0) {
                    $ratio = $event->enrollment / $room->capacity;
                    if ($ratio > $threshold) {
                        continue;
                    }
                }
                $maxEligible = max($maxEligible, $room->capacity);
            }

            if ($maxEligible === 0 && $event->enrollment > 0) {
                if ($enforceThreshold) {
                    $pct = (int) round($threshold * 100);

                    return 'Capacity threshold (hard): no eligible room can keep enrollment ratio at or below '.$pct.'% for a session (course '.$event->courseId.'). Check section size, room sizes, threshold, and course-room rules.';
                }

                return 'Room capacity (hard): no eligible room fits enrollment '.$event->enrollment.' for a session (course '.$event->courseId.'). Check section size vs room sizes and course-room rules.';
            }
        }

        return null;
    }

    /**
     * @param  array<int, Placement>  $assignments
     * @param  array<int, PlacementEvent>  $events
     * @param  array<string, RoomRef>  $roomById
     * @param  array<int, GridCell>  $gridCells
     * @param  array<string, int>  $expectedCounts
     * @param  array<string, mixed>  $baseMeta
     */
    private function buildGenerationResponse(
        string $algorithm,
        array $assignments,
        array $events,
        ScheduleSettings $settings,
        array $roomById,
        array $gridCells,
        SchedulingContext $ctx,
        array $expectedCounts,
        array $baseMeta,
    ): array {
        $sessions = $this->assignmentsToSessions($assignments, $events);
        $hardEval = $this->hardValidator->evaluate($assignments, $events, $settings, $roomById, $ctx);
        $completenessErrors = $this->requiredSessionCompletenessErrors($events, $sessions, $expectedCounts);

        $hardCount = $hardEval['count'] + count($completenessErrors);
        $hardBreakdown = $hardEval['by_constraint'];
        if ($completenessErrors !== []) {
            $hardBreakdown['required_sessions'] = ($hardBreakdown['required_sessions'] ?? 0) + count($completenessErrors);
        }
        $hardErrors = array_values(array_merge($hardEval['errors'], $completenessErrors));

        $softPenalty = isset($baseMeta['soft_penalty'])
            ? (float) $baseMeta['soft_penalty']
            : $this->softScorer->penalty($assignments, $events, $settings, $roomById, $gridCells, $ctx);

        $meta = array_merge($baseMeta, [
            'hard_violations' => $hardCount,
            'hard_breakdown' => $hardBreakdown,
            'soft_penalty' => $softPenalty,
            'violations_summary' => [
                'hard_count' => $hardCount,
            ],
        ]);

        if ($hardErrors !== []) {
            $meta['validation_errors'] = $hardErrors;
        }

        $success = $hardCount === 0;

        return [
            'success' => $success,
            'reason' => $success ? null : 'generated schedule violates hard constraints',
            'algorithm' => $algorithm,
            'sessions' => $sessions,
            'meta' => $meta,
        ];
    }

    /**
     * @param  array<int, PlacementEvent>  $events
     * @param  array<int, array<string, mixed>>  $sessions
     * @param  array<string, int>  $expectedCounts
     * @return list<string>
     */
    private function requiredSessionCompletenessErrors(array $events, array $sessions, array $expectedCounts): array
    {
        $errors = [];

        if (count($sessions) !== count($events)) {
            $errors[] = 'Generated session count does not match required event count.';
        }

        $actualCounts = [];
        foreach ($sessions as $row) {
            $sid = (string) ($row['section_instructor_id'] ?? '');
            if ($sid === '') {
                continue;
            }
            $actualCounts[$sid] = ($actualCounts[$sid] ?? 0) + 1;
        }

        foreach ($expectedCounts as $sectionInstructorId => $expected) {
            $actual = $actualCounts[$sectionInstructorId] ?? 0;
            if ($actual !== $expected) {
                $errors[] = "Section-instructor {$sectionInstructorId} requires {$expected} sessions but {$actual} were generated.";
            }
        }

        foreach ($actualCounts as $sectionInstructorId => $actual) {
            if (! isset($expectedCounts[$sectionInstructorId])) {
                $errors[] = "Section-instructor {$sectionInstructorId} has {$actual} unexpected generated sessions.";
            }
        }

        return $errors;
    }

    /**
     * @return array<string, int>
     */
    private function expectedSessionCountsForSemester(string $semesterId): array
    {
        $bundle = $this->eventsFromDatabase($semesterId);

        return $bundle['expected_counts'];
    }

    /**
     * @param  array<int, Placement>  $assignments
     * @param  array<int, PlacementEvent>  $events
     * @return array<int, array<string, mixed>>
     */
    private function assignmentsToSessions(array $assignments, array $events): array
    {
        $out = [];
        foreach ($events as $event) {
            $placement = $assignments[$event->index] ?? null;
            if ($placement === null) {
                continue;
            }
            $row = [
                'day' => DayMapping::toShort($placement->day),
                'start' => TimeUtil::toHhmm($placement->startMin),
                'end' => TimeUtil::toHhmm($placement->endMin),
                'course_id' => $event->courseId,
                'course_section_id' => $event->courseSectionId,
                'section_id' => $event->courseSectionId,
                'instructor_id' => $event->instructorId,
                'course_offering_id' => $event->courseOfferingId,
                'section_instructor_id' => $event->sectionInstructorId,
                'room_id' => $placement->roomId,
            ];
            if ($event->rowId !== null) {
                $row['id'] = $event->rowId;
            }
            if ($event->courseCode !== null) {
                $row['course_code'] = $event->courseCode;
            }
            if ($event->courseName !== null) {
                $row['course_name'] = $event->courseName;
            }
            $out[] = $row;
        }

        return $out;
    }
}
