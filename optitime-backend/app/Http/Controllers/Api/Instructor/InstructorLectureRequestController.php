<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\LectureRequest;
use App\Models\ScheduleSession;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationDispatchService;
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

    public function store(Request $request, NotificationDispatchService $notificationDispatch): JsonResponse
    {
        $id = $this->instructorId($request);
        $data = $request->validate([
            'schedule_session_id' => 'required|uuid|exists:schedule_sessions,id',
            'request_type' => 'required|in:apology,makeup',
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

        $coordinatorIds = User::query()
            ->whereHas('role', fn ($q) => $q->where('code', 'coordinator'))
            ->pluck('id')
            ->all();
        $notificationDispatch->notifyUsers(
            $coordinatorIds,
            'New lecture request',
            'An instructor submitted a lecture request that needs your review.',
            'lecture_request_submitted',
            'high',
            [
                'route' => '/coordinator/lecture-requests',
                'link' => '/coordinator/lecture-requests',
                'lecture_request_id' => (string) $row->id,
            ]
        );

        AuditLogger::log($request->user(), 'instructor.requests.create', LectureRequest::class, $row->id, $data, $request);

        return response()->json($row->load([
            'scheduleSession.courseOffering.course',
            'scheduleSession.sectionInstructor.section',
        ]), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $instructorId = $this->instructorId($request);
        $row = LectureRequest::query()
            ->where('id', $id)
            ->where('instructor_id', $instructorId)
            ->firstOrFail();

        if ((string) $row->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending lecture requests can be edited.',
            ], 422);
        }

        $data = $request->validate([
            'requested_date' => 'required|date',
            'note' => 'nullable|string|max:2000',
        ]);

        $row->update([
            'requested_date' => $data['requested_date'],
            'note' => $data['note'] ?? null,
        ]);

        AuditLogger::log($request->user(), 'instructor.requests.update', LectureRequest::class, $row->id, $data, $request);

        return response()->json($row->fresh()->load([
            'scheduleSession.courseOffering.course',
            'scheduleSession.sectionInstructor.section',
        ]));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $instructorId = $this->instructorId($request);
        $row = LectureRequest::query()
            ->where('id', $id)
            ->where('instructor_id', $instructorId)
            ->firstOrFail();

        if ((string) $row->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending lecture requests can be deleted.',
            ], 422);
        }

        $row->delete();
        AuditLogger::log($request->user(), 'instructor.requests.delete', LectureRequest::class, $id, null, $request);

        return response()->json(['deleted' => true]);
    }
}
