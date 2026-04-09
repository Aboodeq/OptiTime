<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSessionStudent;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExamGradeController extends Controller
{
    public function storeOrUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_id' => 'required|uuid|exists:students,user_id',
            'schedule_session_id' => 'required|uuid|exists:schedule_sessions,id',
            'oral' => 'nullable|numeric',
            'lab' => 'nullable|numeric',
            'midterm' => 'nullable|numeric',
            'final' => 'nullable|numeric',
            'total' => 'nullable|numeric',
            'letter_grade' => 'nullable|string|max:5',
        ]);
        $payload = ['grade_entered_at' => now()];
        foreach (['oral', 'lab', 'midterm', 'final', 'total', 'letter_grade'] as $k) {
            if (array_key_exists($k, $data)) {
                $payload[$k] = $data[$k];
            }
        }

        $row = ScheduleSessionStudent::query()->updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'schedule_session_id' => $data['schedule_session_id'],
            ],
            $payload
        );
        AuditLogger::log($request->user(), 'exam_grades.upsert', ScheduleSessionStudent::class, $row->id, $data, $request);

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
}
