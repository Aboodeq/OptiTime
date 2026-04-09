<?php

namespace App\Services;

use App\Models\CourseOffering;
use App\Models\InstructorAvailabilityCell;
use App\Models\InstructorAvailabilityProfile;
use App\Models\Room;
use App\Models\ScheduleSessionStudent;
use App\Models\ScheduleSetting;
use App\Scheduling\BacktrackingScheduler;
use App\Scheduling\DayMapping;
use App\Scheduling\GeneticScheduler;
use App\Scheduling\GridBuilder;
use App\Scheduling\Placement;
use App\Scheduling\PlacementEvent;
use App\Scheduling\RoomRef;
use App\Scheduling\ScheduleSettings;
use App\Scheduling\SchedulingContext;
use App\Scheduling\TimeUtil;

final class ScheduleGenerateService
{
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
        $settings = $this->resolveSettings($scheduleSettingsPayload, $settingsId);

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
        foreach ($rooms as $r) {
            $roomById[$r->id] = $r;
        }

        $events = $this->buildPlacementEvents($semesterId, $baseDraft, $settings);
        if (count($events) === 0) {
            return [
                'success' => false,
                'reason' => 'no sessions to place for this semester',
                'sessions' => [],
                'meta' => [],
            ];
        }

        $ctx = $this->buildSchedulingContext($gridCells, $events, $instructorAvailabilitiesPayload);

        $btCfg = config('optitime.backtracking');
        $gaCfg = config('optitime.genetic');
        $evaluator = new \App\Scheduling\ConstraintEvaluator;

        if ($algorithm === 'backtracking') {
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
            );
            $result = $solver->solve();
            if (! $result['ok']) {
                return [
                    'success' => false,
                    'reason' => $result['reason'] ?? 'no feasible schedule found',
                    'sessions' => [],
                    'meta' => [
                        'backtracks' => $solver->getBacktracks(),
                        'recursive_steps' => $solver->getRecursiveSteps(),
                    ],
                ];
            }

            $sessions = $this->assignmentsToSessions($result['assignments'], $events);

            return [
                'success' => true,
                'algorithm' => 'backtracking',
                'sessions' => $sessions,
                'meta' => [
                    'backtracks' => $solver->getBacktracks(),
                    'recursive_steps' => $solver->getRecursiveSteps(),
                    'soft_penalty' => $evaluator->softPenalty(
                        $result['assignments'],
                        $events,
                        $settings,
                        $roomById,
                        $gridCells,
                        $ctx
                    ),
                ],
            ];
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
        $sessions = $this->assignmentsToSessions($gaResult['assignments'], $events);

        return [
            'success' => $gaResult['hard_violations'] === 0,
            'algorithm' => 'genetic',
            'sessions' => $sessions,
            'meta' => [
                'fitness' => $gaResult['fitness'],
                'hard_violations' => $gaResult['hard_violations'],
                'soft_penalty' => $gaResult['soft_penalty'],
                'generations' => $gaResult['generations'],
                'violations_summary' => [
                    'hard_count' => $gaResult['hard_violations'],
                ],
            ],
        ];
    }

    private function buildSchedulingContext(
        array $gridCells,
        array $events,
        ?array $payload,
    ): SchedulingContext {
        $legalCellKeys = [];
        foreach ($gridCells as $c) {
            $legalCellKeys[$c->day.'|'.$c->startMin.'|'.$c->endMin] = true;
        }

        $instructorIds = [];
        foreach ($events as $ev) {
            $instructorIds[$ev->instructorId] = true;
        }
        $ids = array_keys($instructorIds);

        $availMap = [];
        $profiles = InstructorAvailabilityProfile::query()
            ->whereIn('instructor_id', $ids)
            ->with('cells')
            ->get();
        foreach ($profiles as $prof) {
            foreach ($prof->cells as $cell) {
                if ($cell->status !== 'preferred') {
                    continue;
                }
                $availMap[$prof->instructor_id][] = [
                    'day' => DayMapping::toLong((string) $cell->day_value),
                    'startMin' => TimeUtil::toMinutes(substr((string) $cell->start_time, 0, 5)),
                    'endMin' => TimeUtil::toMinutes(substr((string) $cell->end_time, 0, 5)),
                ];
            }
        }

        if (is_array($payload)) {
            foreach ($payload as $row) {
                $iid = (string) ($row['instructor_id'] ?? '');
                if ($iid === '') {
                    continue;
                }
                $availMap[$iid][] = [
                    'day' => DayMapping::toLong((string) ($row['day_of_week'] ?? '')),
                    'startMin' => TimeUtil::toMinutes((string) ($row['start'] ?? '00:00')),
                    'endMin' => TimeUtil::toMinutes((string) ($row['end'] ?? '00:00')),
                ];
            }
        }

        return new SchedulingContext(
            $legalCellKeys,
            $availMap,
            $this->buildSharedStudentSectionPairs()
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

    private function resolveSettings(?array $payload, ?string $settingsId): ScheduleSettings
    {
        if ($settingsId !== null && $settingsId !== '') {
            $row = ScheduleSetting::query()->findOrFail($settingsId);

            return ScheduleSettingsLoader::toScheduleSettings($row);
        }
        if ($payload !== null && $payload !== []) {
            return ScheduleSettings::fromArray($payload);
        }

        return ScheduleSettingsLoader::firstOrFail();
    }

    private function resolveRooms(?array $override): array
    {
        if ($override !== null && $override !== []) {
            $list = [];
            foreach ($override as $r) {
                $list[] = new RoomRef(
                    (string) $r['id'],
                    (int) ($r['capacity'] ?? 0),
                    (bool) ($r['is_lab'] ?? false),
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
            );
        }

        return $list;
    }

    private function buildPlacementEvents(string $semesterId, ?array $baseDraft, ScheduleSettings $settings): array
    {
        $sessions = $baseDraft['sessions'] ?? null;
        if (is_array($sessions) && $sessions !== []) {
            return $this->eventsFromDraft($sessions, $settings);
        }

        return $this->eventsFromDatabase($semesterId, $settings);
    }

    private function eventsFromDraft(array $rows, ScheduleSettings $settings): array
    {
        $slotLen = $settings->slotMinutes();
        $events = [];
        $idx = 0;
        foreach ($rows as $row) {
            $courseId = (string) ($row['course_id'] ?? '');
            $courseSectionId = (string) ($row['course_section_id'] ?? $row['section_id'] ?? '');
            $instructorId = (string) ($row['instructor_id'] ?? '');
            $courseOfferingId = (string) ($row['course_offering_id'] ?? '');
            $sectionInstructorId = (string) ($row['section_instructor_id'] ?? '');
            $enrollment = (int) ($row['enrollment'] ?? $row['capacity'] ?? 0);
            $requiresLab = (bool) ($row['is_lab'] ?? $row['requires_lab'] ?? false);
            $rowId = isset($row['id']) ? (string) $row['id'] : null;
            $courseCode = isset($row['course_code']) ? (string) $row['course_code'] : null;
            $courseName = isset($row['course_name']) ? (string) $row['course_name'] : null;
            $departmentId = isset($row['department_id']) ? (string) $row['department_id'] : null;

            $fixed = null;
            $day = $row['day'] ?? null;
            $start = $row['start'] ?? null;
            $roomId = $row['room_id'] ?? null;
            if ($day && $start && $roomId !== null && $roomId !== '') {
                $dayStr = DayMapping::toLong(strtolower((string) $day));
                $startMin = TimeUtil::toMinutes((string) $start);
                $endRaw = $row['end'] ?? null;
                $endMin = $endRaw ? TimeUtil::toMinutes((string) $endRaw) : $startMin + $slotLen;
                $fixed = new Placement($dayStr, $startMin, $endMin, (string) $roomId);
            }

            $events[] = new PlacementEvent(
                $idx,
                $rowId,
                $courseId,
                $courseSectionId,
                $instructorId,
                $courseOfferingId,
                $sectionInstructorId,
                $enrollment,
                $requiresLab,
                $courseCode,
                $courseName,
                $fixed,
                $departmentId,
            );
            $idx++;
        }

        return $events;
    }

    private function eventsFromDatabase(string $semesterId, ScheduleSettings $settings): array
    {
        $offerings = CourseOffering::query()
            ->where('semester_id', $semesterId)
            ->where('is_active', true)
            ->with(['course.department', 'course.sections.sectionInstructors'])
            ->orderBy('id')
            ->get();

        $events = [];
        $idx = 0;
        foreach ($offerings as $offering) {
            $course = $offering->course;
            foreach ($course->sections as $section) {
                foreach ($section->sectionInstructors as $csi) {
                    $requiresLab = $section->section_type === 'lab';
                    $enrollment = max(1, (int) $section->capacity);
                    $events[] = new PlacementEvent(
                        $idx,
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
                    $idx++;
                }
            }
        }

        return $events;
    }

    private function assignmentsToSessions(array $assignments, array $events): array
    {
        $out = [];
        foreach ($events as $ev) {
            $p = $assignments[$ev->index] ?? null;
            if ($p === null) {
                continue;
            }
            $row = [
                'day' => DayMapping::toShort($p->day),
                'start' => TimeUtil::toHhmm($p->startMin),
                'end' => TimeUtil::toHhmm($p->endMin),
                'course_id' => $ev->courseId,
                'course_section_id' => $ev->courseSectionId,
                'section_id' => $ev->courseSectionId,
                'instructor_id' => $ev->instructorId,
                'course_offering_id' => $ev->courseOfferingId,
                'section_instructor_id' => $ev->sectionInstructorId,
                'room_id' => $p->roomId,
            ];
            if ($ev->rowId !== null) {
                $row['id'] = $ev->rowId;
            }
            if ($ev->courseCode !== null) {
                $row['course_code'] = $ev->courseCode;
            }
            if ($ev->courseName !== null) {
                $row['course_name'] = $ev->courseName;
            }
            $out[] = $row;
        }

        return $out;
    }
}
