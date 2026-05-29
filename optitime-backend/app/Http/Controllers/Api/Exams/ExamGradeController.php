<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSession;
use App\Models\ScheduleSessionStudent;
use App\Models\SemesterSchedulePlan;
use App\Services\AuditLogger;
use App\Services\NotificationDispatchService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExamGradeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'semester_id' => 'required|uuid|exists:semesters,id',
        ]);
        $semesterId = (string) $request->input('semester_id');
        $planId = $this->latestPublishedPlanId($semesterId);
        if (! $planId) {
            return response()->json([]);
        }

        $rows = ScheduleSessionStudent::query()
            ->whereHas('session', fn ($q) => $q->where('schedule_plan_id', $planId))
            ->with(['student.user', 'session.courseOffering.course', 'session.sectionInstructor.section'])
            ->get();

        return response()->json($rows);
    }

    public function storeOrUpdate(Request $request, NotificationDispatchService $notificationDispatch): JsonResponse
    {
        $data = $request->validate([
            'student_id' => 'required|uuid|exists:students,user_id',
            'schedule_session_id' => 'required|uuid|exists:schedule_sessions,id',
            'oral' => 'nullable|numeric|min:0|max:25',
            'lab' => 'nullable|numeric|min:0|max:25',
            'midterm' => 'nullable|numeric|min:0|max:25',
            'final' => 'nullable|numeric|min:0|max:25',
        ]);

        $session = ScheduleSession::query()->findOrFail((string) $data['schedule_session_id']);
        if ((string) $session->grades_entry_status === 'closed' || $session->grades_marked_done_at !== null) {
            return response()->json([
                'message' => 'Grades cannot be edited because this session is marked done.',
            ], 422);
        }

        $row = ScheduleSessionStudent::query()
            ->where('student_id', (string) $data['student_id'])
            ->where('schedule_session_id', (string) $data['schedule_session_id'])
            ->first();
        if (! $row) {
            return response()->json([
                'message' => 'Student is not assigned to this session.',
            ], 422);
        }

        $oral = $this->normalizeComponent($data['oral'] ?? 0);
        $lab = $this->normalizeComponent($data['lab'] ?? 0);
        $midterm = $this->normalizeComponent($data['midterm'] ?? 0);
        $final = $this->normalizeComponent($data['final'] ?? 0);
        $total = round($oral + $lab + $midterm + $final, 2);
        $letter = $this->letterGradeForTotal($total);

        $payload = [
            'oral' => $oral,
            'lab' => $lab,
            'midterm' => $midterm,
            'final' => $final,
            'total' => $total,
            'letter_grade' => $letter,
            'grade_entered_at' => now(),
        ];
        $row->update($payload);

        $notificationDispatch->notifyUsers(
            [(string) $data['student_id']],
            'New grade posted',
            'A new grade was posted for one of your courses.',
            'grade_posted',
            'high',
            [
                'route' => '/student/grades',
                'link' => '/student/grades',
                'schedule_session_id' => (string) $data['schedule_session_id'],
            ]
        );

        AuditLogger::log($request->user(), 'exam_grades.upsert', ScheduleSessionStudent::class, $row->id, array_merge($data, [
            'computed_total' => $total,
            'computed_letter_grade' => $letter,
        ]), $request);

        return response()->json(
            $row->load(['student.user', 'session.courseOffering.course', 'session.sectionInstructor.section'])
        );
    }

    public function exportSessionPdf(Request $request): Response
    {
        $request->validate([
            'schedule_session_id' => 'required|uuid|exists:schedule_sessions,id',
        ]);
        $grades = ScheduleSessionStudent::query()
            ->where('schedule_session_id', $request->schedule_session_id)
            ->with(['student.user', 'session.courseOffering.course'])
            ->get();
        $pdf = Pdf::loadView('pdf.exam_session_grades', [
            'grades' => $grades,
            'schedule_session_id' => $request->schedule_session_id,
        ]);

        return $pdf->download('session-grades.pdf');
    }

    private function normalizeComponent(mixed $value): float
    {
        $numeric = is_numeric($value) ? (float) $value : 0.0;
        $clamped = max(0.0, min(25.0, $numeric));

        return round($clamped, 2);
    }

    private function letterGradeForTotal(float $total): string
    {
        $normalized = max(0.0, min(100.0, $total));

        return match (true) {
            $normalized >= 95 => 'A+',
            $normalized >= 90 => 'A',
            $normalized >= 85 => 'B+',
            $normalized >= 80 => 'B',
            $normalized >= 75 => 'C+',
            $normalized >= 70 => 'C',
            $normalized >= 65 => 'D+',
            $normalized >= 60 => 'D',
            default => 'F',
        };
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
