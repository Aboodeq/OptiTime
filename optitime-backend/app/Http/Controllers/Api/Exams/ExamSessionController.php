<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\SemesterSchedulePlan;
use App\Models\ScheduleSession;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamSessionController extends Controller
{
    public function semesters(): JsonResponse
    {
        $rows = Semester::query()
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->get();

        return response()->json($rows);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate(['semester_id' => 'required|uuid|exists:semesters,id']);
        $semesterId = $request->semester_id;
        $planId = $this->latestPublishedPlanId((string) $semesterId);
        if (! $planId) {
            return response()->json([]);
        }

        $sessions = ScheduleSession::query()
            ->where('schedule_plan_id', $planId)
            ->with([
                'room',
                'courseOffering.course',
                'sectionInstructor.section',
                'sectionInstructor.instructor.user',
                'sessionStudents.student.user',
            ])
            ->orderBy('day_value')
            ->orderBy('start_time')
            ->get();

        $sections = [];
        foreach ($sessions as $session) {
            $section = $session->sectionInstructor?->section;
            if (! $section) {
                continue;
            }
            $sectionId = (string) $section->id;
            if (! isset($sections[$sectionId])) {
                $course = $session->courseOffering?->course;
                $sections[$sectionId] = [
                    'id' => $sectionId,
                    'course_id' => (string) ($course?->id ?? $section->course_id ?? ''),
                    'course' => $course ? [
                        'id' => (string) $course->id,
                        'code' => (string) $course->code,
                        'name_en' => (string) ($course->name_en ?? $course->name ?? ''),
                        'name' => (string) ($course->name_en ?? $course->name ?? ''),
                    ] : null,
                    'exam_sessions' => [],
                ];
            }

            $students = $session->sessionStudents->map(function ($row) {
                $student = $row->student;
                $user = $student?->user;

                return [
                    'id' => (string) ($student?->user_id ?? ''),
                    'name' => (string) ($user?->full_name ?? $user?->name ?? ''),
                    'university_number' => (string) ($student?->university_number ?? ''),
                    'year_level' => $student?->year_level,
                    'study_status' => (string) ($student?->study_status ?? ''),
                ];
            })->values()->all();

            $sections[$sectionId]['exam_sessions'][] = [
                'id' => (string) $session->id,
                'schedule_session_id' => (string) $session->id,
                'section_id' => $sectionId,
                'semester_id' => (string) $semesterId,
                'is_done' => $session->grades_marked_done_at !== null,
                'completed_at' => $session->grades_marked_done_at,
                'day' => (string) $session->day_value,
                'start' => substr((string) $session->start_time, 0, 5),
                'end' => substr((string) $session->end_time, 0, 5),
                'room_name' => (string) ($session->room?->name_en ?? $session->room?->name ?? ''),
                'instructor_name' => (string) ($session->sectionInstructor?->instructor?->user?->full_name ?? ''),
                'student_count' => count($students),
                'students' => $students,
            ];
        }

        return response()->json(array_values($sections));
    }

    public function markDone(Request $request): JsonResponse
    {
        $data = $request->validate([
            'section_id' => 'required|uuid|exists:course_sections,id',
            'semester_id' => 'required|uuid|exists:semesters,id',
        ]);
        $planId = $this->latestPublishedPlanId((string) $data['semester_id']);
        if (! $planId) {
            return response()->json([
                'message' => 'No published schedule plan found for this semester.',
            ], 422);
        }

        $updated = ScheduleSession::query()
            ->where('schedule_plan_id', $planId)
            ->whereHas('sectionInstructor', fn ($q) => $q->where('section_id', $data['section_id']))
            ->update([
                'grades_marked_done_at' => now(),
                'grades_entry_status' => 'closed',
            ]);
        AuditLogger::log($request->user(), 'exam_sessions.done', ScheduleSession::class, (string) $data['section_id'], array_merge($data, ['sessions_updated' => $updated]), $request);

        return response()->json([
            'section_id' => $data['section_id'],
            'semester_id' => $data['semester_id'],
            'sessions_updated' => $updated,
        ]);
    }

    private function latestPublishedPlanId(string $semesterId): ?string
    {
        $plan = SemesterSchedulePlan::query()
            ->where('semester_id', $semesterId)
            ->where('status', 'published')
            ->orderByDesc('generated_at')
            ->orderByDesc('created_at')
            ->first();

        return $plan ? (string) $plan->id : null;
    }
}
