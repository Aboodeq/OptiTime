<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\LectureRequest;
use App\Models\ScheduleSession;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstructorLectureRequestController extends Controller
{
    private function instructorId(Request $request): string
    {
        $ins = $request->user()->instructor;
        abort_if(! $ins, 403);

        return (string) $ins->id;
    }

    public function index(Request $request): JsonResponse
    {
        $id = $this->instructorId($request);

        return response()->json(
            LectureRequest::query()
                ->where('instructor_id', $id)
                ->with([
                    'scheduleSession.courseOffering.course',
                    'scheduleSession.sectionInstructor.section',
                ])
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $id = $this->instructorId($request);
        $data = $request->validate([
            'schedule_session_id' => 'required|uuid|exists:schedule_sessions,id',
            'request_type' => 'required|string|max:20',
            'requested_date' => 'required|date',
            'note' => 'nullable|string|max:2000',
        ]);
        ScheduleSession::query()
            ->where('id', $data['schedule_session_id'])
            ->whereHas('sectionInstructor', fn ($q) => $q->where('instructor_id', $id))
            ->firstOrFail();

        $row = LectureRequest::query()->create([
            'schedule_session_id' => $data['schedule_session_id'],
            'instructor_id' => $id,
            'request_type' => $data['request_type'],
            'requested_date' => $data['requested_date'],
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ]);
        AuditLogger::log($request->user(), 'instructor.requests.create', LectureRequest::class, $row->id, $data, $request);

        return response()->json($row->load([
            'scheduleSession.courseOffering.course',
            'scheduleSession.sectionInstructor.section',
        ]), 201);
    }
}
