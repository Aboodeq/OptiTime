<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\LectureRequest;
use App\Models\ScheduleSessionStudent;
use App\Services\AuditLogger;
use App\Services\NotificationDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoordinatorLectureRequestController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            LectureRequest::query()
                ->with([
                    'instructor.user',
                    'scheduleSession.courseOffering.course',
                    'scheduleSession.sectionInstructor.section',
                ])
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function update(Request $request, string $id, NotificationDispatchService $notificationDispatch): JsonResponse
    {
        $row = LectureRequest::query()->with('instructor')->findOrFail($id);
        $data = $request->validate([
            'status' => 'required|string|max:20',
            'review_note' => 'nullable|string',
        ]);
        $data['reviewed_at'] = now();
        $row->update($data);

        $studentIds = ScheduleSessionStudent::query()
            ->where('schedule_session_id', (string) $row->schedule_session_id)
            ->pluck('student_id')
            ->unique()
            ->values()
            ->all();

        $status = (string) $row->status;
        $notificationDispatch->notifyUsers(
            [(string) $row->instructor->user_id],
            'Lecture request reviewed',
            "Your lecture request status is now: {$status}.",
            'lecture_request_reviewed',
            'high',
            [
                'route' => '/instructor/lecture-requests',
                'link' => '/instructor/lecture-requests',
                'lecture_request_id' => (string) $row->id,
                'status' => $status,
            ]
        );

        if (in_array($status, ['approved', 'accepted', 'rejected'], true)) {
            $notificationDispatch->notifyUsers(
                $studentIds,
                'Lecture schedule change request reviewed',
                'A request related to one of your lectures has been reviewed by the coordinator.',
                'lecture_request_reviewed',
                'medium',
                [
                    'route' => '/student/weekly-schedule',
                    'link' => '/student/weekly-schedule',
                    'lecture_request_id' => (string) $row->id,
                    'schedule_session_id' => (string) $row->schedule_session_id,
                    'status' => $status,
                ]
            );
        }

        AuditLogger::log($request->user(), 'lecture_requests.update', LectureRequest::class, $id, $data, $request);

        return response()->json($row->fresh()->load([
            'instructor.user',
            'scheduleSession.courseOffering.course',
            'scheduleSession.sectionInstructor.section',
        ]));
    }
}
