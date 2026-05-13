<?php

namespace App\Services;

use App\Models\CoursePrerequisite;
use App\Models\ScheduleSession;
use App\Models\ScheduleSessionStudent;
use App\Models\Student;
use App\Scheduling\DayMapping;
use App\Scheduling\TimeUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ScheduleSessionStudentAssignmentService
{
    /**
     * Link all students in each session's course department to that session (grades roster).
     * Idempotent: uses insertOrIgnore on (schedule_session_id, student_id).
     */
    public function syncForPlan(string $planId): void
    {
        $sessions = ScheduleSession::query()
            ->where('schedule_plan_id', $planId)
            ->with([
                'room',
                'sectionInstructor.section',
                'courseOffering.course.courseConstraint',
            ])
            ->orderBy('day_value')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();

        if ($sessions->isEmpty()) {
            return;
        }

        // Refresh auto-assigned rows for this plan while preserving manual/API assignments.
        ScheduleSessionStudent::query()
            ->whereHas('session', fn ($q) => $q->where('schedule_plan_id', $planId))
            ->where('assignment_source', 'auto')
            ->delete();

        $allStudents = Student::query()
            ->whereHas('user', fn ($q) => $q->whereNotNull('department_id'))
            ->pluck('user_id')
            ->all();
        if ($allStudents === []) {
            return;
        }

        $passedByStudent = $this->buildPassedCoursesMap($allStudents);
        $sessionIds = $sessions->pluck('id')->all();
        $plannedByStudent = $this->buildPlannedAssignmentsMap($sessionIds);
        $prerequisitesByCourse = $this->buildPrerequisitesMap($sessions->pluck('courseOffering.course_id')->filter()->all());
        $existingBySessionStudent = [];
        $existingCountsBySession = [];

        $existingRows = ScheduleSessionStudent::query()
            ->whereIn('schedule_session_id', $sessionIds)
            ->get(['schedule_session_id', 'student_id']);
        foreach ($existingRows as $existingRow) {
            $sessionId = (string) $existingRow->schedule_session_id;
            $studentId = (string) $existingRow->student_id;
            if ($sessionId === '' || $studentId === '') {
                continue;
            }
            $existingBySessionStudent[$sessionId][$studentId] = true;
            $existingCountsBySession[$sessionId] = ($existingCountsBySession[$sessionId] ?? 0) + 1;
        }

        $studentsByDepartment = [];

        $rows = [];
        $now = now();
        foreach ($sessions as $session) {
            $course = $session->courseOffering?->course;
            $section = $session->sectionInstructor?->section;
            $room = $session->room;
            $courseId = $course?->id;
            $deptId = $course?->department_id;
            if ($courseId === null || $deptId === null || $section === null || $room === null) {
                continue;
            }

            if (! array_key_exists((string) $deptId, $studentsByDepartment)) {
                $studentsByDepartment[(string) $deptId] = Student::query()
                    ->whereHas('user', fn ($q) => $q->where('department_id', $deptId))
                    ->get(['user_id', 'year_level']);
            }

            $sectionCapacity = max(1, (int) $section->capacity);
            $roomCapacity = max(1, (int) $room->capacity);
            $limit = min($sectionCapacity, $roomCapacity);
            $sessionId = (string) $session->id;
            $alreadyAssignedCount = (int) ($existingCountsBySession[$sessionId] ?? 0);
            $remainingSlots = max(0, $limit - $alreadyAssignedCount);
            if ($remainingSlots < 1) {
                continue;
            }

            $minYear = $course->courseConstraint?->min_student_year_level;
            $maxYear = $course->courseConstraint?->max_student_year_level;

            foreach ($studentsByDepartment[(string) $deptId] as $student) {
                if ($remainingSlots < 1) {
                    break;
                }

                $sid = (string) $student->user_id;
                if ($sid === '' || isset($existingBySessionStudent[$sessionId][$sid])) {
                    continue;
                }

                if (! $this->studentSatisfiesPrerequisites(
                    (string) $sid,
                    (string) $courseId,
                    $passedByStudent,
                    $prerequisitesByCourse
                )) {
                    continue;
                }
                if (! $this->studentSatisfiesYearLevel((int) $student->year_level, $minYear, $maxYear)) {
                    continue;
                }
                if ($this->hasTimeConflictForStudent((string) $sid, $session, $plannedByStudent)) {
                    continue;
                }
                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'schedule_session_id' => $session->id,
                    'student_id' => $sid,
                    'assignment_source' => 'auto',
                    'oral' => 0,
                    'lab' => 0,
                    'midterm' => 0,
                    'final' => 0,
                    'total' => 0,
                    'letter_grade' => 'F',
                    'grade_entered_at' => null,
                    'created_at' => $now,
                ];
                $plannedByStudent[(string) $sid][] = $this->sessionWindow($session);
                $existingBySessionStudent[$sessionId][$sid] = true;
                $existingCountsBySession[$sessionId] = ($existingCountsBySession[$sessionId] ?? 0) + 1;
                $remainingSlots--;
            }
        }

        if ($rows === []) {
            return;
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            ScheduleSessionStudent::query()->insertOrIgnore($chunk);
        }
    }

    /**
     * @return list<string>
     */
    public function detectStudentOverlapErrorsForPlan(string $planId): array
    {
        $rows = ScheduleSessionStudent::query()
            ->join('schedule_sessions', 'schedule_sessions.id', '=', 'schedule_session_students.schedule_session_id')
            ->where('schedule_sessions.schedule_plan_id', $planId)
            ->select(
                'schedule_session_students.student_id',
                'schedule_sessions.day_value',
                'schedule_sessions.start_time',
                'schedule_sessions.end_time'
            )
            ->get();

        $byStudentDay = [];
        foreach ($rows as $row) {
            $studentId = (string) $row->student_id;
            $day = DayMapping::toLong((string) $row->day_value);
            $startMin = TimeUtil::toMinutes(substr((string) $row->start_time, 0, 5));
            $endMin = TimeUtil::toMinutes(substr((string) $row->end_time, 0, 5));
            $byStudentDay[$studentId][$day][] = [
                'startMin' => $startMin,
                'endMin' => $endMin,
            ];
        }

        $errors = [];
        foreach ($byStudentDay as $studentId => $byDay) {
            foreach ($byDay as $day => $intervals) {
                usort($intervals, fn ($a, $b) => $a['startMin'] <=> $b['startMin']);
                $count = count($intervals);
                for ($i = 1; $i < $count; $i++) {
                    if ($intervals[$i]['startMin'] < $intervals[$i - 1]['endMin']) {
                        $errors[] = "student {$studentId} has overlapping sessions on {$day}";
                        break;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    public function detectRoomCapacityErrorsForPlan(string $planId): array
    {
        $rows = DB::table('schedule_sessions as ss')
            ->join('rooms as r', 'r.id', '=', 'ss.room_id')
            ->leftJoin('schedule_session_students as sss', 'sss.schedule_session_id', '=', 'ss.id')
            ->where('ss.schedule_plan_id', $planId)
            ->selectRaw('ss.id as session_id, r.capacity as room_capacity, COUNT(sss.id) as assigned_count')
            ->groupBy('ss.id', 'r.capacity')
            ->havingRaw('COUNT(sss.id) > r.capacity')
            ->get();

        $errors = [];
        foreach ($rows as $row) {
            $errors[] = "session {$row->session_id} has {$row->assigned_count} students assigned, exceeding room capacity {$row->room_capacity}";
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    public function detectStudentEligibilityErrorsForPlan(string $planId): array
    {
        $rows = ScheduleSessionStudent::query()
            ->join('schedule_sessions', 'schedule_sessions.id', '=', 'schedule_session_students.schedule_session_id')
            ->join('course_offerings', 'course_offerings.id', '=', 'schedule_sessions.course_offering_id')
            ->join('courses', 'courses.id', '=', 'course_offerings.course_id')
            ->leftJoin('course_constraints', 'course_constraints.course_id', '=', 'courses.id')
            ->join('students', 'students.user_id', '=', 'schedule_session_students.student_id')
            ->where('schedule_sessions.schedule_plan_id', $planId)
            ->select(
                'schedule_session_students.student_id',
                'schedule_session_students.schedule_session_id',
                'students.year_level',
                'course_constraints.min_student_year_level',
                'course_constraints.max_student_year_level'
            )
            ->get();

        $errors = [];
        foreach ($rows as $row) {
            $min = $row->min_student_year_level !== null ? (int) $row->min_student_year_level : null;
            $max = $row->max_student_year_level !== null ? (int) $row->max_student_year_level : null;
            if ($this->studentSatisfiesYearLevel((int) $row->year_level, $min, $max)) {
                continue;
            }
            $errors[] = "student {$row->student_id} is outside allowed year range for session {$row->schedule_session_id}";
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    public function detectInstructorWeeklyLoadErrorsForPlan(string $planId, bool $enforceMin = false): array
    {
        $rows = ScheduleSession::query()
            ->where('schedule_plan_id', $planId)
            ->join('course_section_instructors', 'course_section_instructors.id', '=', 'schedule_sessions.section_instructor_id')
            ->join('instructors', 'instructors.id', '=', 'course_section_instructors.instructor_id')
            ->select(
                'course_section_instructors.instructor_id',
                'schedule_sessions.start_time',
                'schedule_sessions.end_time',
                'instructors.min_work_hours_per_week',
                'instructors.max_work_hours_per_week'
            )
            ->get();

        $minutesByInstructor = [];
        $limitsByInstructor = [];
        foreach ($rows as $row) {
            $instructorId = (string) $row->instructor_id;
            if ($instructorId === '') {
                continue;
            }
            $startMin = TimeUtil::toMinutes(substr((string) $row->start_time, 0, 5));
            $endMin = TimeUtil::toMinutes(substr((string) $row->end_time, 0, 5));
            $minutesByInstructor[$instructorId] = ($minutesByInstructor[$instructorId] ?? 0) + max(0, $endMin - $startMin);
            $limitsByInstructor[$instructorId] = [
                'min' => max(0, (int) $row->min_work_hours_per_week),
                'max' => max(0, (int) $row->max_work_hours_per_week),
            ];
        }

        $errors = [];
        foreach ($minutesByInstructor as $instructorId => $minutes) {
            $limits = $limitsByInstructor[$instructorId] ?? ['min' => 0, 'max' => 0];
            $minMinutes = ((int) $limits['min']) * 60;
            $maxMinutes = ((int) $limits['max']) * 60;
            if ($maxMinutes > 0 && $minutes > $maxMinutes) {
                $errors[] = "instructor {$instructorId} weekly load exceeds max ({$minutes}min > {$maxMinutes}min)";
            }
            if ($enforceMin && $minMinutes > 0 && $minutes < $minMinutes) {
                $errors[] = "instructor {$instructorId} weekly load is below minimum ({$minutes}min < {$minMinutes}min)";
            }
        }

        return $errors;
    }

    private function buildPrerequisitesMap(array $courseIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $courseIds))));
        if ($ids === []) {
            return [];
        }

        $map = [];
        $rows = CoursePrerequisite::query()
            ->whereIn('course_id', $ids)
            ->get(['course_id', 'prerequisite_course_id']);
        foreach ($rows as $row) {
            $map[(string) $row->course_id][] = (string) $row->prerequisite_course_id;
        }

        return $map;
    }

    private function buildPassedCoursesMap(array $studentIds): array
    {
        $map = [];
        $rows = ScheduleSessionStudent::query()
            ->whereIn('student_id', $studentIds)
            ->whereNotNull('grade_entered_at')
            ->whereHas('session.plan', fn ($q) => $q->where('status', 'published'))
            ->with('session.courseOffering')
            ->get();

        foreach ($rows as $row) {
            $studentId = (string) $row->student_id;
            $courseId = (string) ($row->session?->courseOffering?->course_id ?? '');
            if ($studentId === '' || $courseId === '') {
                continue;
            }
            if ($this->isPassedGrade($row->letter_grade, (float) $row->total)) {
                $map[$studentId][$courseId] = true;
            }
        }

        return $map;
    }

    private function buildPlannedAssignmentsMap(array $sessionIds): array
    {
        if ($sessionIds === []) {
            return [];
        }
        $map = [];
        $rows = ScheduleSessionStudent::query()
            ->whereIn('schedule_session_id', $sessionIds)
            ->with('session')
            ->get();
        foreach ($rows as $row) {
            $studentId = (string) $row->student_id;
            $session = $row->session;
            if (! $session) {
                continue;
            }
            $map[$studentId][] = $this->sessionWindow($session);
        }

        return $map;
    }

    private function studentSatisfiesPrerequisites(
        string $studentId,
        string $courseId,
        array $passedByStudent,
        array $prerequisitesByCourse
    ): bool {
        $required = $prerequisitesByCourse[$courseId] ?? [];
        if ($required === []) {
            return true;
        }
        $passed = $passedByStudent[$studentId] ?? [];
        foreach ($required as $prerequisiteCourseId) {
            if (! isset($passed[$prerequisiteCourseId])) {
                return false;
            }
        }

        return true;
    }

    private function studentSatisfiesYearLevel(int $yearLevel, ?int $minYear, ?int $maxYear): bool
    {
        if ($minYear !== null && $yearLevel < $minYear) {
            return false;
        }
        if ($maxYear !== null && $yearLevel > $maxYear) {
            return false;
        }

        return true;
    }

    private function hasTimeConflictForStudent(string $studentId, ScheduleSession $candidate, array $plannedByStudent): bool
    {
        $candidateWindow = $this->sessionWindow($candidate);
        foreach ($plannedByStudent[$studentId] ?? [] as $window) {
            if ($window['day'] !== $candidateWindow['day']) {
                continue;
            }
            if (TimeUtil::intervalsOverlap(
                $window['startMin'],
                $window['endMin'],
                $candidateWindow['startMin'],
                $candidateWindow['endMin']
            )) {
                return true;
            }
        }

        return false;
    }

    private function sessionWindow(ScheduleSession $session): array
    {
        return [
            'day' => DayMapping::toLong((string) $session->day_value),
            'startMin' => TimeUtil::toMinutes(substr((string) $session->start_time, 0, 5)),
            'endMin' => TimeUtil::toMinutes(substr((string) $session->end_time, 0, 5)),
        ];
    }

    private function isPassedGrade(?string $letterGrade, float $numericTotal): bool
    {
        if ($numericTotal < 50) {
            return false;
        }

        $letter = strtoupper(trim((string) $letterGrade));
        $passingLetters = [
            'A+', 'A', 'A-',
            'B+', 'B', 'B-',
            'C+', 'C', 'C-',
            'D+', 'D',
            'P',
        ];

        return in_array($letter, $passingLetters, true);
    }
}
